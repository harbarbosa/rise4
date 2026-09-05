<?php

namespace PontoRH\Libraries;

class Solides_monitor_service
{
    private $db; private $settings;
    public function __construct(){ $this->db=db_connect(); $this->settings=model('PontoRH\\Models\\PontoRh_settings_model'); }
    public function configured(): bool { return trim($this->settings->get_setting('solides_api_token',''))!=='' && trim($this->settings->get_setting('solides_api_base_url',''))!==''; }
    public function sync(string $startDate='', string $endDate=''): array
    {
        if(!$this->configured()) return array('success'=>false,'message'=>'Configure o token e a URL da API Sólides antes de sincronizar.');
        $startDate=$startDate?:date('Y-m-d',strtotime('-7 days')); $endDate=$endDate?:date('Y-m-d');
        $base=rtrim($this->settings->get_setting('solides_api_base_url',''),'/'); $endpoint=$this->settings->get_setting('solides_punches_endpoint','/');
        $url=$base.'/'.ltrim($endpoint,'/').'?'.http_build_query(array('startDate'=>$startDate,'endDate'=>$endDate,'page'=>0,'size'=>500));
        try {
            $client=\Config\Services::curlrequest(array('timeout'=>30,'http_errors'=>false));
            $response=$client->get($url,array('headers'=>array('Authorization'=>'Basic '.$this->settings->get_setting('solides_api_token'),'Accept'=>'application/json')));
            if($response->getStatusCode()>=300) return array('success'=>false,'message'=>'Sólides respondeu HTTP '.$response->getStatusCode().'. Confira URL, endpoint e token.');
            $payload=json_decode((string)$response->getBody(),true); $items=$this->extractItems($payload); $saved=0;
            foreach($items as $item){ if($this->storePunch($item))$saved++; }
            $this->rebuildAlerts($startDate,$endDate); $this->settings->save_setting('solides_last_sync_at',get_current_utc_time());
            return array('success'=>true,'message'=>'Sincronização concluída.','received'=>count($items),'saved'=>$saved);
        } catch(\Throwable $e){ log_message('error','[PontoRH/Solides] '.$e->getMessage()); return array('success'=>false,'message'=>'Falha ao consultar a Sólides: '.$e->getMessage()); }
    }
    private function extractItems($payload):array { if(!is_array($payload))return array(); foreach(array('data','content','items','records','punches') as $k){if(isset($payload[$k])&&is_array($payload[$k]))return $payload[$k];} return array_is_list($payload)?$payload:array(); }
    private function value(array $i,array $keys,$d=''){foreach($keys as $k){if(isset($i[$k])&&$i[$k]!=='')return $i[$k];}return $d;}
    private function storePunch(array $i):bool
    {
        $employeeId=(string)$this->value($i,array('employeeId','employee_id','userId')); $recordId=(string)$this->value($i,array('id','recordId','punchId')); $time=$this->value($i,array('dateTime','datetime','punchTime','date','time'));
        if(!$employeeId||!$recordId||$time==='')return false;
        if(is_numeric($time)){ $seconds=((float)$time>20000000000)?((float)$time/1000):(float)$time; $punchTime=date('Y-m-d H:i:s',(int)$seconds); } else { try{$punchTime=(new \DateTime((string)$time))->format('Y-m-d H:i:s');}catch(\Throwable $e){return false;} }
        $map=$this->db->table($this->db->prefixTable('pontorh_solides_employees'))->where('solides_employee_id',$employeeId)->get()->getRow();
        $data=array('solides_record_id'=>$recordId,'solides_employee_id'=>$employeeId,'team_member_id'=>$map->team_member_id??null,'punch_time'=>$punchTime,'raw_payload'=>json_encode($i,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),'synced_at'=>get_current_utc_time());
        $table=$this->db->table($this->db->prefixTable('pontorh_solides_punches')); $exists=$table->where('solides_record_id',$recordId)->get()->getRow(); return $exists?(bool)$table->where('id',$exists->id)->update($data):(bool)$table->insert($data);
    }
    public function rebuildAlerts(string $startDate,string $endDate):void
    {
        $expected=max(1,(int)$this->settings->get_setting('solides_expected_daily_punches','4')); $sql='SELECT solides_employee_id, team_member_id, DATE(punch_time) work_date, COUNT(*) punch_count FROM '.$this->db->prefixTable('pontorh_solides_punches').' WHERE DATE(punch_time) BETWEEN ? AND ? GROUP BY solides_employee_id, team_member_id, DATE(punch_time)';
        foreach($this->db->query($sql,array($startDate,$endDate))->getResult() as $r){$status=((int)$r->punch_count===$expected)?'resolved':'pending';$t=$this->db->table($this->db->prefixTable('pontorh_solides_alerts'));$e=$t->where('solides_employee_id',$r->solides_employee_id)->where('work_date',$r->work_date)->get()->getRow();$d=array('team_member_id'=>$r->team_member_id,'punch_count'=>(int)$r->punch_count,'expected_count'=>$expected,'status'=>$status,'updated_at'=>get_current_utc_time());if($status==='resolved')$d['resolved_at']=get_current_utc_time();if($e)$t->where('id',$e->id)->update($d);else{$d+=array('solides_employee_id'=>$r->solides_employee_id,'work_date'=>$r->work_date,'created_at'=>get_current_utc_time());$t->insert($d);}}
    }
    public function alerts(string $status='pending'):array { $a=$this->db->prefixTable('pontorh_solides_alerts');$u=$this->db->prefixTable('users');return $this->db->query("SELECT a.*, CONCAT(COALESCE(u.first_name,''),' ',COALESCE(u.last_name,'')) employee_name FROM {$a} a LEFT JOIN {$u} u ON u.id=a.team_member_id WHERE a.status=? ORDER BY a.work_date DESC, employee_name",array($status))->getResult(); }
    public function employeeStatus(int $id):array { $p=$this->db->table($this->db->prefixTable('pontorh_solides_punches'))->where('team_member_id',$id)->where('punch_time >=',date('Y-m-d 00:00:00',strtotime('-1 day')))->orderBy('punch_time','ASC')->get()->getResult();$days=array();foreach($p as $x){$d=substr($x->punch_time,0,10);$days[$d][]=substr($x->punch_time,11,5);}return array('today'=>$days[date('Y-m-d')]??array(),'yesterday'=>$days[date('Y-m-d',strtotime('-1 day'))]??array(),'expected'=>(int)$this->settings->get_setting('solides_expected_daily_punches','4')); }
}
