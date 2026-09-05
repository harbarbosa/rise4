<?php
namespace PontoRH\Controllers;
use PontoRH\Libraries\Solides_monitor_service;
class PontoRH_solides extends PontoRH_Base_Controller
{
    private $monitor;
    public function __construct(){parent::__construct();$this->monitor=new Solides_monitor_service();}
    public function index(){ $this->ensureAccess(); $data=array('alerts'=>$this->monitor->alerts(),'settings'=>$this->settings_model->get_all_settings_with_defaults()); return $this->template->rander('PontoRH\\Views\\solides\\index',$data); }
    public function sync(){ $this->ensureAccess(); $result=$this->monitor->sync((string)$this->request->getPost('start_date'),(string)$this->request->getPost('end_date')); return $this->response->setJSON($result); }
    public function save_settings(){ $this->ensureAccess(); foreach(array('solides_api_token','solides_api_base_url','solides_punches_endpoint','solides_expected_daily_punches','solides_employee_portal_url') as $k){$v=$this->request->getPost($k);if($v!==null)$this->settings_model->save_setting($k,$v);} return $this->response->setJSON(array('success'=>true,'message'=>'Configurações salvas.')); }
    public function mobile_status(){ $status=$this->monitor->employeeStatus((int)$this->login_user->id); $status['solides_url']=$this->settings_model->get_setting('solides_employee_portal_url',''); $y=count($status['yesterday']); $status['alert']=$y!==$status['expected']?array('type'=>'warning','title'=>'Ponto de ontem incompleto','message'=>'Identificamos '.$y.' de '.$status['expected'].' marcações ontem. Confira e solicite o ajuste diretamente na Sólides.'):null; return $this->response->setJSON(array('success'=>true,'data'=>$status)); }
}
