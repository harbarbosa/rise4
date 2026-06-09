<?php

namespace PontoRH\Models;

class PontoRh_work_schedule_members_model extends PontoRhBaseModel
{
    protected $table = 'pontorh_work_schedule_members';

    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function get_member_ids_by_schedule(int $work_schedule_id): array
    {
        if (!$this->hasTable() || !$work_schedule_id) {
            return array();
        }

        $table = $this->db->prefixTable($this->table);
        $sql = "SELECT wsm.team_member_id
                FROM {$table} wsm
                WHERE wsm.deleted = 0 AND wsm.work_schedule_id = " . $work_schedule_id . "
                ORDER BY wsm.id ASC";

        $rows = $this->queryOrEmpty($sql)->getResult();
        $ids = array();
        foreach ($rows as $row) {
            $ids[] = (int) $row->team_member_id;
        }

        return array_values(array_unique(array_filter($ids)));
    }

    public function sync_members(int $work_schedule_id, array $team_member_ids, int $created_by): bool
    {
        if (!$this->hasTable() || !$work_schedule_id) {
            return false;
        }

        $team_member_ids = array_values(array_unique(array_filter(array_map('intval', $team_member_ids))));
        $table = $this->db->prefixTable($this->table);

        $this->db->query("UPDATE {$table} SET deleted = 1, updated_at = '" . get_current_utc_time() . "' WHERE work_schedule_id = " . $work_schedule_id);

        foreach ($team_member_ids as $team_member_id) {
            $existing = $this->get_one_where(array(
                'work_schedule_id' => $work_schedule_id,
                'team_member_id' => $team_member_id,
            ));

            $data = array(
                'work_schedule_id' => $work_schedule_id,
                'team_member_id' => $team_member_id,
                'active' => 1,
                'created_by' => $created_by,
                'created_at' => get_current_utc_time(),
                'updated_at' => get_current_utc_time(),
                'deleted' => 0,
            );

            if ($existing && !empty($existing->id)) {
                $this->ci_save($data, (int) $existing->id);
            } else {
                $this->ci_save($data);
            }
        }

        return true;
    }
}
