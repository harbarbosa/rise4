<?php

namespace LaudosTecnicos\Models;

use App\Models\Crud_model;

class Laudo_non_conformities_model extends Crud_model
{
    protected $table = 'laudo_non_conformities';

    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function get_details($options = array())
    {
        $table = $this->db->prefixTable($this->table);
        $laudos_table = $this->db->prefixTable('laudos_tecnicos');
        $clients_table = $this->db->prefixTable('clients');
        
        $where = "";
        $join = "LEFT JOIN $laudos_table ON $laudos_table.id = $table.laudo_id ";
        $join .= "LEFT JOIN $clients_table ON $clients_table.id = $table.client_id ";

        $id = $this->_get_clean_value($options, "id");
        if ($id) {
            $where .= " AND $table.id=$id";
        }

        $laudo_id = $this->_get_clean_value($options, "laudo_id");
        if ($laudo_id) {
            $where .= " AND $table.laudo_id=$laudo_id";
        }

        $status = $this->_get_clean_value($options, "status");
        if ($status) {
            $where .= " AND $table.status='$status'";
        }

        $classification = $this->_get_clean_value($options, "classification");
        if ($classification) {
            $where .= " AND $table.classification='$classification'";
        }

        $risk_level = $this->_get_clean_value($options, "risk_level");
        if ($risk_level) {
            $where .= " AND $table.risk_level>=$risk_level";
        }

        $search = $this->_get_clean_value($options, "search");
        if ($search) {
            $where .= " AND ($table.title LIKE '%$search%' OR $table.code LIKE '%$search%' OR $table.description LIKE '%$search%')";
        }

        $sql = "SELECT $table.*, 
            $laudos_table.laudo_number,
            $clients_table.company_name
        FROM $table $join
        WHERE $table.deleted=0 $where
        ORDER BY $table.risk_level DESC, $table.identified_at DESC";

        return $this->db->query($sql);
    }

    public function get_one($id = 0)
    {
        $table = $this->db->prefixTable($this->table);
        $sql = "SELECT * FROM $table WHERE id=$id AND deleted=0";
        return $this->db->query($sql)->getRow();
    }

    public function get_for_laudo($laudo_id)
    {
        return $this->get_details(array('laudo_id' => $laudo_id))->getResult();
    }

    public function get_stats($laudo_id = null)
    {
        $table = $this->db->prefixTable($this->table);
        
        $sql = "SELECT status, classification, COUNT(*) as count 
            FROM $table 
            WHERE deleted=0";
        if ($laudo_id) {
            $sql .= " AND laudo_id=$laudo_id";
        }
        $sql .= " GROUP BY status, classification";
        
        return $this->db->query($sql)->getResult();
    }

    public function calculate_risk($probability, $impact)
    {
        $matrix_model = model(Laudo_risk_matrix_model::class);
        return $matrix_model->get_risk($probability, $impact);
    }

    public function save($data, $id = 0)
    {
        $id = (int)$id;
        
        // Calcular risco se não definido
        if (!isset($data['risk_level']) || empty($data['risk_level'])) {
            $probability = $data['probability'] ?? 1;
            $impact = $data['impact'] ?? 1;
            $risk = $this->calculate_risk($probability, $impact);
            $data['risk_level'] = $risk['result'] ?? 1;
            $data['risk_color'] = $risk['color'] ?? '#198754';
        }

        if ($id) {
            $data['updated_at'] = get_my_local_time();
        } else {
            $data['created_at'] = get_my_local_time();
            $data['updated_at'] = get_my_local_time();
            // Gerar código
            if (empty($data['code'])) {
                $data['code'] = 'NC-' . date('Ym') . '-' . str_pad($this->get_next_number(), 4, '0', STR_PAD_LEFT);
            }
        }
        
        return parent::ci_save($data, $id);
    }

    private function get_next_number()
    {
        $table = $this->db->prefixTable($this->table);
        $result = $this->db->query("SELECT MAX(CAST(SUBSTRING(code, 9) AS UNSIGNED)) as max_num FROM $table WHERE code LIKE 'NC-" . date('Ym') . "%'")->getRow();
        return ($result && $result->max_num) ? $result->max_num + 1 : 1;
    }

    public function update_status($id, $status, $user_id, $comment = '')
    {
        $nc = $this->get_one($id);
        if (!$nc) return false;

        $old_status = $nc->status;
        
        $data = array('status' => $status);
        
        if ($status === 'validated') {
            $data['validated_at'] = get_my_local_time();
            $data['validated_by'] = $user_id;
        }
        
        $this->save($data, $id);

        // Log
        $log_model = model(Laudo_nc_logs_model::class);
        $log_model->add_log($id, $old_status, $status, $user_id, $comment);

        return true;
    }

    public function auto_create_from_checklist($checklist_item_id, $laudo_id, $response)
    {
        $checklist_items_model = model('LaudosTecnicos\Models\Laudo_checklists_model');
        $item = $checklist_items_model->get_item($checklist_item_id);
        
        if (!$item || $response !== 'Não conforme') return null;

        $data = array(
            'title' => $item->question,
            'description' => $item->guidance ?? 'Não conformidade identificada via checklist',
            'laudo_id' => $laudo_id,
            'checklist_item_id' => $checklist_item_id,
            'classification' => $item->severity === 'critical' ? 'critical' : ($item->severity === 'high' ? 'high' : 'moderate'),
            'probability' => 2,
            'impact' => 2,
            'status' => 'open',
            'identified_at' => date('Y-m-d'),
            'created_by' => 0 // Sistema
        );

        return $this->save($data, 0);
    }
}

class Laudo_action_plans_model extends Crud_model
{
    protected $table = 'laudo_action_plans';

    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function get_for_nc($nc_id)
    {
        $table = $this->db->prefixTable($this->table);
        $sql = "SELECT * FROM $table WHERE nc_id=$nc_id AND deleted=0 ORDER BY deadline ASC";
        return $this->db->query($sql)->getResult();
    }

    public function get_one($id = 0)
    {
        $table = $this->db->prefixTable($this->table);
        $sql = "SELECT * FROM $table WHERE id=$id AND deleted=0";
        return $this->db->query($sql)->getRow();
    }

    public function save($data, $id = 0)
    {
        $id = (int)$id;
        if ($id) {
            $data['updated_at'] = get_my_local_time();
        } else {
            $data['created_at'] = get_my_local_time();
        }
        return parent::ci_save($data, $id);
    }

    public function complete($id, $evidence)
    {
        $data = array(
            'status' => 'completed',
            'evidence' => $evidence,
            'completed_at' => date('Y-m-d')
        );
        return $this->save($data, $id);
    }

    public function create_task($id)
    {
        $plan = $this->get_one($id);
        if (!$plan) return null;

        // Criar tarefa no RISE
        $tasks_model = model('App\Models\Tasks_model');
        
        $nc_model = model('LaudosTecnicos\Models\Laudo_non_conformities_model');
        $nc = $nc_model->get_one($plan->nc_id);

        $task_data = array(
            'title' => '[NC-' . ($nc->code ?? $plan->nc_id) . '] ' . substr($plan->action, 0, 100),
            'description' => $plan->action . "\n\nNão Conformidade: " . ($nc->title ?? ''),
            'assigned_to' => $plan->responsible_id,
            'deadline' => $plan->deadline,
            'priority' => $plan->priority === 'high' ? 'high' : ($plan->priority === 'urgent' ? 'high' : 'medium'),
            'status' => 'to_do'
        );

        $task_id = $tasks_model->save($data);
        
        if ($task_id) {
            $this->save(array('task_id' => $task_id), $id);
        }

        return $task_id;
    }
}

class Laudo_risk_matrix_model extends Crud_model
{
    protected $table = 'laudo_risk_matrix';

    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function get_risk($probability, $impact)
    {
        $table = $this->db->prefixTable($this->table);
        $sql = "SELECT * FROM $table WHERE probability=$probability AND impact=$impact AND active=1 LIMIT 1";
        return $this->db->query($sql)->getRow();
    }

    public function get_matrix()
    {
        $table = $this->db->prefixTable($this->table);
        $sql = "SELECT * FROM $table WHERE active=1 ORDER BY result ASC";
        return $this->db->query($sql)->getResult();
    }

    public function get_for_category($category_id)
    {
        $table = $this->db->prefixTable($this->table);
        $sql = "SELECT * FROM $table WHERE (category_id=$category_id OR category_id IS NULL) AND active=1 ORDER BY result ASC";
        return $this->db->query($sql)->getResult();
    }
}

class Laudo_nc_logs_model extends Crud_model
{
    protected $table = 'laudo_nc_logs';

    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function add_log($nc_id, $old_status, $new_status, $user_id, $comment = '')
    {
        $data = array(
            'nc_id' => $nc_id,
            'action' => 'status_change',
            'old_status' => $old_status,
            'new_status' => $new_status,
            'comment' => $comment,
            'user_id' => $user_id,
            'created_at' => get_my_local_time()
        );
        
        return parent::ci_save($data, 0);
    }

    public function get_for_nc($nc_id)
    {
        $table = $this->db->prefixTable($this->table);
        $users_table = $this->db->prefixTable('users');
        
        $sql = "SELECT l.*, u.first_name as user_name 
            FROM $table l
            LEFT JOIN $users_table u ON u.id = l.user_id
            WHERE l.nc_id=$nc_id
            ORDER BY l.created_at DESC";
        
        return $this->db->query($sql)->getResult();
    }
}