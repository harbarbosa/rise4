<?php

namespace LaudosTecnicos\Controllers;

use App\Controllers\Security_Controller;

class Laudo_reports extends Security_Controller
{
    public function __construct()
    {
        parent::__construct(true);
        $this->access_only_team_members();
    }

    public function index()
    {
        $view_data = array(
            'reports' => $this->_get_reports_list()
        );
        return $this->template->rander('LaudosTecnicos\Views\reports\index', $view_data);
    }

    public function run($type)
    {
        $start_date = $this->request->getGet('start_date') ?: date('Y-m-01');
        $end_date = $this->request->getGet('end_date') ?: date('Y-m-t');
        
        $data = $this->_generate_report($type, $start_date, $end_date);
        
        return $this->response->setJSON(array(
            'success' => true,
            'data' => $data
        ));
    }

    public function export($type, $format = 'pdf')
    {
        $start_date = $this->request->getGet('start_date') ?: date('Y-m-01');
        $end_date = $this->request->getGet('end_date') ?: date('Y-m-t');
        
        $data = $this->_generate_report($type, $start_date, $end_date);
        
        if ($format === 'excel' || $format === 'csv') {
            return $this->_export_csv($data, $type);
        }
        
        return $this->_export_pdf($data, $type);
    }

    private function _get_reports_list()
    {
        $db = db_connect('default');
        $prefix = get_db_prefix();
        
        return $db->query("SELECT * FROM {$prefix}laudo_reports ORDER BY name")->getResult();
    }

    private function _generate_report($type, $start_date, $end_date)
    {
        $db = db_connect('default');
        $prefix = get_db_prefix();
        
        $data = array();
        
        switch ($type) {
            case 'laudos_period':
                $sql = "SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as total 
                    FROM {$prefix}laudos_tecnicos 
                    WHERE created_at BETWEEN '$start_date' AND '$end_date' AND deleted=0
                    GROUP BY month ORDER BY month";
                $data = $db->query($sql)->getResult();
                break;
                
            case 'laudos_client':
                $sql = "SELECT c.company_name, COUNT(l.id) as total 
                    FROM {$prefix}laudos_tecnicos l
                    LEFT JOIN {$prefix}clients c ON c.id = l.client_id
                    WHERE l.created_at BETWEEN '$start_date' AND '$end_date' AND l.deleted=0
                    GROUP BY l.client_id ORDER BY total DESC";
                $data = $db->query($sql)->getResult();
                break;
                
            case 'laudos_status':
                $sql = "SELECT status, COUNT(*) as total 
                    FROM {$prefix}laudos_tecnicos 
                    WHERE deleted=0 GROUP BY status ORDER BY total DESC";
                $data = $db->query($sql)->getResult();
                break;
                
            case 'laudos_type':
                $sql = "SELECT t.name as type_name, COUNT(l.id) as total 
                    FROM {$prefix}laudos_tecnicos l
                    LEFT JOIN {$prefix}laudo_types t ON t.id = l.laudo_type_id
                    WHERE l.created_at BETWEEN '$start_date' AND '$end_date' AND l.deleted=0
                    GROUP BY l.laudo_type_id ORDER BY total DESC";
                $data = $db->query($sql)->getResult();
                break;
                
            case 'laudos_overdue':
                $sql = "SELECT * FROM {$prefix}laudos_tecnicos 
                    WHERE validity_end < CURDATE() AND status != 'completed' AND deleted=0
                    ORDER BY validity_end";
                $data = $db->query($sql)->getResult();
                break;
                
            case 'nonconformities':
                $sql = "SELECT classification, status, COUNT(*) as total 
                    FROM {$prefix}laudo_non_conformities 
                    WHERE deleted=0 GROUP BY classification, status";
                $data = $db->query($sql)->getResult();
                break;
                
            case 'action_plans':
                $sql = "SELECT status, priority, COUNT(*) as total 
                    FROM {$prefix}laudo_action_plans 
                    WHERE deleted=0 GROUP BY status, priority";
                $data = $db->query($sql)->getResult();
                break;
                
            case 'inspections_unproductive':
                $sql = "SELECT u.first_name as responsible, COUNT(*) as total, i.scheduled_date
                    FROM {$prefix}laudo_inspections i
                    LEFT JOIN {$prefix}users u ON u.id = i.responsible_id
                    WHERE i.status = 'unproductive' AND i.scheduled_date BETWEEN '$start_date' AND '$end_date'
                    GROUP BY i.responsible_id";
                $data = $db->query($sql)->getResult();
                break;
                
            case 'equipment_calibration':
                $sql = "SELECT e.name, e.serial_number, e.next_calibration_date
                    FROM {$prefix}laudo_equipment e
                    WHERE e.next_calibration_date < CURDATE() AND e.deleted=0
                    ORDER BY e.next_calibration_date";
                $data = $db->query($sql)->getResult();
                break;
                
            case 'productivity':
                $sql = "SELECT 
                    COUNT(*) as total_laudos,
                    AVG(TIMESTAMPDIFF(DAY, created_at, published_at)) as avg_days,
                    COUNT(DISTINCT responsible_id) as technicians
                    FROM {$prefix}laudos_tecnicos
                    WHERE status = 'completed' AND created_at BETWEEN '$start_date' AND '$end_date' AND deleted=0";
                $data = (array)$db->query($sql)->getRow();
                break;
        }
        
        return $data;
    }

    private function _export_csv($data, $type)
    {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="relatorio_' . $type . '_' . date('Ymd') . '.csv"');
        
        $output = fopen('php://output', 'w');
        
        if (!empty($data)) {
            fputcsv($output, array_keys((array)$data[0]));
            foreach ($data as $row) {
                fputcsv($output, (array)$row);
            }
        }
        
        fclose($output);
    }

    private function _export_pdf($data, $type)
    {
        // Simple HTML export for PDF
        $html = '<h1>Relatório: ' . ucfirst(str_replace('_', ' ', $type)) . '</h1>';
        $html .= '<table border="1"><tr>';
        
        if (!empty($data)) {
            $headers = array_keys((array)$data[0]);
            foreach ($headers as $h) {
                $html .= '<th>' . $h . '</th>';
            }
            $html .= '</tr>';
            
            foreach ($data as $row) {
                $html .= '<tr>';
                foreach ((array)$row as $v) {
                    $html .= '<td>' . $v . '</td>';
                }
                $html .= '</tr>';
            }
        }
        
        $html .= '</table>';
        
        return $this->response->setHTML($html);
    }
}

class Laudo_prompts_lib extends Security_Controller
{
    public function __construct()
    {
        parent::__construct(true);
        $this->access_only_team_members();
    }

    public function index()
    {
        $db = db_connect('default');
        $prefix = get_db_prefix();
        
        $prompts = $db->query("SELECT * FROM {$prefix}laudo_prompts WHERE is_active=1 ORDER BY category, name")->getResult();
        
        $view_data = array('prompts' => $prompts);
        return $this->template->rander('LaudosTecnicos\Views\prompts\index', $view_data);
    }

    public function use_prompt($code)
    {
        $db = db_connect('default');
        $prefix = get_db_prefix();
        
        $prompt = $db->query("SELECT * FROM {$prefix}laudo_prompts WHERE code='$code' AND is_active=1")->getRow();
        
        if (!$prompt) {
            return $this->response->setJSON(['success' => false, 'message' => 'Prompt não encontrado']);
        }
        
        return $this->response->setJSON([
            'success' => true,
            'data' => [
                'id' => $prompt->id,
                'name' => $prompt->name,
                'template' => $prompt->prompt_template,
                'variables' => json_decode($prompt->variables)
            ]
        ]);
    }

    public function execute()
    {
        $prompt_id = $this->request->getPost('prompt_id');
        $variables = $this->request->getPost('variables');
        
        $db = db_connect('default');
        $prefix = get_db_prefix();
        
        $prompt = $db->query("SELECT * FROM {$prefix}laudo_prompts WHERE id=$prompt_id")->getRow();
        
        if (!$prompt) {
            return $this->response->setJSON(['success' => false, 'message' => 'Prompt não encontrado']);
        }
        
        // Substituir variáveis
        $final_prompt = $prompt->prompt_template;
        foreach ($variables as $key => $value) {
            $final_prompt = str_replace('{{' . $key . '}}', $value, $final_prompt);
        }
        
        // Chamar IA
        try {
            $ai_model = model('LaudosTecnicos\Models\Laudo_ai_model');
            $response = $ai_model->generate('custom', $final_prompt, $this->login_user->id);
            
            return $this->response->setJSON([
                'success' => true,
                'data' => ['response' => $response]
            ]);
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
}

class Laudo_automations extends Security_Controller
{
    public function __construct()
    {
        parent::__construct(true);
        $this->access_only_team_members();
    }

    public function index()
    {
        $db = db_connect('default');
        $prefix = get_db_prefix();
        
        $automations = $db->query("SELECT * FROM {$prefix}laudo_automations ORDER BY name")->getResult();
        
        $view_data = array('automations' => $automations);
        return $this->template->rander('LaudosTecnicos\Views\automations\index', $view_data);
    }

    public function run($code)
    {
        $db = db_connect('default');
        $prefix = get_db_prefix();
        
        $automation = $db->query("SELECT * FROM {$prefix}laudo_automations WHERE code='$code' AND is_active=1")->getRow();
        
        if (!$automation) {
            return $this->response->setJSON(['success' => false, 'message' => 'Automação não encontrada ou inativa']);
        }
        
        $config = json_decode($automation->config, true);
        $results = array();
        
        switch ($automation->action) {
            case 'notify_expiring':
                $days = $config['days_before'] ?? 30;
                $sql = "SELECT * FROM {$prefix}laudos_tecnicos 
                    WHERE validity_end BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL $days DAY) 
                    AND status != 'completed' AND deleted=0";
                $results['laudos'] = $db->query($sql)->getResult();
                break;
                
            case 'mark_expired':
                $db->query("UPDATE {$prefix}laudos_tecnicos SET status='expired' 
                    WHERE validity_end < CURDATE() AND status NOT IN ('completed', 'canceled') AND deleted=0");
                $results['updated'] = $db->affectedRows();
                break;
                
            case 'notify_upcoming':
                $hours = $config['hours_before'] ?? 24;
                $sql = "SELECT i.*, l.laudo_number, c.company_name 
                    FROM {$prefix}laudo_inspections i
                    LEFT JOIN {$prefix}laudos_tecnicos l ON l.id = i.laudo_id
                    LEFT JOIN {$prefix}clients c ON c.id = l.client_id
                    WHERE i.scheduled_date = CURDATE() 
                    AND i.status IN ('scheduled', 'confirmed') AND i.deleted=0";
                $results['inspections'] = $db->query($sql)->getResult();
                break;
                
            case 'mark_overdue':
                $db->query("UPDATE {$prefix}laudo_inspections SET status='overdue' 
                    WHERE scheduled_date < CURDATE() AND status NOT IN ('completed', 'unproductive', 'canceled') AND deleted=0");
                $results['updated'] = $db->affectedRows();
                break;
                
            case 'notify_calibration':
                $days = $config['days_before'] ?? 15;
                $sql = "SELECT * FROM {$prefix}laudo_equipment 
                    WHERE next_calibration_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL $days DAY) 
                    AND deleted=0";
                $results['equipment'] = $db->query($sql)->getResult();
                break;
                
            case 'mark_calibration_expired':
                $db->query("UPDATE {$prefix}laudo_equipment SET calibration_status='expired' 
                    WHERE next_calibration_date < CURDATE() AND deleted=0");
                $results['updated'] = $db->affectedRows();
                break;
                
            case 'notify_plans_overdue':
                $db->query("UPDATE {$prefix}laudo_action_plans SET status='overdue' 
                    WHERE deadline < CURDATE() AND status NOT IN ('completed', 'canceled') AND deleted=0");
                $results['updated'] = $db->affectedRows();
                break;
                
            case 'revoke_expired_shares':
                $db->query("UPDATE {$prefix}laudo_shares SET active=0 
                    WHERE expires_at < CURDATE() AND active=1");
                $results['revoked'] = $db->affectedRows();
                break;
                
            case 'cleanup_expired_tokens':
                $days = $config['days_old'] ?? 30;
                $db->query("DELETE FROM {$prefix}laudo_devices 
                    WHERE last_access_at < DATE_SUB(NOW(), INTERVAL $days DAY) AND is_revoked=1");
                $results['deleted'] = $db->affectedRows();
                break;
        }
        
        // Atualizar última execução
        $db->query("UPDATE {$prefix}laudo_automations SET last_run_at=NOW() WHERE id=" . $automation->id);
        
        return $this->response->setJSON(['success' => true, 'results' => $results]);
    }

    public function toggle($id)
    {
        $db = db_connect('default');
        $prefix = get_db_prefix();
        
        $automation = $db->query("SELECT * FROM {$prefix}laudo_automations WHERE id=$id")->getRow();
        
        if (!$automation) {
            return $this->response->setJSON(['success' => false]);
        }
        
        $db->query("UPDATE {$prefix}laudo_automations SET is_active = NOT is_active WHERE id=$id");
        
        return $this->response->setJSON(['success' => true]);
    }
}

class Laudo_dashboard extends Security_Controller
{
    public function __construct()
    {
        parent::__construct(true);
        $this->access_only_team_members();
    }

    public function index()
    {
        $db = db_connect('default');
        $prefix = get_db_prefix();
        
        // Laudos por status
        $status_data = $db->query("SELECT status, COUNT(*) as total FROM {$prefix}laudos_tecnicos WHERE deleted=0 GROUP BY status")->getResult();
        
        // Laudos por tipo
        $type_data = $db->query("SELECT t.name, COUNT(l.id) as total 
            FROM {$prefix}laudos_tecnicos l
            LEFT JOIN {$prefix}laudo_types t ON t.id = l.laudo_type_id
            WHERE l.deleted=0 GROUP BY l.laudo_type_id ORDER BY total DESC LIMIT 5")->getResult();
        
        // Laudos próximos vencimento
        $expiring = $db->query("SELECT * FROM {$prefix}laudos_tecnicos 
            WHERE validity_end BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) 
            AND status NOT IN ('completed', 'canceled') AND deleted=0")->getResult();
        
        // Não conformidades críticas
        $nc_critical = $db->query("SELECT * FROM {$prefix}laudo_non_conformities 
            WHERE classification IN ('critical', 'emergential') AND status NOT IN ('validated', 'rejected', 'canceled') 
            AND deleted=0")->getResult();
        
        // Visitas de hoje
        $today = $db->query("SELECT i.*, l.laudo_number, c.company_name, u.first_name as responsible_name
            FROM {$prefix}laudo_inspections i
            LEFT JOIN {$prefix}laudos_tecnicos l ON l.id = i.laudo_id
            LEFT JOIN {$prefix}clients c ON c.id = l.client_id
            LEFT JOIN {$prefix}users u ON u.id = i.responsible_id
            WHERE i.scheduled_date = CURDATE() AND i.deleted=0")->getResult();
        
        // Visitas atrasadas
        $overdue = $db->query("SELECT i.*, l.laudo_number, c.company_name
            FROM {$prefix}laudo_inspections i
            LEFT JOIN {$prefix}laudos_tecnicos l ON l.id = i.laudo_id
            LEFT JOIN {$prefix}clients c ON c.id = l.client_id
            WHERE i.scheduled_date < CURDATE() AND i.status NOT IN ('completed', 'unproductive', 'canceled') AND i.deleted=0")->getResult();
        
        // Planos de ação vencidos
        $plans_overdue = $db->query("SELECT * FROM {$prefix}laudo_action_plans 
            WHERE deadline < CURDATE() AND status NOT IN ('completed', 'canceled') AND deleted=0")->getResult();
        
        // Estatísticas gerais
        $stats = $db->query("SELECT 
            (SELECT COUNT(*) FROM {$prefix}laudos_tecnicos WHERE deleted=0) as total_laudos,
            (SELECT COUNT(*) FROM {$prefix}laudos_tecnicos WHERE status='completed' AND deleted=0) as completed,
            (SELECT COUNT(*) FROM {$prefix}laudo_non_conformities WHERE status NOT IN ('validated', 'rejected', 'canceled') AND deleted=0) as open_nc,
            (SELECT COUNT(*) FROM {$prefix}laudo_inspections WHERE scheduled_date = CURDATE()) as today_inspections")->getRow();
        
        $view_data = array(
            'status_data' => $status_data,
            'type_data' => $type_data,
            'expiring' => $expiring,
            'nc_critical' => $nc_critical,
            'today' => $today,
            'overdue' => $overdue,
            'plans_overdue' => $plans_overdue,
            'stats' => $stats
        );
        
        return $this->template->rander('LaudosTecnicos\Views\dashboard\index', $view_data);
    }
}