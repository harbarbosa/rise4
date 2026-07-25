<?php

namespace LicitaIA\Models;

class Source_model extends LicitaiaBaseModel
{
    protected $table = 'licitaia_sources';

    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function get_active_sources()
    {
        if (!$this->hasTable()) {
            return array();
        }

        $table = $this->db->prefixTable($this->table);
        return $this->db->table($table)
            ->select('*')
            ->where('deleted', 0)
            ->where('active', 1)
            ->orderBy('name', 'ASC')
            ->get()->getResult();
    }

    public function get_active_pncp_sources()
    {
        if (!$this->hasTable()) {
            return array();
        }

        $sources = array();
        foreach ($this->get_active_sources() as $row) {
            $source_type = strtolower(trim((string) ($row->source_type ?? '')));
            $base_url = strtolower(trim((string) (($row->url ?? '') ?: ($row->base_url ?? ''))));

            if ($source_type === 'pncp' || strpos($base_url, 'pncp') !== false) {
                $sources[] = $row;
            }
        }

        return $sources;
    }

    public function get_details($options = array())
    {
        if (!$this->hasTable()) {
            return $this->emptyResult();
        }

        $table = $this->db->prefixTable($this->table);
        $builder = $this->db->table($table . ' s')
            ->select('s.id, s.name, s.source_type, s.url, s.city, s.state, s.search_frequency, s.base_url, s.api_endpoint, s.active, s.notes, s.created_by, s.created_at, s.updated_at, s.last_search_at, s.last_search_by, s.deleted');

        $builder->where('s.deleted', 0);

        $id = (int) get_array_value($options, 'id');
        if ($id > 0) {
            $builder->where('s.id', $id);
        }

        $source_type = trim((string) get_array_value($options, 'source_type'));
        if ($source_type !== '') {
            $builder->where('s.source_type', $source_type);
        }

        $active = get_array_value($options, 'active', '');
        if ($active !== '' && $active !== null) {
            $builder->where('s.active', (int) $active);
        }

        $search = trim((string) get_array_value($options, 'search'));
        if ($search !== '') {
            $builder->groupStart()
                ->like('s.name', $search)
                ->orLike('s.url', $search)
                ->orLike('s.base_url', $search)
                ->orLike('s.city', $search)
                ->orLike('s.state', $search)
                ->groupEnd();
        }

        $builder->orderBy('s.name', 'ASC');
        return $builder->get();
    }

    public function get_active_dropdown($include_blank = true)
    {
        $dropdown = array();
        if ($include_blank) {
            $dropdown[''] = '-';
        }

        if (!$this->hasTable()) {
            return $dropdown;
        }

        foreach ($this->get_active_sources() as $row) {
            $dropdown[$row->id] = $row->name;
        }

        return $dropdown;
    }

    public function get_source_types_dropdown()
    {
        return array(
            'pncp' => app_lang('licitaia_source_type_pncp'),
            'compras_gov' => app_lang('licitaia_source_type_compras_gov'),
            'bec_sp' => app_lang('licitaia_source_type_bec_sp'),
            'portal_municipal' => app_lang('licitaia_source_type_portal_municipal'),
            'paradigma' => app_lang('licitaia_source_type_paradigma'),
            'outro' => app_lang('licitaia_source_type_outro'),
        );
    }

    public function get_frequency_dropdown()
    {
        return array(
            'manual' => app_lang('licitaia_source_frequency_manual'),
            'hourly' => app_lang('licitaia_source_frequency_hourly'),
            'daily' => app_lang('licitaia_source_frequency_daily'),
            'weekly' => app_lang('licitaia_source_frequency_weekly'),
        );
    }

    public function set_active($id, $active)
    {
        $id = (int) $id;
        if (!$id || !$this->hasTable()) {
            return false;
        }

        $data = array(
            'active' => (int) $active ? 1 : 0,
            'updated_at' => get_my_local_time(),
        );

        return $this->ci_save($data, $id);
    }

    public function update_last_search($id, $query = '')
    {
        $id = (int) $id;
        if (!$id || !$this->hasTable()) {
            return false;
        }

        $data = array(
            'last_search_at' => get_my_local_time(),
            'last_search_by' => $this->currentUserId(),
            'last_search_query' => trim((string) $query) !== '' ? trim((string) $query) : null,
            'updated_at' => get_my_local_time(),
        );

        return $this->ci_save($data, $id);
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
