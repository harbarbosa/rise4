<?php

namespace Engenharia\Services;

class LaudoNumberingService
{
    private $db;

    public function __construct($db = null)
    {
        $this->db = $db ?: db_connect('default');
    }

    public function reserve(int $type_id, string $prefix, ?int $year = null): array
    {
        $year = $year ?: (int) date('Y');
        $table = $this->db->prefixTable('eng_number_sequences');
        $this->db->transStart();

        $row = $this->db->query("SELECT * FROM $table WHERE type_id = ? AND sequence_year = ? FOR UPDATE", array($type_id, $year))->getRow();
        if ($row) {
            $sequence = (int) $row->next_number;
            $this->db->table($table)->where('id', $row->id)->update(array('next_number' => $sequence + 1, 'updated_at' => $this->now()));
        } else {
            $sequence = 1;
            $this->db->table($table)->insert(array(
                'type_id' => $type_id,
                'sequence_year' => $year,
                'next_number' => 2,
                'created_at' => $this->now(),
                'updated_at' => $this->now(),
            ));
        }

        $this->db->transComplete();
        if (!$this->db->transStatus()) {
            return array('number' => '', 'sequence' => 0, 'year' => $year);
        }

        $prefix = trim($prefix) ?: 'ENG-';
        return array(
            'number' => $prefix . $year . '-' . str_pad((string) $sequence, 5, '0', STR_PAD_LEFT),
            'sequence' => $sequence,
            'year' => $year,
        );
    }

    private function now(): string
    {
        return function_exists('get_current_utc_time') ? get_current_utc_time() : gmdate('Y-m-d H:i:s');
    }
}
