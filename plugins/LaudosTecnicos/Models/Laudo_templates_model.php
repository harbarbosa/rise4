<?php

namespace LaudosTecnicos\Models;

use App\Models\Crud_model;

class Laudo_templates_model extends Crud_model
{
    protected $table = null;

    public function __construct()
    {
        $this->table = 'laudo_templates';
        parent::__construct($this->table);
    }

    public function get_details($options = array())
    {
        $table = $this->db->prefixTable($this->table);
        $types_table = $this->db->prefixTable('laudo_types');
        $categories_table = $this->db->prefixTable('laudo_categories');
        $users_table = $this->db->prefixTable('users');
        
        $where = "";
        $join = "LEFT JOIN $types_table ON $types_table.id = $table.laudo_type_id ";
        $join .= "LEFT JOIN $categories_table ON $categories_table.id = $table.category_id ";
        $join .= "LEFT JOIN $users_table AS creator ON creator.id = $table.created_by ";
        $join .= "LEFT JOIN $users_table AS editor ON editor.id = $table.updated_by ";

        $id = $this->_get_clean_value($options, "id");
        if ($id) {
            $where .= " AND $table.id=$id";
        }

        $laudo_type_id = $this->_get_clean_value($options, "laudo_type_id");
        if ($laudo_type_id) {
            $where .= " AND $table.laudo_type_id=$laudo_type_id";
        }

        $status = $this->_get_clean_value($options, "status");
        if ($status) {
            $where .= " AND $table.status='$status'";
        }

        $search = $this->_get_clean_value($options, "search");
        if ($search) {
            $where .= " AND ($table.name LIKE '%$search%' OR $table.code LIKE '%$search%')";
        }

        $sql = "SELECT $table.*, 
            $types_table.name as type_name,
            $categories_table.name as category_name,
            creator.first_name as created_by_name,
            editor.first_name as updated_by_name
        FROM $table 
        $join
        WHERE $table.deleted=0 $where
        ORDER BY $table.name ASC";

        return $this->db->query($sql);
    }

    public function get_one($id = 0)
    {
        $table = $this->db->prefixTable($this->table);
        $sql = "SELECT * FROM $table WHERE id=$id AND deleted=0";
        return $this->db->query($sql)->getRow();
    }

    public function get_by_code($code, $version = null)
    {
        $table = $this->db->prefixTable($this->table);
        $sql = "SELECT * FROM $table WHERE code='$code'";
        if ($version) {
            $sql .= " AND version=$version";
        }
        $sql .= " AND deleted=0 ORDER BY version DESC LIMIT 1";
        return $this->db->query($sql)->getRow();
    }

    public function get_for_type($laudo_type_id)
    {
        $table = $this->db->prefixTable($this->table);
        $sql = "SELECT * FROM $table 
            WHERE laudo_type_id=$laudo_type_id AND status='published' AND deleted=0 
            ORDER BY is_default DESC, version DESC";
        return $this->db->query($sql)->getResult();
    }

    public function get_default_for_type($laudo_type_id)
    {
        $table = $this->db->prefixTable($this->table);
        $sql = "SELECT * FROM $table 
            WHERE laudo_type_id=$laudo_type_id AND is_default=1 AND status='published' AND deleted=0 
            ORDER BY version DESC LIMIT 1";
        return $this->db->query($sql)->getRow();
    }

    public function get_sections($template_id)
    {
        $table = $this->db->prefixTable('laudo_template_sections');
        $sql = "SELECT * FROM $table WHERE template_id=$template_id AND deleted=0 ORDER BY sort_order ASC";
        return $this->db->query($sql)->getResult();
    }

    public function get_section($section_id)
    {
        $table = $this->db->prefixTable('laudo_template_sections');
        $sql = "SELECT * FROM $table WHERE id=$section_id AND deleted=0";
        return $this->db->query($sql)->getRow();
    }

    public function get_section_fields($section_id)
    {
        $table = $this->db->prefixTable('laudo_template_fields');
        $sql = "SELECT * FROM $table WHERE section_id=$section_id AND deleted=0 ORDER BY sort_order ASC";
        return $this->db->query($sql)->getResult();
    }

    public function get_fields($template_id)
    {
        $sections = $this->get_sections($template_id);
        $fields = array();
        
        foreach ($sections as $section) {
            $section->fields = $this->get_section_fields($section->id);
            $fields = array_merge($fields, $section->fields);
        }
        
        return $fields;
    }

    public function get_rules($template_id)
    {
        $table = $this->db->prefixTable('laudo_template_rules');
        $sql = "SELECT * FROM $table WHERE template_id=$template_id AND active=1 AND deleted=0 ORDER BY sort_order ASC";
        return $this->db->query($sql)->getResult();
    }

    public function save($row): bool
    {
        $id = 0;
        if (is_object($row) && isset($row->id)) {
            $id = (int)$row->id;
        } elseif (is_array($row) && isset($row["id"])) {
            $id = (int)$row["id"];
        }
        
        $data = is_object($row) ? (array) $row : $row;
        if ($id) {
            $data['updated_at'] = get_my_local_time();
        } else {
            $data['created_at'] = get_my_local_time();
        }
        return parent::ci_save($data, $id) ? true : false;
    }

    public function save_section($data, $id = 0)
    {
        $table = $this->db->prefixTable('laudo_template_sections');
        $id = 0;
        if (is_object($row) && isset($row->id)) {
            $id = (int)$row->id;
        } elseif (is_array($row) && isset($row["id"])) {
            $id = (int)$row["id"];
        }
        
        $data = is_object($row) ? (array) $row : $row;
        
        if ($id) {
            $data['updated_at'] = get_my_local_time();
            $this->db->where('id', $id);
            $this->db->update($table, $data);
            return $id;
        } else {
            $data['created_at'] = get_my_local_time();
            $this->db->insert($table, $data);
            return $this->db->insertID();
        }
    }

    public function save_field($data, $id = 0)
    {
        $table = $this->db->prefixTable('laudo_template_fields');
        $id = 0;
        if (is_object($row) && isset($row->id)) {
            $id = (int)$row->id;
        } elseif (is_array($row) && isset($row["id"])) {
            $id = (int)$row["id"];
        }
        
        $data = is_object($row) ? (array) $row : $row;
        
        if ($id) {
            $data['updated_at'] = get_my_local_time();
            $this->db->where('id', $id);
            $this->db->update($table, $data);
            return $id;
        } else {
            $data['created_at'] = get_my_local_time();
            $this->db->insert($table, $data);
            return $this->db->insertID();
        }
    }

    public function save_rule($data, $id = 0)
    {
        $table = $this->db->prefixTable('laudo_template_rules');
        $id = 0;
        if (is_object($row) && isset($row->id)) {
            $id = (int)$row->id;
        } elseif (is_array($row) && isset($row["id"])) {
            $id = (int)$row["id"];
        }
        
        $data = is_object($row) ? (array) $row : $row;
        
        if ($id) {
            $this->db->where('id', $id);
            $this->db->update($table, $data);
            return $id;
        } else {
            $data['created_at'] = get_my_local_time();
            $this->db->insert($table, $data);
            return $this->db->insertID();
        }
    }

    public function delete_section($id)
    {
        $table = $this->db->prefixTable('laudo_template_sections');
        return $this->db->query("UPDATE $table SET deleted=1 WHERE id=$id");
    }

    public function delete_field($id)
    {
        $table = $this->db->prefixTable('laudo_template_fields');
        return $this->db->query("UPDATE $table SET deleted=1 WHERE id=$id");
    }

    public function delete_rule($id)
    {
        $table = $this->db->prefixTable('laudo_template_rules');
        return $this->db->query("UPDATE $table SET deleted=1 WHERE id=$id");
    }

    public function clone_template($id, $new_name = null)
    {
        $template = $this->get_one($id);
        if (!$template) return false;

        // Criar novo template
        $new_code = $template->code . '_CLONE_' . time();
        $new_name = $new_name ?: $template->name . ' (Cópia)';
        
        $data = array(
            'name' => $new_name,
            'code' => $new_code,
            'description' => $template->description,
            'laudo_type_id' => $template->laudo_type_id,
            'category_id' => $template->category_id,
            'version' => 1,
            'status' => 'draft',
            'is_default' => 0,
            'is_published' => 0,
            'created_by' => $template->created_by
        );
        
        $new_id = $this->save($data, 0);
        
        // Copiar seções
        $sections = $this->get_sections($id);
        foreach ($sections as $section) {
            $section_data = array(
                'template_id' => $new_id,
                'name' => $section->name,
                'code' => $section->code,
                'description' => $section->description,
                'section_type' => $section->section_type,
                'sort_order' => $section->sort_order,
                'page_break' => $section->page_break,
                'show_numbering' => $section->show_numbering,
                'visible_web' => $section->visible_web,
                'visible_mobile' => $section->visible_mobile,
                'visible_pdf' => $section->visible_pdf,
                'is_required' => $section->is_required
            );
            
            $new_section_id = $this->save_section($section_data, 0);
            
            // Copiar campos
            $fields = $this->get_section_fields($section->id);
            foreach ($fields as $field) {
                $field_data = array(
                    'section_id' => $new_section_id,
                    'field_name' => $field->field_name,
                    'field_key' => $field->field_key,
                    'field_type' => $field->field_type,
                    'label' => $field->label,
                    'description' => $field->description,
                    'placeholder' => $field->placeholder,
                    'default_value' => $field->default_value,
                    'is_required' => $field->is_required,
                    'sort_order' => $field->sort_order,
                    'width' => $field->width,
                    'validation_rules' => $field->validation_rules,
                    'mask' => $field->mask,
                    'help_text' => $field->help_text,
                    'visible_web' => $field->visible_web,
                    'visible_mobile' => $field->visible_mobile,
                    'visible_pdf' => $field->visible_pdf,
                    'read_only' => $field->read_only,
                    'options' => $field->options
                );
                $this->save_field($field_data, 0);
            }
        }
        
        // Copiar regras
        $rules = $this->get_rules($id);
        foreach ($rules as $rule) {
            $rule_data = array(
                'template_id' => $new_id,
                'field_id' => null, // Novos IDs
                'section_id' => null,
                'rule_type' => $rule->rule_type,
                'condition_field' => $rule->condition_field,
                'condition_operator' => $rule->condition_operator,
                'condition_value' => $rule->condition_value,
                'action' => $rule->action,
                'action_target' => $rule->action_target,
                'action_value' => $rule->action_value,
                'sort_order' => $rule->sort_order,
                'active' => $rule->active
            );
            $this->save_rule($rule_data, 0);
        }
        
        return $new_id;
    }

    public function create_new_version($id)
    {
        $template = $this->get_one($id);
        if (!$template) return false;
        
        $new_version = $template->version + 1;
        
        $data = array(
            'name' => $template->name,
            'code' => $template->code,
            'description' => $template->description,
            'laudo_type_id' => $template->laudo_type_id,
            'category_id' => $template->category_id,
            'version' => $new_version,
            'status' => 'draft',
            'is_default' => 0,
            'is_published' => 0,
            'created_by' => $template->created_by
        );
        
        $new_id = $this->save($data, 0);
        
        // Copiar seções e campos (sem regras para nova versão)
        $sections = $this->get_sections($id);
        foreach ($sections as $section) {
            $section_data = array(
                'template_id' => $new_id,
                'name' => $section->name,
                'code' => $section->code,
                'description' => $section->description,
                'section_type' => $section->section_type,
                'sort_order' => $section->sort_order,
                'page_break' => $section->page_break,
                'show_numbering' => $section->show_numbering,
                'visible_web' => $section->visible_web,
                'visible_mobile' => $section->visible_mobile,
                'visible_pdf' => $section->visible_pdf,
                'is_required' => $section->is_required
            );
            
            $new_section_id = $this->save_section($section_data, 0);
            
            $fields = $this->get_section_fields($section->id);
            foreach ($fields as $field) {
                $field_data = array(
                    'section_id' => $new_section_id,
                    'field_name' => $field->field_name,
                    'field_key' => $field->field_key,
                    'field_type' => $field->field_type,
                    'label' => $field->label,
                    'description' => $field->description,
                    'placeholder' => $field->placeholder,
                    'default_value' => $field->default_value,
                    'is_required' => $field->is_required,
                    'sort_order' => $field->sort_order,
                    'width' => $field->width,
                    'validation_rules' => $field->validation_rules,
                    'mask' => $field->mask,
                    'help_text' => $field->help_text,
                    'visible_web' => $field->visible_web,
                    'visible_mobile' => $field->visible_mobile,
                    'visible_pdf' => $field->visible_pdf,
                    'read_only' => $field->read_only,
                    'options' => $field->options
                );
                $this->save_field($field_data, 0);
            }
        }
        
        return $new_id;
    }

    public function publish($id)
    {
        $data = array(
            'status' => 'published',
            'is_published' => 1,
            'published_at' => get_my_local_time()
        );
        
        // Se for marcado como padrão, desmarcar outros
        $template = $this->get_one($id);
        if ($template && $template->is_default) {
            $this->db->query("UPDATE {$this->table} SET is_default=0 WHERE laudo_type_id={$template->laudo_type_id} AND id != $id");
        }
        
        return $this->save($data, $id);
    }

    public function unpublish($id)
    {
        $data = array(
            'status' => 'draft',
            'is_published' => 0,
            'published_at' => null
        );
        return $this->save($data, $id);
    }

    public function set_default($id)
    {
        $template = $this->get_one($id);
        if (!$template) return false;
        
        // Desmarcar todos
        $this->db->query("UPDATE {$this->table} SET is_default=0 WHERE laudo_type_id={$template->laudo_type_id}");
        
        // Marcar como padrão
        return $this->save(array('is_default' => 1), $id);
    }
}