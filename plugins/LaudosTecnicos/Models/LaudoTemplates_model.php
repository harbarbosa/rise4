<?php

namespace LaudosTecnicos\Models;

class LaudoTemplates_model extends LaudosTecnicosBaseModel
{
    protected $table = 'laudo_templates';

    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function get_details(array $options = array())
    {
        if (!$this->hasTable()) {
            return $this->emptyResult();
        }

        $templates = $this->db->prefixTable($this->table);
        $types = $this->db->prefixTable('laudo_types');
        $categories = $this->db->prefixTable('laudo_categories');
        $where = " WHERE $templates.deleted = 0";

        $search = trim((string) get_array_value($options, 'search'));
        if ($search !== '') {
            $search = $this->db->escapeLikeString($search);
            $where .= " AND ($templates.name LIKE '%$search%' OR $templates.code LIKE '%$search%' OR $templates.description LIKE '%$search%' OR $templates.template_key LIKE '%$search%')";
        }

        $status = trim((string) get_array_value($options, 'status'));
        if ($status !== '') {
            $status = $this->db->escapeString($status);
            $where .= " AND $templates.status = '$status'";
        }

        $type_id = (int) get_array_value($options, 'type_id');
        if ($type_id) {
            $where .= " AND $templates.type_id = $type_id";
        }

        $category_id = (int) get_array_value($options, 'category_id');
        if ($category_id) {
            $where .= " AND $templates.category_id = $category_id";
        }

        return $this->queryOrEmpty("SELECT $templates.*,
                $types.name AS type_name,
                $types.code AS type_code,
                $categories.name AS category_name,
                $categories.code AS category_code
            FROM $templates
            LEFT JOIN $types ON $types.id = $templates.type_id AND $types.deleted = 0
            LEFT JOIN $categories ON $categories.id = $templates.category_id AND $categories.deleted = 0
            $where
            ORDER BY $templates.is_default DESC, $templates.template_key ASC, $templates.version DESC, $templates.name ASC");
    }

    public function get_active_dropdown($include_blank = true, int $type_id = 0, int $category_id = 0)
    {
        $options = array();
        if ($include_blank) {
            $options[''] = '-';
        }

        if (!$this->hasTable()) {
            return $options;
        }

        $builder = $this->db->table($this->db->prefixTable($this->table))
            ->where('deleted', 0)
            ->where('is_active', 1)
            ->where('status', 'published');

        if ($type_id) {
            $builder->where('type_id', $type_id);
        }
        if ($category_id) {
            $builder->where('category_id', $category_id);
        }

        $rows = $builder->orderBy('is_default', 'DESC')
            ->orderBy('name', 'ASC')
            ->orderBy('version', 'DESC')
            ->get()
            ->getResult();

        foreach ($rows as $row) {
            $label = trim((string) ($row->name ?? ''));
            if ((int) ($row->version ?? 0) > 1) {
                $label .= ' v' . (int) $row->version;
            }
            $options[$row->id] = $label;
        }

        return $options;
    }

    public function get_default_template_for_type(int $type_id, int $category_id = 0)
    {
        if (!$this->hasTable()) {
            return null;
        }

        $builder = $this->db->table($this->db->prefixTable($this->table))
            ->where('deleted', 0)
            ->where('is_active', 1)
            ->where('status', 'published');

        if ($type_id) {
            $builder->groupStart()
                ->where('type_id', $type_id)
                ->orWhere('is_default', 1)
                ->groupEnd();
        }

        if ($category_id) {
            $builder->groupStart()
                ->where('category_id', $category_id)
                ->orWhere('is_default', 1)
                ->groupEnd();
        }

        $row = $builder->orderBy('is_default', 'DESC')
            ->orderBy('version', 'DESC')
            ->orderBy('id', 'DESC')
            ->get()
            ->getRow();

        return $row ?: null;
    }

    public function get_one_with_structure(int $id)
    {
        $row = $this->get_one($id);
        if (!$row || !$row->id) {
            return null;
        }

        $row->structure = $this->decode_structure_json($row->structure_json ?? '');
        return $row;
    }

    public function save_versioned(array $data, array $structure = array(), ?int $id = null, bool $force_new_version = false)
    {
        if (!$this->hasTable()) {
            return false;
        }

        $payload = $this->normalize_payload($data, $structure);
        $payload['updated_at'] = get_current_utc_time();

        if (!empty($payload['is_default'])) {
            $this->clear_default_flag();
        }

        if (($payload['status'] ?? 'draft') === 'published' && empty($payload['published_at'])) {
            $payload['published_at'] = get_current_utc_time();
        }

        if ($id) {
            $current = $this->get_one_with_structure($id);
            if (!$current || !$current->id) {
                return false;
            }

            if (!$force_new_version && (string) ($current->status ?? '') === 'published') {
                return $this->clone_as_new_version((int) $current->id, $payload, $structure);
            }

            $payload['structure_json'] = $this->encode_structure_json($structure ?: $current->structure ?: array());
            $payload['template_key'] = trim((string) ($current->template_key ?? ''));
            $payload['version'] = (int) ($current->version ?? 1);
            $payload['created_by'] = (int) ($current->created_by ?? ($data['created_by'] ?? 0));
            unset($payload['published_at']);

            return $this->db->table($this->db->prefixTable($this->table))->where('id', $id)->update($payload);
        }

        if (empty($payload['template_key'])) {
            $payload['template_key'] = $this->generate_template_key($payload['name'] ?? 'TPL');
        }

        if (empty($payload['version'])) {
            $payload['version'] = 1;
        }

        if (empty($payload['structure_json'])) {
            $payload['structure_json'] = $this->encode_structure_json($structure);
        }

        $payload['created_at'] = get_current_utc_time();
        $inserted = $this->db->table($this->db->prefixTable($this->table))->insert($payload);
        return $inserted ? $this->db->insertID() : false;
    }

    public function clone_template(int $id, array $overrides = array())
    {
        $current = $this->get_one_with_structure($id);
        if (!$current || !$current->id) {
            return false;
        }

        $data = array_merge(array(
            'template_key' => $current->template_key,
            'name' => $current->name,
            'code' => $current->code,
            'description' => $current->description,
            'type_id' => $current->type_id,
            'category_id' => $current->category_id,
            'version' => (int) ($current->version ?? 1) + 1,
            'status' => 'draft',
            'is_active' => 1,
            'is_default' => 0,
            'structure_json' => $this->encode_structure_json($current->structure ?: array()),
            'published_at' => null,
            'created_by' => (int) ($overrides['created_by'] ?? 0),
            'updated_by' => (int) ($overrides['created_by'] ?? 0),
        ), $overrides);

        unset($data['id']);
        $data['template_key'] = (string) ($current->template_key ?? $data['template_key']);
        $data['version'] = (int) ($current->version ?? 1) + 1;
        $data['status'] = 'draft';
        $data['published_at'] = null;
        return $this->save_versioned($data, $current->structure ?: array(), null, true);
    }

    public function publish(int $id)
    {
        return $this->update_status_fields($id, array(
            'status' => 'published',
            'published_at' => get_current_utc_time(),
        ));
    }

    public function archive(int $id)
    {
        return $this->update_status_fields($id, array(
            'status' => 'archived',
        ));
    }

    public function toggle_active(int $id)
    {
        $row = $this->get_one($id);
        if (!$row || !$row->id) {
            return false;
        }

        return $this->update_status_fields($id, array(
            'is_active' => empty($row->is_active) ? 1 : 0,
        ));
    }

    public function delete($id = 0, $undo = false)
    {
        $id = (int) $id;
        if (!$id || $this->is_in_use($id)) {
            return false;
        }

        return parent::delete($id, $undo);
    }

    public function is_in_use(int $id): bool
    {
        if (!$this->hasTable() || !$id) {
            return false;
        }

        $db = $this->db;
        $laudos_table = $db->prefixTable('laudos');
        $types_table = $db->prefixTable('laudo_types');
        $row = $this->get_one($id);
        $template_key = $row && !empty($row->template_key) ? (string) $row->template_key : '';

        if ($db->table($laudos_table)->groupStart()->where('template_id', $id)->orWhere('template_key', $template_key)->groupEnd()->where('deleted', 0)->countAllResults() > 0) {
            return true;
        }

        return $db->table($types_table)->where('default_template_id', $id)->where('deleted', 0)->countAllResults() > 0;
    }

    public function build_structure_payload(array $input = array())
    {
        return array(
            'sections' => $this->sanitize_rows(get_array_value($input, 'sections'), array('key', 'title', 'description', 'sort', 'page_break', 'numbering', 'visible_web', 'visible_mobile', 'visible_pdf', 'required', 'hidden')),
            'fields' => $this->sanitize_rows(get_array_value($input, 'fields'), array('key', 'section_key', 'type', 'name', 'label', 'description', 'placeholder', 'default_value', 'required', 'sort', 'width', 'validation', 'mask', 'help', 'visible_web', 'visible_mobile', 'visible_pdf', 'read_only', 'generated_ai')),
            'rules' => $this->sanitize_rows(get_array_value($input, 'rules'), array('name', 'trigger_field', 'operator', 'trigger_value', 'action_type', 'action_target', 'message', 'sort', 'active')),
        );
    }

    public function decode_structure_json($json): array
    {
        $decoded = json_decode((string) $json, true);
        if (!is_array($decoded)) {
            $decoded = array();
        }

        return array(
            'sections' => is_array(get_array_value($decoded, 'sections')) ? get_array_value($decoded, 'sections') : array(),
            'fields' => is_array(get_array_value($decoded, 'fields')) ? get_array_value($decoded, 'fields') : array(),
            'rules' => is_array(get_array_value($decoded, 'rules')) ? get_array_value($decoded, 'rules') : array(),
        );
    }

    public function encode_structure_json(array $structure): string
    {
        return laudostecnicos_safe_json($this->build_structure_payload($structure));
    }

    public function get_snapshot_from_template($template_row): array
    {
        if (!$template_row) {
            return array('sections' => array(), 'fields' => array(), 'rules' => array());
        }

        return $this->decode_structure_json($template_row->structure_json ?? '');
    }

    private function sanitize_rows($rows, array $allowed_keys)
    {
        if (!is_array($rows)) {
            return array();
        }

        $result = array();
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $clean = array();
            foreach ($allowed_keys as $key) {
                $value = array_key_exists($key, $row) ? $row[$key] : '';
                if (is_array($value)) {
                    $clean[$key] = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                } else {
                    $clean[$key] = trim((string) $value);
                }
            }
            $result[] = $clean;
        }

        return $result;
    }

    private function normalize_payload(array $data, array $structure = array())
    {
        $payload = array();

        $fields = array(
            'template_key',
            'name',
            'code',
            'description',
            'type_id',
            'category_id',
            'version',
            'status',
            'is_active',
            'is_default',
            'structure_json',
            'published_at',
        );

        foreach ($fields as $field) {
            if (array_key_exists($field, $data)) {
                $payload[$field] = $data[$field];
            }
        }

        $int_fields = array('type_id', 'category_id', 'version', 'is_active', 'is_default');
        foreach ($int_fields as $field) {
            if (array_key_exists($field, $payload)) {
                $payload[$field] = $payload[$field] !== '' && $payload[$field] !== null ? (int) $payload[$field] : 0;
            }
        }

        $text_fields = array('template_key', 'name', 'code', 'description', 'status', 'structure_json');
        foreach ($text_fields as $field) {
            if (array_key_exists($field, $payload) && $payload[$field] !== null) {
                $payload[$field] = trim((string) $payload[$field]);
            }
        }

        if (!array_key_exists('status', $payload) || $payload['status'] === '') {
            $payload['status'] = 'draft';
        }
        if (!array_key_exists('is_active', $payload) || $payload['is_active'] === null) {
            $payload['is_active'] = 1;
        }
        if (!array_key_exists('is_default', $payload) || $payload['is_default'] === null) {
            $payload['is_default'] = 0;
        }
        if (!array_key_exists('version', $payload) || $payload['version'] < 1) {
            $payload['version'] = 1;
        }

        if (empty($payload['structure_json'])) {
            $payload['structure_json'] = $this->encode_structure_json($structure);
        }

        return $payload;
    }

    private function update_status_fields(int $id, array $fields)
    {
        if (!$this->hasTable() || !$id) {
            return false;
        }

        $row = $this->get_one($id);
        if (!$row || !$row->id) {
            return false;
        }

        $fields['updated_at'] = get_current_utc_time();
        return $this->db->table($this->db->prefixTable($this->table))->where('id', $id)->update($fields);
    }

    private function clear_default_flag()
    {
        $table = $this->db->prefixTable($this->table);
        return $this->db->table($table)->where('deleted', 0)->update(array('is_default' => 0, 'updated_at' => get_current_utc_time()));
    }

    private function clone_as_new_version(int $id, array $payload, array $structure)
    {
        $current = $this->get_one_with_structure($id);
        if (!$current || !$current->id) {
            return false;
        }

        $new_payload = array_merge(array(
            'template_key' => $current->template_key,
            'name' => $payload['name'] ?? $current->name,
            'code' => $payload['code'] ?? $current->code,
            'description' => $payload['description'] ?? $current->description,
            'type_id' => $payload['type_id'] ?? $current->type_id,
            'category_id' => $payload['category_id'] ?? $current->category_id,
            'version' => (int) ($current->version ?? 1) + 1,
            'status' => 'draft',
            'is_active' => array_key_exists('is_active', $payload) ? (int) $payload['is_active'] : (int) ($current->is_active ?? 1),
            'is_default' => array_key_exists('is_default', $payload) ? (int) $payload['is_default'] : 0,
            'structure_json' => $this->encode_structure_json($structure ?: $current->structure ?: array()),
            'published_at' => null,
            'created_by' => (int) ($payload['created_by'] ?? $current->created_by ?? 0),
            'updated_by' => (int) ($payload['updated_by'] ?? $current->updated_by ?? 0),
        ), $payload);

        unset($new_payload['id']);
        $new_payload['template_key'] = (string) ($current->template_key ?? $new_payload['template_key']);
        $new_payload['version'] = (int) ($current->version ?? 1) + 1;
        $new_payload['status'] = 'draft';
        $new_payload['published_at'] = null;
        $new_payload['created_at'] = get_current_utc_time();
        $new_payload['updated_at'] = get_current_utc_time();
        return $this->db->table($this->db->prefixTable($this->table))->insert($new_payload) ? $this->db->insertID() : false;
    }

    private function generate_template_key(string $name): string
    {
        $name = strtoupper(preg_replace('/[^A-Z0-9]+/', '-', trim($name)));
        $name = trim($name, '-');
        $suffix = strtoupper(substr(sha1($name . microtime(true)), 0, 6));
        return ($name !== '' ? $name : 'TPL') . '-' . $suffix;
    }
}
