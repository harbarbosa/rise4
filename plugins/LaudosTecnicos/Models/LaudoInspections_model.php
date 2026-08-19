<?php

namespace LaudosTecnicos\Models;

class LaudoInspections_model extends LaudosTecnicosBaseModel
{
    protected $table = 'laudo_inspections';

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
        $laudos = $this->db->prefixTable('laudos');
        $clients = $this->db->prefixTable('clients');
        $users = $this->db->prefixTable('users');
        $where = " WHERE $table.deleted = 0";

        $search = trim((string) get_array_value($options, 'search'));
        if ($search !== '') {
            $search = $this->db->escapeLikeString($search);
            $where .= " AND ($table.code LIKE '%$search%' OR $table.location_name LIKE '%$search%' OR $table.unit_name LIKE '%$search%' OR $laudos.title LIKE '%$search%' OR $clients.company_name LIKE '%$search%')";
        }

        $status = trim((string) get_array_value($options, 'status'));
        if ($status !== '') {
            $where .= " AND $table.status = '" . $this->db->escapeString($status) . "'";
        }

        $responsible_id = (int) get_array_value($options, 'responsible_id');
        if ($responsible_id) {
            $where .= " AND $table.responsible_id = $responsible_id";
        }

        $client_id = (int) get_array_value($options, 'client_id');
        if ($client_id) {
            $where .= " AND $table.client_id = $client_id";
        }

        $inspection_date = trim((string) get_array_value($options, 'inspection_date'));
        if ($inspection_date !== '') {
            $where .= " AND $table.inspection_date = '" . $this->db->escapeString($inspection_date) . "'";
        }

        return $this->queryOrEmpty("SELECT $table.*,
                $laudos.number AS laudo_number,
                $laudos.title AS laudo_title,
                $clients.company_name AS client_name,
                CONCAT(IFNULL(r.first_name, ''), ' ', IFNULL(r.last_name, '')) AS responsible_name
            FROM $table
            LEFT JOIN $laudos ON $laudos.id = $table.laudo_id AND $laudos.deleted = 0
            LEFT JOIN $clients ON $clients.id = $table.client_id AND $clients.deleted = 0
            LEFT JOIN $users r ON r.id = $table.responsible_id AND r.deleted = 0
            $where
            ORDER BY $table.inspection_date DESC, $table.start_time ASC, $table.id DESC");
    }

    public function get_calendar_events(array $options = array())
    {
        if (!$this->hasTable()) {
            return array();
        }

        $rows = $this->get_details($options)->getResult();
        $events = array();
        foreach ($rows as $row) {
            $start = trim((string) ($row->inspection_date ?? ''));
            if ($start === '') {
                continue;
            }

            $time = trim((string) ($row->start_time ?? '08:00:00'));
            $end_time = trim((string) ($row->end_time ?? ''));
            if ($end_time === '' && !empty($row->duration_minutes) && $time !== '') {
                $end_time = date('H:i:s', strtotime($time) + ((int) $row->duration_minutes * 60));
            }

            $events[] = array(
                'id' => (int) $row->id,
                'title' => trim((string) ($row->code ?: 'INSPEÇÃO')) . ' - ' . trim((string) ($row->laudo_title ?: $row->client_name ?: '')),
                'start' => $start . 'T' . ($time ?: '08:00:00'),
                'end' => $start . 'T' . ($end_time ?: '09:00:00'),
                'backgroundColor' => $this->statusColor((string) ($row->status ?? 'planned')),
                'borderColor' => $this->statusColor((string) ($row->status ?? 'planned')),
                'extendedProps' => array(
                    'inspection_id' => (int) $row->id,
                    'laudo_id' => (int) $row->laudo_id,
                    'status' => (string) ($row->status ?? ''),
                    'responsible_id' => (int) ($row->responsible_id ?? 0),
                    'client_id' => (int) ($row->client_id ?? 0),
                ),
            );
        }

        return $events;
    }

    public function get_one_with_details(int $id)
    {
        return $this->get_details(array('id' => $id))->getRow();
    }

    public function save_from_post(array $data, ?int $id = null)
    {
        if (!$this->hasTable()) {
            return false;
        }

        $payload = $this->normalize($data);
        $payload['updated_at'] = get_current_utc_time();

        if ($id) {
            return $this->db->table($this->db->prefixTable($this->table))->where('id', $id)->update($payload);
        }

        if (empty($payload['code'])) {
            $payload['code'] = $this->generate_code();
        }
        if (empty($payload['status'])) {
            $payload['status'] = 'planned';
        }
        $payload['created_at'] = get_current_utc_time();
        return $this->db->table($this->db->prefixTable($this->table))->insert($payload) ? $this->db->insertID() : false;
    }

    public function has_conflict(array $data, int $ignore_id = 0): array
    {
        if (!$this->hasTable()) {
            return array();
        }

        $inspection_date = trim((string) get_array_value($data, 'inspection_date'));
        $start_time = trim((string) get_array_value($data, 'start_time'));
        $duration_minutes = max(1, (int) get_array_value($data, 'duration_minutes'));
        $responsible_id = (int) get_array_value($data, 'responsible_id');
        $equipments = json_decode((string) get_array_value($data, 'equipments_json'), true);
        $equipments = is_array($equipments) ? array_filter(array_map('intval', $equipments)) : array();

        if ($inspection_date === '' || $start_time === '') {
            return array();
        }

        $start = strtotime($inspection_date . ' ' . $start_time);
        $end = $start + ($duration_minutes * 60);

        $rows = $this->db->table($this->db->prefixTable($this->table))
            ->where('deleted', 0)
            ->where('inspection_date', $inspection_date)
            ->whereNotIn('status', array('canceled', 'completed', 'improductive'))
            ->get()
            ->getResult();

        $conflicts = array();
        foreach ($rows as $row) {
            if ($ignore_id && (int) $row->id === $ignore_id) {
                continue;
            }

            $existing_start = strtotime((string) $row->inspection_date . ' ' . ($row->start_time ?: '08:00:00'));
            $existing_end = $existing_start + (max(1, (int) ($row->duration_minutes ?? 60)) * 60);

            $overlap = ($start < $existing_end) && ($end > $existing_start);
            if (!$overlap) {
                continue;
            }

            $same_responsible = $responsible_id && (int) ($row->responsible_id ?? 0) === $responsible_id;
            $same_equipment = false;
            if ($equipments) {
                $row_equipments = json_decode((string) ($row->equipments_json ?? '[]'), true);
                $row_equipments = is_array($row_equipments) ? array_filter(array_map('intval', $row_equipments)) : array();
                $same_equipment = (bool) array_intersect($equipments, $row_equipments);
            }

            if ($same_responsible || $same_equipment) {
                $conflicts[] = array(
                    'inspection_id' => (int) $row->id,
                    'inspection_code' => (string) ($row->code ?? ''),
                    'status' => (string) ($row->status ?? ''),
                    'reason' => $same_responsible ? 'technical_conflict' : 'equipment_unavailable',
                );
            }
        }

        return $conflicts;
    }

    public function start(int $id)
    {
        return $this->updateStatus($id, 'iniciado', array('started_at' => get_current_utc_time()));
    }

    public function pause(int $id)
    {
        return $this->updateStatus($id, 'pausada', array('paused_at' => get_current_utc_time()));
    }

    public function finish(int $id)
    {
        return $this->updateStatus($id, 'concluida', array('completed_at' => get_current_utc_time(), 'progress_percent' => 100));
    }

    public function mark_improductive(int $id, array $data = array())
    {
        return $this->updateStatus($id, 'improdutiva', array(
            'is_improductive' => 1,
            'improductive_reason' => trim((string) get_array_value($data, 'improductive_reason')),
            'improductive_evidence_json' => laudostecnicos_safe_json(get_array_value($data, 'improductive_evidence', array())),
            'client_contact_name' => trim((string) get_array_value($data, 'client_contact_name')),
            'suggested_new_date' => trim((string) get_array_value($data, 'suggested_new_date')),
            'costs_json' => laudostecnicos_safe_json(get_array_value($data, 'costs', array())),
            'comments' => trim((string) get_array_value($data, 'comments')),
        ));
    }

    public function validate_completion(int $id): array
    {
        $inspection = $this->get_one($id);
        if (!$inspection || !$inspection->id) {
            return array('inspection_not_found');
        }

        $issues = array();
        $photos_count = $this->db->table($this->db->prefixTable('laudo_inspection_photos'))
            ->where('inspection_id', $id)
            ->where('deleted', 0)
            ->countAllResults();
        $checkins_count = $this->db->table($this->db->prefixTable('laudo_inspection_checkins'))
            ->where('inspection_id', $id)
            ->where('deleted', 0)
            ->countAllResults();
        $responses_count = $this->db->table($this->db->prefixTable('laudo_checklist_responses'))
            ->where('inspection_id', $id)
            ->where('deleted', 0)
            ->countAllResults();
        $signature_missing = empty($inspection->client_signature_file);

        if ($responses_count <= 0) {
            $issues[] = 'checklists_pending';
        }
        if ($photos_count <= 0) {
            $issues[] = 'photos_missing';
        }
        if ($checkins_count <= 0) {
            $issues[] = 'checkin_missing';
        }
        if ($signature_missing) {
            $issues[] = 'signature_missing';
        }

        return $issues;
    }

    private function updateStatus(int $id, string $status, array $extra = array())
    {
        if (!$this->hasTable() || !$id) {
            return false;
        }

        $payload = array_merge(array('status' => $status, 'updated_at' => get_current_utc_time()), $extra);
        return $this->db->table($this->db->prefixTable($this->table))->where('id', $id)->update($payload);
    }

    private function normalize(array $data): array
    {
        $payload = array();
        $fields = array('code', 'laudo_id', 'client_id', 'unit_name', 'location_name', 'inspection_type', 'inspection_date', 'start_time', 'end_time', 'duration_minutes', 'responsible_id', 'team_json', 'equipments_json', 'observations', 'status', 'progress_percent', 'checkin_at', 'checkout_at', 'started_at', 'paused_at', 'completed_at', 'is_improductive', 'improductive_reason', 'improductive_evidence_json', 'client_contact_name', 'client_signature_file', 'suggested_new_date', 'costs_json', 'comments', 'source', 'address', 'latitude', 'longitude', 'created_by', 'updated_by');
        foreach ($fields as $field) {
            if (array_key_exists($field, $data)) {
                $payload[$field] = $data[$field];
            }
        }

        foreach (array('laudo_id', 'client_id', 'duration_minutes', 'responsible_id', 'created_by', 'updated_by', 'is_improductive') as $field) {
            if (array_key_exists($field, $payload)) {
                $payload[$field] = $payload[$field] !== '' && $payload[$field] !== null ? (int) $payload[$field] : null;
            }
        }

        foreach (array('code', 'unit_name', 'location_name', 'inspection_type', 'inspection_date', 'start_time', 'end_time', 'observations', 'status', 'progress_percent', 'checkin_at', 'checkout_at', 'started_at', 'paused_at', 'completed_at', 'improductive_reason', 'client_contact_name', 'client_signature_file', 'suggested_new_date', 'comments', 'source', 'address', 'latitude', 'longitude') as $field) {
            if (array_key_exists($field, $payload) && $payload[$field] !== null) {
                $payload[$field] = trim((string) $payload[$field]);
            }
        }

        if (empty($payload['status'])) {
            $payload['status'] = 'planned';
        }
        if (empty($payload['source'])) {
            $payload['source'] = 'web';
        }
        if (empty($payload['duration_minutes'])) {
            $payload['duration_minutes'] = 60;
        }
        if (!array_key_exists('progress_percent', $payload) || $payload['progress_percent'] === '') {
            $payload['progress_percent'] = 0;
        }

        return $payload;
    }

    private function generate_code(): string
    {
        return 'INSP-' . date('Ymd') . '-' . strtoupper(substr(sha1(microtime(true)), 0, 6));
    }

    private function statusColor(string $status): string
    {
        $map = array(
            'planned' => '#6c757d',
            'scheduled' => '#0d6efd',
            'confirmed' => '#198754',
            'traveling' => '#fd7e14',
            'in_progress' => '#6610f2',
            'paused' => '#ffc107',
            'completed' => '#198754',
            'improductive' => '#dc3545',
            'rescheduled' => '#6f42c1',
            'canceled' => '#343a40',
        );

        return get_array_value($map, $status) ?: '#6c757d';
    }
}
