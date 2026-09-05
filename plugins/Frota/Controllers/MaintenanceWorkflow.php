<?php

namespace Frota\Controllers;

use App\Controllers\Security_Controller;

class MaintenanceWorkflow extends Security_Controller
{
    protected $db;

    public function __construct()
    {
        parent::__construct();
        helper('frota');
        $this->db = db_connect('default');
        $this->ensureLinkTable();
    }

    protected function table(string $name)
    {
        return $this->db->table($this->db->prefixTable($name));
    }

    protected function ensureLinkTable(): void
    {
        $prefix = $this->db->getPrefix();
        $this->db->query("CREATE TABLE IF NOT EXISTS `{$prefix}frota_maintenance_issue_links` (
            `id` int unsigned NOT NULL AUTO_INCREMENT,
            `maintenance_id` int unsigned NOT NULL,
            `issue_id` int unsigned NOT NULL,
            `created_at` datetime DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `maintenance_issue_unique` (`maintenance_id`,`issue_id`),
            KEY `maintenance_id` (`maintenance_id`),
            KEY `issue_id` (`issue_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    protected function requireMaintenanceAccess(): void
    {
        if ($this->login_user && $this->login_user->is_admin) return;
        $permissions = $this->login_user->permissions ?? [];
        if (get_array_value($permissions, 'frota_maintenance') != '1' && get_array_value($permissions, 'frota_manage') != '1') app_redirect('forbidden');
    }

    public function issuesByVehicle($vehicleId)
    {
        $this->requireMaintenanceAccess();
        $vehicleId = (int)$vehicleId;
        $maintenanceId = (int)$this->request->getGet('maintenance_id');
        $linkedIds = [];
        if ($maintenanceId) {
            $links = $this->table('frota_maintenance_issue_links')->select('issue_id')->where('maintenance_id', $maintenanceId)->get()->getResultArray();
            $linkedIds = array_map('intval', array_column($links, 'issue_id'));
        }

        $builder = $this->table('frota_issues')->where('vehicle_id', $vehicleId)->where('deleted', 0);
        if ($linkedIds) {
            $builder->groupStart()->whereIn('id', $linkedIds)->orWhereIn('status', ['open', 'in_progress'])->groupEnd();
        } else {
            $builder->whereIn('status', ['open', 'in_progress']);
        }

        $data = [];
        foreach ($builder->orderBy('reported_at', 'DESC')->get()->getResultArray() as $row) {
            $data[] = [
                'id' => (int)$row['id'], 'title' => (string)($row['title'] ?? ''), 'description' => (string)($row['description'] ?? ''),
                'severity' => (string)($row['severity'] ?? ''), 'status' => (string)($row['status'] ?? ''),
                'reported_at' => (string)($row['reported_at'] ?? ''), 'selected' => in_array((int)$row['id'], $linkedIds, true)
            ];
        }
        return $this->response->setJSON(['success' => true, 'data' => $data]);
    }

    public function save()
    {
        $this->requireMaintenanceAccess();
        $id = (int)$this->request->getPost('id');
        $fields = ['vehicle_id','type','description','supplier','odometer','service_date','next_service_odometer','next_service_date','cost','status'];
        $data = [];
        foreach ($fields as $field) {
            $value = $this->request->getPost($field);
            if ($value !== null) $data[$field] = is_string($value) ? trim($value) : $value;
        }
        foreach (['odometer','next_service_odometer','cost','next_service_date'] as $nullable) {
            if (array_key_exists($nullable, $data) && $data[$nullable] === '') $data[$nullable] = null;
        }
        $issueIds = $this->request->getPost('issue_ids');
        if (!is_array($issueIds)) $issueIds = $issueIds ? explode(',', (string)$issueIds) : [];
        $issueIds = array_values(array_unique(array_filter(array_map('intval', $issueIds))));

        try {
            $this->db->transStart();
            if ($id) {
                $this->table('frota_maintenances')->where('id', $id)->where('deleted', 0)->update($data);
            } else {
                $data['created_by'] = (int)$this->login_user->id;
                $data['created_at'] = get_current_utc_time();
                $data['deleted'] = 0;
                $this->table('frota_maintenances')->insert($data);
                $id = (int)$this->db->insertID();
            }

            $this->table('frota_maintenance_issue_links')->where('maintenance_id', $id)->delete();
            foreach ($issueIds as $issueId) {
                $issue = $this->table('frota_issues')->where('id', $issueId)->where('vehicle_id', (int)($data['vehicle_id'] ?? 0))->where('deleted', 0)->get()->getRowArray();
                if (!$issue) continue;
                $this->table('frota_maintenance_issue_links')->insert(['maintenance_id' => $id, 'issue_id' => $issueId, 'created_at' => get_current_utc_time()]);
            }

            if (($data['status'] ?? '') === 'completed') {
                $this->table('frota_maintenances')->where('id', $id)->update(['completed_at' => get_current_utc_time()]);
                if ($issueIds) {
                    $this->table('frota_issues')->whereIn('id', $issueIds)->where('vehicle_id', (int)($data['vehicle_id'] ?? 0))->where('deleted', 0)->update([
                        'status' => 'resolved', 'resolved_at' => get_current_utc_time(), 'resolution' => 'Ocorrência encerrada pela manutenção #' . $id
                    ]);
                }
            }

            if (!empty($data['vehicle_id'])) {
                $vehicleUpdate = [];
                if (array_key_exists('next_service_odometer', $data)) $vehicleUpdate['next_service_odometer'] = $data['next_service_odometer'];
                if (array_key_exists('next_service_date', $data)) $vehicleUpdate['next_service_date'] = $data['next_service_date'];
                if ($vehicleUpdate) {
                    $vehicleUpdate['updated_at'] = get_current_utc_time();
                    $this->table('frota_vehicles')->where('id', (int)$data['vehicle_id'])->update($vehicleUpdate);
                }
            }

            $this->db->transComplete();
            if (!$this->db->transStatus()) throw new \RuntimeException('Falha ao salvar manutenção.');
        } catch (\Throwable $e) {
            log_message('error', '[Frota/Manutencao] ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON(['success' => false, 'message' => 'Não foi possível salvar a manutenção.']);
        }

        return $this->response->setJSON(['success' => true, 'id' => $id, 'message' => app_lang('record_saved')]);
    }
}
