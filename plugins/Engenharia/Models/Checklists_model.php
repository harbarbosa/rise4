<?php

namespace Engenharia\Models;

class Checklists_model extends EngenhariaBaseModel
{
    protected $table = 'eng_checklists';

    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function get_published_for_type(int $type_id = 0)
    {
        $builder = $this->db->table($this->db->prefixTable($this->table))
            ->where('status', 'published')->where('deleted', 0);
        if ($type_id) {
            $builder->where('type_id', $type_id);
        }

        return $builder->orderBy('name', 'ASC')->orderBy('version', 'DESC')->get();
    }

    public function get_version(int $id)
    {
        return $this->db->table($this->db->prefixTable($this->table))
            ->where('id', $id)->where('deleted', 0)->get(1)->getRow();
    }

    public function get_details(array $options = array())
    {
        $table = $this->db->prefixTable($this->table);
        $types = $this->db->prefixTable('eng_types');
        $where = "$table.deleted = 0";
        if (!empty($options['id'])) { $where .= ' AND ' . $table . '.id = ' . (int) $options['id']; }
        if (!empty($options['type_id'])) { $where .= ' AND ' . $table . '.type_id = ' . (int) $options['type_id']; }
        $search = trim((string) ($options['search'] ?? ''));
        if ($search !== '') { $safe = $this->db->escapeLikeString($search); $where .= " AND ($table.name LIKE '%$safe%' OR $table.code LIKE '%$safe%')"; }
        return $this->db->query("SELECT $table.*, $types.name AS type_name FROM $table LEFT JOIN $types ON $types.id=$table.type_id AND $types.deleted=0 WHERE $where ORDER BY $table.name ASC, $table.version DESC");
    }

    public function save_record(array $data, int $id = 0): int
    {
        $table = $this->db->prefixTable($this->table);
        if ($id) {
            $current = $this->get_version($id);
            if (!$current || $current->status === 'published' || $this->isUsed($id)) { throw new \RuntimeException('Published or used checklists must be versioned.'); }
            $data['updated_at'] = $this->now();
            $this->db->table($table)->where('id', $id)->update($data);
            return $id;
        }
        $data += array('root_id' => null, 'version' => 1, 'status' => 'draft', 'is_enabled' => 1, 'deleted' => 0, 'created_at' => $this->now(), 'updated_at' => $this->now());
        $this->db->table($table)->insert($data);
        $id = (int) $this->db->insertID();
        $this->db->table($table)->where('id', $id)->update(array('root_id' => $id));
        return $id;
    }

    public function isUsed(int $id): bool
    {
        $laudos = $this->db->prefixTable('eng_laudos');
        return $this->db->tableExists($laudos) && (bool) $this->db->table($laudos)->where('checklist_id', $id)->where('deleted', 0)->countAllResults();
    }

    public function setEnabled(int $id, bool $enabled): bool
    {
        return (bool) $this->db->table($this->db->prefixTable($this->table))->where('id', $id)->where('deleted', 0)->update(array('is_enabled' => $enabled ? 1 : 0, 'status' => $enabled ? 'published' : 'archived', 'updated_at' => $this->now()));
    }

    public function update_domain(int $id, array $data): bool
    {
        $data['updated_at'] = $this->now();
        return (bool) $this->db->table($this->db->prefixTable($this->table))->where('id', $id)->where('deleted', 0)->update($data);
    }

    public function duplicate(int $source_id, int $user_id): int
    {
        $source = $this->get_version($source_id);
        if (!$source) { return 0; }
        $data = array('root_id' => null, 'type_id' => $source->type_id, 'name' => $source->name . ' - cópia', 'code' => $source->code . '-COPY-' . date('YmdHis'), 'description' => $source->description, 'version' => 1, 'status' => 'draft', 'is_default' => 0, 'is_enabled' => 1, 'created_by' => $user_id, 'updated_by' => $user_id, 'created_at' => $this->now(), 'updated_at' => $this->now(), 'deleted' => 0);
        $new_id = $this->save_record($data);
        $this->copyChildren($source_id, $new_id);
        return $new_id;
    }

    public function create_version(int $source_id, array $data, int $user_id): int
    {
        $source = $this->get_version($source_id);
        if (!$source) {
            return 0;
        }

        $next_version = (int) $source->version + 1;
        $row = array(
            'root_id' => $source->root_id ?: $source->id,
            'type_id' => $source->type_id,
            'name' => $data['name'] ?? $source->name,
            'code' => $source->code,
            'description' => $data['description'] ?? $source->description,
            'version' => $next_version,
            'status' => 'draft',
            'is_default' => 0,
            'created_by' => $user_id,
            'updated_by' => $user_id,
            'created_at' => $this->now(),
            'updated_at' => $this->now(),
            'deleted' => 0,
        );

        $this->db->transStart();
        $this->db->table($this->db->prefixTable($this->table))->insert($row);
        $new_id = (int) $this->db->insertID();
        $this->copyChildren($source_id, $new_id);
        $this->db->transComplete();

        return $this->db->transStatus() ? $new_id : 0;
    }

    public function snapshot(int $id): array
    {
        $checklist = $this->get_version($id);
        if (!$checklist) {
            return array();
        }

        $groups = $this->db->table($this->db->prefixTable('eng_checklist_groups'))
            ->where('checklist_id', $id)->where('deleted', 0)->orderBy('sort', 'ASC')->get()->getResultArray();
        $items = $this->db->table($this->db->prefixTable('eng_checklist_items'))
            ->where('checklist_id', $id)->where('deleted', 0)->orderBy('sort', 'ASC')->get()->getResultArray();

        return array('checklist' => (array) $checklist, 'groups' => $groups, 'items' => $items);
    }

    private function copyChildren(int $source_id, int $new_id)
    {
        $group_map = array();
        $groups = $this->db->table($this->db->prefixTable('eng_checklist_groups'))
            ->where('checklist_id', $source_id)->where('deleted', 0)->get()->getResultArray();
        foreach ($groups as $group) {
            $old_id = (int) $group['id'];
            unset($group['id']);
            $group['checklist_id'] = $new_id;
            $group['created_at'] = $this->now();
            $group['updated_at'] = $group['created_at'];
            $this->db->table($this->db->prefixTable('eng_checklist_groups'))->insert($group);
            $group_map[$old_id] = (int) $this->db->insertID();
        }

        $items = $this->db->table($this->db->prefixTable('eng_checklist_items'))
            ->where('checklist_id', $source_id)->where('deleted', 0)->get()->getResultArray();
        foreach ($items as $item) {
            unset($item['id']);
            $item['checklist_id'] = $new_id;
            $item['group_id'] = $group_map[(int) $item['group_id']] ?? null;
            $item['created_at'] = $this->now();
            $item['updated_at'] = $item['created_at'];
            $this->db->table($this->db->prefixTable('eng_checklist_items'))->insert($item);
        }
    }
}
