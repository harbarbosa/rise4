<?php

namespace LaudosTecnicos\Models;

class LaudoMeasurements_model extends LaudosTecnicosBaseModel
{
    protected $table = 'laudo_measurements';

    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function get_details(array $options = array())
    {
        if (!$this->hasTable()) {
            return $this->emptyResult();
        }

        $table = $this->db->prefixTable($this->table);
        $types = $this->db->prefixTable('laudo_measurement_types');
        $equipments = $this->db->prefixTable('laudo_equipments');
        $where = " WHERE $table.deleted = 0";

        $laudo_id = (int) get_array_value($options, 'laudo_id');
        if ($laudo_id) {
            $where .= " AND $table.laudo_id = $laudo_id";
        }

        return $this->queryOrEmpty("SELECT $table.*,
                mt.name AS type_name,
                mt.unit AS type_unit,
                e.name AS equipment_name
            FROM $table
            LEFT JOIN $types mt ON mt.id = $table.measurement_type_id AND mt.deleted = 0
            LEFT JOIN $equipments e ON e.id = $table.equipment_id AND e.deleted = 0
            $where
            ORDER BY $table.measured_at DESC, $table.id DESC");
    }

    public function save_from_post(array $data, ?int $id = null)
    {
        $measurement_type = null;
        if (!empty($data['measurement_type_id'])) {
            $measurement_type = model(LaudoMeasurementTypes_model::class)->get_one((int) $data['measurement_type_id']);
        }

        $data['result'] = $data['result'] ?? $this->classify_value($data, $measurement_type);
        $data['updated_at'] = get_current_utc_time();

        if ($id) {
            return $this->db->table($this->db->prefixTable($this->table))->where('id', $id)->update($data);
        }

        $data['created_at'] = get_current_utc_time();
        return $this->db->table($this->db->prefixTable($this->table))->insert($data);
    }

    public function classify_value(array $data, $measurement_type = null): string
    {
        $type = is_object($measurement_type) ? $measurement_type : null;
        if (!$type || empty($type->auto_classification)) {
            return trim((string) get_array_value($data, 'result')) ?: 'informado';
        }

        $raw_value = get_array_value($data, 'value');
        if (!is_numeric($raw_value)) {
            return 'atencao';
        }

        $value = (float) $raw_value;
        $min = $type->min_value !== null && $type->min_value !== '' ? (float) $type->min_value : null;
        $max = $type->max_value !== null && $type->max_value !== '' ? (float) $type->max_value : null;
        $reference = $type->reference_value !== null && $type->reference_value !== '' ? (float) $type->reference_value : null;
        $tolerance = $type->tolerance_value !== null && $type->tolerance_value !== '' ? abs((float) $type->tolerance_value) : null;

        if ($reference !== null && $tolerance !== null) {
            $distance = abs($value - $reference);
            if ($distance <= $tolerance) {
                return 'conforme';
            }
            if ($distance <= ($tolerance * 2)) {
                return 'atencao';
            }
            return 'nao_conforme';
        }

        if ($min !== null && $value < $min) {
            return $tolerance !== null && ($min - $value) <= $tolerance ? 'atencao' : 'nao_conforme';
        }

        if ($max !== null && $value > $max) {
            return $tolerance !== null && ($value - $max) <= $tolerance ? 'atencao' : 'nao_conforme';
        }

        return 'conforme';
    }
}
