<?php
namespace ProjectAnalizer\Models;

use App\Models\Crud_model;

class Team_activities_model extends Crud_model
{
    protected $table = 'team_activities';
    protected $primaryKey = 'id';
    protected $returnType = 'object';

    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function get_details($filters = [])
    {
        $builder = $this->db->table($this->table . ' AS ta');
        $builder->select('
            ta.id,
            ta.project_id,
            ta.members_ids,
            ta.task_id,
            ta.activity_date,
            ta.time_mode,
            ta.start_datetime,
            ta.end_datetime,
            ta.hours,
            ta.percentage_executed,
            ta.description,
            t.title AS task_title
        ');
        $builder->join('tasks AS t', 't.id = ta.task_id', 'left');

        if (!empty($filters['project_id'])) {
            $builder->where('ta.project_id', $filters['project_id']);
        }

        $builder->orderBy('ta.activity_date', 'DESC');
        return $builder->get();
    }
}
