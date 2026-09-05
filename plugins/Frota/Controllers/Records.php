<?php

namespace Frota\Controllers;

use App\Controllers\Security_Controller;

class Records extends Security_Controller
{
    protected $db;

    public function __construct()
    {
        parent::__construct();
        helper('frota');
        $this->db = db_connect('default');
    }

    protected function table(string $name)
    {
        return $this->db->table($this->db->prefixTable($name));
    }

    protected function requireAccess(string $permission): void
    {
        if ($this->login_user && $this->login_user->is_admin) {
            return;
        }

        $permissions = $this->login_user->permissions ?? [];
        if (get_array_value($permissions, $permission) != '1' && get_array_value($permissions, 'frota_manage') != '1') {
            app_redirect('forbidden');
        }
    }

    protected function userName(int $userId): string
    {
        if (!$userId) {
            return '-';
        }

        $user = $this->table('users')
            ->select('id, first_name, last_name, email')
            ->where('id', $userId)
            ->get()
            ->getRowArray();

        if (!$user) {
            return '#' . $userId;
        }

        $name = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
        return $name !== '' ? $name : ($user['email'] ?? ('#' . $userId));
    }

    public function issueReporter($id = 0)
    {
        $this->requireAccess('frota_issue');
        $id = (int)$id;
        $userId = (int)($this->login_user->id ?? 0);

        if ($id) {
            $issue = $this->table('frota_issues')
                ->select('reported_by')
                ->where('id', $id)
                ->where('deleted', 0)
                ->get()
                ->getRowArray();
            if ($issue && !empty($issue['reported_by'])) {
                $userId = (int)$issue['reported_by'];
            }
        }

        return $this->response->setJSON([
            'success' => true,
            'user_id' => $userId,
            'name' => $this->userName($userId)
        ]);
    }

    public function issuesListData()
    {
        $this->requireAccess('frota_view');

        $vehicleMap = [];
        foreach ($this->table('frota_vehicles')->where('deleted', 0)->get()->getResultArray() as $vehicle) {
            $vehicleMap[$vehicle['id']] = trim(($vehicle['plate'] ?? '') . ' - ' . ($vehicle['model'] ?? ''));
        }

        $builder = $this->table('frota_issues')->where('deleted', 0);
        foreach (['vehicle_id', 'severity', 'status'] as $key) {
            $value = $this->request->getPost($key);
            if ($value !== null && $value !== '') {
                $builder->where($key, $value);
            }
        }

        $result = [];
        foreach ($builder->orderBy('reported_at', 'DESC')->get()->getResultArray() as $r) {
            $actions = modal_anchor(
                get_uri('frota/ocorrencias/modal_form'),
                '<i data-feather="edit" class="icon-16"></i>',
                ['class' => 'edit', 'title' => app_lang('edit'), 'data-post-id' => (int)$r['id']]
            );

            if (($r['status'] ?? '') !== 'resolved') {
                $actions .= modal_anchor(
                    get_uri('frota/ocorrencias/resolve_modal_form'),
                    '<i data-feather="check-circle" class="icon-16"></i>',
                    ['class' => 'edit ms-2', 'title' => 'Resolver ocorrência', 'data-post-id' => (int)$r['id']]
                );
            }

            $result[] = [
                esc($r['reported_at']),
                esc($vehicleMap[$r['vehicle_id']] ?? '#' . $r['vehicle_id']),
                '<strong>' . esc($r['title']) . '</strong><div class="text-off small">' . esc($r['description']) . '</div>',
                esc($this->userName((int)($r['reported_by'] ?? 0))),
                ucfirst(esc($r['severity'] ?? '-')),
                !empty($r['odometer']) ? number_format((float)$r['odometer'], 0, ',', '.') . ' km' : '-',
                frota_status_badge($r['status'] ?? ''),
                $actions
            ];
        }

        return $this->response->setJSON(['data' => $result]);
    }

    public function deleteFueling($id)
    {
        $this->requireAccess('frota_fueling');
        return $this->deleteSimple('frota_fuelings', (int)$id, 'Abastecimento');
    }

    public function deleteMaintenance($id)
    {
        $this->requireAccess('frota_maintenance');
        $id = (int)$id;
        $row = $this->table('frota_maintenances')->where('id', $id)->where('deleted', 0)->get()->getRowArray();
        if (!$row) {
            return $this->notFound('Manutenção');
        }

        try {
            $this->db->transStart();
            $this->table('frota_maintenances')->where('id', $id)->delete();
            $this->recalculateNextService((int)$row['vehicle_id']);
            $this->db->transComplete();

            if (!$this->db->transStatus()) {
                throw new \RuntimeException('Falha na transação.');
            }
        } catch (\Throwable $e) {
            log_message('error', '[Frota/Manutencao] Erro ao excluir #' . $id . ': ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'message' => 'Não foi possível excluir a manutenção.'
            ]);
        }

        return $this->response->setJSON(['success' => true, 'message' => 'Manutenção excluída.']);
    }

    public function deleteIssue($id)
    {
        $this->requireAccess('frota_issue');
        $id = (int)$id;
        $row = $this->table('frota_issues')->where('id', $id)->where('deleted', 0)->get()->getRowArray();
        if (!$row) {
            return $this->notFound('Ocorrência');
        }

        try {
            $this->table('frota_issues')->where('id', $id)->delete();
            $this->removeDirectory(FCPATH . 'files/frota/ocorrencias/' . $id);
        } catch (\Throwable $e) {
            log_message('error', '[Frota/Ocorrencia] Erro ao excluir #' . $id . ': ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'message' => 'Não foi possível excluir a ocorrência.'
            ]);
        }

        return $this->response->setJSON(['success' => true, 'message' => 'Ocorrência excluída.']);
    }

    protected function deleteSimple(string $table, int $id, string $label)
    {
        if (!$id) {
            return $this->notFound($label);
        }

        $exists = $this->table($table)->where('id', $id)->where('deleted', 0)->countAllResults();
        if (!$exists) {
            return $this->notFound($label);
        }

        try {
            $this->table($table)->where('id', $id)->delete();
        } catch (\Throwable $e) {
            log_message('error', '[Frota/Registros] Erro ao excluir ' . $label . ' #' . $id . ': ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'message' => 'Não foi possível excluir o registro.'
            ]);
        }

        return $this->response->setJSON(['success' => true, 'message' => $label . ' excluído.']);
    }

    protected function notFound(string $label)
    {
        return $this->response->setStatusCode(404)->setJSON([
            'success' => false,
            'message' => $label . ' não encontrado.'
        ]);
    }

    protected function recalculateNextService(int $vehicleId): void
    {
        $rows = $this->table('frota_maintenances')
            ->where('vehicle_id', $vehicleId)
            ->where('deleted', 0)
            ->orderBy('service_date', 'DESC')
            ->orderBy('id', 'DESC')
            ->get()
            ->getResultArray();

        $nextKm = null;
        $nextDate = null;
        foreach ($rows as $row) {
            if ($nextKm === null && !empty($row['next_service_odometer'])) {
                $nextKm = $row['next_service_odometer'];
            }
            if ($nextDate === null && !empty($row['next_service_date']) && $row['next_service_date'] !== '0000-00-00') {
                $nextDate = $row['next_service_date'];
            }
            if ($nextKm !== null && $nextDate !== null) {
                break;
            }
        }

        $this->table('frota_vehicles')->where('id', $vehicleId)->update([
            'next_service_odometer' => $nextKm,
            'next_service_date' => $nextDate,
            'updated_at' => get_current_utc_time()
        ]);
    }

    protected function removeDirectory(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        $items = scandir($path);
        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $target = $path . DIRECTORY_SEPARATOR . $item;
            if (is_dir($target)) {
                $this->removeDirectory($target);
            } else {
                @unlink($target);
            }
        }

        @rmdir($path);
    }
}
