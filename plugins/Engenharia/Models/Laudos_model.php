<?php

namespace Engenharia\Models;

class Laudos_model extends EngenhariaBaseModel
{
    protected $table = 'eng_laudos';

    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function get_details(array $options = array())
    {
        if (!$this->hasTable()) {
            return $this->emptyResult();
        }

        $laudos = $this->db->prefixTable('eng_laudos');
        $types = $this->db->prefixTable('eng_types');
        $clients = $this->db->prefixTable('clients');
        $projects = $this->db->prefixTable('projects');
        $users = $this->db->prefixTable('users');
        $where = "$laudos.deleted = 0";

        foreach (array('id', 'type_id', 'client_id', 'contact_id', 'project_id', 'technical_responsible_id', 'inspection_technician_id') as $field) {
            $value = (int) get_array_value($options, $field);
            if ($value) {
                $where .= " AND $laudos.$field = $value";
            }
        }

        $status = trim((string) get_array_value($options, 'status'));
        if ($status !== '') {
            $where .= " AND $laudos.status = '" . $this->db->escapeString($status) . "'";
        }

        $search = trim((string) get_array_value($options, 'search'));
        $search_by = trim((string) get_array_value($options, 'search_by'));
        if ($search === '' && $search_by !== '') {
            $search = $search_by;
        }
        if ($search !== '') {
            $search = $this->db->escapeLikeString($search);
            $where .= " AND ($laudos.number LIKE '%$search%' OR $laudos.title LIKE '%$search%' OR $clients.company_name LIKE '%$search%' OR $types.name LIKE '%$search%')";
        }

        $inspection_date_from = trim((string) get_array_value($options, 'inspection_date_from'));
        $inspection_date_to = trim((string) get_array_value($options, 'inspection_date_to'));
        if ($inspection_date_from !== '') {
            $where .= " AND $laudos.inspection_date >= '" . $this->db->escapeString($inspection_date_from) . "'";
        }
        if ($inspection_date_to !== '') {
            $where .= " AND $laudos.inspection_date <= '" . $this->db->escapeString($inspection_date_to) . "'";
        }

        $order_by = trim((string) get_array_value($options, 'order_by'));
        $order_dir = strtoupper(trim((string) get_array_value($options, 'order_dir')));
        $order_map = array(
            'number' => "$laudos.number",
            'title' => "$laudos.title",
            'type_name' => "$types.name",
            'client_name' => "$clients.company_name",
            'inspection_date' => "$laudos.inspection_date",
            'status' => "$laudos.status",
            'updated_at' => "$laudos.updated_at",
        );
        $default_order = $order_by ? (($order_dir === 'DESC' ? 'DESC' : 'ASC')) : 'DESC';
        $order_sql = 'ORDER BY ' . ($order_map[$order_by] ?? "$laudos.id") . ' ' . $default_order;
        $limit = (int) get_array_value($options, 'limit');
        $skip = max(0, (int) get_array_value($options, 'skip'));
        $limit_sql = $limit > 0 ? ' LIMIT ' . $skip . ', ' . min($limit, 100) : '';

        $sql = "SELECT SQL_CALC_FOUND_ROWS $laudos.*, $types.name AS type_name, $types.code AS type_code,
                $clients.company_name AS client_name, $projects.title AS project_name,
                CONCAT(IFNULL(technical.first_name, ''), ' ', IFNULL(technical.last_name, '')) AS technical_responsible_name,
                CONCAT(IFNULL(inspector.first_name, ''), ' ', IFNULL(inspector.last_name, '')) AS inspection_technician_name,
                CONCAT(IFNULL(contact.first_name, ''), ' ', IFNULL(contact.last_name, '')) AS contact_name
            FROM $laudos
            LEFT JOIN $types ON $types.id = $laudos.type_id AND $types.deleted = 0
            LEFT JOIN $clients ON $clients.id = $laudos.client_id AND $clients.deleted = 0
            LEFT JOIN $projects ON $projects.id = $laudos.project_id AND $projects.deleted = 0
            LEFT JOIN $users technical ON technical.id = $laudos.technical_responsible_id AND technical.deleted = 0
            LEFT JOIN $users inspector ON inspector.id = $laudos.inspection_technician_id AND inspector.deleted = 0
            LEFT JOIN $users contact ON contact.id = $laudos.contact_id AND contact.deleted = 0
            WHERE $where $order_sql $limit_sql";

        $result = $this->queryOrEmpty($sql);
        if (get_array_value($options, 'server_side')) {
            $total = $this->db->query('SELECT FOUND_ROWS() AS found_rows')->getRow();
            return array(
                'data' => $result->getResult(),
                'recordsTotal' => (int) ($total->found_rows ?? 0),
                'recordsFiltered' => (int) ($total->found_rows ?? 0),
            );
        }

        return $result;
    }

    public function get_dashboard_stats()
    {
        $table = $this->db->prefixTable($this->table);
        if (!$this->hasTable()) {
            return (object) array('total' => 0);
        }

        return $this->db->query("SELECT COUNT(*) AS total,
            SUM(status = 'draft') AS draft,
            SUM(status = 'scheduled') AS scheduled,
            SUM(status = 'inspection') AS inspection,
            SUM(status = 'awaiting_information') AS awaiting_information,
            SUM(status = 'review') AS review,
            SUM(status = 'finalized') AS finalized,
            SUM(status = 'canceled') AS canceled
            FROM $table WHERE deleted = 0")->getRow();
    }

    public function create(array $data)
    {
        $this->validate_crm_references($data);
        $data['created_at'] = $data['created_at'] ?? $this->now();
        $data['updated_at'] = $data['updated_at'] ?? $data['created_at'];
        $data['deleted'] = 0;
        $this->db->table($this->db->prefixTable($this->table))->insert($data);
        return (int) $this->db->insertID();
    }

    public function validate_crm_references(array $data): void
    {
        $client_id = (int) ($data['client_id'] ?? 0);
        $contact_id = (int) ($data['contact_id'] ?? 0);
        $project_id = (int) ($data['project_id'] ?? 0);

        if ($client_id) {
            $clients = $this->db->prefixTable('clients');
            if ($this->db->tableExists($clients) && !$this->db->table($clients)->where('id', $client_id)->where('deleted', 0)->where('is_lead', 0)->get(1)->getRow()) {
                throw new \InvalidArgumentException('Invalid RISE CRM client reference.');
            }
        }

        if ($contact_id) {
            $users = $this->db->prefixTable('users');
            $query = $this->db->table($users)->where('id', $contact_id)->where('deleted', 0);
            if ($this->db->fieldExists('user_type', $users)) {
                $query->where('user_type', 'client');
            }
            if ($client_id && $this->db->fieldExists('client_id', $users)) {
                $query->where('client_id', $client_id);
            }
            if (!$query->get(1)->getRow()) {
                throw new \InvalidArgumentException('Invalid RISE CRM contact reference.');
            }
        }

        if ($project_id) {
            $projects = $this->db->prefixTable('projects');
            $query = $this->db->table($projects)->where('id', $project_id)->where('deleted', 0);
            if ($client_id && $this->db->fieldExists('client_id', $projects)) {
                $query->where('client_id', $client_id);
            }
            if (!$query->get(1)->getRow()) {
                throw new \InvalidArgumentException('Invalid RISE CRM project reference.');
            }
        }
    }

    public function update_domain(int $id, array $data): bool
    {
        if (!$id || !$this->hasTable()) {
            return false;
        }

        $data['updated_at'] = $this->now();
        return $this->db->table($this->db->prefixTable($this->table))
            ->where('id', $id)->where('deleted', 0)->update($data);
    }

    public function soft_delete(int $id, int $user_id): bool
    {
        return $this->update_domain($id, array('deleted' => 1, 'updated_by' => $user_id));
    }

    public function change_status(int $id, string $status, int $user_id): bool
    {
        return $this->update_domain($id, array('status' => $status, 'updated_by' => $user_id));
    }
}
