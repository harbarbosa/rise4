<?php

namespace Fotovoltaico\Models;

use App\Models\Crud_model;

class Proposals_model extends Crud_model
{
    protected $table = 'fv_proposals';
    protected $primaryKey = 'id';
    protected $returnType = 'object';
    protected $useSoftDeletes = true;
    protected $deletedField = 'deleted';
    protected $allowedFields = array('proposal_code', 'client_id', 'lead_id', 'contact_id', 'project_id', 'distributor_id', 'consumer_unit', 'consumption_avg', 'current_version', 'wizard_step', 'wizard_data_json', 'title', 'status', 'currency', 'subtotal', 'discount_total', 'tax_total', 'total', 'issue_date', 'valid_until', 'notes', 'metadata_json', 'deleted', 'created_by', 'created_at', 'updated_at');

    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function get_details($options = array())
    {
        $table = $this->db->prefixTable('fv_proposals');
        $users_table = $this->db->prefixTable('users');
        $clients_table = $this->db->prefixTable('clients');
        $distributors_table = $this->db->prefixTable('fv_distributors');

        if (!$this->db->tableExists($table)) {
            return $this->db->query('SELECT 1 AS id WHERE 0');
        }

        $where = "WHERE $table.deleted=0";

        $id = (int) get_array_value($options, 'id');
        if ($id) {
            $where .= " AND $table.id=$id";
        }

        $client_id = (int) get_array_value($options, 'client_id');
        if ($client_id) {
            $where .= " AND $table.client_id=$client_id";
        }

        $lead_id = (int) get_array_value($options, 'lead_id');
        if ($lead_id) {
            $where .= " AND $table.lead_id=$lead_id";
        }

        $contact_id = (int) get_array_value($options, 'contact_id');
        if ($contact_id) {
            $where .= " AND $table.contact_id=$contact_id";
        }

        $distributor_id = (int) get_array_value($options, 'distributor_id');
        if ($distributor_id) {
            $where .= " AND $table.distributor_id=$distributor_id";
        }

        $status = trim((string) get_array_value($options, 'status'));
        if ($status !== '') {
            $where .= " AND $table.status=" . $this->db->escape($status);
        }

        $search = trim((string) get_array_value($options, 'search'));
        if ($search !== '') {
            $search = $this->db->escapeLikeString($search);
            $where .= " AND (" . implode(" OR ", array(
                "$table.title LIKE '%$search%' ESCAPE '!'",
                "$table.proposal_code LIKE '%$search%' ESCAPE '!'",
                "$table.consumer_unit LIKE '%$search%' ESCAPE '!'",
                "$clients_table.company_name LIKE '%$search%' ESCAPE '!'",
                "CONCAT(IFNULL($users_table.first_name, ''), ' ', IFNULL($users_table.last_name, '')) LIKE '%$search%' ESCAPE '!'",
                "$distributors_table.title LIKE '%$search%' ESCAPE '!'"
            )) . ")";
        }

        $sql = "SELECT $table.*,
            CONCAT(IFNULL($users_table.first_name, ''), ' ', IFNULL($users_table.last_name, '')) AS created_by_name,
            client_table.company_name AS client_company_name,
            lead_table.company_name AS lead_company_name,
            contact_table.first_name AS contact_first_name,
            contact_table.last_name AS contact_last_name,
            contact_client_table.company_name AS contact_client_company_name,
            $distributors_table.title AS distributor_title,
            $distributors_table.acronym AS distributor_acronym
        FROM $table
        LEFT JOIN $users_table ON $users_table.id=$table.created_by AND $users_table.deleted=0
        LEFT JOIN $clients_table AS client_table ON client_table.id=$table.client_id AND client_table.deleted=0 AND client_table.is_lead=0
        LEFT JOIN $clients_table AS lead_table ON lead_table.id=$table.lead_id AND lead_table.deleted=0 AND lead_table.is_lead=1
        LEFT JOIN $users_table AS contact_table ON contact_table.id=$table.contact_id AND contact_table.deleted=0
        LEFT JOIN $clients_table AS contact_client_table ON contact_client_table.id=contact_table.client_id AND contact_client_table.deleted=0
        LEFT JOIN $distributors_table ON $distributors_table.id=$table.distributor_id AND $distributors_table.deleted=0
        $where
        ORDER BY $table.id DESC";

        try {
            return $this->db->query($sql);
        } catch (\Throwable $e) {
            log_message('error', '[Fotovoltaico] Proposals query error: ' . $e->getMessage());
            return $this->db->query('SELECT 1 AS id WHERE 0');
        }
    }

    public function get_one_with_details($id)
    {
        return $this->get_details(array('id' => (int) $id))->getRow();
    }

    public function get_last_wizard_draft_by_user($user_id)
    {
        $table = $this->db->prefixTable('fv_proposals');
        $user_id = (int) $user_id;
        if (!$this->db->tableExists($table)) {
            return null;
        }

        $sql = "SELECT * FROM $table
            WHERE deleted=0 AND created_by=$user_id AND status IN ('draft','in_progress')
            ORDER BY id DESC
            LIMIT 1";

        try {
            $query = $this->db->query($sql);
            return $query ? $query->getRow() : null;
        } catch (\Throwable $e) {
            log_message('error', '[Fotovoltaico] Proposals wizard draft query error: ' . $e->getMessage());
            return null;
        }
    }

    public function save_wizard_state($proposal_id, $data)
    {
        $proposal_id = (int) $proposal_id;
        if (!$proposal_id) {
            return false;
        }

        $existing = $this->get_one($proposal_id);
        $wizard_data = array();
        if ($existing && $existing->wizard_data_json) {
            $decoded = json_decode($existing->wizard_data_json, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $wizard_data = $decoded;
            }
        }

        $step = trim((string) get_array_value($data, 'wizard_step'));
        unset($data['wizard_step']);

        $wizard_data = array_merge($wizard_data, $data);

        $save_data = array(
            'wizard_step' => $step,
            'wizard_data_json' => json_encode($wizard_data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'updated_at' => get_my_local_time(),
        );

        $save_data = clean_data($save_data);
        return $this->ci_save($save_data, $proposal_id);
    }
}
