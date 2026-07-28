<?php

namespace LaudosTecnicos\Models;

use App\Models\Crud_model;

class Laudos_settings_model extends Crud_model
{
    protected $table = null;

    public function __construct()
    {
        $this->table = 'laudo_settings';
        parent::__construct($this->table);
    }

    public function get_settings($company_id = null)
    {
        $table = $this->db->prefixTable($this->table);
        
        if (!$company_id) {
            $company_id = get_company_id();
        }

        $sql = "SELECT * FROM $table WHERE company_id=$company_id LIMIT 1";
        $result = $this->db->query($sql);
        
        if ($result->getRow()) {
            return $result->getRow();
        }

        // Retornar configurações padrão se não existir
        return (object) array(
            'module_name' => 'Laudos Técnicos',
            'laudo_prefix' => 'LAU',
            'number_format' => '{PREFIX}-{YEAR}{MONTH}{SEQUENCE}',
            'next_number' => 1,
            'primary_color' => '#3788d8',
            'timezone' => 'America/Sao_Paulo',
            'language' => 'pt-BR',
            'date_format' => 'd/m/Y',
            'module_active' => 1,
            'enable_detailed_logs' => 0,
            'default_validity_days' => 365,
            'require_inspection' => 1,
            'require_approval' => 1,
            'auto_notify_client' => 1
        );
    }

    public function save_settings($data, $company_id = null)
    {
        if (!$company_id) {
            $company_id = get_company_id();
        }

        $table = $this->db->prefixTable($this->table);
        
        // Verificar se já existe
        $sql = "SELECT id FROM $table WHERE company_id=$company_id";
        $exists = $this->db->query($sql)->getRow();

        $data['updated_at'] = get_my_local_time();

        if ($exists) {
            $this->db->where('company_id', $company_id);
            return $this->db->update($table, $data);
        } else {
            $data['company_id'] = $company_id;
            $data['created_at'] = get_my_local_time();
            return $this->db->insert($table, $data);
        }
    }
}