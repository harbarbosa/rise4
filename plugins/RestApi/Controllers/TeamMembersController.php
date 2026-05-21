<?php

namespace RestApi\Controllers;

class TeamMembersController extends Rest_api_Controller
{
    public function index()
    {
        $filters = $this->request->getGet();
        $builder = $this->buildTeamMembersQuery();

        if (!$this->toBool($filters['include_inactive'] ?? false)) {
            $builder->where('u.status', 'active');
        }

        $search = trim((string) ($filters['q'] ?? ''));
        if ($search !== '') {
            $builder->groupStart()
                ->like('u.first_name', $search)
                ->orLike('u.last_name', $search)
                ->orLike('u.email', $search)
                ->orLike('u.phone', $search)
                ->orLike('u.job_title', $search)
                ->groupEnd();
        }

        $sort = (string) ($filters['sort'] ?? 'u.first_name');
        $allowedSorts = [
            'id' => 'u.id',
            'first_name' => 'u.first_name',
            'last_name' => 'u.last_name',
            'email' => 'u.email',
            'phone' => 'u.phone',
            'job_title' => 'u.job_title',
            'status' => 'u.status',
            'created_at' => 'u.created_at',
            'last_online' => 'u.last_online',
        ];
        $sort = $allowedSorts[$sort] ?? 'u.first_name';

        $order = strtolower((string) ($filters['order'] ?? 'asc'));
        $order = $order === 'desc' ? 'desc' : 'asc';
        $builder->orderBy($sort, $order);

        $page = max(1, (int) ($filters['page'] ?? 1));
        $limit = (int) ($filters['limit'] ?? 50);
        if ($limit < 1) {
            $limit = 50;
        }
        if ($limit > 200) {
            $limit = 200;
        }
        $offset = ($page - 1) * $limit;

        $countBuilder = clone $builder;
        $total = $countBuilder->countAllResults();
        $rows = $builder->limit($limit, $offset)->get()->getResultArray();

        return $this->respond([
            'status' => true,
            'resource' => 'team_members',
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
            ],
            'data' => $rows,
        ]);
    }

    protected function toBool($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        return in_array(strtolower(trim((string) $value)), ['1', 'true', 'yes', 'on'], true);
    }

    public function show($id = null)
    {
        if (!ctype_digit((string) $id)) {
            return $this->failValidationErrors('Invalid team member id.');
        }

        $row = $this->getQuery()->where('u.id', (int) $id)->get()->getRowArray();
        if (!$row) {
            return $this->failNotFound('Team member not found.');
        }

        return $this->respond([
            'status' => true,
            'data' => $row,
        ]);
    }

    protected function getQuery()
    {
        return $this->buildTeamMembersQuery();
    }

    protected function buildTeamMembersQuery()
    {
        $db = db_connect('default');
        $usersTable = $db->prefixTable('users');
        $rolesTable = $db->prefixTable('roles');
        $jobInfoTable = $db->prefixTable('team_member_job_info');

        $builder = $db->table($usersTable . ' u');
        $select = [
            'u.id',
            'u.first_name',
            'u.last_name',
            'u.email',
            'u.phone',
            'u.job_title',
            'u.image',
            'u.gender',
            'u.user_type',
            'u.status',
            'u.is_admin',
            'u.role_id',
        ];

        foreach (['disable_login', 'created_at', 'last_online', 'skype', 'whatsapp'] as $field) {
            if ($db->fieldExists($field, $usersTable)) {
                $select[] = 'u.' . $field;
            }
        }

        if ($db->fieldExists('title', $rolesTable)) {
            $select[] = 'r.title AS role_title';
        }

        foreach (['date_of_hire', 'salary', 'salary_term'] as $field) {
            if ($db->fieldExists($field, $jobInfoTable)) {
                $select[] = 'tm.' . $field;
            }
        }

        $builder->select($select);
        $builder->join($rolesTable . ' r', 'r.id = u.role_id AND r.deleted = 0', 'left');
        $builder->join($jobInfoTable . ' tm', 'tm.user_id = u.id', 'left');
        $builder->where('u.deleted', 0);
        $builder->where('u.user_type', 'staff');
        return $builder;
    }
}
