<?php

namespace LaudosTecnicos\Models;

use App\Models\Crud_model;

class Laudo_data_model extends Crud_model
{
    protected $table = null;

    public function __construct()
    {
        $this->table = 'laudo_data';
        parent::__construct($this->table);
    }

    public function get_for_laudo($laudo_id)
    {
        $table = $this->db->prefixTable($this->table);
        $sql = "SELECT * FROM $table WHERE laudo_id=$laudo_id ORDER BY id ASC";
        return $this->db->query($sql)->getResult();
    }

    public function get_section_data($laudo_id, $section_id)
    {
        $table = $this->db->prefixTable($this->table);
        $sql = "SELECT * FROM $table WHERE laudo_id=$laudo_id AND section_id=$section_id ORDER BY id ASC";
        return $this->db->query($sql)->getResult();
    }

    public function get_field_data($laudo_id, $field_key)
    {
        $table = $this->db->prefixTable($this->table);
        $sql = "SELECT * FROM $table WHERE laudo_id=$laudo_id AND field_key='$field_key' LIMIT 1";
        return $this->db->query($sql)->getRow();
    }

    public function save_field_value($laudo_id, $template_id, $template_version, $section_id, $field_id, $field_key, $value)
    {
        // Verificar se já existe
        $existing = $this->get_field_data($laudo_id, $field_key);
        
        $data = array(
            'laudo_id' => $laudo_id,
            'template_id' => $template_id,
            'template_version' => $template_version,
            'section_id' => $section_id,
            'field_id' => $field_id,
            'field_key' => $field_key,
            'field_value' => $value,
            'updated_at' => get_my_local_time()
        );
        
        if ($existing) {
            $this->db->where('id', $existing->id);
            $this->db->update($this->table, $data);
            return $existing->id;
        } else {
            $data['created_at'] = get_my_local_time();
            $this->db->insert($this->table, $data);
            return $this->db->insertID();
        }
    }

    public function apply_template($laudo_id, $template_id)
    {
        $templates_model = model(Laudo_templates_model::class);
        
        $template = $templates_model->get_one($template_id);
        if (!$template) return false;
        
        $sections = $templates_model->get_sections($template_id);
        
        foreach ($sections as $section) {
            $fields = $templates_model->get_section_fields($section->id);
            
            foreach ($fields as $field) {
                // Não aplicar valores padrão se o laudo já existe
                if ($field->default_value) {
                    $this->save_field_value(
                        $laudo_id,
                        $template_id,
                        $template->version,
                        $section->id,
                        $field->id,
                        $field->field_key,
                        $field->default_value
                    );
                }
            }
        }
        
        // Atualizar laudo com referência ao template
        $laudos_model = model(Laudos_model::class);
        $laudos_model->save(array(
            'template_id' => $template_id,
            'template_version' => $template->version
        ), $laudo_id);
        
        return true;
    }

    public function delete_for_laudo($laudo_id)
    {
        $table = $this->db->prefixTable($this->table);
        return $this->db->query("DELETE FROM $table WHERE laudo_id=$laudo_id");
    }

    public function to_array($laudo_id)
    {
        $data = $this->get_for_laudo($laudo_id);
        $result = array();
        
        foreach ($data as $item) {
            $result[$item->field_key] = $item->field_value;
        }
        
        return $result;
    }
}