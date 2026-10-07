<?php

namespace ProjectAnalizer\Controllers;

use App\Controllers\Security_Controller;

class Project_materials extends Security_Controller
{
    private $materials_model;

    public function __construct()
    {
        parent::__construct();
        $this->access_only_team_members();
        $this->materials_model = model('ProjectAnalizer\\Models\\Project_materials_model');
    }

    public function index($project_id = 0)
    {
        $project_id = (int)$project_id;
        $project = $this->_get_project($project_id);
        if (!$project) show_404();
        $this->_sync($project);

        $Tasks_model = model('App\\Models\\Tasks_model');
        $tasks_query = $Tasks_model->get_details(array('project_id' => $project_id));
        $tasks = $tasks_query ? $tasks_query->getResult() : array();
        $materials = $this->materials_model->get_project_materials($project_id);
        $allocations = $this->materials_model->get_allocations($project_id);
        $by_task = array();
        foreach ($allocations as $row) {
            $by_task[(int)$row->task_id][] = $row;
        }

        $db = db_connect('default');
        $items = array();
        $items_table = $db->prefixTable('items');
        if ($db->tableExists($items_table)) {
            $q = $db->table($items_table)->select('id,title,unit_type')->where('deleted', 0)->orderBy('title')->get();
            $items = $q ? $q->getResult() : array();
        }

        return $this->template->view('ProjectAnalizer\\Views\\project_materials\\index', array(
            'project' => $project,
            'project_id' => $project_id,
            'materials' => $materials,
            'tasks' => $tasks,
            'allocations_by_task' => $by_task,
            'catalog_items' => $items
        ));
    }

    public function projects()
    {
        $options = array();
        if (!$this->can_manage_all_projects()) {
            $options['user_id'] = $this->login_user->id;
        }

        $query = model('App\\Models\\Projects_model')->get_details($options);
        $projects = $query ? $query->getResult() : array();

        return $this->template->rander('ProjectAnalizer\\Views\\project_materials\\projects', array(
            'projects' => $projects
        ));
    }

    public function add()
    {
        $project_id = (int)$this->request->getPost('project_id');
        if (!$this->_get_project($project_id)) return $this->_error(app_lang('permission_denied'));
        $item_id = (int)$this->request->getPost('item_id');
        $description = trim((string)$this->request->getPost('description'));
        $unit = trim((string)$this->request->getPost('unit')) ?: 'UN';
        $quantity = $this->_decimal($this->request->getPost('quantity'));
        if ($quantity <= 0) return $this->_error('Informe uma quantidade válida.');

        $db = db_connect('default');
        if ($item_id) {
            $row = $db->table($db->prefixTable('items'))->select('title,unit_type')->where('id', $item_id)->get()->getRow();
            if ($row) {
                $description = $description ?: (string)$row->title;
                $unit = trim((string)$row->unit_type) ?: $unit;
            }
        }
        if (!$description) return $this->_error('Informe o material.');

        $key = 'manual:' . hash('sha256', strtolower(($item_id ?: $description) . '|' . $unit . '|' . microtime(true)));
        $id = $this->materials_model->ci_save(array(
            'project_id' => $project_id, 'item_id' => $item_id ?: null, 'description' => $description,
            'unit' => $unit, 'planned_quantity' => 0, 'additional_quantity' => $quantity,
            'source' => 'manual', 'source_key' => $key, 'created_by' => $this->login_user->id,
            'created_at' => get_my_local_time(), 'updated_at' => get_my_local_time(), 'deleted' => 0
        ), 0);
        return $this->response->setJSON(array('success' => (bool)$id, 'message' => $id ? app_lang('record_saved') : app_lang('error_occurred')));
    }

    public function allocate()
    {
        $project_id = (int)$this->request->getPost('project_id');
        $material_id = (int)$this->request->getPost('project_material_id');
        $task_id = (int)$this->request->getPost('task_id');
        $quantity = $this->_decimal($this->request->getPost('quantity'));
        if (!$this->_get_project($project_id) || !$material_id || !$task_id || $quantity <= 0) return $this->_error(app_lang('invalid_request'));

        $db = db_connect('default');
        $m = $db->table($db->prefixTable('pa_project_materials'))->where('id', $material_id)->where('project_id', $project_id)->where('deleted', 0)->get()->getRow();
        $t = $db->table($db->prefixTable('tasks'))->where('id', $task_id)->where('project_id', $project_id)->where('deleted', 0)->get()->getRow();
        if (!$m || !$t) return $this->_error(app_lang('record_not_found'));

        $table = $db->prefixTable('pa_project_task_materials');
        $existing = $db->table($table)->where('project_material_id', $material_id)->where('task_id', $task_id)->where('deleted', 0)->get()->getRow();
        $allocated_query = $db->table($table)
            ->selectSum('quantity', 'total')
            ->where('project_material_id', $material_id)
            ->where('deleted', 0);
        if ($existing) {
            $allocated_query->where('id !=', (int)$existing->id);
        }
        $allocated_row = $allocated_query->get()->getRow();
        $allocated_elsewhere = $allocated_row ? (float)$allocated_row->total : 0;
        $available = (float)$m->planned_quantity + (float)$m->additional_quantity;
        if (($allocated_elsewhere + $quantity) > ($available + 0.00001)) {
            return $this->_error('A quantidade associada ultrapassa o total disponível deste material no projeto.');
        }
        $data = array('project_id' => $project_id, 'project_material_id' => $material_id, 'task_id' => $task_id, 'quantity' => $quantity, 'notes' => trim((string)$this->request->getPost('notes')), 'updated_at' => get_my_local_time());
        if ($existing) {
            $requested = $this->materials_model->get_requested_quantity((int)$existing->id);
            if ($quantity < $requested) return $this->_error('A quantidade não pode ser menor que o total já requisitado.');
            $ok = $db->table($table)->where('id', (int)$existing->id)->update($data);
        } else {
            $data['created_by'] = $this->login_user->id; $data['created_at'] = get_my_local_time(); $data['deleted'] = 0;
            $ok = $db->table($table)->insert($data);
        }
        return $this->response->setJSON(array('success' => (bool)$ok, 'message' => $ok ? app_lang('record_saved') : app_lang('error_occurred')));
    }

    public function allocate_batch()
    {
        $project_id = (int)$this->request->getPost('project_id');
        $task_id = (int)$this->request->getPost('task_id');
        $material_ids = (array)$this->request->getPost('project_material_id');
        $quantities = (array)$this->request->getPost('quantity');

        if (!$this->_get_project($project_id) || !$task_id) {
            return $this->_error(app_lang('invalid_request'));
        }

        $db = db_connect('default');
        $task = $db->table($db->prefixTable('tasks'))
            ->where('id', $task_id)
            ->where('project_id', $project_id)
            ->where('deleted', 0)
            ->get()->getRow();
        if (!$task) {
            return $this->_error(app_lang('record_not_found'));
        }

        $selected = array();
        foreach ($material_ids as $index => $material_id) {
            $material_id = (int)$material_id;
            $quantity = $this->_decimal($quantities[$index] ?? 0);
            if ($material_id && $quantity > 0) {
                $selected[$material_id] = $quantity;
            }
        }
        if (!$selected) {
            return $this->_error('Selecione ao menos um material e informe a quantidade.');
        }

        $materials_table = $db->prefixTable('pa_project_materials');
        $allocations_table = $db->prefixTable('pa_project_task_materials');
        $db->transStart();

        foreach ($selected as $material_id => $quantity) {
            $material_query = $db->query(
                "SELECT * FROM $materials_table WHERE id=? AND project_id=? AND deleted=0 FOR UPDATE",
                array($material_id, $project_id)
            );
            $material = $material_query ? $material_query->getRow() : null;
            if (!$material) {
                $db->transRollback();
                return $this->_error('Um dos materiais selecionados não foi encontrado.');
            }

            $existing = $db->table($allocations_table)
                ->where('project_material_id', $material_id)
                ->where('task_id', $task_id)
                ->where('deleted', 0)
                ->get()->getRow();

            $allocated_query = $db->table($allocations_table)
                ->selectSum('quantity', 'total')
                ->where('project_material_id', $material_id)
                ->where('deleted', 0);
            if ($existing) {
                $allocated_query->where('id !=', (int)$existing->id);
            }
            $allocated_row = $allocated_query->get()->getRow();
            $allocated_elsewhere = $allocated_row ? (float)$allocated_row->total : 0;
            $available = (float)$material->planned_quantity + (float)$material->additional_quantity;
            if (($allocated_elsewhere + $quantity) > ($available + 0.00001)) {
                $db->transRollback();
                return $this->_error('A quantidade de ' . $material->description . ' ultrapassa o saldo disponível no projeto.');
            }

            if ($existing) {
                $requested = $this->materials_model->get_requested_quantity((int)$existing->id);
                if ($quantity < $requested) {
                    $db->transRollback();
                    return $this->_error('A quantidade de ' . $material->description . ' não pode ser menor que o total já requisitado.');
                }
                $ok = $db->table($allocations_table)->where('id', (int)$existing->id)->update(array(
                    'quantity' => $quantity,
                    'updated_at' => get_my_local_time()
                ));
            } else {
                $ok = $db->table($allocations_table)->insert(array(
                    'project_id' => $project_id,
                    'project_material_id' => $material_id,
                    'task_id' => $task_id,
                    'quantity' => $quantity,
                    'created_by' => $this->login_user->id,
                    'created_at' => get_my_local_time(),
                    'updated_at' => get_my_local_time(),
                    'deleted' => 0
                ));
            }

            if (!$ok) {
                $db->transRollback();
                return $this->_error(app_lang('error_occurred'));
            }
        }

        $db->transComplete();
        if (!$db->transStatus()) {
            return $this->_error(app_lang('error_occurred'));
        }

        return $this->response->setJSON(array(
            'success' => true,
            'message' => count($selected) . ' material(is) associado(s) à tarefa.'
        ));
    }

    public function create_request()
    {
        $project_id = (int)$this->request->getPost('project_id');
        $task_id = (int)$this->request->getPost('task_id');
        $allocation_ids = (array)$this->request->getPost('allocation_id');
        $quantities = (array)$this->request->getPost('request_quantity');
        $desired_date = trim((string)$this->request->getPost('desired_date'));
        $project = $this->_get_project($project_id);
        if (!$project || !$task_id || !$desired_date) return $this->_error('Selecione os materiais e informe a data desejada.');

        $selected = array();
        foreach ($allocation_ids as $index => $allocation_id) {
            $allocation_id = (int)$allocation_id;
            $qty = $this->_decimal($quantities[$index] ?? 0);
            if ($allocation_id && $qty > 0) $selected[$allocation_id] = $qty;
        }
        if (!$selected) return $this->_error('Selecione pelo menos um material.');

        $db = db_connect('default');
        $db->transStart();
        $rows = array();
        foreach ($selected as $allocation_id => $qty) {
            $a_table = $db->prefixTable('pa_project_task_materials');
            $lock = $db->query("SELECT id FROM $a_table WHERE id=? AND project_id=? AND task_id=? AND deleted=0 FOR UPDATE", array($allocation_id, $project_id, $task_id));
            if (!$lock || !$lock->getRow()) { $db->transRollback(); return $this->_error(app_lang('record_not_found')); }
            $allocation_rows = $this->materials_model->get_allocations($project_id, $task_id);
            $allocation = null;
            foreach ($allocation_rows as $candidate) if ((int)$candidate->id === $allocation_id) { $allocation = $candidate; break; }
            if (!$allocation) { $db->transRollback(); return $this->_error(app_lang('record_not_found')); }
            $remaining = (float)$allocation->quantity - (float)$allocation->requested_quantity;
            if ($qty > $remaining + 0.00001) { $db->transRollback(); return $this->_error('A quantidade solicitada é maior que o saldo da tarefa para ' . $allocation->description . '.'); }
            $rows[] = array('allocation' => $allocation, 'quantity' => $qty);
        }

        if (!class_exists('Purchases\\Models\\Purchases_requests_model')) {
            $db->transRollback(); return $this->_error('O módulo de compras não está disponível.');
        }
        $Requests = model('Purchases\\Models\\Purchases_requests_model');
        $RequestItems = model('Purchases\\Models\\Purchases_request_items_model');
        $code = $Requests->get_next_request_code_data($this->_company_id());
        $task = model('App\\Models\\Tasks_model')->get_one($task_id);
        $request_id = $Requests->ci_save(array(
            'company_id' => $this->_company_id(), 'request_code_number' => $code['request_code_number'], 'request_code' => $code['request_code'],
            'project_id' => $project_id, 'client_id' => $project->client_id ?? null, 'os_id' => null, 'is_internal' => 0,
            'cost_center' => $this->_cost_center_name($project), 'priority' => 'medium',
            'note' => 'Materiais da tarefa #' . $task_id . ' - ' . ($task->title ?? ''), 'requested_by' => $this->login_user->id,
            'requester_id' => $this->login_user->id, 'request_date' => get_my_local_time(), 'status' => 'draft',
            'created_by' => $this->login_user->id, 'created_at' => get_my_local_time(), 'updated_at' => get_my_local_time(), 'deleted' => 0
        ));
        $request_id = is_numeric($request_id) ? (int)$request_id : (int)$db->insertID();
        if (!$request_id) { $db->transRollback(); return $this->_error(app_lang('error_occurred')); }

        $link_table = $db->prefixTable('pa_project_material_request_items');
        foreach ($rows as $row) {
            $a = $row['allocation']; $qty = $row['quantity'];
            $item_id = $RequestItems->ci_save(array('company_id' => $this->_company_id(), 'request_id' => $request_id, 'item_id' => $a->item_id ?: null, 'description' => $a->description, 'unit' => $a->unit ?: 'UN', 'quantity' => $qty, 'rate' => 0, 'total' => 0, 'desired_date' => $desired_date, 'note' => 'Tarefa #' . $task_id, 'created_by' => $this->login_user->id, 'created_at' => get_my_local_time(), 'deleted' => 0), 0);
            $item_id = is_numeric($item_id) ? (int)$item_id : (int)$db->insertID();
            if (!$item_id || !$db->table($link_table)->insert(array('project_id' => $project_id, 'task_id' => $task_id, 'project_material_id' => (int)$a->project_material_id, 'allocation_id' => (int)$a->id, 'purchase_request_id' => $request_id, 'purchase_request_item_id' => $item_id, 'quantity' => $qty, 'created_by' => $this->login_user->id, 'created_at' => get_my_local_time(), 'deleted' => 0))) { $db->transRollback(); return $this->_error(app_lang('error_occurred')); }
        }
        $db->transComplete();
        if (!$db->transStatus()) return $this->_error(app_lang('error_occurred'));
        return $this->response->setJSON(array('success' => true, 'message' => 'Requisição criada com sucesso.', 'redirect_to' => get_uri('purchases_requests/view/' . $request_id)));
    }

    private function _get_project($project_id)
    {
        if (!$project_id) return null;
        $this->init_project_permission_checker($project_id);
        $project = model('App\\Models\\Projects_model')->get_one($project_id);
        return ($project && empty($project->deleted)) ? $project : null;
    }

    private function _sync($project)
    {
        $proposal_id = (int)($project->proposal_id ?? 0);
        if (!$proposal_id) {
            $db = db_connect('default'); $p = $db->prefixTable('proposals_custom');
            if ($db->tableExists($p) && $db->fieldExists('project_id', $p)) { $q = $db->table($p)->select('id')->where('project_id', (int)$project->id)->where('deleted', 0)->get(); $row = $q ? $q->getRow() : null; $proposal_id = $row ? (int)$row->id : 0; }
        }
        if ($proposal_id) $this->materials_model->sync_from_proposal((int)$project->id, $proposal_id);
    }

    private function _company_id() { return !empty($this->login_user->company_id) ? (int)$this->login_user->company_id : (int)get_default_company_id(); }
    private function _decimal($v) { $v = trim((string)$v); if (strpos($v, ',') !== false) { $v = str_replace('.', '', $v); $v = str_replace(',', '.', $v); } return is_numeric($v) ? (float)$v : 0; }
    private function _error($message) { return $this->response->setJSON(array('success' => false, 'message' => $message)); }
    private function _cost_center_name($project) { $id = (int)($project->cost_center_id ?? 0); if (!$id) return ''; $db = db_connect('default'); $t = $db->prefixTable('contaazul_cost_centers'); if (!$db->tableExists($t)) return ''; $q = $db->table($t)->where('id', $id)->get(); $r = $q ? $q->getRow() : null; return $r ? (string)($r->title ?? $r->name ?? '') : ''; }
}
