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

    protected function staffId(): int
    {
        $email = strtolower(trim((string)($this->api_user->user ?? $this->api_user->email ?? '')));
        if ($email === '') return 0;
        $row = $this->db->table($this->db->prefixTable('users'))->select('id')->where('email',$email)->where('deleted',0)->get()->getRowArray();
        return (int)($row['id'] ?? 0);
    }

    protected function tableRows(string $table, array $where = []): array
    {
        $builder = $this->db->table($this->db->prefixTable($table))->where('deleted',0);
        foreach ($where as $key => $value) $builder->where($key,$value);
        return $builder->orderBy('id','DESC')->get()->getResultArray();
    }

    public function dashboard()
    {
        $vehicles = $this->tableRows('frota_vehicles');
        $issues = $this->tableRows('frota_issues');
        $fuelings = $this->tableRows('frota_fuelings');
        $maintenances = $this->tableRows('frota_maintenances');
        $fuelMonth = $maintenanceMonth = 0;
        foreach ($fuelings as $r) if (substr((string)$r['fueling_at'],0,7) === date('Y-m')) $fuelMonth += (float)$r['total_amount'];
        foreach ($maintenances as $r) if (substr((string)$r['service_date'],0,7) === date('Y-m')) $maintenanceMonth += (float)$r['cost'];
        return $this->respond(['status'=>true,'resource'=>'frota_dashboard','data'=>[
            'vehicles'=>count($vehicles),
            'active_vehicles'=>count(array_filter($vehicles,fn($r)=>$r['status']==='active')),
            'open_issues'=>count(array_filter($issues,fn($r)=>$r['status']!=='resolved')),
            'fuel_cost_month'=>$fuelMonth,
            'maintenance_cost_month'=>$maintenanceMonth,
        ]]);
    }

    public function vehicles()
    {
        $rows = $this->tableRows('frota_vehicles',['status'=>'active']);
        return $this->respond(['status'=>true,'resource'=>'frota_vehicles','count'=>count($rows),'data'=>$rows]);
    }

    public function vehicle(int $id)
    {
        $row = $this->db->table($this->db->prefixTable('frota_vehicles'))->where('id',$id)->where('deleted',0)->get()->getRowArray();
        if (!$row) return $this->failNotFound('Veículo não encontrado.');
        $row['recent_fuelings'] = array_slice($this->tableRows('frota_fuelings',['vehicle_id'=>$id]),0,10);
        $row['open_issues'] = array_values(array_filter($this->tableRows('frota_issues',['vehicle_id'=>$id]),fn($r)=>$r['status']!=='resolved'));
        $row['maintenances'] = array_slice($this->tableRows('frota_maintenances',['vehicle_id'=>$id]),0,10);
        return $this->respond(['status'=>true,'resource'=>'frota_vehicle','data'=>$row]);
    }

    public function fuelings()
    {
        $where = [];
        $vehicleId = (int)$this->request->getGet('vehicle_id');
        if ($vehicleId) $where['vehicle_id'] = $vehicleId;
        $rows = $this->tableRows('frota_fuelings',$where);
        return $this->respond(['status'=>true,'resource'=>'frota_fuelings','count'=>count($rows),'data'=>$rows]);
    }

    public function createFueling()
    {
        $p = $this->payload();
        foreach (['vehicle_id','odometer','liters','total_amount'] as $field) if (empty($p[$field])) return $this->failValidationErrors("Campo obrigatório: {$field}");
        $vehicleId = (int)$p['vehicle_id'];
        $vehicle = $this->db->table($this->db->prefixTable('frota_vehicles'))->where('id',$vehicleId)->where('deleted',0)->get()->getRowArray();
        if (!$vehicle) return $this->failNotFound('Veículo não encontrado.');
        $data = [
            'vehicle_id'=>$vehicleId,'user_id'=>$this->staffId(),'fueling_at'=>$p['fueling_at'] ?? date('Y-m-d H:i:s'),
            'odometer'=>$this->parseDecimal($p['odometer']),'liters'=>$this->parseDecimal($p['liters']),
            'unit_price'=>isset($p['unit_price'])?$this->parseDecimal($p['unit_price']):null,'total_amount'=>$this->parseDecimal($p['total_amount']),
            'fuel_type'=>$p['fuel_type'] ?? $vehicle['fuel_type'],'station'=>$p['station'] ?? null,'receipt_url'=>$p['receipt_url'] ?? null,
            'notes'=>$p['notes'] ?? null,'created_at'=>get_current_utc_time(),'deleted'=>0,
        ];
        $this->db->table($this->db->prefixTable('frota_fuelings'))->insert($data);
        $id = (int)$this->db->insertID();
        if ((float)$data['odometer'] > (float)$vehicle['current_odometer']) $this->db->table($this->db->prefixTable('frota_vehicles'))->where('id',$vehicleId)->update(['current_odometer'=>$data['odometer'],'updated_at'=>get_current_utc_time()]);
        return $this->respondCreated(['status'=>true,'message'=>'Abastecimento registrado.','id'=>$id,'data'=>$data]);
    }

    public function issues()
    {
        $where = [];
        $vehicleId = (int)$this->request->getGet('vehicle_id'); if ($vehicleId) $where['vehicle_id']=$vehicleId;
        $rows = $this->tableRows('frota_issues',$where);
        return $this->respond(['status'=>true,'resource'=>'frota_issues','count'=>count($rows),'data'=>$rows]);
    }

    public function createIssue()
    {
        $p = $this->payload();
        foreach (['vehicle_id','title','description'] as $field) if (empty($p[$field])) return $this->failValidationErrors("Campo obrigatório: {$field}");
        $vehicleId = (int)$p['vehicle_id'];
        $vehicle = $this->db->table($this->db->prefixTable('frota_vehicles'))->where('id',$vehicleId)->where('deleted',0)->get()->getRowArray();
        if (!$vehicle) return $this->failNotFound('Veículo não encontrado.');
        $data = [
            'vehicle_id'=>$vehicleId,'title'=>trim((string)$p['title']),'description'=>trim((string)$p['description']),
            'severity'=>$p['severity'] ?? 'medium','status'=>'open','odometer'=>isset($p['odometer'])?$this->parseDecimal($p['odometer']):null,
            'reported_by'=>$this->staffId(),'assigned_to'=>null,'reported_at'=>date('Y-m-d H:i:s'),'photo_url'=>$p['photo_url'] ?? null,
            'created_at'=>get_current_utc_time(),'deleted'=>0,
        ];
        $this->db->table($this->db->prefixTable('frota_issues'))->insert($data);
        $id = (int)$this->db->insertID();
        return $this->respondCreated(['status'=>true,'message'=>'Problema registrado.','id'=>$id,'data'=>$data]);
    }

    public function maintenances()
    {
        $where = [];
        $vehicleId = (int)$this->request->getGet('vehicle_id'); if ($vehicleId) $where['vehicle_id']=$vehicleId;
        $rows = $this->tableRows('frota_maintenances',$where);
        return $this->respond(['status'=>true,'resource'=>'frota_maintenances','count'=>count($rows),'data'=>$rows]);
    }
}
