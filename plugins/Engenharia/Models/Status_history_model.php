<?php

namespace Engenharia\Models;

class Status_history_model extends EngenhariaBaseModel
{
    protected $table = 'eng_status_history';

    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function add(int $laudo_id, ?string $from, string $to, int $user_id, string $comment = '', string $source = 'web'): int
    {
        $this->db->table($this->db->prefixTable($this->table))->insert(array(
            'laudo_id' => $laudo_id,
            'from_status' => $from,
            'to_status' => $to,
            'comment' => $comment,
            'changed_by' => $user_id,
            'changed_at' => $this->now(),
            'source' => $source,
        ));

        return (int) $this->db->insertID();
    }

    public function get_for_laudo(int $laudo_id)
    {
        $history = $this->db->prefixTable($this->table);
        $users = $this->db->prefixTable('users');
        return $this->db->query("SELECT $history.*, CONCAT(IFNULL($users.first_name, ''), ' ', IFNULL($users.last_name, '')) AS changed_by_name
            FROM $history LEFT JOIN $users ON $users.id = $history.changed_by
            WHERE $history.laudo_id = " . (int) $laudo_id . " ORDER BY $history.id ASC");
    }
}
