<?php

namespace LaudosTecnicos\Models;

use App\Models\Crud_model;
use LaudosTecnicos\Models\Laudo_status_model;
use LaudosTecnicos\Models\Laudo_status_history_model;
use LaudosTecnicos\Models\Laudos_settings_model;

class Laudos_model extends Crud_model
{
    protected $table = null;

    public function __construct()
    {
        $this->table = 'laudos_tecnicos';
        parent::__construct($this->table);
    }

    public function get_details($options = array())
    {
        $table = $this->db->prefixTable($this->table);
        $laudo_types_table = $this->db->prefixTable('laudo_types');
        $laudo_categories_table = $this->db->prefixTable('laudo_categories');
        $users_table = $this->db->prefixTable('users');
        $clients_table = $this->db->prefixTable('clients');
        $projects_table = $this->db->prefixTable('projects');

        $where = "";
        $join = "LEFT JOIN $laudo_types_table ON $laudo_types_table.id = $table.laudo_type_id ";
        $join .= "LEFT JOIN $laudo_categories_table ON $laudo_categories_table.id = $table.category_id ";
        $join .= "LEFT JOIN $users_table AS technician ON technician.id = $table.technician_id ";
        $join .= "LEFT JOIN $users_table AS reviewer ON reviewer.id = $table.reviewer_id ";
        $join .= "LEFT JOIN $users_table AS approver ON approver.id = $table.approver_id ";
        $join .= "LEFT JOIN $users_table AS commercial ON commercial.id = $table.commercial_responsible_id ";
        $join .= "LEFT JOIN $users_table AS created_user ON created_user.id = $table.created_by ";
        $join .= "LEFT JOIN $clients_table ON $clients_table.id = $table.client_id ";
        $join .= "LEFT JOIN $projects_table ON $projects_table.id = $table.project_id ";

        $id = $this->_get_clean_value($options, "id");
        if ($id) {
            $where .= " AND $table.id=$id";
        }

        $status = $this->_get_clean_value($options, "status");
        if ($status) {
            $where .= " AND $table.status='$status'";
        }

        $laudo_type_id = $this->_get_clean_value($options, "laudo_type_id");
        if ($laudo_type_id) {
            $where .= " AND $table.laudo_type_id=$laudo_type_id";
        }

        $category_id = $this->_get_clean_value($options, "category_id");
        if ($category_id) {
            $where .= " AND $table.category_id=$category_id";
        }

        $client_id = $this->_get_clean_value($options, "client_id");
        if ($client_id) {
            $where .= " AND $table.client_id=$client_id";
        }

        $project_id = $this->_get_clean_value($options, "project_id");
        if ($project_id) {
            $where .= " AND $table.project_id=$project_id";
        }

        $technician_id = $this->_get_clean_value($options, "technician_id");
        if ($technician_id) {
            $where .= " AND $table.technician_id=$technician_id";
        }

        $reviewer_id = $this->_get_clean_value($options, "reviewer_id");
        if ($reviewer_id) {
            $where .= " AND $table.reviewer_id=$reviewer_id";
        }

        $priority = $this->_get_clean_value($options, "priority");
        if ($priority) {
            $where .= " AND $table.priority='$priority'";
        }

        $search = $this->_get_clean_value($options, "search");
        if ($search) {
            $where .= " AND ($table.title LIKE '%$search%' OR $table.laudo_number LIKE '%$search%' OR $table.custom_code LIKE '%$search%' OR $table.description LIKE '%$search%' OR $clients_table.company_name LIKE '%$search%')";
        }

        // Filtro de data
        $start_date = $this->_get_clean_value($options, "start_date");
        if ($start_date) {
            $where .= " AND $table.request_date >= '$start_date'";
        }
        $end_date = $this->_get_clean_value($options, "end_date");
        if ($end_date) {
            $where .= " AND $table.request_date <= '$end_date'";
        }

        // Validade
        $validity_status = $this->_get_clean_value($options, "validity_status");
        if ($validity_status === 'expired') {
            $where .= " AND $table.valid_until < CURDATE() AND $table.status='issued'";
        } elseif ($validity_status === 'valid') {
            $where .= " AND ($table.valid_until >= CURDATE() OR $table.valid_until IS NULL) AND $table.status='issued'";
        }

        $company_id = $this->_get_company_id();
        if ($company_id) {
            $where .= " AND ($clients_table.company_id=$company_id OR $table.client_id IS NULL OR $table.client_id=0)";
        }

        $sql = "SELECT $table.*, 
            $laudo_types_table.name as type_name, 
            $laudo_types_table.prefix as type_prefix,
            $laudo_categories_table.name as category_name,
            $laudo_categories_table.color as category_color,
            technician.first_name as technician_name,
            reviewer.first_name as reviewer_name,
            approver.first_name as approver_name,
            commercial.first_name as commercial_name,
            created_user.first_name as created_by_name,
            $clients_table.company_name,
            $projects_table.title as project_title
        FROM $table 
        $join
        WHERE $table.deleted=0 $where
        ORDER BY $table.created_at DESC";

        return $this->db->query($sql);
    }

    public function get_one($id = 0)
    {
        $table = $this->db->prefixTable($this->table);
        $laudo_types_table = $this->db->prefixTable('laudo_types');
        $laudo_categories_table = $this->db->prefixTable('laudo_categories');
        $users_table = $this->db->prefixTable('users');
        $clients_table = $this->db->prefixTable('clients');

        $join = "LEFT JOIN $laudo_types_table ON $laudo_types_table.id = $table.laudo_type_id ";
        $join .= "LEFT JOIN $laudo_categories_table ON $laudo_categories_table.id = $table.category_id ";
        $join .= "LEFT JOIN $users_table AS technician ON technician.id = $table.technician_id ";
        $join .= "LEFT JOIN $users_table AS reviewer ON reviewer.id = $table.reviewer_id ";
        $join .= "LEFTJOIN $users_table AS approver ON approver.id = $table.approver_id ";
        $join .= "LEFT JOIN $clients_table ON $clients_table.id = $table.client_id ";

        $sql = "SELECT $table.*, 
            $laudo_types_table.name as type_name, 
            $laudo_types_table.prefix as type_prefix,
            $laudo_types_table.validity_days as type_validity_days,
            $laudo_categories_table.name as category_name,
            $laudo_categories_table.color as category_color,
            technician.first_name as technician_name,
            technician.last_name as technician_last_name,
            reviewer.first_name as reviewer_name,
            approver.first_name as approver_name,
            $clients_table.company_name,
            $clients_table.phone as client_phone,
            $clients_table.email as client_email
        FROM $table 
        $join
        WHERE $table.id=$id AND $table.deleted=0";

        return $this->db->query($sql)->getRow();
    }

    public function get_counts_by_status($company_id = null)
    {
        $table = $this->db->prefixTable($this->table);
        $clients_table = $this->db->prefixTable('clients');
        
        $status_model = model(Laudo_status_model::class);
        $status_list = $status_model->get_dropdown();
        
        $counts = array('total' => 0);
        foreach (array_keys($status_list) as $status) {
            $counts[$status] = 0;
        }

        $where = "WHERE $table.deleted=0";
        if ($company_id) {
            $where .= " AND ($clients_table.company_id=$company_id OR $table.client_id IS NULL OR $table.client_id=0)";
        }

        $sql = "SELECT status, COUNT(*) as count 
            FROM $table 
            LEFT JOIN $clients_table ON $clients_table.id = $table.client_id
            $where
            GROUP BY status";

        $result = $this->db->query($sql)->getResult();
        
        foreach ($result as $row) {
            if (isset($counts[$row->status])) {
                $counts[$row->status] = (int)$row->count;
                $counts['total'] += (int)$row->count;
            }
        }

        return $counts;
    }

    public function get_counts_by_priority($company_id = null)
    {
        $table = $this->db->prefixTable($this->table);
        
        $sql = "SELECT priority, COUNT(*) as count 
            FROM $table 
            WHERE deleted=0 AND status NOT IN ('issued', 'canceled', 'expired')
            GROUP BY priority";

        $result = $this->db->query($sql)->getResult();
        
        $counts = array('low' => 0, 'normal' => 0, 'high' => 0, 'urgent' => 0);
        foreach ($result as $row) {
            $counts[$row->priority] = (int)$row->count;
        }

        return $counts;
    }

    public function get_team_members($laudo_id)
    {
        $table = $this->db->prefixTable($this->table);
        $users_table = $this->db->prefixTable('users');

        $sql = "SELECT $users_table.id, $users_table.first_name, $users_table.last_name, $users_table.avatar,
            CASE 
                WHEN $table.technician_id = $users_table.id THEN 'inspector'
                WHEN $table.reviewer_id = $users_table.id THEN 'reviewer'
                WHEN $table.approver_id = $users_table.id THEN 'approver'
                WHEN $table.commercial_responsible_id = $users_table.id THEN 'commercial'
                ELSE 'member'
            END as role
            FROM $table 
            LEFT JOIN $users_table ON $users_table.id IN ($table.technician_id, $table.reviewer_id, $table.approver_id, $table.commercial_responsible_id)
            WHERE $table.id = $laudo_id AND $users_table.id IS NOT NULL";

        return $this->db->query($sql)->getResult();
    }

    public function generate_laudo_number($laudo_type_id = null)
    {
        $settings_model = model(Laudos_settings_model::class);
        $settings = $settings_model->get_settings();

        $prefix = $settings['laudo_prefix'] ?? 'LAU';
        $format = $settings['number_format'] ?? '{PREFIX}-{YEAR}{MONTH}{SEQUENCE}';
        $next_number = (int)($settings['next_number'] ?? 1);

        if ($laudo_type_id) {
            $types_table = $this->db->prefixTable('laudo_types');
            $type = $this->db->query("SELECT prefix FROM $types_table WHERE id=$laudo_type_id")->getRow();
            if ($type && $type->prefix) {
                $prefix = $type->prefix;
            }
        }

        $year = date('Y');
        $month = date('m');
        $day = date('d');
        $sequence = str_pad($next_number, 4, '0', STR_PAD_LEFT);

        $number = str_replace(
            ['{PREFIX}', '{YEAR}', '{MONTH}', '{DAY}', '{SEQUENCE}'],
            [$prefix, $year, $month, $day, $sequence],
            $format
        );

        // Atualizar próximo número
        $settings_model->save_settings(array('next_number' => $next_number + 1));

        return $number;
    }

    public function save($data, $id = 0)
    {
        $id = (int)$id;
        
        // Gerar número se for novo
        if (!$id && empty($data['laudo_number'])) {
            $data['laudo_number'] = $this->generate_laudo_number($data['laudo_type_id'] ?? null);
        }

        // Calcular validade se não definida
        if (!isset($data['valid_until']) || empty($data['valid_until'])) {
            $type_id = $data['laudo_type_id'] ?? null;
            if ($type_id) {
                $types_table = $this->db->prefixTable('laudo_types');
                $type = $this->db->query("SELECT validity_days FROM $types_table WHERE id=$type_id")->getRow();
                if ($type && $type->validity_days) {
                    $data['valid_until'] = date('Y-m-d', strtotime("+{$type->validity_days} days"));
                }
            }
        }

        if ($id) {
            $data['updated_at'] = get_my_local_time();
        } else {
            $data['created_at'] = get_my_local_time();
            $data['updated_at'] = get_my_local_time();
        }

        return parent::ci_save($data, $id);
    }

    public function change_status($id, $new_status, $user_id, $comment = '')
    {
        $laudo = $this->get_one($id);
        if (!$laudo) {
            return false;
        }

        $old_status = $laudo->status;

        // Verificar se transição é válida
        $transitions_model = model(Laudo_status_transitions_model::class);
        if (!$transitions_model->can_transition($old_status, $new_status)) {
            return array('success' => false, 'message' => 'Transição de status não permitida');
        }

        // Atualizar status
        $data = array(
            'status' => $new_status,
            'previous_status' => $old_status,
            'current_status_changed_at' => get_my_local_time()
        );

        // Definir datas baseado no status
        if ($new_status === 'issued' && empty($laudo->issue_date)) {
            $data['issue_date'] = date('Y-m-d');
        } elseif ($new_status === 'approved' && empty($laudo->approved_at)) {
            $data['approved_at'] = get_my_local_time();
        } elseif ($new_status === 'rejected' && empty($laudo->rejected_at)) {
            $data['rejected_at'] = get_my_local_time();
            $data['rejection_reason'] = $comment;
        } elseif ($new_status === 'signed' && empty($laudo->signature_date)) {
            $data['signature_date'] = get_my_local_time();
        }

        $this->save($data, $id);

        // Registrar histórico
        $history_model = model(Laudo_status_history_model::class);
        $history_model->add_history($id, $old_status, $new_status, $user_id, $comment);

        return array('success' => true, 'message' => 'Status alterado com sucesso');
    }

    public function duplicate($id, $options = array())
    {
        $original = $this->get_one($id);
        if (!$original) {
            return false;
        }

        // Campos a copiar
        $fields_to_copy = array(
            'laudo_type_id', 'category_id', 'client_id', 'project_id',
            'title', 'description', 'address', 'city', 'state', 'location',
            'priority', 'contact_id', 'contract_id', 'work_order_id',
            'request_date', 'scheduled_date', 'inspection_date',
            'commercial_responsible_id', 'technician_id',
            'objective', 'scope', 'methodology', 'assumptions', 'limitations',
            'installation_description', 'results', 'diagnosis', 'conclusion', 'recommendations',
            'observations', 'internal_notes', 'tags', 'cost_center',
            'proposal_number', 'contract_number', 'external_reference', 'confidentiality', 'client_observations'
        );

        $data = array();
        foreach ($fields_to_copy as $field) {
            if (property_exists($original, $field)) {
                $data[$field] = $original->$field;
            }
        }

        // Resetar campos que não devem ser copiados
        $data['laudo_number'] = null; // Será gerado automaticamente
        $data['custom_code'] = null;
        $data['version'] = 1;
        $data['status'] = 'draft';
        $data['file_path'] = null;
        $data['signature_data'] = null;
        $data['signature_date'] = null;
        $data['approved_at'] = null;
        $data['rejected_at'] = null;
        $data['rejection_reason'] = null;
        $data['issue_date'] = null;
        $data['valid_until'] = null;
        $data['start_inspection_date'] = null;
        $data['end_inspection_date'] = null;

        // Adicionar sufixo ao título
        if (!empty($data['title'])) {
            $data['title'] = $data['title'] . ' (Cópia)';
        }

        return $this->save($data, 0);
    }

    public function can_delete($id)
    {
        $laudo = $this->get_one($id);
        if (!$laudo) {
            return false;
        }

        // Rascunhos podem ser excluídos
        if ($laudo->status === 'draft') {
            return true;
        }

        // Verificar se tem permissão
        return false;
    }

    public function soft_delete($id)
    {
        $laudo = $this->get_one($id);
        if (!$laudo) {
            return false;
        }

        // Laudos emitidos não podem ser excluídos
        if ($laudo->status === 'issued' || $laudo->status === 'signed') {
            return false;
        }

        return parent::delete($id);
    }

    public function delete($id, $undo = false)
    {
        $laudo = $this->get_one($id);
        if (!$laudo) {
            return false;
        }

        // Verificar se pode excluir
        if (!$undo && $laudo->status !== 'draft' && $laudo->status !== 'requested') {
            return false;
        }

        return parent::delete($id, $undo);
    }

    public function get_laudos_for_client($client_id)
    {
        return $this->get_details(array('client_id' => $client_id))->getResult();
    }

    public function get_laudos_for_project($project_id)
    {
        return $this->get_details(array('project_id' => $project_id))->getResult();
    }

    public function search($term, $limit = 20)
    {
        $table = $this->db->prefixTable($this->table);
        $clients_table = $this->db->prefixTable('clients');
        $types_table = $this->db->prefixTable('laudo_types');

        $sql = "SELECT $table.id, $table.laudo_number, $table.title, $table.status, $table.version,
            $clients_table.company_name, $types_table.name as type_name
            FROM $table 
            LEFT JOIN $clients_table ON $clients_table.id = $table.client_id
            LEFT JOIN $types_table ON $types_table.id = $table.laudo_type_id
            WHERE $table.deleted=0 
            AND ($table.title LIKE '%$term%' OR $table.laudo_number LIKE '%$term%' OR $clients_table.company_name LIKE '%$term%')
            ORDER BY $table.created_at DESC
            LIMIT $limit";

        return $this->db->query($sql)->getResult();
    }
}