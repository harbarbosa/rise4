<?php

namespace LaudosTecnicos\Models;

class LaudoNonconformities_model extends LaudosTecnicosBaseModel
{
    protected $table = 'laudo_nonconformities';

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
        $clients = $this->db->prefixTable('clients');
        $laudos = $this->db->prefixTable('laudos');
        $inspections = $this->db->prefixTable('laudo_inspections');
        $equipments = $this->db->prefixTable('laudo_equipments');
        $checklists = $this->db->prefixTable('laudo_checklists');
        $norms = $this->db->prefixTable('laudo_norms');
        $users = $this->db->prefixTable('users');

        $where = " WHERE $table.deleted = 0";

        $status = trim((string) get_array_value($options, 'status'));
        if ($status !== '') {
            $where .= " AND $table.status = '" . $this->db->escapeString($status) . "'";
        }

        $classification = trim((string) get_array_value($options, 'classification'));
        if ($classification !== '') {
            $where .= " AND $table.classification = '" . $this->db->escapeString($classification) . "'";
        }

        $client_id = (int) get_array_value($options, 'client_id');
        if ($client_id) {
            $where .= " AND $table.client_id = $client_id";
        }

        $laudo_id = (int) get_array_value($options, 'laudo_id');
        if ($laudo_id) {
            $where .= " AND $table.laudo_id = $laudo_id";
        }

        $inspection_id = (int) get_array_value($options, 'inspection_id');
        if ($inspection_id) {
            $where .= " AND $table.inspection_id = $inspection_id";
        }

        $search = trim((string) get_array_value($options, 'search'));
        if ($search !== '') {
            $search = $this->db->escapeLikeString($search);
            $where .= " AND ($table.code LIKE '%$search%' OR $table.title LIKE '%$search%' OR $table.description LIKE '%$search%' OR $table.recommendation LIKE '%$search%')";
        }

        return $this->queryOrEmpty("SELECT $table.*,
                c.company_name AS client_name,
                l.title AS laudo_title,
                i.code AS inspection_code,
                e.name AS equipment_name,
                ck.name AS checklist_name,
                n.title AS norm_title,
                CONCAT(IFNULL(r.first_name, ''), ' ', IFNULL(r.last_name, '')) AS responsible_name,
                CONCAT(IFNULL(v.first_name, ''), ' ', IFNULL(v.last_name, '')) AS validator_name
            FROM $table
            LEFT JOIN $clients c ON c.id = $table.client_id AND c.deleted = 0
            LEFT JOIN $laudos l ON l.id = $table.laudo_id AND l.deleted = 0
            LEFT JOIN $inspections i ON i.id = $table.inspection_id AND i.deleted = 0
            LEFT JOIN $equipments e ON e.id = $table.equipment_id AND e.deleted = 0
            LEFT JOIN $checklists ck ON ck.id = $table.checklist_id AND ck.deleted = 0
            LEFT JOIN $norms n ON n.id = $table.norm_id AND n.deleted = 0
            LEFT JOIN $users r ON r.id = $table.responsible_id AND r.deleted = 0
            LEFT JOIN $users v ON v.id = $table.validator_id AND v.deleted = 0
            $where
            ORDER BY $table.id DESC");
    }

    public function get_dashboard_stats(array $options = array()): array
    {
        if (!$this->hasTable()) {
            return array(
                'open' => 0,
                'critical' => 0,
                'expired' => 0,
                'corrected' => 0,
                'awaiting_validation' => 0,
                'delayed_action_plans' => 0,
                'avg_correction_days' => 0,
            );
        }

        $table = $this->db->prefixTable($this->table);
        $plans = $this->db->prefixTable('laudo_action_plans');
        $where = " WHERE $table.deleted = 0";

        $client_id = (int) get_array_value($options, 'client_id');
        if ($client_id) {
            $where .= " AND $table.client_id = $client_id";
        }

        $summary = $this->db->query("SELECT
                SUM(CASE WHEN $table.status IN ('open','analysis','awaiting_correction','in_correction') THEN 1 ELSE 0 END) AS open_count,
                SUM(CASE WHEN $table.classification IN ('critica','emergencial') THEN 1 ELSE 0 END) AS critical_count,
                SUM(CASE WHEN $table.suggested_deadline IS NOT NULL AND $table.suggested_deadline < CURDATE() AND $table.status NOT IN ('corrected','validated','canceled') THEN 1 ELSE 0 END) AS expired_count,
                SUM(CASE WHEN $table.status = 'corrected' THEN 1 ELSE 0 END) AS corrected_count,
                SUM(CASE WHEN $table.status = 'awaiting_validation' THEN 1 ELSE 0 END) AS awaiting_validation_count,
                AVG(CASE WHEN $table.corrected_at IS NOT NULL AND $table.identified_at IS NOT NULL THEN TIMESTAMPDIFF(DAY, $table.identified_at, $table.corrected_at) END) AS avg_correction_days
            FROM $table $where")->getRow();

        $delayed_plans = $this->db->query("SELECT COUNT($plans.id) AS total
            FROM $plans
            INNER JOIN $table n ON n.id = $plans.nonconformity_id AND n.deleted = 0
            WHERE $plans.deleted = 0
                AND $plans.deadline IS NOT NULL
                AND $plans.deadline < CURDATE()
                AND $plans.status NOT IN ('done','validated','canceled')"
            . ($client_id ? " AND n.client_id = $client_id" : ""))->getRow();

        return array(
            'open' => (int) ($summary->open_count ?? 0),
            'critical' => (int) ($summary->critical_count ?? 0),
            'expired' => (int) ($summary->expired_count ?? 0),
            'corrected' => (int) ($summary->corrected_count ?? 0),
            'awaiting_validation' => (int) ($summary->awaiting_validation_count ?? 0),
            'delayed_action_plans' => (int) ($delayed_plans->total ?? 0),
            'avg_correction_days' => round((float) ($summary->avg_correction_days ?? 0), 2),
        );
    }

    public function get_one_with_details(int $id)
    {
        $row = $this->get_one($id);
        return $row && $row->id ? $row : null;
    }

    public function save_from_post(array $data, ?int $id = null)
    {
        $data = $this->normalize($data);
        $data['updated_at'] = get_current_utc_time();

        if ($id) {
            return $this->db->table($this->db->prefixTable($this->table))->where('id', $id)->update($data);
        }

        $data['created_at'] = get_current_utc_time();
        $inserted = $this->db->table($this->db->prefixTable($this->table))->insert($data);
        return $inserted ? $this->db->insertID() : false;
    }

    public function create_automatic(array $payload)
    {
        if (!$this->hasTable()) {
            return false;
        }

        $normalized = $this->normalize($payload);
        $normalized['status'] = 'open';
        $normalized['identified_at'] = $normalized['identified_at'] ?: get_current_utc_time();
        $normalized['updated_at'] = get_current_utc_time();
        $normalized['created_at'] = get_current_utc_time();

        $duplicate = $this->db->table($this->db->prefixTable($this->table))
            ->where('deleted', 0)
            ->groupStart()
                ->where('laudo_id', (int) $normalized['laudo_id'])
                ->where('inspection_id', (int) $normalized['inspection_id'])
                ->where('checklist_item_id', (int) $normalized['checklist_item_id'])
                ->where('title', $normalized['title'])
            ->groupEnd()
            ->get()
            ->getRow();

        if ($duplicate && $duplicate->id) {
            return $duplicate->id;
        }

        if (empty($normalized['code'])) {
            $normalized['code'] = $this->generate_code($normalized['classification'] ?: 'NC');
        }

        if (empty($normalized['risk_level'])) {
            $risk = $this->resolve_risk((int) $normalized['probability'], (int) $normalized['impact'], (int) $normalized['client_id']);
            $normalized['risk_level'] = $risk['risk_level'];
            $normalized['risk_color'] = $risk['risk_color'];
            if (empty($normalized['suggested_deadline']) && !empty($risk['deadline_days'])) {
                $normalized['suggested_deadline'] = date('Y-m-d', strtotime('+' . (int) $risk['deadline_days'] . ' days'));
            }
        }

        $inserted = $this->db->table($this->db->prefixTable($this->table))->insert($normalized);
        return $inserted ? $this->db->insertID() : false;
    }

    public function resolve_risk(int $probability, int $impact, int $category_id = 0): array
    {
        $matrix_table = $this->db->prefixTable('laudo_risk_matrix');
        $builder = $this->db->table($matrix_table)->where('deleted', 0)->where('is_active', 1);

        if ($category_id) {
            $builder->groupStart()->where('category_id', $category_id)->orWhere('category_id', null)->groupEnd();
        }

        $row = $builder->where('probability', $probability)->where('impact', $impact)->orderBy('is_default', 'DESC')->orderBy('id', 'DESC')->get()->getRow();
        if ($row && $row->id) {
            return array(
                'result' => (int) ($row->result ?? ($probability * $impact)),
                'classification' => (string) ($row->classification ?? 'observacao'),
                'risk_level' => (string) ($row->classification ?? 'observacao'),
                'risk_color' => (string) ($row->color ?? '#6c757d'),
                'deadline_days' => (int) ($row->suggested_deadline_days ?? 0),
            );
        }

        $result = $probability * $impact;
        $classification = 'observacao';
        $color = '#6c757d';
        $deadline_days = 30;
        if ($result >= 16) {
            $classification = 'emergencial';
            $color = '#7a1f1f';
            $deadline_days = 1;
        } elseif ($result >= 12) {
            $classification = 'critica';
            $color = '#dc3545';
            $deadline_days = 3;
        } elseif ($result >= 8) {
            $classification = 'alta';
            $color = '#fd7e14';
            $deadline_days = 7;
        } elseif ($result >= 4) {
            $classification = 'moderada';
            $color = '#ffc107';
            $deadline_days = 15;
        } elseif ($result >= 2) {
            $classification = 'baixa';
            $color = '#198754';
            $deadline_days = 30;
        }

        return array(
            'result' => $result,
            'classification' => $classification,
            'risk_level' => $classification,
            'risk_color' => $color,
            'deadline_days' => $deadline_days,
        );
    }

    public function get_by_nc(int $nonconformity_id)
    {
        if (!$this->hasTable() || !$nonconformity_id) {
            return array();
        }

        return $this->db->table($this->db->prefixTable($this->table))
            ->where('deleted', 0)
            ->where('id', $nonconformity_id)
            ->get()
            ->getResult();
    }

    private function normalize(array $data): array
    {
        $evidence = get_array_value($data, 'evidence_json');
        $photos = get_array_value($data, 'photos_json');
        $correction_evidence = get_array_value($data, 'correction_evidence_json');

        $classification = trim((string) get_array_value($data, 'classification')) ?: 'observacao';

        return array(
            'code' => trim((string) get_array_value($data, 'code')),
            'title' => trim((string) get_array_value($data, 'title')),
            'description' => trim((string) get_array_value($data, 'description')),
            'client_id' => (int) get_array_value($data, 'client_id') ?: null,
            'laudo_id' => (int) get_array_value($data, 'laudo_id') ?: null,
            'inspection_id' => (int) get_array_value($data, 'inspection_id') ?: null,
            'location_text' => trim((string) get_array_value($data, 'location_text')),
            'sector' => trim((string) get_array_value($data, 'sector')),
            'equipment_id' => (int) get_array_value($data, 'equipment_id') ?: null,
            'checklist_id' => (int) get_array_value($data, 'checklist_id') ?: null,
            'checklist_item_id' => (int) get_array_value($data, 'checklist_item_id') ?: null,
            'norm_id' => (int) get_array_value($data, 'norm_id') ?: null,
            'evidence_json' => is_array($evidence) ? laudostecnicos_safe_json($evidence) : trim((string) $evidence),
            'photos_json' => is_array($photos) ? laudostecnicos_safe_json($photos) : trim((string) $photos),
            'classification' => $classification,
            'probability' => (int) get_array_value($data, 'probability') ?: 1,
            'impact' => (int) get_array_value($data, 'impact') ?: 1,
            'risk_level' => trim((string) get_array_value($data, 'risk_level')),
            'risk_color' => trim((string) get_array_value($data, 'risk_color')),
            'recommendation' => trim((string) get_array_value($data, 'recommendation')),
            'suggested_deadline' => trim((string) get_array_value($data, 'suggested_deadline')),
            'responsible_id' => (int) get_array_value($data, 'responsible_id') ?: null,
            'validator_id' => (int) get_array_value($data, 'validator_id') ?: null,
            'status' => trim((string) get_array_value($data, 'status')) ?: 'open',
            'identified_at' => trim((string) get_array_value($data, 'identified_at')) ?: null,
            'corrected_at' => trim((string) get_array_value($data, 'corrected_at')) ?: null,
            'correction_evidence_json' => is_array($correction_evidence) ? laudostecnicos_safe_json($correction_evidence) : trim((string) $correction_evidence),
            'correction_comments' => trim((string) get_array_value($data, 'correction_comments')),
            'created_by' => (int) get_array_value($data, 'created_by') ?: null,
            'updated_by' => (int) get_array_value($data, 'updated_by') ?: null,
            'deleted' => 0,
        );
    }

    private function generate_code(string $prefix): string
    {
        $prefix = preg_replace('/[^A-Z0-9_-]+/i', '', strtoupper($prefix));
        $prefix = $prefix !== '' ? $prefix . '-' : 'NC-';
        $count = (int) $this->db->table($this->db->prefixTable($this->table))->where('deleted', 0)->countAllResults();
        return $prefix . str_pad((string) ($count + 1), 6, '0', STR_PAD_LEFT);
    }
}
