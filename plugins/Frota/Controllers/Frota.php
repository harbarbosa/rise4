<?php

namespace Frota\Controllers;

use App\Controllers\Security_Controller;

class Frota extends Security_Controller
{
    protected $db;

    public function __construct()
    {
        parent::__construct();
        helper('frota');
        $this->db = db_connect('default');
    }

    protected function requireAccess(string $permission = 'frota_view'): void
    {
        if ($this->login_user && $this->login_user->is_admin) {
            return;
        }

        $permissions = $this->login_user->permissions ?? [];
        if (get_array_value($permissions, $permission) != '1' && get_array_value($permissions, 'frota_manage') != '1') {
            app_redirect('forbidden');
        }
    }

    protected function table(string $name)
    {
        return $this->db->table($this->db->prefixTable($name));
    }

    protected function rows(string $table, array $where = [], string $orderBy = 'id DESC'): array
    {
        $builder = $this->table($table)->where('deleted', 0);
        foreach ($where as $key => $value) {
            if ($value !== '' && $value !== null) {
                $builder->where($key, $value);
            }
        }

        [$field, $direction] = array_pad(explode(' ', $orderBy, 2), 2, 'DESC');
        return $builder->orderBy($field, $direction)->get()->getResultArray();
    }

    protected function one(string $table, int $id): array
    {
        if (!$id) {
            return [];
        }

        return $this->table($table)->where('id', $id)->where('deleted', 0)->get()->getRowArray() ?: [];
    }

    protected function vehicles(): array
    {
        return $this->table('frota_vehicles')->where('deleted', 0)->orderBy('plate', 'ASC')->get()->getResultArray();
    }

    protected function commonData(bool $includeBlank = true): array
    {
        $vehicles = $this->vehicles();
        $vehicleMap = [];
        $vehicleOptions = $includeBlank ? ['' => '- ' . app_lang('select') . ' -'] : [];

        foreach ($vehicles as $vehicle) {
            $label = trim(($vehicle['plate'] ?? '') . ' - ' . ($vehicle['model'] ?? ''));
            $vehicleMap[$vehicle['id']] = $label;
            $vehicleOptions[$vehicle['id']] = $label;
        }

        return compact('vehicles', 'vehicleMap', 'vehicleOptions');
    }

    protected function posted(array $allowed): array
    {
        $data = [];
        foreach ($allowed as $key) {
            $value = $this->request->getPost($key);
            if ($value !== null) {
                $data[$key] = is_string($value) ? trim($value) : $value;
            }
        }
        return $data;
    }

    protected function jsonSaved(array $row = [], int $id = 0)
    {
        return $this->response->setJSON([
            'success' => true,
            'data' => $row,
            'id' => $id,
            'message' => app_lang('record_saved')
        ]);
    }

    public function index()
    {
        $this->requireAccess();
        $data = $this->commonData();
        $fuelings = $this->rows('frota_fuelings');
        $maintenances = $this->rows('frota_maintenances', [], 'service_date DESC');
        $issues = $this->rows('frota_issues', [], 'reported_at DESC');

        $fuelMonth = 0;
        $maintenanceMonth = 0;
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

        $openIssues = count(array_filter($issues, fn($r) => ($r['status'] ?? '') !== 'resolved'));
        $maintenanceDue = 0;
        foreach ($data['vehicles'] as $vehicle) {
            $dateDue = !empty($vehicle['next_service_date']) && $vehicle['next_service_date'] <= date('Y-m-d', strtotime('+30 days'));
            $kmDue = !empty($vehicle['next_service_odometer']) && (float)$vehicle['current_odometer'] >= (float)$vehicle['next_service_odometer'];
            if ($dateDue || $kmDue) {
                $maintenanceDue++;
            }
        }

        $data += compact('fuelMonth', 'maintenanceMonth', 'openIssues', 'maintenanceDue', 'issues', 'maintenances');
        return $this->template->rander('Frota\\Views\\dashboard', $data);
    }

    public function veiculos()
    {
        $this->requireAccess();
        return $this->template->rander('Frota\\Views\\vehicles');
    }

    public function abastecimentos()
    {
        $this->requireAccess();
        return $this->template->rander('Frota\\Views\\fuelings', $this->commonData());
    }

    public function manutencoes()
    {
        $this->requireAccess();
        return $this->template->rander('Frota\\Views\\maintenances', $this->commonData());
    }

    public function ocorrencias()
    {
        $this->requireAccess();
        return $this->template->rander('Frota\\Views\\issues', $this->commonData());
    }

    public function veiculosListData()
    {
        $this->requireAccess();
        $status = (string)$this->request->getPost('status');
        $where = $status ? ['status' => $status] : [];
        $result = [];
        foreach ($this->rows('frota_vehicles', $where, 'plate ASC') as $row) {
            $result[] = $this->vehicleRow($row);
        }
        return $this->response->setJSON(['data' => $result]);
    }

    protected function vehicleRow(array $v): array
    {
        $id = (int)$v['id'];
        $next = '-';
        if (!empty($v['next_service_date'])) {
            $next = $v['next_service_date'];
        } elseif (!empty($v['next_service_odometer'])) {
            $next = number_format((float)$v['next_service_odometer'], 0, ',', '.') . ' km';
        }

        $actions = modal_anchor(
            get_uri('frota/veiculos/modal_form'),
            '<i data-feather="edit" class="icon-16"></i>',
            ['class' => 'edit', 'title' => app_lang('edit'), 'data-post-id' => $id]
        );

        return [
            '<strong>' . esc($v['plate']) . '</strong>',
            esc($v['prefix'] ?: '-'),
            esc(trim(($v['make'] ?? '') . ' ' . ($v['model'] ?? ''))),
            esc($v['year'] ?: '-'),
            number_format((float)($v['current_odometer'] ?? 0), 0, ',', '.') . ' km',
            esc($next),
            frota_status_badge($v['status'] ?? ''),
            $actions
        ];
    }

    public function abastecimentosListData()
    {
        $this->requireAccess();
        $data = $this->commonData();
        $vehicleId = (int)$this->request->getPost('vehicle_id');
        $dateFrom = trim((string)$this->request->getPost('date_from'));
        $dateTo = trim((string)$this->request->getPost('date_to'));
        $builder = $this->table('frota_fuelings')->where('deleted', 0);
        if ($vehicleId) $builder->where('vehicle_id', $vehicleId);
        if ($dateFrom) $builder->where('DATE(fueling_at) >=', $dateFrom);
        if ($dateTo) $builder->where('DATE(fueling_at) <=', $dateTo);

        $result = [];
        foreach ($builder->orderBy('fueling_at', 'DESC')->get()->getResultArray() as $r) {
            $actions = modal_anchor(get_uri('frota/abastecimentos/modal_form'), '<i data-feather="edit" class="icon-16"></i>', ['class' => 'edit', 'title' => app_lang('edit'), 'data-post-id' => (int)$r['id']]);
            $result[] = [
                esc($r['fueling_at']),
                esc($data['vehicleMap'][$r['vehicle_id']] ?? '#' . $r['vehicle_id']),
                number_format((float)$r['odometer'], 0, ',', '.') . ' km',
                number_format((float)$r['liters'], 2, ',', '.') . ' L',
                frota_money($r['unit_price']),
                frota_money($r['total_amount']),
                esc($r['station'] ?: '-'),
                $actions
            ];
        }
        return $this->response->setJSON(['data' => $result]);
    }

    public function manutencoesListData()
    {
        $this->requireAccess();
        $data = $this->commonData();
        $where = [];
        foreach (['vehicle_id', 'type', 'status'] as $key) {
            $value = $this->request->getPost($key);
            if ($value !== null && $value !== '') $where[$key] = $value;
        }

        $result = [];
        foreach ($this->rows('frota_maintenances', $where, 'service_date DESC') as $r) {
            $actions = modal_anchor(get_uri('frota/manutencoes/modal_form'), '<i data-feather="edit" class="icon-16"></i>', ['class' => 'edit', 'title' => app_lang('edit'), 'data-post-id' => (int)$r['id']]);
            $result[] = [
                esc($r['service_date']),
                esc($data['vehicleMap'][$r['vehicle_id']] ?? '#' . $r['vehicle_id']),
                ($r['type'] ?? '') === 'preventive' ? 'Preventiva' : 'Corretiva',
                esc($r['description']),
                esc($r['supplier'] ?: '-'),
                frota_money($r['cost']),
                frota_status_badge($r['status'] ?? ''),
                $actions
            ];
        }
        return $this->response->setJSON(['data' => $result]);
    }

    public function ocorrenciasListData()
    {
        $this->requireAccess();
        $data = $this->commonData();
        $where = [];
        foreach (['vehicle_id', 'severity', 'status'] as $key) {
            $value = $this->request->getPost($key);
            if ($value !== null && $value !== '') $where[$key] = $value;
        }

        $result = [];
        foreach ($this->rows('frota_issues', $where, 'reported_at DESC') as $r) {
            $actions = modal_anchor(get_uri('frota/ocorrencias/modal_form'), '<i data-feather="edit" class="icon-16"></i>', ['class' => 'edit', 'title' => app_lang('edit'), 'data-post-id' => (int)$r['id']]);
            if (($r['status'] ?? '') !== 'resolved') {
                $actions .= modal_anchor(get_uri('frota/ocorrencias/resolve_modal_form'), '<i data-feather="check-circle" class="icon-16"></i>', ['class' => 'edit ms-2', 'title' => 'Resolver ocorrência', 'data-post-id' => (int)$r['id']]);
            }

            $result[] = [
                esc($r['reported_at']),
                esc($data['vehicleMap'][$r['vehicle_id']] ?? '#' . $r['vehicle_id']),
                '<strong>' . esc($r['title']) . '</strong><div class="text-off small">' . esc($r['description']) . '</div>',
                ucfirst(esc($r['severity'] ?? '-')),
                !empty($r['odometer']) ? number_format((float)$r['odometer'], 0, ',', '.') . ' km' : '-',
                frota_status_badge($r['status'] ?? ''),
                $actions
            ];
        }
        return $this->response->setJSON(['data' => $result]);
    }

    public function veiculoModalForm()
    {
        $this->requireAccess('frota_manage');
        $id = (int)$this->request->getPost('id');
        return $this->template->view('Frota\\Views\\vehicle_modal_form', ['model_info' => (object)$this->one('frota_vehicles', $id)]);
    }

    public function abastecimentoModalForm()
    {
        $this->requireAccess('frota_fueling');
        $id = (int)$this->request->getPost('id');
        $data = $this->commonData();
        $data['model_info'] = (object)$this->one('frota_fuelings', $id);
        return $this->template->view('Frota\\Views\\fueling_modal_form', $data);
    }

    public function manutencaoModalForm()
    {
        $this->requireAccess('frota_maintenance');
        $id = (int)$this->request->getPost('id');
        $data = $this->commonData();
        $data['model_info'] = (object)$this->one('frota_maintenances', $id);
        return $this->template->view('Frota\\Views\\maintenance_modal_form', $data);
    }

    public function ocorrenciaModalForm()
    {
        $this->requireAccess('frota_issue');
        $id = (int)$this->request->getPost('id');
        $data = $this->commonData();
        $data['model_info'] = (object)$this->one('frota_issues', $id);
        return $this->template->view('Frota\\Views\\issue_modal_form', $data);
    }

    public function resolverOcorrenciaModalForm()
    {
        $this->requireAccess('frota_manage');
        $id = (int)$this->request->getPost('id');
        return $this->template->view('Frota\\Views\\resolve_issue_modal_form', ['model_info' => (object)$this->one('frota_issues', $id)]);
    }

    public function salvarVeiculo()
    {
        $this->requireAccess('frota_manage');
        $id = (int)$this->request->getPost('id');
        $data = $this->posted(['plate','prefix','make','model','year','fuel_type','current_odometer','next_service_odometer','next_service_date','status','assigned_user_id','notes']);
        $data['plate'] = strtoupper((string)($data['plate'] ?? ''));
        $data['updated_at'] = get_current_utc_time();
        if (!$id) {
            $data['created_at'] = get_current_utc_time();
            $data['deleted'] = 0;
            $this->table('frota_vehicles')->insert($data);
            $id = (int)$this->db->insertID();
        } else {
            $this->table('frota_vehicles')->where('id', $id)->update($data);
        }
        return $this->jsonSaved($this->vehicleRow($this->one('frota_vehicles', $id)), $id);
    }

    public function salvarAbastecimento()
    {
        $this->requireAccess('frota_fueling');
        $id = (int)$this->request->getPost('id');
        $data = $this->posted(['vehicle_id','fueling_at','odometer','liters','unit_price','total_amount','fuel_type','station','receipt_url','notes']);
        if (empty($data['fueling_at'])) $data['fueling_at'] = date('Y-m-d H:i:s');

        if ($id) {
            $this->table('frota_fuelings')->where('id', $id)->update($data);
        } else {
            $data['user_id'] = (int)$this->login_user->id;
            $data['created_at'] = get_current_utc_time();
            $data['deleted'] = 0;
            $this->table('frota_fuelings')->insert($data);
            $id = (int)$this->db->insertID();
        }

        if (!empty($data['vehicle_id']) && !empty($data['odometer'])) {
            $this->table('frota_vehicles')->where('id', (int)$data['vehicle_id'])->set('current_odometer', 'GREATEST(current_odometer,' . (float)$data['odometer'] . ')', false)->update();
        }
        return $this->jsonSaved([], $id);
    }

    public function salvarManutencao()
    {
        $this->requireAccess('frota_maintenance');
        $id = (int)$this->request->getPost('id');
        $data = $this->posted(['vehicle_id','type','description','supplier','odometer','service_date','next_service_odometer','next_service_date','cost','status']);
        if (($data['status'] ?? '') === 'completed') $data['completed_at'] = get_current_utc_time();

        if ($id) {
            $this->table('frota_maintenances')->where('id', $id)->update($data);
        } else {
            $data['created_by'] = (int)$this->login_user->id;
            $data['created_at'] = get_current_utc_time();
            $data['deleted'] = 0;
            $this->table('frota_maintenances')->insert($data);
            $id = (int)$this->db->insertID();
        }

        if (!empty($data['vehicle_id'])) {
            $update = [];
            if (!empty($data['next_service_odometer'])) $update['next_service_odometer'] = $data['next_service_odometer'];
            if (!empty($data['next_service_date'])) $update['next_service_date'] = $data['next_service_date'];
            if ($update) $this->table('frota_vehicles')->where('id', (int)$data['vehicle_id'])->update($update);
        }
        return $this->jsonSaved([], $id);
    }

    public function salvarOcorrencia()
    {
        $this->requireAccess('frota_issue');
        $id = (int)$this->request->getPost('id');
        $data = $this->posted(['vehicle_id','title','description','severity','odometer','assigned_to','photo_url','status']);

        if ($id) {
            unset($data['status']);
            $this->table('frota_issues')->where('id', $id)->update($data);
        } else {
            $data['reported_by'] = (int)$this->login_user->id;
            $data['reported_at'] = get_current_utc_time();
            $data['created_at'] = get_current_utc_time();
            $data['status'] = 'open';
            $data['deleted'] = 0;
            $this->table('frota_issues')->insert($data);
            $id = (int)$this->db->insertID();
        }
        return $this->jsonSaved([], $id);
    }

    public function resolverOcorrencia($id)
    {
        $this->requireAccess('frota_manage');
        $this->table('frota_issues')->where('id', (int)$id)->update([
            'status' => 'resolved',
            'resolution' => trim((string)$this->request->getPost('resolution')),
            'resolved_at' => get_current_utc_time()
        ]);
        return $this->jsonSaved([], (int)$id);
    }
}
