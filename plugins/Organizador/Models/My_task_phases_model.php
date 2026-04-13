<?php

namespace Organizador\Models;

use App\Models\Crud_model;

class My_task_phases_model extends Crud_model
{
    protected $table = 'my_task_phases';
    protected $primaryKey = 'id';
    protected $returnType = 'object';

    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function get_details($options = array())
    {
        $table = $this->db->prefixTable('my_task_phases');
        if (!$this->db->tableExists($table)) {
            return false;
        }
        $where = "WHERE $table.deleted=0";

        $id = (int) get_array_value($options, 'id');
        if ($id) {
            $where .= " AND $table.id=$id";
        }

        $key_name = clean_data(get_array_value($options, 'key_name'));
        if ($key_name) {
            $where .= " AND $table.key_name=" . $this->db->escape($key_name);
        }

        return $this->db->query("SELECT * FROM $table $where ORDER BY $table.sort ASC, $table.id ASC");
    }

    public function get_dropdown()
    {
        $dropdown = $this->get_default_dropdown();

        if (!$this->db->tableExists($this->db->prefixTable('my_task_phases'))) {
            return $dropdown;
        }

        $query = $this->get_details();
        if ($query) {
            foreach ($query->getResult() as $row) {
                $dropdown[$row->key_name] = $row->title;
            }
        }

        return $dropdown;
    }

    public function get_all_phases()
    {
        $phases = array();
        $default_order = 0;
        foreach ($this->get_default_phase_options() as $key_name => $phase) {
            $default_phase = $this->get_phase_by_key($key_name);
            if (!isset($default_phase->sort)) {
                $default_phase->sort = $default_order;
            }
            $phases[$key_name] = $default_phase;
            $default_order++;
        }

        if (!$this->db->tableExists($this->db->prefixTable('my_task_phases'))) {
            uasort($phases, array($this, 'sort_phases_by_sort'));
            return array_values($phases);
        }

        $query = $this->get_details();
        if ($query) {
            foreach ($query->getResult() as $row) {
                $phases[$row->key_name] = $row;
            }
        }

        uasort($phases, array($this, 'sort_phases_by_sort'));
        return array_values($phases);
    }

    public function get_phase_options()
    {
        $options = $this->get_default_phase_options();

        if (!$this->db->tableExists($this->db->prefixTable('my_task_phases'))) {
            return $options;
        }

        $query = $this->get_details();
        if ($query) {
            foreach ($query->getResult() as $row) {
                $options[$row->key_name] = array(
                    'id' => $row->key_name,
                    'text' => $row->title,
                );
            }
        }

        return array_values($options);
    }

    public function get_phase_by_key($key_name)
    {
        if ($this->db->tableExists($this->db->prefixTable('my_task_phases'))) {
            $query = $this->get_details(array('key_name' => $key_name));
            if ($query) {
                $phase = $query->getRow();
                if ($phase) {
                    return $phase;
                }
            }
        }

        $fallback = (object) array(
            'key_name' => $key_name,
            'title' => app_lang('organizador_status_' . $key_name),
            'color' => $this->get_default_color($key_name),
            'sort' => $this->get_default_sort($key_name),
        );

        return $fallback;
    }

    public function get_color_by_key($key_name)
    {
        $phase = $this->get_phase_by_key($key_name);
        return $phase->color ?: $this->get_default_color($key_name);
    }

    public function get_default_key($preferred = 'pending')
    {
        if ($this->db->tableExists($this->db->prefixTable('my_task_phases'))) {
            $query = $this->get_details(array('key_name' => $preferred));
            if ($query) {
                $phase = $query->getRow();
                if ($phase) {
                    return $phase->key_name;
                }
            }
        }

        return $preferred;
    }

    public function is_phase_used($key_name)
    {
        $key_name = clean_data($key_name);
        if (!$key_name) {
            return false;
        }

        $table = $this->db->prefixTable('my_tasks');
        $sql = "SELECT id FROM $table WHERE deleted=0 AND status=" . $this->db->escape($key_name) . " LIMIT 1";
        return (bool) $this->db->query($sql)->getRow();
    }

    private function get_default_color($key_name)
    {
        $map = array(
            'pending' => '#6c757d',
            'in_progress' => '#0d6efd',
            'done' => '#198754',
            'canceled' => '#dc3545',
        );

        return get_array_value($map, $key_name) ?: '#6c757d';
    }

    private function get_default_dropdown()
    {
        return array(
            'pending' => app_lang('organizador_status_pending'),
            'in_progress' => app_lang('organizador_status_in_progress'),
            'done' => app_lang('organizador_status_done'),
            'canceled' => app_lang('organizador_status_canceled'),
        );
    }

    private function get_default_phase_options()
    {
        return array(
            'pending' => array('id' => 'pending', 'text' => app_lang('organizador_status_pending')),
            'in_progress' => array('id' => 'in_progress', 'text' => app_lang('organizador_status_in_progress')),
            'done' => array('id' => 'done', 'text' => app_lang('organizador_status_done')),
            'canceled' => array('id' => 'canceled', 'text' => app_lang('organizador_status_canceled')),
        );
    }

    private function get_default_sort($key_name)
    {
        $map = array(
            'pending' => 0,
            'in_progress' => 1,
            'done' => 4,
            'canceled' => 5,
        );

        return get_array_value($map, $key_name) ?: 9999;
    }

    private function sort_phases_by_sort($a, $b)
    {
        $a_sort = isset($a->sort) ? (int) $a->sort : 9999;
        $b_sort = isset($b->sort) ? (int) $b->sort : 9999;

        if ($a_sort === $b_sort) {
            $a_title = isset($a->title) ? $a->title : '';
            $b_title = isset($b->title) ? $b->title : '';
            return strcmp($a_title, $b_title);
        }

        return $a_sort <=> $b_sort;
    }
}
