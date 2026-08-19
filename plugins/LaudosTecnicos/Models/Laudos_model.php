<?php

namespace LaudosTecnicos\Models;

use LaudosTecnicos\Models\LaudoTemplates_model;

class Laudos_model extends LaudosTecnicosBaseModel
{
    protected $table = 'laudos';

    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function get_dashboard_stats()
    {
        if (!$this->hasTable()) {
            return (object) array(
                'total' => 0,
                'draft' => 0,
                'drafting' => 0,
                'awaiting_review' => 0,
                'approved' => 0,
                'issued' => 0,
                'overdue' => 0,
                'canceled' => 0,
            );
        }

        $table = $this->db->prefixTable($this->table);
        $sql = "SELECT
            COUNT(*) AS total,
            SUM(CASE WHEN $table.status = 'draft' THEN 1 ELSE 0 END) AS draft,
            SUM(CASE WHEN $table.status = 'drafting' THEN 1 ELSE 0 END) AS drafting,
            SUM(CASE WHEN $table.status = 'awaiting_review' THEN 1 ELSE 0 END) AS awaiting_review,
            SUM(CASE WHEN $table.status = 'approved' THEN 1 ELSE 0 END) AS approved,
            SUM(CASE WHEN $table.status = 'issued' THEN 1 ELSE 0 END) AS issued,
            SUM(CASE WHEN $table.status = 'overdue' OR ($table.validity_date IS NOT NULL AND $table.validity_date <> '0000-00-00' AND $table.validity_date < CURDATE() AND $table.status NOT IN ('canceled')) THEN 1 ELSE 0 END) AS overdue,
            SUM(CASE WHEN $table.status = 'canceled' THEN 1 ELSE 0 END) AS canceled
            FROM $table
            WHERE $table.deleted = 0";

        return $this->db->query($sql)->getRow();
    }

    public function get_recent_laudos($limit = 5)
    {
        if (!$this->hasTable()) {
            return array();
        }

        $limit = max(1, (int) $limit);
        $rows = $this->db->table($this->db->prefixTable($this->table))
            ->where('deleted', 0)
            ->orderBy('id', 'DESC')
            ->limit($limit)
            ->get()
            ->getResult();

        return $rows ?: array();
    }

    public function get_details(array $options = array())
    {
        if (!$this->hasTable()) {
            return $this->emptyResult();
        }

        $laudos = $this->db->prefixTable($this->table);
        $types = $this->db->prefixTable('laudo_types');
        $categories = $this->db->prefixTable('laudo_categories');
        $templates = $this->db->prefixTable('laudo_templates');
        $clients = $this->db->prefixTable('clients');
        $projects = $this->db->prefixTable('projects');
        $statuses = $this->db->prefixTable('laudo_statuses');
        $users = $this->db->prefixTable('users');

        $where = " WHERE $laudos.deleted = 0";

        $id = (int) get_array_value($options, 'id');
        if ($id) {
            $where .= " AND $laudos.id = $id";
        }

        $client_id = (int) get_array_value($options, 'client_id');
        if ($client_id) {
            $where .= " AND $laudos.client_id = $client_id";
        }

        $contact_id = (int) get_array_value($options, 'contact_id');
        if ($contact_id) {
            $where .= " AND $laudos.contact_id = $contact_id";
        }

        $project_id = (int) get_array_value($options, 'project_id');
        if ($project_id) {
            $where .= " AND $laudos.project_id = $project_id";
        }

        $type_id = (int) get_array_value($options, 'type_id');
        if ($type_id) {
            $where .= " AND $laudos.type_id = $type_id";
        }

        $category_id = (int) get_array_value($options, 'category_id');
        if ($category_id) {
            $where .= " AND $laudos.category_id = $category_id";
        }

        $responsible_id = (int) get_array_value($options, 'responsible_id');
        if ($responsible_id) {
            $where .= " AND $laudos.technical_responsible_id = $responsible_id";
        }

        $reviewer_id = (int) get_array_value($options, 'reviewer_id');
        if ($reviewer_id) {
            $where .= " AND $laudos.reviewer_id = $reviewer_id";
        }

        $approver_id = (int) get_array_value($options, 'approver_id');
        if ($approver_id) {
            $where .= " AND $laudos.approver_id = $approver_id";
        }

        $commercial_responsible_id = (int) get_array_value($options, 'commercial_responsible_id');
        if ($commercial_responsible_id) {
            $where .= " AND $laudos.commercial_responsible_id = $commercial_responsible_id";
        }

        $status = trim((string) get_array_value($options, 'status'));
        if ($status !== '') {
            $status = $this->db->escapeString($status);
            $where .= " AND $laudos.status = '$status'";
        }

        $priority = trim((string) get_array_value($options, 'priority'));
        if ($priority !== '') {
            $priority = $this->db->escapeString($priority);
            $where .= " AND $laudos.priority = '$priority'";
        }

        $unit_name = trim((string) get_array_value($options, 'unit_name'));
        if ($unit_name !== '') {
            $unit_name = $this->db->escapeLikeString($unit_name);
            $where .= " AND $laudos.unit_name LIKE '%$unit_name%'";
        }

        $search = trim((string) get_array_value($options, 'search'));
        if ($search !== '') {
            $search = $this->db->escapeLikeString($search);
            $where .= " AND (
                $laudos.number LIKE '%$search%'
                OR $laudos.custom_code LIKE '%$search%'
                OR $laudos.title LIKE '%$search%'
                OR $clients.company_name LIKE '%$search%'
                OR $projects.title LIKE '%$search%'
                OR $types.name LIKE '%$search%'
                OR $categories.name LIKE '%$search%'
                OR $statuses.name LIKE '%$search%'
            )";
        }

        $request_start_date = trim((string) get_array_value($options, 'request_start_date'));
        $request_end_date = trim((string) get_array_value($options, 'request_end_date'));
        if ($request_start_date !== '') {
            $where .= " AND $laudos.request_date >= '" . $this->db->escapeString($request_start_date) . "'";
        }
        if ($request_end_date !== '') {
            $where .= " AND $laudos.request_date <= '" . $this->db->escapeString($request_end_date) . "'";
        }

        $validity_start_date = trim((string) get_array_value($options, 'validity_start_date'));
        $validity_end_date = trim((string) get_array_value($options, 'validity_end_date'));
        if ($validity_start_date !== '') {
            $where .= " AND $laudos.validity_date >= '" . $this->db->escapeString($validity_start_date) . "'";
        }
        if ($validity_end_date !== '') {
            $where .= " AND $laudos.validity_date <= '" . $this->db->escapeString($validity_end_date) . "'";
        }

        $sql = "SELECT
                $laudos.*,
                $types.name AS type_name,
                $types.code AS type_code,
                $categories.name AS category_name,
                $categories.code AS category_code,
                $templates.name AS template_name,
                $templates.code AS template_code,
                $templates.template_key AS template_key,
                $templates.version AS template_version,
                $laudos.template_key AS laudo_template_key,
                $laudos.template_code AS laudo_template_code,
                $laudos.template_name AS laudo_template_name,
                $laudos.template_version AS laudo_template_version,
                $laudos.template_snapshot_json AS template_snapshot_json,
                $laudos.template_applied_at AS template_applied_at,
                $clients.company_name AS client_name,
                $projects.title AS project_name,
                $statuses.name AS status_name,
                $statuses.color AS status_color,
                $statuses.icon AS status_icon,
                $statuses.status_final AS status_final,
                $statuses.status_cancellation AS status_cancellation,
                CONCAT(IFNULL(commercial.first_name, ''), ' ', IFNULL(commercial.last_name, '')) AS commercial_responsible_name,
                CONCAT(IFNULL(technical.first_name, ''), ' ', IFNULL(technical.last_name, '')) AS technical_responsible_name,
                CONCAT(IFNULL(reviewer.first_name, ''), ' ', IFNULL(reviewer.last_name, '')) AS reviewer_name,
                CONCAT(IFNULL(approver.first_name, ''), ' ', IFNULL(approver.last_name, '')) AS approver_name,
                CONCAT(IFNULL(contact.first_name, ''), ' ', IFNULL(contact.last_name, '')) AS contact_name
            FROM $laudos
            LEFT JOIN $types ON $types.id = $laudos.type_id AND $types.deleted = 0
            LEFT JOIN $categories ON $categories.id = $laudos.category_id AND $categories.deleted = 0
            LEFT JOIN $templates ON $templates.id = $laudos.template_id AND $templates.deleted = 0
            LEFT JOIN $clients ON $clients.id = $laudos.client_id AND $clients.deleted = 0
            LEFT JOIN $projects ON $projects.id = $laudos.project_id AND $projects.deleted = 0
            LEFT JOIN $statuses ON $statuses.code = $laudos.status AND $statuses.deleted = 0
            LEFT JOIN $users commercial ON commercial.id = $laudos.commercial_responsible_id AND commercial.deleted = 0
            LEFT JOIN $users technical ON technical.id = $laudos.technical_responsible_id AND technical.deleted = 0
            LEFT JOIN $users reviewer ON reviewer.id = $laudos.reviewer_id AND reviewer.deleted = 0
            LEFT JOIN $users approver ON approver.id = $laudos.approver_id AND approver.deleted = 0
            LEFT JOIN $users contact ON contact.id = $laudos.contact_id AND contact.deleted = 0
            $where
            ORDER BY $laudos.id DESC";

        return $this->queryOrEmpty($sql);
    }

    public function get_one_with_details(int $id)
    {
        return $this->get_details(array('id' => $id))->getRow();
    }

    public function get_units_dropdown($include_blank = true)
    {
        $options = array();
        if ($include_blank) {
            $options[''] = '-';
        }

        if (!$this->hasTable()) {
            return $options;
        }

        $rows = $this->db->table($this->db->prefixTable($this->table))
            ->select('unit_name')
            ->where('deleted', 0)
            ->where('unit_name IS NOT NULL', null, false)
            ->groupBy('unit_name')
            ->orderBy('unit_name', 'ASC')
            ->get()
            ->getResult();

        foreach ($rows as $row) {
            $unit = trim((string) ($row->unit_name ?? ''));
            if ($unit !== '') {
                $options[$unit] = $unit;
            }
        }

        return $options;
    }

    public function format_team_members($value)
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }

        $ids = array_filter(array_map('trim', explode(',', $value)));
        if (!$ids) {
            return '';
        }

        $users_table = $this->db->prefixTable('users');
        $rows = $this->db->table($users_table)
            ->select("id, CONCAT(IFNULL(first_name, ''), ' ', IFNULL(last_name, '')) AS full_name")
            ->whereIn('id', $ids)
            ->where('deleted', 0)
            ->orderBy('first_name', 'ASC')
            ->get()
            ->getResult();

        $names = array();
        foreach ($rows as $row) {
            $name = trim((string) ($row->full_name ?? ''));
            if ($name !== '') {
                $names[] = $name;
            }
        }

        return implode(', ', $names);
    }

    public function generate_number(array $context = array())
    {
        if (!$this->hasTable()) {
            return array(
                'number' => '',
                'sequence' => 0,
                'sequence_key' => '',
            );
        }

        $settings = model(LaudoSettings_model::class)->get_all_settings_with_defaults();
        $format = trim((string) get_array_value($settings, 'numbering_format'));
        $prefix = trim((string) get_array_value($settings, 'laudo_prefix'));
        $next_number = max(1, (int) get_array_value($settings, 'next_number'));
        $padding = max(1, (int) get_array_value($settings, 'sequence_padding') ?: 6);

        $type_code = trim((string) get_array_value($context, 'type_code'));
        $category_code = trim((string) get_array_value($context, 'category_code'));
        $client_name = trim((string) get_array_value($context, 'client_name'));
        $unit_name = trim((string) get_array_value($context, 'unit_name'));
        $year = trim((string) get_array_value($context, 'year')) ?: date('Y');
        $month = trim((string) get_array_value($context, 'month')) ?: date('m');

        if ($type_code === '' && (int) get_array_value($context, 'type_id')) {
            $type_row = $this->db->table($this->db->prefixTable('laudo_types'))
                ->select('code, name')
                ->where('id', (int) get_array_value($context, 'type_id'))
                ->get()
                ->getRow();
            if ($type_row) {
                $type_code = trim((string) ($type_row->code ?: $type_row->name));
            }
        }

        if ($category_code === '' && (int) get_array_value($context, 'category_id')) {
            $category_row = $this->db->table($this->db->prefixTable('laudo_categories'))
                ->select('code, name')
                ->where('id', (int) get_array_value($context, 'category_id'))
                ->get()
                ->getRow();
            if ($category_row) {
                $category_code = trim((string) ($category_row->code ?: $category_row->name));
            }
        }

        if ($client_name === '' && (int) get_array_value($context, 'client_id')) {
            $client_row = $this->db->table($this->db->prefixTable('clients'))
                ->select('company_name')
                ->where('id', (int) get_array_value($context, 'client_id'))
                ->get()
                ->getRow();
            if ($client_row) {
                $client_name = trim((string) ($client_row->company_name ?: ''));
            }
        }

        $replacements = array(
            '{PREFIX}' => $prefix,
            '{TYPE}' => $this->normalize_number_token($type_code),
            '{CATEGORY}' => $this->normalize_number_token($category_code),
            '{YEAR}' => $year,
            '{MONTH}' => $month,
            '{CLIENT}' => $this->normalize_number_token($client_name),
            '{UNIT}' => $this->normalize_number_token($unit_name),
        );

        $resolved_prefix = str_replace(array_keys($replacements), array_values($replacements), $format ?: '{PREFIX}{SEQ}');
        $sequence_key = sha1($resolved_prefix);

        $sequence = $this->reserve_sequence($sequence_key, $next_number);
        $number = str_replace('{SEQ}', str_pad((string) $sequence, $padding, '0', STR_PAD_LEFT), $resolved_prefix);

        return array(
            'number' => $number,
            'sequence' => $sequence,
            'sequence_key' => $sequence_key,
        );
    }

    public function save_from_post(array $data, ?int $id = null)
    {
        if (!$this->hasTable()) {
            return false;
        }

        $table = $this->db->prefixTable($this->table);
        $current_row = $id ? $this->get_one((int) $id) : null;
        $payload = $this->normalize_payload($data);
        $payload = $this->apply_template_snapshot($payload);

        if ($id && $current_row && !empty($current_row->template_id) && (int) $current_row->template_id === (int) get_array_value($payload, 'template_id')) {
            $payload['template_key'] = $current_row->template_key ?? ($payload['template_key'] ?? '');
            $payload['template_code'] = $current_row->template_code ?? ($payload['template_code'] ?? '');
            $payload['template_name'] = $current_row->template_name ?? ($payload['template_name'] ?? '');
            $payload['template_version'] = $current_row->template_version ?? ($payload['template_version'] ?? null);
            $payload['template_snapshot_json'] = $current_row->template_snapshot_json ?? ($payload['template_snapshot_json'] ?? '');
            $payload['template_applied_at'] = $current_row->template_applied_at ?? ($payload['template_applied_at'] ?? null);
            $payload['is_template_based'] = !empty($current_row->is_template_based) ? 1 : (int) ($payload['is_template_based'] ?? 0);
        }

        if (array_key_exists('created_by', $data)) {
            $payload['created_by'] = (int) $data['created_by'];
        }
        if (array_key_exists('updated_by', $data)) {
            $payload['updated_by'] = (int) $data['updated_by'];
        }

        if (!$id && empty($payload['number'])) {
            $generated = $this->generate_number($payload);
            $payload['number'] = $generated['number'];
            $payload['number_sequence'] = $generated['sequence'];
            $payload['number_sequence_key'] = $generated['sequence_key'];
        }

        $payload['updated_at'] = get_current_utc_time();

        if ($id) {
            unset($payload['created_at'], $payload['created_by']);
            return $this->db->table($table)->where('id', $id)->update($payload);
        }

        if (!array_key_exists('created_by', $payload)) {
            $payload['created_by'] = (int) ($data['created_by'] ?? 0);
        }
        if (!array_key_exists('updated_by', $payload)) {
            $payload['updated_by'] = (int) ($data['updated_by'] ?? 0);
        }

        $payload['created_at'] = get_current_utc_time();
        $inserted = $this->db->table($table)->insert($payload);
        if ($inserted) {
            return $this->db->insertID();
        }

        return false;
    }

    public function duplicate(int $id, array $options = array())
    {
        if (!$this->hasTable() || !$id) {
            return false;
        }

        $original = $this->get_one_with_details($id);
        if (!$original || !$original->id) {
            return false;
        }

        $copy_general = !array_key_exists('copy_general', $options) || !empty($options['copy_general']);
        $copy_content = !array_key_exists('copy_content', $options) || !empty($options['copy_content']);
        $copy_template = !array_key_exists('copy_template', $options) || !empty($options['copy_template']);
        $copy_team = !array_key_exists('copy_team', $options) || !empty($options['copy_team']);

        $data = array(
            'title' => $copy_general ? ($original->title ?? '') : '',
            'custom_code' => $copy_general ? ($original->custom_code ?? '') : '',
            'revision' => '00',
            'type_id' => $copy_general ? (int) ($original->type_id ?? 0) : null,
            'category_id' => $copy_general ? (int) ($original->category_id ?? 0) : null,
            'client_id' => $copy_general ? (int) ($original->client_id ?? 0) : null,
            'project_id' => $copy_general ? (int) ($original->project_id ?? 0) : null,
            'task_id' => $copy_general ? (int) ($original->task_id ?? 0) : null,
            'contract_id' => $copy_general ? (int) ($original->contract_id ?? 0) : null,
            'proposal_id' => $copy_general ? (int) ($original->proposal_id ?? 0) : null,
            'service_order_id' => $copy_general ? (int) ($original->service_order_id ?? 0) : null,
            'contact_id' => $copy_general ? (int) ($original->contact_id ?? 0) : null,
            'unit_name' => $copy_general ? ($original->unit_name ?? '') : '',
            'address' => $copy_general ? ($original->address ?? '') : '',
            'inspection_location' => $copy_general ? ($original->inspection_location ?? '') : '',
            'priority' => $copy_general ? ($original->priority ?? 'normal') : 'normal',
            'request_date' => $copy_general ? ($original->request_date ?? null) : null,
            'scheduled_date' => $copy_general ? ($original->scheduled_date ?? null) : null,
            'visit_date' => $copy_general ? ($original->visit_date ?? null) : null,
            'inspection_date' => $copy_general ? ($original->inspection_date ?? null) : null,
            'issue_date' => null,
            'validity_date' => null,
            'commercial_responsible_id' => $copy_team ? (int) ($original->commercial_responsible_id ?? 0) : null,
            'inspection_team' => $copy_team ? ($original->inspection_team ?? '') : '',
            'technical_responsible_id' => $copy_team ? (int) ($original->technical_responsible_id ?? 0) : null,
            'reviewer_id' => $copy_team ? (int) ($original->reviewer_id ?? 0) : null,
            'approver_id' => $copy_team ? (int) ($original->approver_id ?? 0) : null,
            'objective' => $copy_content ? ($original->objective ?? '') : '',
            'scope' => $copy_content ? ($original->scope ?? '') : '',
            'methodology' => $copy_content ? ($original->methodology ?? '') : '',
            'premises' => $copy_content ? ($original->premises ?? '') : '',
            'limitations' => $copy_content ? ($original->limitations ?? '') : '',
            'installation_description' => $copy_content ? ($original->installation_description ?? '') : '',
            'results' => $copy_content ? ($original->results ?? '') : '',
            'diagnosis' => $copy_content ? ($original->diagnosis ?? '') : '',
            'conclusion' => $copy_content ? ($original->conclusion ?? '') : '',
            'recommendations' => $copy_content ? ($original->recommendations ?? '') : '',
            'internal_notes' => $copy_content ? ($original->internal_notes ?? '') : '',
            'tags' => $copy_general ? ($original->tags ?? '') : '',
            'cost_center' => $copy_general ? ($original->cost_center ?? '') : '',
            'proposal_number' => $copy_general ? ($original->proposal_number ?? '') : '',
            'contract_number' => $copy_general ? ($original->contract_number ?? '') : '',
            'external_reference' => $copy_general ? ($original->external_reference ?? '') : '',
            'confidentiality' => $copy_general ? ($original->confidentiality ?? '') : '',
            'client_observations' => $copy_general ? ($original->client_observations ?? '') : '',
            'template_id' => $copy_template ? (int) ($original->template_id ?? 0) : null,
            'template_key' => $copy_template ? ($original->laudo_template_key ?? $original->template_key ?? '') : '',
            'template_code' => $copy_template ? ($original->laudo_template_code ?? $original->template_code ?? '') : '',
            'template_name' => $copy_template ? ($original->laudo_template_name ?? $original->template_name ?? '') : '',
            'template_version' => $copy_template ? (int) ($original->laudo_template_version ?? $original->template_version ?? 0) : null,
            'template_snapshot_json' => $copy_template ? ($original->template_snapshot_json ?? '') : '',
            'template_applied_at' => $copy_template ? ($original->template_applied_at ?? null) : null,
            'is_template_based' => $copy_template ? (int) ($original->is_template_based ?? 0) : 0,
            'status' => 'draft',
        );

        $data = array_filter($data, function ($value) {
            return $value !== '';
        });

        $data['created_by'] = (int) (get_array_value($options, 'created_by') ?: 0);
        $data['updated_by'] = (int) (get_array_value($options, 'created_by') ?: 0);

        return $this->save_from_post($data, null);
    }

    public function change_status(int $laudo_id, string $to_status_code, int $user_id = 0, string $comment = '', string $source = 'web')
    {
        if (!$this->hasTable() || !$laudo_id) {
            return false;
        }

        $laudo = $this->get_one($laudo_id);
        if (!$laudo || !$laudo->id) {
            return false;
        }

        $from_status_code = trim((string) ($laudo->status ?? ''));
        $to_status_code = trim($to_status_code);
        if ($to_status_code === '') {
            return false;
        }

        $statuses_model = model(LaudoStatuses_model::class);
        if (!$statuses_model->get_status_by_code($to_status_code)) {
            return false;
        }

        $transitions_model = model(LaudoStatusTransitions_model::class);
        if ($from_status_code !== '' && !$transitions_model->is_allowed($from_status_code, $to_status_code)) {
            return false;
        }

        $table = $this->db->prefixTable($this->table);
        $ok = $this->db->table($table)->where('id', $laudo_id)->update(array(
            'status' => $to_status_code,
            'updated_at' => get_current_utc_time(),
        ));

        if ($ok) {
            model(LaudoStatusHistory_model::class)->log_change(array(
                'laudo_id' => $laudo_id,
                'from_status_code' => $from_status_code ?: null,
                'to_status_code' => $to_status_code,
                'user_id' => $user_id,
                'comment' => $comment,
                'source' => $source,
                'ip_address' => service('request')->getIPAddress(),
            ));

            model(LaudoAuditLogs_model::class)->log_action(array(
                'entity_type' => 'laudo',
                'entity_id' => $laudo_id,
                'action' => 'change_status',
                'user_id' => $user_id,
                'ip_address' => service('request')->getIPAddress(),
                'source' => $source,
                'description' => 'Status alterado de ' . ($from_status_code ?: '-') . ' para ' . $to_status_code,
                'created_by' => $user_id,
            ));
        }

        return $ok;
    }

    private function normalize_payload(array $data)
    {
        $payload = array();

        $fields = array(
            'number',
            'custom_code',
            'revision',
            'title',
            'type_id',
            'category_id',
            'client_id',
            'project_id',
            'task_id',
            'contact_id',
            'contract_id',
            'proposal_id',
            'service_order_id',
            'unit_name',
            'address',
            'inspection_location',
            'priority',
            'status',
            'request_date',
            'scheduled_date',
            'visit_date',
            'inspection_date',
            'issue_date',
            'validity_date',
            'commercial_responsible_id',
            'inspection_team',
            'technical_responsible_id',
            'reviewer_id',
            'approver_id',
            'objective',
            'scope',
            'methodology',
            'premises',
            'limitations',
            'installation_description',
            'results',
            'diagnosis',
            'conclusion',
            'recommendations',
            'internal_notes',
            'tags',
            'cost_center',
            'proposal_number',
            'contract_number',
            'external_reference',
            'confidentiality',
            'client_observations',
            'number_sequence',
            'number_sequence_key',
            'template_id',
            'template_key',
            'template_code',
            'template_name',
            'template_version',
            'template_snapshot_json',
            'template_applied_at',
            'is_template_based',
        );

        foreach ($fields as $field) {
            if (array_key_exists($field, $data)) {
                $payload[$field] = $data[$field];
            }
        }

        $int_fields = array(
            'type_id',
            'category_id',
            'client_id',
            'project_id',
            'task_id',
            'contact_id',
            'contract_id',
            'proposal_id',
            'service_order_id',
            'commercial_responsible_id',
            'technical_responsible_id',
            'reviewer_id',
            'approver_id',
            'template_id',
            'template_version',
            'is_template_based',
        );

        foreach ($int_fields as $field) {
            if (array_key_exists($field, $payload)) {
                $payload[$field] = $payload[$field] !== '' && $payload[$field] !== null ? (int) $payload[$field] : null;
            }
        }

        $text_fields = array(
            'number',
            'custom_code',
            'revision',
            'title',
            'unit_name',
            'address',
            'inspection_location',
            'priority',
            'status',
            'request_date',
            'scheduled_date',
            'visit_date',
            'inspection_date',
            'issue_date',
            'validity_date',
            'inspection_team',
            'objective',
            'scope',
            'methodology',
            'premises',
            'limitations',
            'installation_description',
            'results',
            'diagnosis',
            'conclusion',
            'recommendations',
            'internal_notes',
            'tags',
            'cost_center',
            'proposal_number',
            'contract_number',
            'external_reference',
            'confidentiality',
            'client_observations',
            'number_sequence_key',
            'template_key',
            'template_code',
            'template_name',
            'template_snapshot_json',
            'template_applied_at',
        );

        if (array_key_exists('number_sequence', $payload)) {
            $payload['number_sequence'] = $payload['number_sequence'] !== '' && $payload['number_sequence'] !== null ? (int) $payload['number_sequence'] : null;
        }

        foreach ($text_fields as $field) {
            if (array_key_exists($field, $payload) && $payload[$field] !== null) {
                $payload[$field] = trim((string) $payload[$field]);
            }
        }

        if (!array_key_exists('revision', $payload) || $payload['revision'] === '') {
            $payload['revision'] = '00';
        }

        if (!array_key_exists('status', $payload) || $payload['status'] === '') {
            $payload['status'] = 'draft';
        }

        if (!array_key_exists('priority', $payload) || $payload['priority'] === '') {
            $payload['priority'] = 'normal';
        }

        if (!array_key_exists('is_template_based', $payload) || $payload['is_template_based'] === null) {
            $payload['is_template_based'] = 0;
        }

        return $payload;
    }

    private function apply_template_snapshot(array $payload): array
    {
        $template_id = (int) get_array_value($payload, 'template_id');
        if (!$template_id) {
            $type_id = (int) get_array_value($payload, 'type_id');
            $category_id = (int) get_array_value($payload, 'category_id');
            if ($type_id || $category_id) {
                $template = model(LaudoTemplates_model::class)->get_default_template_for_type($type_id, $category_id);
                if ($template && $template->id) {
                    $template_id = (int) $template->id;
                    $payload['template_id'] = $template_id;
                }
            }
        }

        if (!$template_id) {
            return $payload;
        }

        if (!empty($payload['template_snapshot_json']) && !empty($payload['template_key']) && !empty($payload['template_name'])) {
            return $payload;
        }

        $template = model(LaudoTemplates_model::class)->get_one_with_structure($template_id);
        if (!$template || !$template->id) {
            return $payload;
        }

        $payload['template_id'] = (int) $template->id;
        $payload['template_key'] = (string) ($template->template_key ?? '');
        $payload['template_code'] = (string) ($template->code ?? '');
        $payload['template_name'] = (string) ($template->name ?? '');
        $payload['template_version'] = (int) ($template->version ?? 1);
        $payload['template_snapshot_json'] = laudostecnicos_safe_json($template->structure ?: array());
        $payload['template_applied_at'] = get_current_utc_time();
        $payload['is_template_based'] = 1;

        return $payload;
    }

    private function normalize_number_token($value)
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }

        $value = strtoupper($value);
        $value = preg_replace('/[^A-Z0-9]+/', '-', $value);
        return trim($value, '-');
    }

    private function reserve_sequence(string $sequence_key, int $initial_sequence = 1): int
    {
        $table = $this->db->prefixTable('laudo_number_sequences');
        $initial_sequence = max(1, $initial_sequence);

        for ($attempt = 0; $attempt < 3; $attempt++) {
            $this->db->transBegin();

            $row = $this->db->query("SELECT id, next_sequence FROM $table WHERE sequence_key = ? FOR UPDATE", array($sequence_key))->getRow();
            if ($row && !empty($row->id)) {
                $sequence = max(1, (int) $row->next_sequence);
                $this->db->table($table)->where('id', (int) $row->id)->update(array(
                    'next_sequence' => $sequence + 1,
                    'updated_at' => get_current_utc_time(),
                ));
                $this->db->transComplete();

                if ($this->db->transStatus()) {
                    return $sequence;
                }

                continue;
            }

            try {
                $this->db->table($table)->insert(array(
                    'sequence_key' => $sequence_key,
                    'next_sequence' => $initial_sequence + 1,
                    'created_at' => get_current_utc_time(),
                    'updated_at' => get_current_utc_time(),
                ));
                $this->db->transComplete();

                if ($this->db->transStatus()) {
                    return $initial_sequence;
                }
            } catch (\Throwable $e) {
                $this->db->transRollback();
            }
        }

        return $initial_sequence;
    }
}
