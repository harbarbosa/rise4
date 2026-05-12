<?php

namespace GED\Models;

class Ged_notification_logs_model extends GedBaseModel
{
    protected $table = 'ged_notification_logs';

    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function get_details($options = array())
    {
        $where = array();

        $document_id = get_array_value($options, 'document_id');
        if ($document_id) {
            $where['document_id'] = (int) $document_id;
        }

        $submission_id = get_array_value($options, 'submission_id');
        if ($submission_id) {
            $where['submission_id'] = (int) $submission_id;
        }

        $notification_type = get_array_value($options, 'notification_type');
        if ($notification_type) {
            $where['notification_type'] = $notification_type;
        }

        return $this->get_all_where($where, 100000, 0, 'sent_at');
    }

    public function has_log($document_id, $notification_type, $days_before, $user_id, $submission_id = 0)
    {
        $builder = $this->db->table($this->table);
        $builder->where('document_id', (int) $document_id);
        $builder->where('notification_type', $notification_type);
        $builder->where('user_id', (int) $user_id);
        $builder->where('days_before', $days_before);

        if ((int) $submission_id > 0) {
            $builder->where('submission_id', (int) $submission_id);
        } else {
            $builder->where('submission_id IS NULL', null, false);
        }

        return (bool) $builder->get()->getRow();
    }

    public function add_log($data)
    {
        $payload = array(
            'document_id' => (int) get_array_value($data, 'document_id'),
            'submission_id' => get_array_value($data, 'submission_id') ? (int) get_array_value($data, 'submission_id') : null,
            'user_id' => (int) get_array_value($data, 'user_id'),
            'notification_type' => trim((string) get_array_value($data, 'notification_type')),
            'days_before' => get_array_value($data, 'days_before'),
            'sent_at' => get_array_value($data, 'sent_at') ?: get_my_local_time(),
            'created_at' => get_array_value($data, 'created_at') ?: get_my_local_time(),
        );

        return $this->ci_save($payload, 0);
    }

    public function get_logged_user_ids($document_id, $notification_type, $days_before, $submission_id = 0)
    {
        $builder = $this->db->table($this->table);
        $builder->select('user_id');
        $builder->where('document_id', (int) $document_id);
        $builder->where('notification_type', $notification_type);
        $builder->where('days_before', $days_before);

        if ((int) $submission_id > 0) {
            $builder->where('submission_id', (int) $submission_id);
        } else {
            $builder->where('submission_id IS NULL', null, false);
        }

        $rows = $builder->get()->getResult();
        $user_ids = array();
        foreach ($rows as $row) {
            if (!empty($row->user_id)) {
                $user_ids[] = (int) $row->user_id;
            }
        }

        return array_values(array_unique($user_ids));
    }
}
