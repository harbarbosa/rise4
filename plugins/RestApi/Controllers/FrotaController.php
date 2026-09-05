<?php

namespace RestApi\Controllers;

class FrotaController extends ModuleApiController
{
    protected $db;

    public function __construct()
    {
        parent::__construct();
        $this->db = db_connect('default');
    }

    protected function table(string $name)
    {
        return $this->db->table($this->db->prefixTable($name));
    }

    protected function staffId(): int
    {
        $email = strtolower(trim((string)($this->api_user->user ?? $this->api_user->email ?? '')));
        if ($email === '') {
            return 0;
        }

        $row = $this->table('users')
            ->select('id')
            ->where('email', $email)
            ->where('deleted', 0)
            ->get()
            ->getRowArray();

        return (int)($row['id'] ?? 0);
    }

    protected function userName(int $userId): string
    {
        if (!$userId) {
            return '-';
        }

        $row = $this->table('users')
            ->select('first_name,last_name,email')
            ->where('id', $userId)
            ->where('deleted', 0)
            ->get()
            ->getRowArray();

        if (!$row) {
            return '#' . $userId;
        }

        $name = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));
        return $name !== '' ? $name : (string)($row['email'] ?? ('#' . $userId));
    }

    protected function findRow(string $table, int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        $row = $this->table($table)
            ->where('id', $id)
            ->where('deleted', 0)
            ->get()
            ->getRowArray();

        return $row ?: null;
    }

    protected function normalizeDate($value): ?string
    {
        $value = trim((string)$value);
        if ($value === '' || $value === '0000-00-00') {
            return null;
        }

        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $m)) {
            $year = (int)$m[1];
            $month = (int)$m[2];
            $day = (int)$m[3];
            if ($year > 1900 && checkdate($month, $day, $year)) {
                return sprintf('%04d-%02d-%02d', $year, $month, $day);
            }
        }

        $timestamp = strtotime($value);
        if (!$timestamp) {
            return null;
        }
        $year = (int)date('Y', $timestamp);
        return $year > 1900 ? date('Y-m-d', $timestamp) : null;
    }

    protected function normalizeDateTime($value): string
    {
        $value = trim((string)$value);
        if ($value === '') {
            return date('Y-m-d H:i:s');
        }
        $timestamp = strtotime($value);
        return $timestamp ? date('Y-m-d H:i:s', $timestamp) : date('Y-m-d H:i:s');
    }

    protected function parsePhotos($value): array
    {
        if (is_array($value)) {
            return array_values(array_filter(array_map('strval', $value)));
        }
        $value = trim((string)$value);
        if ($value === '') {
            return [];
        }
        $decoded = json_decode($value, true);
        if (is_array($decoded)) {
            return array_values(array_filter(array_map('strval', $decoded)));
        }
        return [$value];
    }

    protected function vehicleExists(int $id): ?array
    {
        return $this->findRow('frota_vehicles', $id);
    }

    public function dashboard()
    {
        $vehicles = $this->table('frota_vehicles')->where('deleted', 0)->get()->getResultArray();
        $issues = $this->table('frota_issues')->where('deleted', 0)->get()->getResultArray();
        $fuelings = $this->table('frota_fuelings')->where('deleted', 0)->get()->getResultArray();
        $maintenances = $this->table('frota_maintenances')->where('deleted', 0)->get()->getResultArray();

        $fuelMonth = 0.0;
        $maintenanceMonth = 0.0;
        foreach ($fuelings as $row) {
            if (substr((string)($row['fueling_at'] ?? ''), 0, 7) === date('Y-m')) {
                $fuelMonth += (float)($row['total_amount'] ?? 0);
            }
        }
        foreach ($maintenances as $row) {
            if (substr((string)($row['service_date'] ?? ''), 0, 7) === date('Y-m')) {
                $maintenanceMonth += (float)($row['cost'] ?? 0);
            }
        }

        return $this->respond([
            'status' => true,
            'resource' => 'frota_dashboard',
            'data' => [
                'vehicles' => count($vehicles),
                'active_vehicles' => count(array_filter($vehicles, fn($r) => ($r['status'] ?? '') === 'active')),
                'open_issues' => count(array_filter($issues, fn($r) => in_array(($r['status'] ?? ''), ['open', 'in_progress'], true))),
                'fuel_cost_month' => round($fuelMonth, 2),
                'maintenance_cost_month' => round($maintenanceMonth, 2),
            ]
        ]);
    }

    public function vehicles()
    {
        $builder = $this->table('frota_vehicles')->where('deleted', 0);
        $status = trim((string)$this->request->getGet('status'));
        $q = trim((string)$this->request->getGet('q'));

        if ($status !== '') {
            $builder->where('status', $status);
        }
        if ($q !== '') {
            $builder->groupStart()
                ->like('plate', $q)
                ->orLike('prefix', $q)
                ->orLike('make', $q)
                ->orLike('model', $q)
                ->groupEnd();
        }

        $rows = $builder->orderBy('plate', 'ASC')->get()->getResultArray();
        return $this->respond(['status' => true, 'resource' => 'frota_vehicles', 'count' => count($rows), 'data' => $rows]);
    }

    public function vehicle(int $id)
    {
        $row = $this->vehicleExists($id);
        if (!$row) {
            return $this->failNotFound('Veículo não encontrado.');
        }

        $row['recent_fuelings'] = $this->table('frota_fuelings')->where('vehicle_id', $id)->where('deleted', 0)->orderBy('fueling_at', 'DESC')->limit(10)->get()->getResultArray();
        $row['open_issues'] = $this->table('frota_issues')->where('vehicle_id', $id)->where('deleted', 0)->whereIn('status', ['open', 'in_progress'])->orderBy('reported_at', 'DESC')->get()->getResultArray();
        $row['maintenances'] = $this->table('frota_maintenances')->where('vehicle_id', $id)->where('deleted', 0)->orderBy('service_date', 'DESC')->limit(10)->get()->getResultArray();

        return $this->respond(['status' => true, 'resource' => 'frota_vehicle', 'data' => $row]);
    }

    public function createVehicle()
    {
        return $this->saveVehicle(0);
    }

    public function updateVehicle(int $id)
    {
        return $this->saveVehicle($id);
    }

    protected function saveVehicle(int $id)
    {
        $payload = $this->payload();
        if ($id && !$this->vehicleExists($id)) {
            return $this->failNotFound('Veículo não encontrado.');
        }

        if (!$id && empty($payload['plate'])) {
            return $this->failValidationErrors('Campo obrigatório: plate');
        }
        if (!$id && empty($payload['model'])) {
            return $this->failValidationErrors('Campo obrigatório: model');
        }

        $allowed = ['plate','prefix','make','model','year','fuel_type','current_odometer','next_service_odometer','next_service_date','status','assigned_user_id','notes'];
        $data = array_intersect_key($payload, array_flip($allowed));
        if (isset($data['plate'])) $data['plate'] = strtoupper(trim((string)$data['plate']));
        if (array_key_exists('current_odometer', $data)) $data['current_odometer'] = $this->parseDecimal($data['current_odometer']);
        if (array_key_exists('next_service_odometer', $data)) $data['next_service_odometer'] = ($data['next_service_odometer'] === '' || $data['next_service_odometer'] === null) ? null : $this->parseDecimal($data['next_service_odometer']);
        if (array_key_exists('next_service_date', $data)) $data['next_service_date'] = $this->normalizeDate($data['next_service_date']);
        if (array_key_exists('assigned_user_id', $data)) $data['assigned_user_id'] = ($data['assigned_user_id'] === '' || $data['assigned_user_id'] === null) ? null : (int)$data['assigned_user_id'];
        $data['updated_at'] = get_current_utc_time();

        try {
            if ($id) {
                $this->table('frota_vehicles')->where('id', $id)->update($data);
            } else {
                $data['status'] = $data['status'] ?? 'active';
                $data['created_at'] = get_current_utc_time();
                $data['deleted'] = 0;
                $this->table('frota_vehicles')->insert($data);
                $id = (int)$this->db->insertID();
            }
        } catch (\Throwable $e) {
            log_message('error', '[RestApi/Frota] veículo: ' . $e->getMessage());
            return $this->failServerError('Não foi possível salvar o veículo.');
        }

        return $this->respond(['status' => true, 'message' => 'Veículo salvo.', 'id' => $id, 'data' => $this->vehicleExists($id)]);
    }

    public function deleteVehicle(int $id)
    {
        $vehicle = $this->vehicleExists($id);
        if (!$vehicle) {
            return $this->failNotFound('Veículo não encontrado.');
        }

        $issueIds = array_column($this->table('frota_issues')->select('id')->where('vehicle_id', $id)->get()->getResultArray(), 'id');

        try {
            $this->db->transStart();
            if ($this->db->tableExists($this->db->prefixTable('frota_maintenance_issue_links')) && $issueIds) {
                $this->table('frota_maintenance_issue_links')->whereIn('issue_id', $issueIds)->delete();
            }
            $this->table('frota_fuelings')->where('vehicle_id', $id)->delete();
            $this->table('frota_maintenances')->where('vehicle_id', $id)->delete();
            $this->table('frota_issues')->where('vehicle_id', $id)->delete();
            $this->table('frota_vehicles')->where('id', $id)->delete();
            $this->db->transComplete();
            if (!$this->db->transStatus()) throw new \RuntimeException('Falha na transação.');
        } catch (\Throwable $e) {
            log_message('error', '[RestApi/Frota] excluir veículo: ' . $e->getMessage());
            return $this->failServerError('Não foi possível excluir o veículo.');
        }

        return $this->respondDeleted(['status' => true, 'message' => 'Veículo e registros relacionados excluídos.']);
    }

    public function fuelings()
    {
        $builder = $this->table('frota_fuelings')->where('deleted', 0);
        $vehicleId = (int)$this->request->getGet('vehicle_id');
        if ($vehicleId) $builder->where('vehicle_id', $vehicleId);
        $dateFrom = trim((string)$this->request->getGet('date_from'));
        $dateTo = trim((string)$this->request->getGet('date_to'));
        if ($dateFrom) $builder->where('DATE(fueling_at) >=', $dateFrom);
        if ($dateTo) $builder->where('DATE(fueling_at) <=', $dateTo);
        $rows = $builder->orderBy('fueling_at', 'DESC')->get()->getResultArray();
        return $this->respond(['status' => true, 'resource' => 'frota_fuelings', 'count' => count($rows), 'data' => $rows]);
    }

    public function fueling(int $id)
    {
        $row = $this->findRow('frota_fuelings', $id);
        return $row ? $this->respond(['status' => true, 'data' => $row]) : $this->failNotFound('Abastecimento não encontrado.');
    }

    public function createFueling()
    {
        return $this->saveFueling(0);
    }

    public function updateFueling(int $id)
    {
        return $this->saveFueling($id);
    }

    protected function saveFueling(int $id)
    {
        $payload = $this->payload();
        if ($id && !$this->findRow('frota_fuelings', $id)) return $this->failNotFound('Abastecimento não encontrado.');
        foreach (['vehicle_id','odometer','liters'] as $field) {
            if (!$id && (!isset($payload[$field]) || $payload[$field] === '')) return $this->failValidationErrors("Campo obrigatório: {$field}");
        }

        $vehicleId = isset($payload['vehicle_id']) ? (int)$payload['vehicle_id'] : (int)($this->findRow('frota_fuelings', $id)['vehicle_id'] ?? 0);
        $vehicle = $this->vehicleExists($vehicleId);
        if (!$vehicle) return $this->failNotFound('Veículo não encontrado.');

        $allowed = ['vehicle_id','fueling_at','odometer','liters','unit_price','total_amount','fuel_type','station','receipt_url','notes'];
        $data = array_intersect_key($payload, array_flip($allowed));
        $data['vehicle_id'] = $vehicleId;
        if (array_key_exists('odometer', $data)) $data['odometer'] = $this->parseDecimal($data['odometer']);
        if (array_key_exists('liters', $data)) $data['liters'] = $this->parseDecimal($data['liters']);
        if (array_key_exists('unit_price', $data)) $data['unit_price'] = $data['unit_price'] === '' ? null : $this->parseDecimal($data['unit_price']);
        if (array_key_exists('total_amount', $data)) $data['total_amount'] = $this->parseDecimal($data['total_amount']);
        if (isset($data['liters'], $data['unit_price']) && !isset($payload['total_amount'])) $data['total_amount'] = round((float)$data['liters'] * (float)$data['unit_price'], 2);
        if (array_key_exists('fueling_at', $data)) $data['fueling_at'] = $this->normalizeDateTime($data['fueling_at']);

        if (!$id) {
            $data['user_id'] = $this->staffId();
            $data['fueling_at'] = $data['fueling_at'] ?? date('Y-m-d H:i:s');
            $data['fuel_type'] = $data['fuel_type'] ?? $vehicle['fuel_type'];
            $data['total_amount'] = $data['total_amount'] ?? 0;
            $data['created_at'] = get_current_utc_time();
            $data['deleted'] = 0;
            $this->table('frota_fuelings')->insert($data);
            $id = (int)$this->db->insertID();
        } else {
            $this->table('frota_fuelings')->where('id', $id)->update($data);
        }

        $saved = $this->findRow('frota_fuelings', $id);
        if ($saved && (float)$saved['odometer'] > (float)$vehicle['current_odometer']) {
            $this->table('frota_vehicles')->where('id', $vehicleId)->update(['current_odometer' => $saved['odometer'], 'updated_at' => get_current_utc_time()]);
        }

        return $this->respond(['status' => true, 'message' => 'Abastecimento salvo.', 'id' => $id, 'data' => $saved]);
    }

    public function deleteFueling(int $id)
    {
        if (!$this->findRow('frota_fuelings', $id)) return $this->failNotFound('Abastecimento não encontrado.');
        $this->table('frota_fuelings')->where('id', $id)->delete();
        return $this->respondDeleted(['status' => true, 'message' => 'Abastecimento excluído.']);
    }

    public function issues()
    {
        $builder = $this->table('frota_issues')->where('deleted', 0);
        $vehicleId = (int)$this->request->getGet('vehicle_id');
        $status = trim((string)$this->request->getGet('status'));
        $severity = trim((string)$this->request->getGet('severity'));
        if ($vehicleId) $builder->where('vehicle_id', $vehicleId);
        if ($status) $builder->where('status', $status);
        if ($severity) $builder->where('severity', $severity);
        if ($this->toBool($this->request->getGet('open_only'))) $builder->whereIn('status', ['open', 'in_progress']);

        $rows = $builder->orderBy('reported_at', 'DESC')->get()->getResultArray();
        foreach ($rows as &$row) {
            $row['reported_by_name'] = $this->userName((int)($row['reported_by'] ?? 0));
            $row['photos'] = $this->parsePhotos($row['photo_url'] ?? '');
        }
        unset($row);
        return $this->respond(['status' => true, 'resource' => 'frota_issues', 'count' => count($rows), 'data' => $rows]);
    }

    public function issue(int $id)
    {
        $row = $this->findRow('frota_issues', $id);
        if (!$row) return $this->failNotFound('Ocorrência não encontrada.');
        $row['reported_by_name'] = $this->userName((int)($row['reported_by'] ?? 0));
        $row['photos'] = $this->parsePhotos($row['photo_url'] ?? '');
        return $this->respond(['status' => true, 'data' => $row]);
    }

    public function openIssuesByVehicle(int $vehicleId)
    {
        if (!$this->vehicleExists($vehicleId)) return $this->failNotFound('Veículo não encontrado.');
        $rows = $this->table('frota_issues')->where('vehicle_id', $vehicleId)->where('deleted', 0)->whereIn('status', ['open', 'in_progress'])->orderBy('reported_at', 'DESC')->get()->getResultArray();
        return $this->respond(['status' => true, 'count' => count($rows), 'data' => $rows]);
    }

    public function createIssue()
    {
        return $this->saveIssue(0);
    }

    public function updateIssue(int $id)
    {
        return $this->saveIssue($id);
    }

    protected function saveIssue(int $id)
    {
        $payload = $this->payload();
        if ($id && !$this->findRow('frota_issues', $id)) return $this->failNotFound('Ocorrência não encontrada.');
        foreach (['vehicle_id','title','description'] as $field) {
            if (!$id && empty($payload[$field])) return $this->failValidationErrors("Campo obrigatório: {$field}");
        }

        $existing = $id ? $this->findRow('frota_issues', $id) : [];
        $vehicleId = isset($payload['vehicle_id']) ? (int)$payload['vehicle_id'] : (int)($existing['vehicle_id'] ?? 0);
        if (!$this->vehicleExists($vehicleId)) return $this->failNotFound('Veículo não encontrado.');

        $allowed = ['vehicle_id','title','description','severity','status','odometer','assigned_to','photo_url'];
        $data = array_intersect_key($payload, array_flip($allowed));
        $data['vehicle_id'] = $vehicleId;
        if (array_key_exists('odometer', $data)) $data['odometer'] = ($data['odometer'] === '' || $data['odometer'] === null) ? null : $this->parseDecimal($data['odometer']);
        if (array_key_exists('assigned_to', $data)) $data['assigned_to'] = ($data['assigned_to'] === '' || $data['assigned_to'] === null) ? null : (int)$data['assigned_to'];
        if (array_key_exists('photo_url', $data) && is_array($data['photo_url'])) $data['photo_url'] = json_encode(array_values($data['photo_url']), JSON_UNESCAPED_SLASHES);

        if (!$id) {
            $data['reported_by'] = $this->staffId();
            $data['reported_at'] = date('Y-m-d H:i:s');
            $data['created_at'] = get_current_utc_time();
            $data['status'] = 'open';
            $data['deleted'] = 0;
            $this->table('frota_issues')->insert($data);
            $id = (int)$this->db->insertID();
        } else {
            unset($data['reported_by'], $data['reported_at']);
            $this->table('frota_issues')->where('id', $id)->update($data);
        }

        $saved = $this->findRow('frota_issues', $id);
        if ($saved) {
            $saved['reported_by_name'] = $this->userName((int)($saved['reported_by'] ?? 0));
            $saved['photos'] = $this->parsePhotos($saved['photo_url'] ?? '');
        }
        return $this->respond(['status' => true, 'message' => 'Ocorrência salva.', 'id' => $id, 'data' => $saved]);
    }

    public function resolveIssue(int $id)
    {
        if (!$this->findRow('frota_issues', $id)) return $this->failNotFound('Ocorrência não encontrada.');
        $payload = $this->payload();
        $this->table('frota_issues')->where('id', $id)->update([
            'status' => 'resolved',
            'resolved_at' => get_current_utc_time(),
            'resolution' => trim((string)($payload['resolution'] ?? 'Ocorrência resolvida via API'))
        ]);
        return $this->respond(['status' => true, 'message' => 'Ocorrência resolvida.', 'data' => $this->findRow('frota_issues', $id)]);
    }

    public function uploadIssuePhotos(int $id)
    {
        $issue = $this->findRow('frota_issues', $id);
        if (!$issue) return $this->failNotFound('Ocorrência não encontrada.');

        $files = $this->request->getFiles();
        $photos = $files['photos'] ?? [];
        if (!is_array($photos)) $photos = [$photos];
        if (!$photos) return $this->failValidationErrors('Envie ao menos uma foto no campo photos[].');

        $targetDir = FCPATH . 'files/frota/issues/';
        if (!is_dir($targetDir) && !mkdir($targetDir, 0775, true) && !is_dir($targetDir)) return $this->failServerError('Não foi possível preparar a pasta de fotos.');

        $allowed = ['image/jpeg','image/png','image/webp'];
        $saved = [];
        foreach ($photos as $photo) {
            if (!$photo || !$photo->isValid() || $photo->hasMoved()) continue;
            if ($photo->getSize() > 10 * 1024 * 1024) return $this->failValidationErrors('Cada foto deve ter no máximo 10 MB.');
            if (!in_array($photo->getMimeType(), $allowed, true)) return $this->failValidationErrors('Formato inválido. Envie JPG, PNG ou WEBP.');
            $ext = strtolower($photo->getExtension() ?: 'jpg');
            $fileName = date('YmdHis') . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
            $photo->move($targetDir, $fileName);
            $saved[] = base_url('files/frota/issues/' . $fileName);
        }

        $all = array_values(array_unique(array_merge($this->parsePhotos($issue['photo_url'] ?? ''), $saved)));
        $this->table('frota_issues')->where('id', $id)->update(['photo_url' => json_encode($all, JSON_UNESCAPED_SLASHES)]);
        return $this->respond(['status' => true, 'message' => 'Fotos adicionadas.', 'files' => $saved, 'photos' => $all]);
    }

    public function deleteIssue(int $id)
    {
        $issue = $this->findRow('frota_issues', $id);
        if (!$issue) return $this->failNotFound('Ocorrência não encontrada.');
        if ($this->db->tableExists($this->db->prefixTable('frota_maintenance_issue_links'))) {
            $this->table('frota_maintenance_issue_links')->where('issue_id', $id)->delete();
        }
        $this->table('frota_issues')->where('id', $id)->delete();
        return $this->respondDeleted(['status' => true, 'message' => 'Ocorrência excluída.']);
    }

    public function maintenances()
    {
        $builder = $this->table('frota_maintenances')->where('deleted', 0);
        $vehicleId = (int)$this->request->getGet('vehicle_id');
        $status = trim((string)$this->request->getGet('status'));
        $type = trim((string)$this->request->getGet('type'));
        if ($vehicleId) $builder->where('vehicle_id', $vehicleId);
        if ($status) $builder->where('status', $status);
        if ($type) $builder->where('type', $type);
        $rows = $builder->orderBy('service_date', 'DESC')->get()->getResultArray();
        foreach ($rows as &$row) $row['issue_ids'] = $this->maintenanceIssueIds((int)$row['id']);
        unset($row);
        return $this->respond(['status' => true, 'resource' => 'frota_maintenances', 'count' => count($rows), 'data' => $rows]);
    }

    public function maintenance(int $id)
    {
        $row = $this->findRow('frota_maintenances', $id);
        if (!$row) return $this->failNotFound('Manutenção não encontrada.');
        $row['issue_ids'] = $this->maintenanceIssueIds($id);
        $row['issues'] = $row['issue_ids'] ? $this->table('frota_issues')->whereIn('id', $row['issue_ids'])->get()->getResultArray() : [];
        return $this->respond(['status' => true, 'data' => $row]);
    }

    protected function maintenanceIssueIds(int $maintenanceId): array
    {
        if (!$this->db->tableExists($this->db->prefixTable('frota_maintenance_issue_links'))) return [];
        return array_map('intval', array_column($this->table('frota_maintenance_issue_links')->select('issue_id')->where('maintenance_id', $maintenanceId)->get()->getResultArray(), 'issue_id'));
    }

    public function createMaintenance()
    {
        return $this->saveMaintenance(0);
    }

    public function updateMaintenance(int $id)
    {
        return $this->saveMaintenance($id);
    }

    protected function saveMaintenance(int $id)
    {
        $payload = $this->payload();
        if ($id && !$this->findRow('frota_maintenances', $id)) return $this->failNotFound('Manutenção não encontrada.');
        foreach (['vehicle_id','description','service_date'] as $field) {
            if (!$id && empty($payload[$field])) return $this->failValidationErrors("Campo obrigatório: {$field}");
        }

        $existing = $id ? $this->findRow('frota_maintenances', $id) : [];
        $vehicleId = isset($payload['vehicle_id']) ? (int)$payload['vehicle_id'] : (int)($existing['vehicle_id'] ?? 0);
        if (!$this->vehicleExists($vehicleId)) return $this->failNotFound('Veículo não encontrado.');

        $allowed = ['vehicle_id','type','description','supplier','odometer','service_date','next_service_odometer','next_service_date','cost','status'];
        $data = array_intersect_key($payload, array_flip($allowed));
        $data['vehicle_id'] = $vehicleId;
        foreach (['odometer','next_service_odometer','cost'] as $field) {
            if (array_key_exists($field, $data)) $data[$field] = ($data[$field] === '' || $data[$field] === null) ? null : $this->parseDecimal($data[$field]);
        }
        if (array_key_exists('service_date', $data)) $data['service_date'] = $this->normalizeDate($data['service_date']);
        if (array_key_exists('next_service_date', $data)) $data['next_service_date'] = $this->normalizeDate($data['next_service_date']);

        $issueIds = $payload['issue_ids'] ?? [];
        if (!is_array($issueIds)) $issueIds = explode(',', (string)$issueIds);
        $issueIds = array_values(array_unique(array_filter(array_map('intval', $issueIds))));

        try {
            $this->db->transStart();
            if ($id) {
                $this->table('frota_maintenances')->where('id', $id)->update($data);
            } else {
                $data['type'] = $data['type'] ?? 'preventive';
                $data['status'] = $data['status'] ?? 'scheduled';
                $data['created_by'] = $this->staffId();
                $data['created_at'] = get_current_utc_time();
                $data['deleted'] = 0;
                $this->table('frota_maintenances')->insert($data);
                $id = (int)$this->db->insertID();
            }

            if ($this->db->tableExists($this->db->prefixTable('frota_maintenance_issue_links'))) {
                $this->table('frota_maintenance_issue_links')->where('maintenance_id', $id)->delete();
                foreach ($issueIds as $issueId) {
                    $issue = $this->table('frota_issues')->where('id', $issueId)->where('vehicle_id', $vehicleId)->where('deleted', 0)->get()->getRowArray();
                    if ($issue) {
                        $this->table('frota_maintenance_issue_links')->insert(['maintenance_id' => $id, 'issue_id' => $issueId, 'created_at' => get_current_utc_time()]);
                    }
                }
            }

            $finalStatus = $data['status'] ?? ($existing['status'] ?? 'scheduled');
            if ($finalStatus === 'completed') {
                $this->table('frota_maintenances')->where('id', $id)->update(['completed_at' => get_current_utc_time()]);
                if ($issueIds) {
                    $this->table('frota_issues')->whereIn('id', $issueIds)->where('vehicle_id', $vehicleId)->where('deleted', 0)->update([
                        'status' => 'resolved',
                        'resolved_at' => get_current_utc_time(),
                        'resolution' => 'Ocorrência encerrada pela manutenção #' . $id
                    ]);
                }
            }

            $vehicleUpdate = [];
            if (array_key_exists('next_service_odometer', $data)) $vehicleUpdate['next_service_odometer'] = $data['next_service_odometer'];
            if (array_key_exists('next_service_date', $data)) $vehicleUpdate['next_service_date'] = $data['next_service_date'];
            if ($vehicleUpdate) {
                $vehicleUpdate['updated_at'] = get_current_utc_time();
                $this->table('frota_vehicles')->where('id', $vehicleId)->update($vehicleUpdate);
            }

            $this->db->transComplete();
            if (!$this->db->transStatus()) throw new \RuntimeException('Falha na transação.');
        } catch (\Throwable $e) {
            log_message('error', '[RestApi/Frota] manutenção: ' . $e->getMessage());
            return $this->failServerError('Não foi possível salvar a manutenção.');
        }

        return $this->respond(['status' => true, 'message' => 'Manutenção salva.', 'id' => $id, 'data' => $this->maintenanceData($id)]);
    }

    protected function maintenanceData(int $id): ?array
    {
        $row = $this->findRow('frota_maintenances', $id);
        if (!$row) return null;
        $row['issue_ids'] = $this->maintenanceIssueIds($id);
        return $row;
    }

    public function deleteMaintenance(int $id)
    {
        if (!$this->findRow('frota_maintenances', $id)) return $this->failNotFound('Manutenção não encontrada.');
        if ($this->db->tableExists($this->db->prefixTable('frota_maintenance_issue_links'))) {
            $this->table('frota_maintenance_issue_links')->where('maintenance_id', $id)->delete();
        }
        $this->table('frota_maintenances')->where('id', $id)->delete();
        return $this->respondDeleted(['status' => true, 'message' => 'Manutenção excluída.']);
    }

    public function endpoints()
    {
        return $this->respond([
            'status' => true,
            'resource' => 'frota_endpoints',
            'data' => [
                'dashboard' => 'GET /api/frota/dashboard',
                'vehicles' => 'GET|POST /api/frota/vehicles; GET|PUT|PATCH|DELETE /api/frota/vehicles/{id}',
                'open_vehicle_issues' => 'GET /api/frota/vehicles/{id}/issues/open',
                'fuelings' => 'GET|POST /api/frota/fuelings; GET|PUT|PATCH|DELETE /api/frota/fuelings/{id}',
                'issues' => 'GET|POST /api/frota/issues; GET|PUT|PATCH|DELETE /api/frota/issues/{id}',
                'resolve_issue' => 'POST /api/frota/issues/{id}/resolve',
                'issue_photos' => 'POST multipart/form-data /api/frota/issues/{id}/photos (photos[])',
                'maintenances' => 'GET|POST /api/frota/maintenances; GET|PUT|PATCH|DELETE /api/frota/maintenances/{id}'
            ]
        ]);
    }
}
