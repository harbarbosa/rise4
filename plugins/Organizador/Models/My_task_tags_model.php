<?php

namespace Organizador\Models;

use App\Models\Crud_model;

class My_task_tags_model extends Crud_model
{
    protected $table = 'my_task_tags';
    protected $primaryKey = 'id';
    protected $returnType = 'object';

    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function get_details($options = array())
    {
        $table = $this->db->prefixTable('my_task_tags');
        $where = "WHERE $table.deleted=0";

        $id = (int) get_array_value($options, 'id');
        if ($id) {
            $where .= " AND $table.id=$id";
        }

        return $this->db->query("SELECT * FROM $table $where ORDER BY $table.sort ASC, $table.title ASC");
    }

    public function get_suggestions()
    {
        $suggestions = array();
        foreach ($this->get_details()->getResult() as $row) {
            $suggestions[] = array(
                'id' => $row->id,
                'text' => $row->title,
            );
        }
        return $suggestions;
    }

    public function get_dropdown()
    {
        $dropdown = array();
        foreach ($this->get_details()->getResult() as $row) {
            $dropdown[$row->id] = $row->title;
        }
        return $dropdown;
    }

    public function get_badges_html($labels = "")
    {
        $label_ids = array_filter(array_map('intval', explode(',', (string) $labels)));
        if (!$label_ids) {
            return '';
        }

        $tags = $this->get_details()->getResult();
        $tags_by_id = array();
        foreach ($tags as $tag) {
            $tags_by_id[(int) $tag->id] = $tag;
        }

        $html = '';
        foreach ($label_ids as $id) {
            if (!isset($tags_by_id[$id])) {
                continue;
            }

            $tag = $tags_by_id[$id];
            $color = $tag->color ?: '#6c757d';
            $html .= '<span class="badge rounded-pill me-1 mb-1" style="background:' . esc($color) . ';">' . esc($tag->title) . '</span>';
        }

        return $html;
    }

    public function is_tag_used($id)
    {
        $id = (int) $id;
        if (!$id) {
            return false;
        }

        $table = $this->db->prefixTable('my_tasks');
        $sql = "SELECT id FROM $table WHERE deleted=0 AND FIND_IN_SET('$id', labels) LIMIT 1";
        return (bool) $this->db->query($sql)->getRow();
    }
}
