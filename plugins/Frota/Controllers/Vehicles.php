<?php

namespace Frota\Controllers;

use App\Controllers\Security_Controller;

class Vehicles extends Security_Controller
{
    protected $db;

    public function __construct()
    {
        parent::__construct();
        helper('frota');
        $this->db = db_connect('default');
    }

    protected function requireManage(): void
    {
        if ($this->login_user && $this->login_user->is_admin) {
            return;
        }

        $permissions = $this->login_user->permissions ?? [];
        if (get_array_value($permissions, 'frota_manage') != '1') {
            app_redirect('forbidden');
        }
    }

    protected function table(string $name)
    {
        return $this->db->table($this->db->prefixTable($name));
    }

    protected function normalizeDate($value): ?string
    {
        $value = trim((string)$value);
        if ($value === '' || $value === '0000-00-00') {
            return null;
        }

        if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $m)) {
            return null;
        }

        $year = (int)$m[1];
        $month = (int)$m[2];
        $day = (int)$m[3];
        if ($year <= 1900 || !checkdate($month, $day, $year)) {
            return null;
        }

        return sprintf('%04d-%02d-%02d', $year, $month, $day);
    }

    public function save()
    {
        $this->requireManage();

        $id = (int)$this->request->getPost('id');
        $fields = [
            'plate', 'prefix', 'make', 'model', 'year', 'fuel_type',
            'current_odometer', 'next_service_odometer', 'next_service_date',
            'status', 'assigned_user_id', 'notes'
        ];

        $data = [];
        foreach ($fields as $field) {
            $value = $this->request->getPost($field);
            if ($value !== null) {
                $data[$field] = is_string($value) ? trim($value) : $value;
            }
        }

        $data['plate'] = strtoupper((string)($data['plate'] ?? ''));
        $data['next_service_date'] = $this->normalizeDate($data['next_service_date'] ?? null);

        if (($data['next_service_odometer'] ?? '') === '') {
            $data['next_service_odometer'] = null;
        }
        if (($data['assigned_user_id'] ?? '') === '') {
            $data['assigned_user_id'] = null;
        }

        $data['updated_at'] = get_current_utc_time();

        try {
            if ($id) {
                $exists = $this->table('frota_vehicles')->where('id', $id)->where('deleted', 0)->countAllResults();
                if (!$exists) {
                    return $this->response->setStatusCode(404)->setJSON([
                        'success' => false,
                        'message' => 'Veículo não encontrado.'
                    ]);
                }
                $this->table('frota_vehicles')->where('id', $id)->update($data);
            } else {
                $data['created_at'] = get_current_utc_time();
                $data['deleted'] = 0;
                $this->table('frota_vehicles')->insert($data);
                $id = (int)$this->db->insertID();
            }
        } catch (\Throwable $e) {
            log_message('error', '[Frota/Veiculos] Erro ao salvar veículo: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'message' => 'Não foi possível salvar o veículo.'
            ]);
        }

        return $this->response->setJSON([
            'success' => true,
            'id' => $id,
            'message' => app_lang('record_saved')
        ]);
    }

    public function delete($id)
    {
        $this->requireManage();
        $id = (int)$id;

        if (!$id) {
            return $this->response->setStatusCode(400)->setJSON([
                'success' => false,
                'message' => 'Veículo inválido.'
            ]);
        }

        $vehicle = $this->table('frota_vehicles')->where('id', $id)->get()->getRowArray();
        if (!$vehicle) {
            return $this->response->setStatusCode(404)->setJSON([
                'success' => false,
                'message' => 'Veículo não encontrado.'
            ]);
        }

        $issueRows = $this->table('frota_issues')
            ->select('id')
            ->where('vehicle_id', $id)
            ->get()
            ->getResultArray();

        try {
            $this->db->transStart();

            // Exclusão definitiva solicitada: remove todo o histórico relacionado.
            $this->table('frota_fuelings')->where('vehicle_id', $id)->delete();
            $this->table('frota_maintenances')->where('vehicle_id', $id)->delete();
            $this->table('frota_issues')->where('vehicle_id', $id)->delete();
            $this->table('frota_vehicles')->where('id', $id)->delete();

            $this->db->transComplete();

            if (!$this->db->transStatus()) {
                throw new \RuntimeException('Falha na transação de exclusão.');
            }

            // Remove também as fotos físicas das ocorrências ligadas ao veículo.
            foreach ($issueRows as $issue) {
                $this->removeDirectory(FCPATH . 'files/frota/ocorrencias/' . (int)$issue['id']);
            }
        } catch (\Throwable $e) {
            log_message('error', '[Frota/Veiculos] Erro ao excluir veículo #' . $id . ': ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'message' => 'Não foi possível excluir o veículo e seus registros relacionados.'
            ]);
        }

        return $this->response->setJSON([
            'success' => true,
            'message' => 'Veículo e todos os registros relacionados foram excluídos.'
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
