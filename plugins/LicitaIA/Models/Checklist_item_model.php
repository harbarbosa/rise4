<?php

namespace LicitaIA\Models;

class Checklist_item_model extends LicitaiaBaseModel
{
    protected $table = 'licitaia_checklist_items';

    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function get_active_items()
    {
        if (!$this->hasTable()) {
            return array();
        }

        $table = $this->db->prefixTable($this->table);
        $sql = "SELECT * FROM {$table} WHERE deleted = 0 AND active = 1 ORDER BY sort ASC, item_name ASC";

        return $this->db->query($sql)->getResult();
    }

    public function get_details($options = array())
    {
        if (!$this->hasTable()) {
            return $this->emptyResult();
        }

        $table = $this->db->prefixTable($this->table);
        $users_table = $this->db->prefixTable('users');

        $sql = "SELECT c.id, c.item_name, c.category, c.description, c.is_required, c.status, c.notes, c.active, c.sort,
                       c.created_by, c.created_at, c.updated_at, c.deleted,
                       CONCAT(TRIM(COALESCE(u.first_name, '')), ' ', TRIM(COALESCE(u.last_name, ''))) AS created_by_name
                FROM {$table} c
                LEFT JOIN {$users_table} u ON u.id = c.created_by
                WHERE c.deleted = 0";

        $category = trim((string) get_array_value($options, 'category'));
        if ($category !== '') {
            $sql .= ' AND c.category = ' . $this->db->escape($category);
        }

        $status = trim((string) get_array_value($options, 'status'));
        if ($status === 'active') {
            $sql .= ' AND c.active = 1';
        } elseif ($status === 'inactive') {
            $sql .= ' AND c.active = 0';
        }

        $search = trim((string) get_array_value($options, 'search'));
        if ($search !== '') {
            $search = $this->db->escapeLikeString($search);
            $sql .= " AND (c.item_name LIKE '%{$search}%' ESCAPE '!' OR c.category LIKE '%{$search}%' ESCAPE '!' OR c.description LIKE '%{$search}%' ESCAPE '!')";
        }

        return $this->queryOrEmpty($sql . ' ORDER BY c.sort ASC, c.item_name ASC, c.id DESC');
    }

    public function get_categories_dropdown($include_blank = true)
    {
        $dropdown = array();
        if ($include_blank) {
            $dropdown[''] = '-';
        }

        foreach ($this->get_category_list() as $category) {
            $dropdown[$category] = $category;
        }

        return $dropdown;
    }

    public function seed_default_items()
    {
        if (!$this->hasTable()) {
            return false;
        }

        $items = array(
            array('item_name' => 'Contrato social', 'category' => 'Juridica', 'description' => '', 'is_required' => 1, 'active' => 1, 'sort' => 10),
            array('item_name' => 'Cartao CNPJ', 'category' => 'Fiscal', 'description' => '', 'is_required' => 1, 'active' => 1, 'sort' => 20),
            array('item_name' => 'Certidao Federal', 'category' => 'Fiscal', 'description' => '', 'is_required' => 1, 'active' => 1, 'sort' => 30),
            array('item_name' => 'Certidao Estadual', 'category' => 'Fiscal', 'description' => '', 'is_required' => 1, 'active' => 1, 'sort' => 40),
            array('item_name' => 'Certidao Municipal', 'category' => 'Fiscal', 'description' => '', 'is_required' => 1, 'active' => 1, 'sort' => 50),
            array('item_name' => 'FGTS', 'category' => 'Trabalhista', 'description' => '', 'is_required' => 1, 'active' => 1, 'sort' => 60),
            array('item_name' => 'CNDT', 'category' => 'Trabalhista', 'description' => '', 'is_required' => 1, 'active' => 1, 'sort' => 70),
            array('item_name' => 'Balanco patrimonial', 'category' => 'Economico-financeira', 'description' => '', 'is_required' => 1, 'active' => 1, 'sort' => 80),
            array('item_name' => 'Indices financeiros', 'category' => 'Economico-financeira', 'description' => '', 'is_required' => 0, 'active' => 1, 'sort' => 90),
            array('item_name' => 'Atestado tecnico', 'category' => 'Tecnica', 'description' => '', 'is_required' => 1, 'active' => 1, 'sort' => 100),
            array('item_name' => 'CAT/CREA', 'category' => 'Tecnica', 'description' => '', 'is_required' => 0, 'active' => 1, 'sort' => 110),
            array('item_name' => 'Declaracao de cumprimento de requisitos', 'category' => 'Declaracoes', 'description' => '', 'is_required' => 1, 'active' => 1, 'sort' => 120),
            array('item_name' => 'Declaracao de inexistencia de fato impeditivo', 'category' => 'Declaracoes', 'description' => '', 'is_required' => 1, 'active' => 1, 'sort' => 130),
            array('item_name' => 'Proposta comercial', 'category' => 'Proposta Comercial', 'description' => '', 'is_required' => 1, 'active' => 1, 'sort' => 140),
            array('item_name' => 'Planilha de custos', 'category' => 'Proposta Comercial', 'description' => '', 'is_required' => 1, 'active' => 1, 'sort' => 150),
        );

        foreach ($items as $item) {
            $exists = $this->get_one_where(array('item_name' => $item['item_name'], 'deleted' => 0));
            if ($exists && !empty($exists->id)) {
                continue;
            }

            $item['created_by'] = $this->currentUserId();
            $item['created_at'] = get_my_local_time();
            $item['updated_at'] = get_my_local_time();
            $item['deleted'] = 0;
            $this->ci_save($item, 0);
        }

        return true;
    }

    private function get_category_list()
    {
        return array(
            'Juridica',
            'Fiscal',
            'Trabalhista',
            'Economico-financeira',
            'Tecnica',
            'Declaracoes',
            'Proposta Comercial',
        );
    }

    private function currentUserId()
    {
        try {
            $users_model = model('App\\Models\\Users_model');
            return (int) $users_model->login_user_id();
        } catch (\Throwable $e) {
            return 0;
        }
    }
}
