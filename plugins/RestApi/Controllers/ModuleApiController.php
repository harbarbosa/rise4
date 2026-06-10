<?php

namespace RestApi\Controllers;

class ModuleApiController extends Rest_api_Controller
{
    public function __construct()
    {
        parent::__construct();
        helper(['general', 'date_time']);
    }

    protected function payload(): array
    {
        try {
            $json = $this->request->getJSON(true);
            if (is_array($json) && $json) {
                return $json;
            }
        } catch (\Throwable $e) {
            log_message('error', '[RestApi] Invalid JSON payload: ' . $e->getMessage());
        }

        $post = $this->request->getPost();
        if (is_array($post) && $post) {
            return $post;
        }

        $raw_input = trim((string) $this->request->getBody());
        if ($raw_input !== '') {
            $decoded = json_decode($raw_input, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded) && $decoded) {
                return $decoded;
            }

            $raw = $this->request->getRawInput();
            if (is_array($raw) && $raw) {
                return $raw;
            }
        }

        return [];
    }

    protected function tableColumns(string $table): array
    {
        $db = db_connect('default');
        $fullTable = $db->prefixTable($table);
        if (!$db->tableExists($fullTable)) {
            return [];
        }

        return $db->getFieldNames($fullTable) ?: [];
    }

    protected function filterPayload(string $table, array $payload, array $exclude = []): array
    {
        $columns = $this->tableColumns($table);
        if (!$columns) {
            return [];
        }

        $exclude = array_fill_keys($exclude, true);
        $data = [];
        foreach ($columns as $column) {
            if (isset($exclude[$column])) {
                continue;
            }
            if (array_key_exists($column, $payload)) {
                $data[$column] = $payload[$column];
            }
        }

        return $data;
    }

    protected function saveRow(string $table, array $payload, int $id = 0): array
    {
        $db = db_connect('default');
        $columns = $this->tableColumns($table);
        $data = $this->filterPayload($table, $payload, ['id']);

        if (!$data) {
            return [false, 0, 'No valid fields were provided.'];
        }

        if (in_array('updated_at', $columns, true)) {
            $data['updated_at'] = get_current_utc_time();
        }

        if ($id > 0) {
            $db->table($db->prefixTable($table))
                ->where('id', $id)
                ->update($data);

            return [true, $id, 'record_saved'];
        }

        if (in_array('created_at', $columns, true) && !array_key_exists('created_at', $data)) {
            $data['created_at'] = get_current_utc_time();
        }
        if (in_array('deleted', $columns, true) && !array_key_exists('deleted', $data)) {
            $data['deleted'] = 0;
        }
        if (in_array('deleted_at', $columns, true) && !array_key_exists('deleted_at', $data)) {
            $data['deleted_at'] = null;
        }

        $db->table($db->prefixTable($table))->insert($data);

        return [true, (int) $db->insertID(), 'record_saved'];
    }

    protected function softDelete(string $table, int $id): bool
    {
        if ($id <= 0) {
            return false;
        }

        $db = db_connect('default');
        $fullTable = $db->prefixTable($table);
        $columns = $this->tableColumns($table);

        if (in_array('deleted_at', $columns, true)) {
            return (bool) $db->table($fullTable)
                ->where('id', $id)
                ->update(['deleted_at' => get_current_utc_time()]);
        }

        if (in_array('deleted', $columns, true)) {
            return (bool) $db->table($fullTable)
                ->where('id', $id)
                ->update(['deleted' => 1]);
        }

        return (bool) $db->table($fullTable)->where('id', $id)->delete();
    }

    protected function parseDecimal($value): float
    {
        $text = trim((string) $value);
        if ($text === '') {
            return 0.0;
        }

        $text = preg_replace('/[^\d,\.\-]/', '', $text);
        $lastComma = strrpos($text, ',');
        $lastDot = strrpos($text, '.');

        if ($lastComma !== false && $lastDot !== false) {
            if ($lastComma > $lastDot) {
                $text = str_replace('.', '', $text);
                $text = str_replace(',', '.', $text);
            } else {
                $text = str_replace(',', '', $text);
            }
        } elseif ($lastComma !== false) {
            $text = str_replace('.', '', $text);
            $text = str_replace(',', '.', $text);
        }

        return (float) $text;
    }

    protected function normalizeDecimalFields(array &$data, array $fields): void
    {
        foreach ($fields as $field) {
            if (!array_key_exists($field, $data)) {
                continue;
            }

            $value = $data[$field];
            if ($value === '' || $value === null) {
                $data[$field] = 0;
                continue;
            }

            $data[$field] = $this->parseDecimal($value);
        }
    }

    protected function normalizeIntFields(array &$data, array $fields): void
    {
        foreach ($fields as $field) {
            if (!array_key_exists($field, $data)) {
                continue;
            }

            $value = $data[$field];
            if ($value === '' || $value === null) {
                $data[$field] = null;
                continue;
            }

            $data[$field] = (int) $value;
        }
    }

    protected function respondData(array $data, array $extra = [])
    {
        return $this->respond(array_merge(['status' => true, 'data' => $data], $extra));
    }

    protected function toBool($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value) || is_float($value)) {
            return (bool) $value;
        }

        $normalized = strtolower(trim((string) $value));
        return in_array($normalized, ['1', 'true', 'yes', 'on', 'sim'], true);
    }
}
