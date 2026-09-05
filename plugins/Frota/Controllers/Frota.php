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
            show_404();
        }
    }

    protected function rows(string $table, array $where = []): array
    {
        $builder = $this->db->table($this->db->prefixTable($table))->where('deleted', 0);
        foreach ($where as $key => $value) {
            $builder->where($key, $value);
        }
        return $builder->orderBy('id', 'DESC')->get()->getResultArray();
    }

    protected function vehicles(): array
    {
        return $this->db->table($this->db->prefixTable('frota_vehicles'))
            ->where('deleted', 0)->orderBy('plate', 'ASC')->get()->getResultArray();
    }

    protected function render(string $section)
    {
        $this->requireAccess();
        $vehicles = $this->vehicles();
        $fuelings = $this->rows('frota_fuelings');
        $maintenances = $this->rows('frota_maintenances');
        $issues = $this->rows('frota_issues');
        $vehicleMap = [];
        foreach ($vehicles as $vehicle) {
            $vehicleMap[$vehicle['id']] = trim($vehicle['plate'] . ' - ' . $vehicle['model']);
        }
        $fuelMonth = 0;
        $maintenanceMonth = 0;
        foreach ($fuelings as $row) {
            if (substr((string)$row['fueling_at'], 0, 7) === date('Y-m')) $fuelMonth += (float)$row['total_amount'];
        }
        foreach ($maintenances as $row) {
            if (substr((string)$row['service_date'], 0, 7) === date('Y-m')) $maintenanceMonth += (float)$row['cost'];
        }
        $openIssues = count(array_filter($issues, fn($r) => $r['status'] !== 'resolved'));
        $maintenanceDue = 0;
        foreach ($vehicles as $vehicle) {
            if ((!empty($vehicle['next_service_date']) && $vehicle['next_service_date'] <= date('Y-m-d', strtotime('+30 days'))) || (!empty($vehicle['next_service_odometer']) && (float)$vehicle['current_odometer'] >= (float)$vehicle['next_service_odometer'])) $maintenanceDue++;
        }
        return $this->template->rander('Frota\\Views\\index', compact('section','vehicles','fuelings','maintenances','issues','vehicleMap','fuelMonth','maintenanceMonth','openIssues','maintenanceDue'));
    }

    public function index() { return $this->render('dashboard'); }
    public function veiculos() { return $this->render('vehicles'); }
    public function abastecimentos() { return $this->render('fuelings'); }
    public function manutencoes() { return $this->render('maintenances'); }
    public function ocorrencias() { return $this->render('issues'); }

    protected function posted(array $allowed): array
    {
        $data = [];
        foreach ($allowed as $key) {
            $value = $this->request->getPost($key);
            if ($value !== null) $data[$key] = is_string($value) ? trim($value) : $value;
        }
        return $data;
    }

    public function salvarVeiculo()
    {
        $this->requireAccess('frota_manage');
        $id = (int)$this->request->getPost('id');
        $data = $this->posted(['plate','prefix','make','model','year','fuel_type','current_odometer','next_service_odometer','next_service_date','status','assigned_user_id','notes']);
        $data['plate'] = strtoupper((string)($data['plate'] ?? ''));
        $data['updated_at'] = get_current_utc_time();
        if (!$id) { $data['created_at'] = get_current_utc_time(); $data['deleted'] = 0; }
        $table = $this->db->table($this->db->prefixTable('frota_vehicles'));
        $id ? $table->where('id',$id)->update($data) : $table->insert($data);
        return redirect()->to(get_uri('frota/veiculos'));
    }

    public function salvarAbastecimento()
    {
        $this->requireAccess('frota_fueling');
        $data = $this->posted(['vehicle_id','fueling_at','odometer','liters','unit_price','total_amount','fuel_type','station','receipt_url','notes']);
        $data['user_id'] = (int)$this->login_user->id;
        $data['created_at'] = get_current_utc_time(); $data['deleted'] = 0;
        if (empty($data['fueling_at'])) $data['fueling_at'] = date('Y-m-d H:i:s');
        $this->db->table($this->db->prefixTable('frota_fuelings'))->insert($data);
        if (!empty($data['vehicle_id']) && !empty($data['odometer'])) $this->db->table($this->db->prefixTable('frota_vehicles'))->where('id',(int)$data['vehicle_id'])->set('current_odometer','GREATEST(current_odometer,'.(float)$data['odometer'].')',false)->update();
        return redirect()->to(get_uri('frota/abastecimentos'));
    }

    public function salvarManutencao()
    {
        $this->requireAccess('frota_maintenance');
        $data = $this->posted(['vehicle_id','type','description','supplier','odometer','service_date','next_service_odometer','next_service_date','cost','status']);
        $data['created_by'] = (int)$this->login_user->id; $data['created_at'] = get_current_utc_time(); $data['deleted'] = 0;
        if (($data['status'] ?? '') === 'completed') $data['completed_at'] = get_current_utc_time();
        $this->db->table($this->db->prefixTable('frota_maintenances'))->insert($data);
        if (!empty($data['vehicle_id'])) {
            $update = [];
            if (!empty($data['next_service_odometer'])) $update['next_service_odometer'] = $data['next_service_odometer'];
            if (!empty($data['next_service_date'])) $update['next_service_date'] = $data['next_service_date'];
            if ($update) $this->db->table($this->db->prefixTable('frota_vehicles'))->where('id',(int)$data['vehicle_id'])->update($update);
        }
        return redirect()->to(get_uri('frota/manutencoes'));
    }

    public function salvarOcorrencia()
    {
        $this->requireAccess('frota_issue');
        $data = $this->posted(['vehicle_id','title','description','severity','odometer','assigned_to','photo_url']);
        $data['reported_by'] = (int)$this->login_user->id; $data['reported_at'] = get_current_utc_time(); $data['created_at'] = get_current_utc_time(); $data['status'] = 'open'; $data['deleted'] = 0;
        $this->db->table($this->db->prefixTable('frota_issues'))->insert($data);
        return redirect()->to(get_uri('frota/ocorrencias'));
    }

    public function resolverOcorrencia($id)
    {
        $this->requireAccess('frota_manage');
        $this->db->table($this->db->prefixTable('frota_issues'))->where('id',(int)$id)->update(['status'=>'resolved','resolution'=>$this->request->getPost('resolution'),'resolved_at'=>get_current_utc_time()]);
        return redirect()->to(get_uri('frota/ocorrencias'));
    }
}
