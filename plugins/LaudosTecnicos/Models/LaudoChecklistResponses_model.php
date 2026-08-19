<?php

namespace LaudosTecnicos\Models;

class LaudoChecklistResponses_model extends LaudosTecnicosBaseModel
{
    protected $table = 'laudo_checklist_responses';

    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function save_bulk(array $responses, array $base = array())
    {
        if (!$this->hasTable() || !$responses) {
            return false;
        }

        $saved = 0;
        foreach ($responses as $response) {
            if (!is_array($response)) {
                continue;
            }

            $payload = array_merge($base, $this->normalize($response));
            $payload['created_at'] = get_current_utc_time();
            $payload['updated_at'] = get_current_utc_time();
            if ($this->db->table($this->db->prefixTable($this->table))->insert($payload)) {
                $saved++;
            }
        }

        return $saved;
    }

    public function get_progress(array $options = array())
    {
        if (!$this->hasTable()) {
            return (object) array(
                'total' => 0,
                'answered' => 0,
                'pending' => 0,
                'non_conforming' => 0,
                'critical' => 0,
                'groups' => array(),
            );
        }

        $builder = $this->db->table($this->db->prefixTable($this->table))
            ->where('deleted', 0);

        $checklist_id = (int) get_array_value($options, 'checklist_id');
        if ($checklist_id) {
            $builder->where('checklist_id', $checklist_id);
        }

        $laudo_id = (int) get_array_value($options, 'laudo_id');
        if ($laudo_id) {
            $builder->where('laudo_id', $laudo_id);
        }

        $rows = $builder->get()->getResult();

        $summary = array(
            'total' => 0,
            'answered' => 0,
            'pending' => 0,
            'non_conforming' => 0,
            'critical' => 0,
            'groups' => array(),
        );

        foreach ($rows as $row) {
            $summary['total']++;
            $group_key = (string) ($row->group_key ?? 'default');
            if (!isset($summary['groups'][$group_key])) {
                $summary['groups'][$group_key] = array(
                    'total' => 0,
                    'answered' => 0,
                    'pending' => 0,
                    'non_conforming' => 0,
                    'critical' => 0,
                );
            }

            $summary['groups'][$group_key]['total']++;

            $response = strtolower(trim((string) ($row->response ?? '')));
            $answered = $response !== '';
            if ($answered) {
                $summary['answered']++;
                $summary['groups'][$group_key]['answered']++;
            } else {
                $summary['pending']++;
                $summary['groups'][$group_key]['pending']++;
            }

            if (in_array($response, array('nao_conforme', 'nao conforme', 'não conforme', 'non_conforming', 'critical', 'critico', 'crítico'), true)) {
                $summary['non_conforming']++;
                $summary['groups'][$group_key]['non_conforming']++;
            }

            if (in_array($response, array('critical', 'critico', 'crítico'), true)) {
                $summary['critical']++;
                $summary['groups'][$group_key]['critical']++;
            }
        }

        $summary['groups'] = array_values(array_map(function ($group_key, $group) {
            $group['group_key'] = $group_key;
            return $group;
        }, array_keys($summary['groups']), $summary['groups']));

        return (object) $summary;
    }

    public function get_by_laudo(int $laudo_id, int $checklist_id = 0)
    {
        if (!$this->hasTable() || !$laudo_id) {
            return array();
        }

        $builder = $this->db->table($this->db->prefixTable($this->table))
            ->where('deleted', 0)
            ->where('laudo_id', $laudo_id);

        if ($checklist_id) {
            $builder->where('checklist_id', $checklist_id);
        }

        return $builder->orderBy('answered_at', 'DESC')->get()->getResult();
    }

    private function normalize(array $response)
    {
        $photos = get_array_value($response, 'photos');
        $measurements = get_array_value($response, 'measurements');

        return array(
            'laudo_id' => (int) get_array_value($response, 'laudo_id'),
            'inspection_id' => (int) get_array_value($response, 'inspection_id') ?: null,
            'checklist_id' => (int) get_array_value($response, 'checklist_id'),
            'group_key' => trim((string) get_array_value($response, 'group_key')),
            'item_id' => (int) get_array_value($response, 'item_id') ?: null,
            'response' => trim((string) get_array_value($response, 'response')),
            'observation' => trim((string) get_array_value($response, 'observation')),
            'user_id' => (int) get_array_value($response, 'user_id') ?: null,
            'source' => trim((string) get_array_value($response, 'source')) ?: 'web',
            'photos_json' => is_array($photos) ? laudostecnicos_safe_json($photos) : trim((string) get_array_value($response, 'photos_json')),
            'measurements_json' => is_array($measurements) ? laudostecnicos_safe_json($measurements) : trim((string) get_array_value($response, 'measurements_json')),
            'nonconformity_id' => (int) get_array_value($response, 'nonconformity_id') ?: null,
            'answered_at' => get_current_utc_time(),
            'ip_address' => trim((string) get_array_value($response, 'ip_address')),
            'created_by' => (int) get_array_value($response, 'created_by') ?: null,
            'updated_by' => (int) get_array_value($response, 'updated_by') ?: null,
            'deleted' => 0,
        );
    }
}
