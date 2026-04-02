<?php

namespace OrdemServico\Controllers;

use App\Controllers\Security_Controller;
use App\Libraries\Dropdown_list;

class OrdemServico extends Security_Controller
{
    private $OrdemServico_model;
    public function __construct()
    {
        parent::__construct(true);
        $this->OrdemServico_model = model('OrdemServico\\Models\\OrdemServico_model');
    }

    private function ensure_tables()
    {
        try {
            $db = db_connect('default');
            $prefix = $db->getPrefix();
            $missing = [];
            foreach (['os_ordens','os_tipos','os_motivos','os_categorias','os_comments','os_servicos','os_services_items','os_products_items','os_atendimentos','os_atendimentos_members','os_files'] as $t) {
                $like = $db->query("SHOW TABLES LIKE '" . $prefix . $t . "'");
                if (!($like && method_exists($like, 'getResult') && count($like->getResult()) > 0)) {
                    $missing[] = $t;
                }
            }
            if ($missing) {
                $sql_file = PLUGINPATH . 'OrdemServico/Migrations/install.sql';
                if (is_file($sql_file)) {
                    $sql = @file_get_contents($sql_file);
                    if ($sql) {
                        $sql = str_replace('{{DB_PREFIX}}', $prefix, $sql);
                        $stmts = array_filter(array_map('trim', explode(';', $sql)));
                        foreach ($stmts as $statement) {
                            if ($statement) { $db->query($statement); }
                        }
                    }
                }
            }

            // Ensure required columns exist in os_ordens for upgrades
            try {
                $os_table = $db->prefixTable('os_ordens');
                $fields = $db->getFieldNames($os_table);
                if (is_array($fields)) {
                    if (!in_array('titulo', $fields)) {
                        $db->query("ALTER TABLE `{$os_table}` ADD `titulo` VARCHAR(255) NULL AFTER `tecnico_id`");
                    }
                    if (!in_array('tipo_id', $fields)) {
                        $db->query("ALTER TABLE `{$os_table}` ADD `tipo_id` INT(11) NULL AFTER `tecnico_id`");
                    }
                    if (!in_array('motivo_id', $fields)) {
                        // place after tipo_id when possible
                        $db->query("ALTER TABLE `{$os_table}` ADD `motivo_id` INT(11) NULL AFTER `tipo_id`");
                    }
                    if (!in_array('created_by', $fields)) {
                        $db->query("ALTER TABLE `{$os_table}` ADD `created_by` INT(11) NULL AFTER `valor_total`");
                    }
                    if (!in_array('created_at', $fields)) {
                        $db->query("ALTER TABLE `{$os_table}` ADD `created_at` DATETIME NULL AFTER `created_by`");
                    }
                    if (!in_array('updated_at', $fields)) {
                        $db->query("ALTER TABLE `{$os_table}` ADD `updated_at` DATETIME NULL AFTER `created_at`");
                    }
                    if (!in_array('data_abertura', $fields)) {
                        $db->query("ALTER TABLE `{$os_table}` ADD `data_abertura` DATE NULL AFTER `status`");
                    }
                    if (!in_array('data_fechamento', $fields)) {
                        $db->query("ALTER TABLE `{$os_table}` ADD `data_fechamento` DATE NULL AFTER `data_abertura`");
                    }
                    if (!in_array('contract_id', $fields)) {
                        $db->query("ALTER TABLE `{$os_table}` ADD `contract_id` INT(11) NULL AFTER `task_id`");
                    }
                }
            } catch (\Throwable $e) {
                // ignore upgrade errors to avoid fatal; listing will fallback
            }

            // Ensure upgrade for os_services_items (service_id)
            try {
                $items_table = $db->prefixTable('os_services_items');
                $ifields = $db->getFieldNames($items_table);
                if (is_array($ifields)) {
                    if (!in_array('service_id', $ifields)) {
                        $db->query("ALTER TABLE `{$items_table}` ADD `service_id` INT(11) NULL AFTER `os_id`");
                    }
                }
            } catch (\Throwable $e) {
                // ignore
            }

            // os_products_items upgrade
            try {
                $p_table = $db->prefixTable('os_products_items');
                $pf = $db->getFieldNames($p_table);
                if (is_array($pf)) {
                    if (!in_array('product_id', $pf)) {
                        $db->query("ALTER TABLE `{$p_table}` ADD `product_id` INT(11) NULL AFTER `os_id`");
                    }
                }
            } catch (\Throwable $e) {}

            // os_atendimentos upgrade: ensure files column
            try {
                $a_table = $db->prefixTable('os_atendimentos');
                $af = $db->getFieldNames($a_table);
                if (is_array($af) && !in_array('files', $af)) {
                    $db->query("ALTER TABLE `{$a_table}` ADD `files` TEXT NULL AFTER `end_datetime`");
                }
            } catch (\Throwable $e) {}

            // os_files upgrade: ensure original_file_name column
            try {
                $f_table = $db->prefixTable('os_files');
                $ff = $db->getFieldNames($f_table);
                if (is_array($ff) && !in_array('original_file_name', $ff)) {
                    $db->query("ALTER TABLE `{$f_table}` ADD `original_file_name` VARCHAR(255) NULL AFTER `file_name`");
                }
            } catch (\Throwable $e) {}
        } catch (\Throwable $e) {
            // ignore; controllers will handle empty datasets gracefully
        }
    }

    public function index()
    {
        $this->ensure_tables();
        $view_data = [];
        $dropdown_list = new Dropdown_list($this);
        $view_data['clients_dropdown'] = $dropdown_list->get_clients_id_and_text_dropdown();
        // Técnicos: lista completa, ordenada alfabeticamente (sem exigir digitação)
        $db = db_connect('default');
        $users_table = $db->prefixTable('users');
        $rows = $db->table($users_table)
            ->select("id, CONCAT(first_name, ' ', last_name) AS name", false)
            ->where('user_type', 'staff')
            ->where('status', 'active')
            ->where('deleted', 0)
            ->orderBy('first_name', 'ASC')
            ->orderBy('last_name', 'ASC')
            ->get()->getResult();
        $tech_list = [[ 'id' => '', 'text' => '-' ]];
        foreach ($rows as $r) { $tech_list[] = [ 'id' => (int)$r->id, 'text' => $r->name ]; }
        $view_data['technicians_dropdown'] = json_encode($tech_list);
        // Tipos
        $Tipos = model('OrdemServico\\Models\\OsTipos_model');
        $tipos = [[ 'id' => '', 'text' => '-' ]];
        $tipos_rs = $Tipos->get_all();
        if ($tipos_rs && method_exists($tipos_rs, 'getResult')) {
            foreach ($tipos_rs->getResult() as $t) {
                $tipos[] = ['id' => (int)$t->id, 'text' => ($t->title ?: ('Tipo #'.$t->id))];
            }
        }
        $view_data['tipos_dropdown'] = json_encode($tipos);

        // Motivos
        $Motivos = model('OrdemServico\\Models\\OsMotivos_model');
        $motivos = [[ 'id' => '', 'text' => '-' ]];
        $motivos_rs = $Motivos->get_all();
        if ($motivos_rs && method_exists($motivos_rs, 'getResult')) {
            foreach ($motivos_rs->getResult() as $m) {
                $motivos[] = ['id' => (int)$m->id, 'text' => ($m->title ?: ('Motivo #'.$m->id))];
            }
        }
        $view_data['motivos_dropdown'] = json_encode($motivos);

        return $this->template->rander('OrdemServico\\Views\\list', $view_data);
    }

    public function view($id = 0)
    {
        $this->ensure_tables();
        $id = (int)$id;
        if (!$id) { show_404(); }
        $rs = $this->OrdemServico_model->get_details(['id' => $id]);
        $os = $rs && method_exists($rs, 'getRow') ? $rs->getRow() : null;
        if (!$os) { show_404(); }

        $Clients = model('App\\Models\\Clients_model');
        $Users = model('App\\Models\\Users_model');
        $client = $Clients->get_one($os->cliente_id);
        $creator = $Users->get_one((int)($os->created_by ?? 0));
        $tech = $Users->get_one($os->tecnico_id);

        $view_data = [];
        $view_data['os'] = $os;
        $view_data['client'] = $client;
        $view_data['creator'] = $creator;
        $view_data['tech'] = $tech;
        $view_data['os_id'] = $id;

        // Load comments like project comments section
        try {
            $Comments = model('OrdemServico\\Models\\OsComments_model');
            $comments = [];
            $crs = $Comments->get_all_where(['deleted' => 0, 'os_id' => $id])->getResult();
            foreach ($crs as $c) {
                $u = $Users->get_one($c->user_id);
                $c->created_by = $c->user_id;
                $c->created_by_user = trim(($u->first_name ?: '') . ' ' . ($u->last_name ?: '')) ?: ('#' . $c->user_id);
                $c->created_by_avatar = $u->image ?? '';
                $c->user_type = 'staff';
                $comments[] = $c;
            }
            $view_data['comments'] = $comments;
        } catch (\Throwable $e) {
            $view_data['comments'] = [];
        }

        return $this->template->rander('OrdemServico\\Views\\view', $view_data);
    }

    public function close()
    {
        $this->ensure_tables();
        $id = (int)$this->request->getPost('id');
        if (!$id) { return $this->response->setJSON(['success' => false]); }
        $ok = $this->OrdemServico_model->save_from_post(['status' => 'fechada'], $id);
        if ($ok === false) { return $this->response->setJSON(['success' => false]); }
        return $this->response->setJSON(['success' => true, 'message' => app_lang('record_saved') ?? 'saved']);
    }

    public function comment_save()
    {
        $this->ensure_tables();
        $Comments = model('OrdemServico\\Models\\OsComments_model');
        $id = (int)$this->request->getPost('id');
        $os_id = (int)$this->request->getPost('os_id');
        $comment = trim((string)$this->request->getPost('comment'));
        if (!$os_id || $comment === '') {
            return $this->response->setJSON(['success' => false, 'message' => app_lang('field_required')]);
        }
        $data = [
            'os_id' => $os_id,
            'user_id' => $this->login_user->id,
            'comment' => $comment,
            'updated_at' => get_my_local_time(),
        ];
        if (!$id) { $data['created_at'] = get_my_local_time(); }
        $ok = $Comments->ci_save($data, $id);
        if ($ok === false) { return $this->response->setJSON(['success' => false]); }

        // Build single comment row HTML similar to project comments
        $save_id = $id ?: (is_int($ok) ? $ok : 0);
        if (!$save_id) { $save_id = db_connect('default')->insertID(); }
        $row = $Comments->get_one($save_id);
        $Users = model('App\\Models\\Users_model');
        $u = $Users->get_one($row->user_id);
        $row->created_by = $row->user_id;
        $row->created_by_user = trim(($u->first_name ?: '') . ' ' . ($u->last_name ?: '')) ?: ('#' . $row->user_id);
        $row->created_by_avatar = $u->image ?? '';
        $row->user_type = 'staff';
        $html = view('OrdemServico\\Views\\comments\\comment_row', ['comment' => $row, 'login_user' => $this->login_user]);
        return $this->response->setJSON(['success' => true, 'message' => app_lang('record_saved') ?? 'saved', 'data' => $html]);
    }




  

    public function comment_delete()
    {
        $this->ensure_tables();
        $Comments = model('OrdemServico\\Models\\OsComments_model');
        $id = (int)$this->request->getPost('id');
        if (!$id) { return $this->response->setJSON(['success' => false]); }
        $ok = $Comments->delete($id);
        
        return $this->response->setJSON(['success' => $ok ? true : false]);
    }

    // -------------------- ATENDIMENTOS (OS appointments) --------------------

    public function os_atendimentos_list_data($os_id = null)
    {
        $this->ensure_tables();
        $os_id = $os_id ? (int)$os_id : (int)($this->request->getPost('os_id') ?? 0);
        $rows = [];
        if (!$os_id) { return $this->response->setJSON(['data' => $rows]); }

        $At = model('OrdemServico\\Models\\OsAtendimentos_model');
        $Amm = model('OrdemServico\\Models\\OsAtendimentos_members_model');
        $Users = model('App\\Models\\Users_model');

        $rs = $At->get_all_where([ 'os_id' => $os_id, 'deleted' => 0 ]);
        if ($rs && method_exists($rs, 'getResult')) {
            foreach ($rs->getResult() as $r) {
                // members avatars with tooltip names
                $membersHtml = '';
                $memberCount = 0;
                $mm = $Amm->get_all_where([ 'atendimento_id' => $r->id, 'deleted' => 0 ]);
                if ($mm && method_exists($mm, 'getResult')) {
                    foreach ($mm->getResult() as $m) {
                        $u = $Users->get_one($m->member_id);
                        $name = trim(($u->first_name ?: '') . ' ' . ($u->last_name ?: '')) ?: ('#'.$m->member_id);
                        $img = get_avatar($u->image ?? '');
                        $membersHtml .= "<span class='avatar avatar-xs mr5' data-bs-toggle='tooltip' title='" . esc($name) . "'><img src='" . esc($img) . "' alt='" . esc($name) . "'></span>";
                        $memberCount++;
                    }
                }
                if (!$membersHtml) { $membersHtml = '-'; }

                $ini = $r->start_datetime ? format_to_datetime($r->start_datetime) : '-';
                $fim = $r->end_datetime ? format_to_datetime($r->end_datetime) : '-';

                // duration in hours
                $duration = '-';
                if (!empty($r->start_datetime) && !empty($r->end_datetime)) {
                    $diff = strtotime($r->end_datetime) - strtotime($r->start_datetime);
                    if ($diff < 0) { $diff = 0; }
                    $hours = $diff / 3600;
                    $adjHours = $hours * (max(1, (int)$memberCount));
                    $duration = number_format($adjHours, 2, ',', '.') . ' h';
                }

                $edit = modal_anchor(get_uri('ordemservico/os_atendimentos_modal_form'), "<i data-feather='edit' class='icon-16'></i>", [ 'title' => 'Editar atendimento', 'data-post-id' => $r->id, 'data-post-os_id' => $os_id, 'class' => 'btn btn-sm btn-outline-secondary' ]);
                $del = js_anchor("<i data-feather='x' class='icon-16'></i>", [ 'title' => app_lang('delete'), 'class' => 'btn btn-sm btn-outline-danger delete', 'data-id' => $r->id, 'data-action-url' => get_uri('ordemservico/os_atendimentos_delete'), 'data-action' => 'delete-confirmation', 'data-success-callback' => 'reloadOsAtendimentos' ]);

                $rows[] = [
                    $membersHtml,
                    esc($ini),
                    esc($fim),
                    esc($duration),
                    esc($r->notes ?: ''),
                    $edit . $del
                ];
            }
        }
        return $this->response->setJSON(['data' => $rows]);
    }

    public function os_atendimentos_modal_form()
    {
        $this->ensure_tables();
        $id = (int)($this->request->getPost('id') ?? 0);
        $os_id = (int)($this->request->getPost('os_id') ?? 0);
        $At = model('OrdemServico\\Models\\OsAtendimentos_model');
        $Amm = model('OrdemServico\\Models\\OsAtendimentos_members_model');

        $view_data = [];
        $view_data['model_info'] = $id ? $At->get_one($id) : (object)['os_id' => $os_id];
        $view_data['os_id'] = $os_id;

        // build staff dropdown list (A?Z)
        $db = db_connect('default');
        $users_table = $db->prefixTable('users');
        $rows = $db->table($users_table)
            ->select("id, CONCAT(first_name, ' ', last_name) AS name", false)
            ->where('user_type', 'staff')
            ->where('status', 'active')
            ->where('deleted', 0)
            ->orderBy('first_name', 'ASC')
            ->orderBy('last_name', 'ASC')
            ->get()->getResult();
        $list = [];
        foreach ($rows as $r) { $list[] = [ 'id' => (int)$r->id, 'text' => $r->name ]; }
        $view_data['members_dropdown'] = json_encode($list);

        // selected members for edit
        $selected = [];
        if ($id) {
            $mm = $Amm->get_all_where(['atendimento_id' => $id, 'deleted' => 0]);
            if ($mm && method_exists($mm, 'getResult')) {
                foreach ($mm->getResult() as $m) { $selected[] = (int)$m->member_id; }
            }
        }
        $view_data['selected_members'] = json_encode($selected);

        return $this->template->view('OrdemServico\\Views\\os_atendimentos\\modal_form', $view_data);
    }

    public function os_atendimentos_save()
    {
        $this->ensure_tables();
        $At = model('OrdemServico\\Models\\OsAtendimentos_model');
        $Amm = model('OrdemServico\\Models\\OsAtendimentos_members_model');
        $id = (int)($this->request->getPost('id') ?? 0);
        $os_id = (int)($this->request->getPost('os_id') ?? 0);
       
        if (!$os_id) { return $this->response->setJSON(['success' => false, 'message' => 'OS inválida']); }

        $sd = trim((string)$this->request->getPost('start_date'));
        $st = trim((string)$this->request->getPost('start_time'));
        $ed = trim((string)$this->request->getPost('end_date'));
        $et = trim((string)$this->request->getPost('end_time'));
        $notes = trim((string)$this->request->getPost('notes'));

        $start = null; $end = null;
        if ($sd) { $start = $sd . ($st ? (' ' . $st) : ' 00:00:00'); }
        if ($ed) { $end = $ed . ($et ? (' ' . $et) : ' 00:00:00'); }

        // handle attachments uploaded via dropzone
        $target_path = get_setting('timeline_file_path');
        $files_data = move_files_from_temp_dir_to_permanent_dir($target_path, 'os_atendimento');

        $data = [
            'os_id' => $os_id,
            'start_datetime' => $start,
            'end_datetime' => $end,
            'notes' => $notes,
        ];
        if ($id) {
            // append new files to existing ones on edit
            try { $existing = (string)($At->get_one($id)->files ?? ''); } catch (\Throwable $e) { $existing = ''; }
            if ($files_data && $files_data !== 'a:0:{}') {
                if ($existing) {
                    $old = @unserialize($existing); if (!is_array($old)) { $old = []; }
                    $new = @unserialize($files_data); if (!is_array($new)) { $new = []; }
                    $data['files'] = serialize(array_merge($old, $new));
                } else {
                    $data['files'] = $files_data;
                }
            }
        } else {
            $data['files'] = $files_data;
        }
        if (!$id) { $data['created_by'] = $this->login_user->id ?? null; $data['created_at'] = get_my_local_time(); }
        $save_id = $At->ci_save($data, $id);
        if (!$save_id) { return $this->response->setJSON(['success' => false]); }
        if (!is_int($save_id)) { $save_id = $id ?: db_connect('default')->insertID(); }

        // sync members
        // Accept either comma-separated string, JSON array string, or array
        $membersRaw = $this->request->getPost('member_ids');

       
        if ($membersRaw === null) { $membersRaw = $this->request->getPost('member_ids[]'); }
        $members = [];
        if (is_array($membersRaw)) {
            $members = array_filter(array_map('intval', $membersRaw));
        } elseif (is_string($membersRaw)) {
            $raw = trim($membersRaw);
            if ($raw !== '') {
                // try JSON first
                if ($raw[0] === '[') {
                    $decoded = json_decode($raw, true);
                    if (is_array($decoded)) { $members = array_filter(array_map('intval', $decoded)); }
                }
                // fallback: comma separated
                if (!$members) { $members = array_filter(array_map('intval', explode(',', $raw))); }
            }
        }

        // delete existing
        $mm = $Amm->get_all_where(['atendimento_id' => $save_id]);

       
        if ($mm && method_exists($mm, 'getResult')) {
            foreach ($mm->getResult() as $m) { $Amm->delete($m->id); }
        }
       
        foreach ($members as $uid) {
            $row = [
                'atendimento_id' => $save_id,
                'member_id' => $uid,
                'created_at' => get_my_local_time()
            ];
            $Amm->ci_save($row);
        }
       

       

        return $this->response->setJSON(['success' => true, 'id' => $save_id, 'message' => app_lang('record_saved')]);
    }

    public function os_atendimentos_delete()
    {
        $this->ensure_tables();
        $id = (int)($this->request->getPost('id') ?? 0);
        if (!$id) { return $this->response->setJSON(['success' => false]); }
        $At = model('OrdemServico\\Models\\OsAtendimentos_model');
        $ok = $At->delete($id);
        return $this->response->setJSON(['success' => $ok ? true : false, 'message' => 'Atendimento excluido com sucesso']);
    }

    public function os_atendimentos_totals()
    {
        $this->ensure_tables();
        $os_id = (int)($this->request->getPost('os_id') ?? $this->request->getGet('os_id') ?? 0);
        if (!$os_id) { return $this->response->setJSON(['success' => false, 'total_hours' => 0, 'formatted' => '0,00 h']); }

        $At = model('OrdemServico\\Models\\OsAtendimentos_model');
        $totalSeconds = 0;
        $rs = $At->get_all_where(['os_id' => $os_id, 'deleted' => 0]);
        if ($rs && method_exists($rs, 'getResult')) {
            foreach ($rs->getResult() as $r) {
                if (!empty($r->start_datetime) && !empty($r->end_datetime)) {
                    $diff = strtotime($r->end_datetime) - strtotime($r->start_datetime);
                    if ($diff > 0) { $totalSeconds += $diff; }
                }
            }
        }
        $hours = $totalSeconds / 3600.0;
        $formatted = number_format($hours, 2, ',', '.') . ' h';
        return $this->response->setJSON(['success' => true, 'total_hours' => $hours, 'formatted' => $formatted]);
    }

    public function modal_form()
    {
        $this->ensure_tables();
        $view_data = [];
        $id = (int)($this->request->getPost('id') ?? 0);
        try {
            $view_data['model_info'] = $this->OrdemServico_model->get_one($id);
        } catch (\Throwable $e) {
            $view_data['model_info'] = (object)[];
        }
        $dropdown_list = new Dropdown_list($this);
        $view_data['clients_dropdown'] = $dropdown_list->get_clients_id_and_text_dropdown();
        $db = db_connect('default');
        $users_table = $db->prefixTable('users');
        $rows = $db->table($users_table)
            ->select("id, CONCAT(first_name, ' ', last_name) AS name", false)
            ->where('user_type', 'staff')
            ->where('status', 'active')
            ->where('deleted', 0)
            ->orderBy('first_name', 'ASC')
            ->orderBy('last_name', 'ASC')
            ->get()->getResult();
        $tech_list = [[ 'id' => '', 'text' => '-' ]];
        foreach ($rows as $r) { $tech_list[] = [ 'id' => (int)$r->id, 'text' => $r->name ]; }
        $view_data['technicians_dropdown'] = json_encode($tech_list);
        // Tipos
        $Tipos = model('OrdemServico\\Models\\OsTipos_model');
        $tipos = [[ 'id' => '', 'text' => '-' ]];
        $tipos_rs = $Tipos->get_all();
        if ($tipos_rs && method_exists($tipos_rs, 'getResult')) {
            foreach ($tipos_rs->getResult() as $t) {
                $tipos[] = ['id' => (int)$t->id, 'text' => ($t->title ?: ('Tipo #'.$t->id))];
            }
        }
        $view_data['tipos_dropdown'] = json_encode($tipos);

        // Motivos
        $Motivos = model('OrdemServico\\Models\\OsMotivos_model');
        $motivos = [[ 'id' => '', 'text' => '-' ]];
        $motivos_rs = $Motivos->get_all();
        if ($motivos_rs && method_exists($motivos_rs, 'getResult')) {
            foreach ($motivos_rs->getResult() as $m) {
                $motivos[] = ['id' => (int)$m->id, 'text' => ($m->title ?: ('Motivo #'.$m->id))];
            }
        }
        $view_data['motivos_dropdown'] = json_encode($motivos);

        return $this->template->view('OrdemServico\\Views\\modal_form', $view_data);
    }

    public function list_data()
    {
        $this->ensure_tables();
        $result = $this->OrdemServico_model->get_details();
        $rows = [];
        if (!$result || !method_exists($result, 'getResult')) {
            // fallback to minimal dataset if something failed inside model
            $result = $this->OrdemServico_model->get_all();
            foreach ($result->getResult() as $r) {
                $edit = modal_anchor(get_uri('ordemservico/modal_form'), "<i data-feather='edit' class='icon-16'></i>", [
                    'title' => 'Editar OS', 'data-post-id' => $r->id, 'class' => 'btn btn-sm btn-outline-secondary'] );
                $delete = js_anchor("<i data-feather='x' class='icon-16'></i>", [
                    'title' => app_lang('delete'), 'class' => 'btn btn-sm btn-outline-danger delete',
                    'data-id' => $r->id,
                    'data-action-url' => get_uri('ordemservico/delete'),
                    'data-action' => 'delete-confirmation'
                ]);
                $abertura = $r->data_abertura ? date('d/m/Y', strtotime($r->data_abertura)) : '';
                $fechamento = $r->data_fechamento ? date('d/m/Y', strtotime($r->data_fechamento)) : '';
                $rows[] = [
                    $r->id,
                    $r->cliente_id,
                    $r->tecnico_id,
                    $r->status,
                    $abertura,
                    $fechamento,
                    '',
                    '',
                    $edit . $delete
                ];
            }
            return $this->response->setJSON(['data' => $rows]);
        }

        foreach ($result->getResult() as $r) {
            $edit = modal_anchor(get_uri('ordemservico/modal_form'), "<i data-feather='edit' class='icon-16'></i>", [
                'title' => 'Editar OS', 'data-post-id' => $r->id, 'class' => 'btn btn-sm btn-outline-secondary'] );
            $delete = js_anchor("<i data-feather='x' class='icon-16'></i>", [
                'title' => app_lang('delete'), 'class' => 'btn btn-sm btn-outline-danger delete',
                'data-id' => $r->id,
                'data-action-url' => get_uri('ordemservico/delete'),
                'data-action' => 'delete-confirmation'
            ]);
            $abertura = $r->data_abertura ? date('d/m/Y', strtotime($r->data_abertura)) : '';
            $fechamento = $r->data_fechamento ? date('d/m/Y', strtotime($r->data_fechamento)) : '';
            $rows[] = [
                anchor(get_uri('ordemservico/view/' . $r->id), $r->id),
                esc($r->titulo ?? ''),
                ($r->client_name ?: $r->cliente_id),
                ($r->tech_name ?: $r->tecnico_id),
                $r->status,
                $abertura,
                $fechamento,
                ($r->tipo_title ?: ''),
                ($r->motivo_title ?: ''),
                $edit . $delete
            ];
        }
        return $this->response->setJSON([ 'data' => $rows ]);
    }

    public function save()
    {
        $this->ensure_tables();
        $id = (int)$this->request->getPost('id');
        // Only consume fields actually present in the modal
        $payload = [
            'titulo'     => $this->request->getPost('titulo'),
            'cliente_id' => get_only_numeric_value($this->request->getPost('cliente_id')),
            'tecnico_id' => get_only_numeric_value($this->request->getPost('tecnico_id')),
            'status'     => $this->request->getPost('status'),
            'descricao'  => $this->request->getPost('descricao'),
            'tipo_id'    => get_only_numeric_value($this->request->getPost('tipo_id')),
            'motivo_id'  => get_only_numeric_value($this->request->getPost('motivo_id')),
        ];
        if (!$id) {
            $payload['created_by'] = $this->login_user->id;
        }

        // Server-side required fields: Cliente, Tipo, Motivo
        if (!$payload['cliente_id'] || !$payload['tipo_id'] || !$payload['motivo_id']) {
            return $this->response->setJSON(['success' => false, 'message' => app_lang('field_required')]);
        }

        $ok = $this->OrdemServico_model->save_from_post($payload, $id);

        if ($ok === false) {
            $err = '';
            try { $err = db_connect('default')->error()['message'] ?? ''; } catch (\Throwable $e) {}
            return $this->response->setJSON(['success' => false, 'message' => $err ?: 'save_failed']);
        }

        

        // Build single row for appTable newData
        $save_id = $id ?: (is_int($ok) ? $ok : 0);

       

        if (!$save_id) {
            // fallback to fetch last inserted id for current user
            $row = db_connect('default')->table(db_connect('default')->prefixTable('os_ordens'))
                ->select('id')->where('created_by', $this->login_user->id)
                ->orderBy('id', 'DESC')->get(1)->getRow();
            $save_id = $row ? (int)$row->id : 0;
        }

     
      
        // Compose single row through model (SQL in model)
        $rs = $this->OrdemServico_model->get_details(['id' => $save_id]);

 

        $os = $rs && method_exists($rs, 'getRow') ? $rs->getRow() : null;
        if (!$os) {
            return $this->response->setJSON(['success' => true, 'id' => $save_id, 'message' => app_lang('record_saved') ?? 'saved']);
        }
        $edit = modal_anchor(get_uri('ordemservico/modal_form'), "<i data-feather='edit' class='icon-16'></i>", [
            'title' => 'Editar OS', 'data-post-id' => $os->id, 'class' => 'btn btn-sm btn-outline-secondary'] );
        $delete = js_anchor("<i data-feather='x' class='icon-16'></i>", [
            'title' => app_lang('delete'), 'class' => 'btn btn-sm btn-outline-danger delete',
            'data-id' => $os->id,
            'data-action-url' => get_uri('ordemservico/delete'),
            'data-action' => 'delete-confirmation'
        ]);
        $abertura = $os->data_abertura ? date('d/m/Y', strtotime($os->data_abertura)) : '';
        $fechamento = $os->data_fechamento ? date('d/m/Y', strtotime($os->data_fechamento)) : '';
        $row = [
            anchor(get_uri('ordemservico/view/' . $os->id), $os->id),
            esc($os->titulo ?? ''),
            ($os->client_name ?: $os->cliente_id),
            ($os->tech_name ?: $os->tecnico_id),
            $os->status,
            $abertura,
            $fechamento,
            ($os->tipo_title ?: ''),
            ($os->motivo_title ?: ''),
            $edit . $delete
        ];

        return $this->response->setJSON([ 'success' => true, 'id' => $save_id, 'data' => $row, 'message' => app_lang('record_saved') ?? 'saved' ]);
    }

    // Settings: Tipos
    public function types()
    {
        $this->ensure_tables();
        return $this->template->view('OrdemServico\\Views\\settings\\types');
    }

    public function types_list_data()
    {
        $this->ensure_tables();
        $Tipos = model('OrdemServico\\Models\\OsTipos_model');
        $rows = [];
        $rs = $Tipos->get_all();
        if ($rs && method_exists($rs, 'getResult')) {
            foreach ($rs->getResult() as $t) {
                $rows[] = [
                    esc($t->title),
                    modal_anchor(get_uri('ordemservico/types_modal_form'), "<i data-feather='edit' class='icon-16'></i>", [ 'title' => 'Editar Tipo', 'data-post-id' => $t->id, 'class' => 'btn btn-sm btn-outline-secondary'])
                ];
            }
        }
        return $this->response->setJSON(['data' => $rows]);
    }

    public function types_modal_form()
    {
        $this->ensure_tables();
        $Tipos = model('OrdemServico\\Models\\OsTipos_model');
        $id = (int)($this->request->getPost('id') ?? 0);
        try {
            $view_data['model_info'] = $Tipos->get_one($id);
        } catch (\Throwable $e) {
            $view_data['model_info'] = (object)['id' => '', 'title' => ''];
        }
        return $this->template->view('OrdemServico\\Views\\settings\\types_modal_form', $view_data);
    }

    public function types_save()
    {
        $this->ensure_tables();
        $Tipos = model('OrdemServico\\Models\\OsTipos_model');
        $id = (int)$this->request->getPost('id');
        $data = ['title' => $this->request->getPost('title')];
        $ok = $Tipos->ci_save($data, $id);
        if (!$ok) { return $this->response->setJSON(['success' => false]); }
        $saved = $Tipos->get_one($id ? $id : db_connect('default')->insertID());
        $row = [ esc($saved->title), modal_anchor(get_uri('ordemservico/types_modal_form'), "<i data-feather='edit' class='icon-16'></i>", [ 'title' => 'Editar Tipo', 'data-post-id' => $saved->id, 'class' => 'btn btn-sm btn-outline-secondary']) ];
        return $this->response->setJSON(['success' => true, 'id' => (int)$saved->id, 'data' => $row, 'message' => app_lang('record_saved') ?? 'saved']);
    }

    // Settings: Motivos
    public function reasons()
    {
        $this->ensure_tables();
        return $this->template->view('OrdemServico\\Views\\settings\\reasons');
    }

    public function reasons_list_data()
    {
        $this->ensure_tables();
        $Motivos = model('OrdemServico\\Models\\OsMotivos_model');
        $rows = [];
        $rs = $Motivos->get_all();
        if ($rs && method_exists($rs, 'getResult')) {
            foreach ($rs->getResult() as $m) {
                $rows[] = [
                    esc($m->title),
                    modal_anchor(get_uri('ordemservico/reasons_modal_form'), "<i data-feather='edit' class='icon-16'></i>", [ 'title' => 'Editar Motivo', 'data-post-id' => $m->id, 'class' => 'btn btn-sm btn-outline-secondary'])
                ];
            }
        }
        return $this->response->setJSON(['data' => $rows]);
    }

    public function reasons_modal_form()
    {
        $this->ensure_tables();
        $Motivos = model('OrdemServico\\Models\\OsMotivos_model');
        $id = (int)($this->request->getPost('id') ?? 0);
        try {
            $view_data['model_info'] = $Motivos->get_one($id);
        } catch (\Throwable $e) {
            $view_data['model_info'] = (object)['id' => '', 'title' => ''];
        }
        return $this->template->view('OrdemServico\\Views\\settings\\reasons_modal_form', $view_data);
    }

    public function reasons_save()
    {
        $this->ensure_tables();
        $Motivos = model('OrdemServico\\Models\\OsMotivos_model');
        $id = (int)$this->request->getPost('id');
        $data = ['title' => $this->request->getPost('title')];
        $ok = $Motivos->ci_save($data, $id);
        if ($ok === false) { return $this->response->setJSON(['success' => false]); }
        $saved = $Motivos->get_one($id ? $id : db_connect('default')->insertID());
        $row = [ esc($saved->title), modal_anchor(get_uri('ordemservico/reasons_modal_form'), "<i data-feather='edit' class='icon-16'></i>", [ 'title' => 'Editar Motivo', 'data-post-id' => $saved->id, 'class' => 'btn btn-sm btn-outline-secondary']) ];
        return $this->response->setJSON(['success' => true, 'id' => (int)$saved->id, 'data' => $row, 'message' => app_lang('record_saved') ?? 'saved']);
    }

    public function delete()
    {
        $this->ensure_tables();
        $id = (int)$this->request->getPost('id');
        if (!$id) {
            return $this->response->setJSON(['success' => false, 'message' => app_lang('invalid_id') ?: 'invalid_id']);
        }
        $ok = $this->OrdemServico_model->delete($id);
        if ($ok) {
            return $this->response->setJSON(['success' => true, 'message' => app_lang('record_deleted') ?: 'deleted']);
        }
        return $this->response->setJSON(['success' => false, 'message' => app_lang('error_occurred') ?: 'error']);
    }

    // Técnicos (staff) para dropdown remoto
    public function technicians_search()
    {
        $term = trim($this->request->getPost('search') ?? '');
        $limit = (int)($this->request->getPost('limit') ?? 20);
        if ($limit <= 0 || $limit > 50) { $limit = 20; }

        $db = db_connect('default');
        $users_table = $db->prefixTable('users');
        $builder = $db->table($users_table)
            ->select("id, CONCAT(first_name, ' ', last_name) AS name", false)
            ->where('user_type', 'staff')
            ->where('status', 'active')
            ->where('deleted', 0)
            ->orderBy('first_name', 'ASC')
            ->limit($limit);
        if ($term !== '') {
            $builder->groupStart()->like('first_name', $term)->orLike('last_name', $term)->orLike('email', $term)->groupEnd();
        }
        $id = (int)($this->request->getPost('id') ?? 0);
        if ($id) {
            $row = $db->table($users_table)->select("id, CONCAT(first_name, ' ', last_name) AS name", false)->where('id', $id)->get(1)->getRow();
            return $row ? $this->response->setJSON([[ 'id' => (int)$row->id, 'text' => $row->name ]]) : $this->response->setJSON([]);
        }
        $items = [];
        foreach ($builder->get()->getResult() as $r) { $items[] = [ 'id' => (int)$r->id, 'text' => $r->name ]; }
        return $this->response->setJSON($items);
    }

    public function comments_html()
    {
        $this->ensure_tables();
        $os_id = (int)($this->request->getPost('os_id') ?? $this->request->getGet('os_id') ?? 0);
        if (!$os_id) {
            return $this->response->setBody('');
        }

        $Comments = model('OrdemServico\\Models\\OsComments_model');
        $Users = model('App\\Models\\Users_model');
        $comments = [];
        $rs = $Comments->get_all_where(['deleted' => 0, 'os_id' => $os_id])->getResult();
        foreach ($rs as $c) {
            $u = $Users->get_one($c->user_id);
            $c->created_by = $c->user_id;
            $c->created_by_user = trim(($u->first_name ?: '') . ' ' . ($u->last_name ?: '')) ?: ('#' . $c->user_id);
            $c->created_by_avatar = $u->image ?? '';
            $c->user_type = 'staff';
            $comments[] = $c;
        }

        $html = view('OrdemServico\\Views\\comments\\list', [
            'comments' => $comments,
            'login_user' => $this->login_user
        ]);

        return $this->response->setBody($html);
    }

    // Anexos da OS (agregados dos atendimentos)
    public function os_attachments_html()
    {
        $this->ensure_tables();
        $os_id = (int)($this->request->getPost('os_id') ?? $this->request->getGet('os_id') ?? 0);
        if (!$os_id) { return $this->response->setBody(''); }

        $At = model('OrdemServico\\Models\\OsAtendimentos_model');
        $timeline_file_path = get_setting('timeline_file_path');
        $items = [];
        $rs = $At->get_all_where(['os_id' => $os_id, 'deleted' => 0]);
        if ($rs && method_exists($rs, 'getResult')) {
            foreach ($rs->getResult() as $r) {
                if (!empty($r->files)) {
                    $arr = @unserialize($r->files);
                    if (is_array($arr) && count($arr)) {
                        foreach ($arr as $file) { $items[] = $file; }
                    }
                }
            }
        }

        // Build simple thumbnails grid
        ob_start();
        echo "<div class='row g-3'>";
        foreach ($items as $file) {
            $file_name = get_array_value($file, 'file_name');
            $thumb = get_source_url_of_file($file, $timeline_file_path, 'thumbnail');
            $url = get_source_url_of_file($file, $timeline_file_path, 'file');
            $is_image = is_viewable_image_file($file_name);
            echo "<div class='col-sm-3'>";
            echo "<div class='card p10 text-center'>";
            if ($is_image) {
                echo "<a href='" . esc($url) . "' target='_blank'><img src='" . esc($thumb) . "' class='img-fluid' alt='" . esc($file_name) . "' /></a>";
            } else {
                $icon = get_file_icon(strtolower(pathinfo($file_name, PATHINFO_EXTENSION)));
                echo "<a href='" . esc($url) . "' target='_blank'>" . $icon . "</a>";
            }
            echo "<div class='mt5 small text-truncate' title='" . esc($file_name) . "'>" . esc($file_name) . "</div>";
            echo "</div>";
            echo "</div>";
        }
        if (!count($items)) {
            echo "<div class='col-12 text-off'>Nenhum anexo encontrado.</div>";
        }
        echo "</div>";
        $html = ob_get_clean();
        return $this->response->setBody($html);
    }



    public function settings()
    {
        return $this->template->rander('OrdemServico\\Views\\settings\\index');
    }

    // -------------------- ARQUIVOS (similar a files de projetos) --------------------
    public function os_files_list_data($os_id)
    {
        $this->ensure_tables();
        $Files = model('OrdemServico\\Models\\OsFiles_model');
        $rs = $Files->get_details(['os_id' => (int)$os_id]);
        $rows = [];
        $base = get_setting('project_file_path');
        $base = rtrim($base, '/');
        $folder = $base . 'os_' . (int)$os_id . '/';
        if ($rs && method_exists($rs, 'getResult')) {
            foreach ($rs->getResult() as $f) {
                $ext = strtolower(pathinfo($f->file_name, PATHINFO_EXTENSION));
                $icon = get_file_icon($ext);
                $display_name = $f->original_file_name ?: $f->file_name;
                $name_html = "<a href='" . base_url($folder . $f->file_name) . "' target='_blank'>" . esc($display_name) . "</a>";
                $u = trim(($f->uploaded_by_name ?? ''));
                $rows[] = [
                    $name_html,
                    esc($f->description ?? ''),
                    $u ? esc($u) : '-',
                    convert_file_size($f->file_size ?? 0),
                    format_to_datetime($f->created_at),
                    js_anchor("<i data-feather='x' class='icon-16'></i>", [ 'title' => app_lang('delete'), 'class' => 'btn btn-sm btn-outline-danger delete', 'data-id' => $f->id, 'data-action-url' => get_uri('ordemservico/os_files_delete'), 'data-action' => 'delete-confirmation', 'data-success-callback' => 'reloadOsFiles' ])
                ];
            }
        }
        return $this->response->setJSON(['data' => $rows]);
    }

    public function os_files_modal_form()
    {
        $this->ensure_tables();
        $view_data = [];
        $view_data['os_id'] = (int)($this->request->getPost('os_id') ?? 0);
        return $this->template->view('OrdemServico\\Views\\os_files\\modal_form', $view_data);
    }

    public function os_files_save()
    {
        $this->ensure_tables();
        $Files = model('OrdemServico\\Models\\OsFiles_model');
        $os_id = (int)$this->request->getPost('os_id');
        if (!$os_id) { return $this->response->setJSON(['success' => false]); }

        $now = get_my_local_time();
        $base = rtrim(get_setting('project_file_path'), '/');
        $target_path = getcwd() . '/' . $base . 'os_' . $os_id . '/';
        // Use generic move helper (handles Dropzone arrays and manual uploads)
        $files_serialized = move_files_from_temp_dir_to_permanent_dir($target_path, 'os_file');
        $files_arr = @unserialize($files_serialized);
        $desc = $this->request->getPost('description');
        $success = false;
        $orig_names = $this->request->getPost('file_names');
        if (is_array($files_arr) && count($files_arr)) {
            foreach ($files_arr as $idx => $f) {
                $orig = is_array($orig_names) && array_key_exists($idx, $orig_names) ? $orig_names[$idx] : null;
                $data = [
                    'os_id' => $os_id,
                    'file_name' => get_array_value($f, 'file_name'),
                    'original_file_name' => $orig,
                    'file_id' => get_array_value($f, 'file_id'),
                    'service_type' => get_array_value($f, 'service_type'),
                    'description' => is_array($desc) ? get_array_value($desc, $idx) : $desc,
                    'file_size' => get_array_value($f, 'file_size'),
                    'created_at' => $now,
                    'uploaded_by' => $this->login_user->id
                ];
                $saved = $Files->ci_save($data);
                if ($saved) { $success = true; }
            }
        }
        if ($success) { return $this->response->setJSON(['success' => true, 'message' => app_lang('record_saved')]); }
        return $this->response->setJSON(['success' => false, 'message' => app_lang('error_occurred')]);
    }

    public function os_files_delete()
    {
        $this->ensure_tables();
        $id = (int)$this->request->getPost('id');
        if (!$id) { return $this->response->setJSON(['success' => false]); }
        $Files = model('OrdemServico\\Models\\OsFiles_model');
        $ok = $Files->delete($id);
        return $this->response->setJSON(['success' => $ok ? true : false, 'message'=>'Arquivo excluido com sucesso!']);
    }

    // OS Serviços: listagem por tipo
    public function os_services_list_data($os_id )
    {
        $this->ensure_tables();
 

     
        $tipo = $this->request->getPost('tipo'); // opcional: 'cobrado' | 'nao_cobrado'
        $Items = model('OrdemServico\\Models\\OsServices_items_model');
        $where = ['deleted' => 0, 'os_id' => $os_id];
        if ($tipo) { $where['tipo_cobranca'] = $tipo; }
        $rs = $Items->get_all_where($where)->getResult();
        $rows = [];
        foreach ($rs as $it) {
            $qtd = (float)$it->quantidade;
            $vu = (float)$it->valor_unitario;
            $desc = (float)$it->desconto;
            $line_total = $qtd * $vu;
            $total = ($it->tipo_cobranca === 'nao_cobrado') ? 0 : ($line_total - $desc);
            $tag = ($it->tipo_cobranca === 'nao_cobrado')
                ? "<span class='badge bg-secondary'>Sem Cobrança</span>"
                : "<span class='badge bg-success'>Cobrado</span>";
            $rows[] = [
                esc($it->descricao),
                number_format($qtd, 2),
                esc($it->unidade),
                to_currency($vu, 'R$'),
                to_currency($desc, 'R$'),
                to_currency($total, 'R$'),
                $tag,
                modal_anchor(get_uri('ordemservico/os_services_modal_form'), "<i data-feather='edit' class='icon-16'></i>", [ 'title' => 'Editar item', 'data-post-id' => $it->id, 'class' => 'btn btn-sm btn-outline-secondary']) .
                js_anchor("<i data-feather='x' class='icon-16'></i>", [ 'title' => app_lang('delete'), 'class' => 'btn btn-sm btn-outline-danger delete', 'data-id' => $it->id, 'data-action-url' => get_uri('ordemservico/os_services_delete'), 'data-action' => 'delete-confirmation', 'data-success-callback' => 'reloadOsItems'])
            ];
        }
       
        return $this->response->setJSON(['data' => $rows]);
    }

    public function os_services_modal_form()
    {
        $this->ensure_tables();
        $Items = model('OrdemServico\\Models\\OsServices_items_model');
        $id = (int)($this->request->getPost('id') ?? 0);
        $os_id = (int)($this->request->getPost('os_id') ?? 0);
        $view_data['model_info'] = $Items->get_one($id);
        if (!$id && $os_id) { $view_data['model_info']->os_id = $os_id; }

        // Catálogo de serviços cadastrados (os_servicos) para auto-preenchimento
        try {
            $Serv = model('OrdemServico\\Models\\OsServicos_model');
            $svc_rs = $Serv->get_all();
            $svc_list = [['id'=>'','text'=>'-']];
            $svc_lookup = [];
            if ($svc_rs && method_exists($svc_rs, 'getResult')) {
                foreach ($svc_rs->getResult() as $s) {
                    $svc_list[] = [
                        'id' => (int)$s->id,
                        'text' => trim($s->descricao) . ' - ' . to_currency((float)$s->valor_venda, 'R$')
                    ];
                    $svc_lookup[(int)$s->id] = [
                        'descricao' => $s->descricao,
                        'valor_venda' => (float)$s->valor_venda
                    ];
                }
            }
            $view_data['services_dropdown'] = json_encode($svc_list);
            $view_data['services_lookup'] = json_encode($svc_lookup);
        } catch (\Throwable $e) {
            $view_data['services_dropdown'] = json_encode([['id'=>'','text'=>'-']]);
            $view_data['services_lookup'] = json_encode(new \stdClass());
        }
        return $this->template->view('OrdemServico\\Views\\os_services\\modal_form', $view_data);
    }

    public function os_services_save()
    {
        $this->ensure_tables();
        $Items = model('OrdemServico\\Models\\OsServices_items_model');
        $id = (int)$this->request->getPost('id');
        $qtd = (float)unformat_currency($this->request->getPost('quantidade'));
        $vu = (float)unformat_currency($this->request->getPost('valor_unitario'));
        $desc = (float)unformat_currency($this->request->getPost('desconto'));
        $tipo_cobranca = $this->request->getPost('tipo_cobranca') ?: 'cobrado';
        $line_total = $qtd * $vu;
        if ($tipo_cobranca === 'nao_cobrado') {
            // aplicar desconto de 100%
            $desc = $line_total;
        }
        $total = $line_total - $desc;
        $data = [
            'os_id' => (int)$this->request->getPost('os_id'),
            'service_id' => get_only_numeric_value($this->request->getPost('service_id')),
            'descricao' => trim((string)$this->request->getPost('descricao')),
            'quantidade' => $qtd,
            'unidade' => $this->request->getPost('unidade') ?: 'UN',
            'valor_unitario' => $vu,
            'desconto' => $desc,
            'valor_total' => $total,
            'tipo_cobranca' => $tipo_cobranca,
            'updated_at' => get_my_local_time(),
        ];
        if (!$data['descricao']) { return $this->response->setJSON(['success'=>false,'message'=>app_lang('field_required')]); }
        if (!$id) { $data['created_at'] = get_my_local_time(); }
        $ok = $Items->ci_save($data, $id);
        if ($ok === false) { return $this->response->setJSON(['success'=>false]); }

        // Build row for table update
        $save_id = $id ?: (is_int($ok) ? $ok : db_connect('default')->insertID());
        $it = $Items->get_one($save_id);
        $tag = ($it->tipo_cobranca === 'nao_cobrado')
            ? "<span class='badge bg-secondary'>Sem Cobrança</span>"
            : "<span class='badge bg-success'>Cobrado</span>";
        $row = [
            esc($it->descricao),
            number_format((float)$it->quantidade, 2),
            esc($it->unidade),
            to_currency((float)$it->valor_unitario, 'R$'),
            to_currency((float)$it->desconto, 'R$'),
            to_currency(((float)$it->tipo_cobranca === 'nao_cobrado' ? 0 : ((float)$it->quantidade*(float)$it->valor_unitario - (float)$it->desconto)), 'R$'),
            $tag,
            modal_anchor(get_uri('ordemservico/os_services_modal_form'), "<i data-feather='edit' class='icon-16'></i>", [ 'title' => 'Editar item', 'data-post-id' => $it->id, 'class' => 'btn btn-sm btn-outline-secondary']) .
            js_anchor("<i data-feather='x' class='icon-16'></i>", [ 'title' => app_lang('delete'), 'class' => 'btn btn-sm btn-outline-danger delete', 'data-id' => $it->id, 'data-action-url' => get_uri('ordemservico/os_services_delete'), 'data-action' => 'delete-confirmation', 'data-success-callback' => 'reloadOsItems'])
        ];
        return $this->response->setJSON(['success'=>true,'id'=>$save_id,'data'=>$row,'message'=>'Serviço salvo com sucesso!']);
    }

    public function os_services_delete()
    {
        $this->ensure_tables();
        $Items = model('OrdemServico\\Models\\OsServices_items_model');
        $id = (int)$this->request->getPost('id');
        if (!$id) { return $this->response->setJSON(['success'=>false]); }
        $ok = $Items->delete($id);
        return $this->response->setJSON(['success'=>$ok?true:false, 'message'=>'Excluido com sucesso!']);
    }

    public function os_services_totals()
    {
        $this->ensure_tables();
        $Items = model('OrdemServico\\Models\\OsServices_items_model');
        $os_id = (int)$this->request->getPost('os_id');
        $rs = $Items->get_all_where(['deleted'=>0,'os_id'=>$os_id])->getResult();
        $sum_paid = 0; $sum_free = 0;
        foreach ($rs as $it) {
            $line_total = (float)$it->quantidade * (float)$it->valor_unitario;
            $total = ($it->tipo_cobranca === 'nao_cobrado') ? 0 : ($line_total - (float)$it->desconto);
            if ($it->tipo_cobranca === 'nao_cobrado') { $sum_free += 0; } else { $sum_paid += $total; }
        }
        $total_geral = $sum_paid + 0; // nao_cobrado sempre 0
        return $this->response->setJSON([
            'total_geral' => $total_geral,
            'formatted' => [
                'total_geral' => to_currency($total_geral, 'R$'),
            ]
        ]);
    }

    // Produtos: listagem, salvar, excluir e totais (espelho de serviços) usando itens do Rise
    public function os_products_list_data( $os_id)
    {
        $this->ensure_tables();
      
        $Items = model('OrdemServico\\Models\\OsProducts_items_model');
        $rs = $Items->get_all_where(['deleted'=>0,'os_id'=>$os_id])->getResult();
        $rows = [];
        foreach ($rs as $it) {
            $qtd=(float)$it->quantidade; $vu=(float)$it->valor_unitario; $desc=(float)$it->desconto;
            $line_total = $qtd*$vu; $total = ($it->tipo_cobranca==='nao_cobrado')?0:($line_total-$desc);
            $tag = ($it->tipo_cobranca==='nao_cobrado')?"<span class='badge bg-secondary'>Sem Cobrança</span>":"<span class='badge bg-success'>Cobrado</span>";
            $rows[] = [
                esc($it->descricao),
                number_format($qtd,2),
                esc($it->unidade),
                to_currency($vu,'R$'),
                to_currency($desc,'R$'),
                to_currency($total,'R$'),
                $tag,
                modal_anchor(get_uri('ordemservico/os_products_modal_form'), "<i data-feather='edit' class='icon-16'></i>", [ 'title' => 'Editar item', 'data-post-id' => $it->id, 'class' => 'btn btn-sm btn-outline-secondary']) .
                js_anchor("<i data-feather='x' class='icon-16'></i>", [ 'title' => app_lang('delete'), 'class' => 'btn btn-sm btn-outline-danger delete', 'data-id' => $it->id, 'data-action-url' => get_uri('ordemservico/os_products_delete'), 'data-action' => 'delete-confirmation', 'data-success-callback' => 'reloadOsItems'])
            ];
        }
        return $this->response->setJSON(['data'=>$rows]);
    }

    public function os_products_modal_form()
    {
        $this->ensure_tables();
        $Items = model('OrdemServico\\Models\\OsProducts_items_model');
        $id=(int)($this->request->getPost('id')??0); $os_id=(int)($this->request->getPost('os_id')??0);
        $view_data['model_info']=$Items->get_one($id);
        if(!$id && $os_id){ $view_data['model_info']->os_id=$os_id; }

        // Build full products dropdown (alphabetical) from Rise items library
        try {
            $db = db_connect('default');
            $items_table = $db->prefixTable('items');
            $res = $db->table($items_table)->select('id, title, unit_type, rate')->where('deleted', 0)->orderBy('title', 'ASC')->get()->getResult();
            $list = [['id'=>'','text'=>'-']];
            $lookup = [];
            foreach($res as $r){
                $list[] = ['id'=>(int)$r->id,'text'=>$r->title];
                $lookup[(int)$r->id] = [
                    'title' => $r->title,
                    'unit_type' => $r->unit_type ?: 'UN',
                    'rate' => (string)to_decimal_format($r->rate)
                ];
            }
            $view_data['products_dropdown'] = json_encode($list);
            $view_data['products_lookup'] = json_encode($lookup);
        } catch (\Throwable $e) {
            $view_data['products_dropdown'] = json_encode([['id'=>'','text'=>'-']]);
            $view_data['products_lookup'] = json_encode(new \stdClass());
        }

        //return var_dump($view_data);
        return $this->template->view('OrdemServico\\Views\\os_products\\modal_form',$view_data);
    }

    public function os_products_save()
    {
        $this->ensure_tables();
        $Items = model('OrdemServico\\Models\\OsProducts_items_model');
        $id=(int)$this->request->getPost('id');
        $qtd=(float)unformat_currency($this->request->getPost('quantidade'));
        $vu=(float)unformat_currency($this->request->getPost('valor_unitario'));
        $desc=(float)unformat_currency($this->request->getPost('desconto'));
        $tipo=$this->request->getPost('tipo_cobranca')?:'cobrado';
        $line=$qtd*$vu; if($tipo==='nao_cobrado'){ $desc=$line; }
        $data=[
            'os_id'=>(int)$this->request->getPost('os_id'),
            'product_id'=>get_only_numeric_value($this->request->getPost('product_id')),
            'descricao'=>trim((string)$this->request->getPost('descricao')),
            'quantidade'=>$qtd,
            'unidade'=>$this->request->getPost('unidade')?:'UN',
            'valor_unitario'=>$vu,
            'desconto'=>$desc,
            'valor_total'=>$line-$desc,
            'tipo_cobranca'=>$tipo,
            'updated_at'=>get_my_local_time(),
        ];
        if(!$data['descricao']){ return $this->response->setJSON(['success'=>false,'message'=>app_lang('field_required')]); }
        if(!$id){ $data['created_at']=get_my_local_time(); }
        $ok=$Items->ci_save($data,$id); if($ok===false){ return $this->response->setJSON(['success'=>false]); }
        $save_id=$id?: (is_int($ok)?$ok:db_connect('default')->insertID());
        $it=$Items->get_one($save_id);
        $tag=($it->tipo_cobranca==='nao_cobrado')?"<span class='badge bg-secondary'>Sem Cobrança</span>":"<span class='badge bg-success'>Cobrado</span>";
        $row=[
            esc($it->descricao),
            number_format((float)$it->quantidade,2),
            esc($it->unidade),
            to_currency((float)$it->valor_unitario,'R$'),
            to_currency((float)$it->desconto,'R$'),
            to_currency(($it->tipo_cobranca==='nao_cobrado'?0:((float)$it->quantidade*(float)$it->valor_unitario-(float)$it->desconto)),'R$'),
            $tag,
            modal_anchor(get_uri('ordemservico/os_products_modal_form'), "<i data-feather='edit' class='icon-16'></i>", [ 'title' => 'Editar item', 'data-post-id' => $it->id, 'class' => 'btn btn-sm btn-outline-secondary']) .
            js_anchor("<i data-feather='x' class='icon-16'></i>", [ 'title' => app_lang('delete'), 'class' => 'btn btn-sm btn-outline-danger delete', 'data-id' => $it->id, 'data-action-url' => get_uri('ordemservico/os_products_delete'), 'data-action' => 'delete-confirmation', 'data-success-callback' => 'reloadOsItems'])
        ];
        return $this->response->setJSON(['success'=>true,'id'=>$save_id,'data'=>$row,'message'=>'Produto salvo com sucesso!']);
    }

    public function os_products_delete()
    {
        $this->ensure_tables();
        $Items = model('OrdemServico\\Models\\OsProducts_items_model');
        $id=(int)$this->request->getPost('id'); if(!$id){ return $this->response->setJSON(['success'=>false]); }
        $ok=$Items->delete($id); return $this->response->setJSON(['success'=>$ok?true:false]);
    }

    public function os_products_totals()
    {
        $this->ensure_tables();
        $Items = model('OrdemServico\\Models\\OsProducts_items_model');
        $os_id=(int)$this->request->getPost('os_id');
        $rs=$Items->get_all_where(['deleted'=>0,'os_id'=>$os_id])->getResult();
        $sum=0; foreach($rs as $it){ $line=(float)$it->quantidade*(float)$it->valor_unitario; $sum += ($it->tipo_cobranca==='nao_cobrado')?0:($line-(float)$it->desconto); }
        return $this->response->setJSON(['total_geral'=>$sum,'formatted'=>['total_geral'=>to_currency($sum,'R$')]]);
    }

    // Catálogo: obter informação atual do serviço selecionado (valor/descrição) direto do banco
    public function service_info()
    {
        $this->ensure_tables();
        $id = (int)$this->request->getPost('id');
        if (!$id) { return $this->response->setJSON(['success'=>false]); }
        $Serv = model('OrdemServico\\Models\\OsServicos_model');
        $s = $Serv->get_one($id);
        if (empty($s->id)) { return $this->response->setJSON(['success'=>false]); }
        return $this->response->setJSON([
            'success'=>true,
            'data'=>[
                'descricao' => $s->descricao,
                'valor_venda' => (float)$s->valor_venda,
                'valor_venda_formatted' => to_decimal_format($s->valor_venda)
            ]
        ]);
    }

    // Settings: Categorias
    public function categories()
    {
        $this->ensure_tables();
        return $this->template->view('OrdemServico\\Views\\settings\\categories');
    }

    public function categories_list_data()
    {
        $this->ensure_tables();
        $Cats = model('OrdemServico\\Models\\OsCategorias_model');
        $rows = [];
        $rs = $Cats->get_all();
        if ($rs && method_exists($rs, 'getResult')) {
            foreach ($rs->getResult() as $c) {
                $rows[] = [
                    esc($c->title),
                    modal_anchor(get_uri('ordemservico/categories_modal_form'), "<i data-feather='edit' class='icon-16'></i>", [ 'title' => 'Editar Categoria', 'data-post-id' => $c->id, 'class' => 'btn btn-sm btn-outline-secondary'])
                ];
            }
        }
        return $this->response->setJSON(['data' => $rows]);
    }

    public function categories_modal_form()
    {
        $this->ensure_tables();
        $Cats = model('OrdemServico\\Models\\OsCategorias_model');
        $id = (int)($this->request->getPost('id') ?? 0);
        try {
            $view_data['model_info'] = $Cats->get_one($id);
        } catch (\Throwable $e) {
            $view_data['model_info'] = (object)['id' => '', 'title' => ''];
        }
        return $this->template->view('OrdemServico\\Views\\settings\\categories_modal_form', $view_data);
    }

    public function categories_save()
    {
        $this->ensure_tables();
        $Cats = model('OrdemServico\\Models\\OsCategorias_model');
        $id = (int)$this->request->getPost('id');
        $data = ['title' => $this->request->getPost('title')];
        if (!$data['title']) { return $this->response->setJSON(['success'=>false,'message'=>app_lang('field_required')]); }
        $ok = $Cats->ci_save($data, $id);
        if ($ok === false) { return $this->response->setJSON(['success' => false]); }
        $saved = $Cats->get_one($id ? $id : db_connect('default')->insertID());
        $row = [ esc($saved->title), modal_anchor(get_uri('ordemservico/categories_modal_form'), "<i data-feather='edit' class='icon-16'></i>", [ 'title' => 'Editar Categoria', 'data-post-id' => $saved->id, 'class' => 'btn btn-sm btn-outline-secondary']) ];
        return $this->response->setJSON(['success' => true, 'id' => (int)$saved->id, 'data' => $row, 'message' => app_lang('record_saved') ?? 'saved']);
    }

    // Serviços catalogáveis
    public function services()
    {
        $this->ensure_tables();
        return $this->template->rander('OrdemServico\\Views\\services\\index');
    }

    public function services_list_data()
    {
        $this->ensure_tables();
        $Serv = model('OrdemServico\\Models\\OsServicos_model');
        // Build categories map id => title to display names instead of ids
        $cat_map = [];
        try {
            $Cats = model('OrdemServico\\Models\\OsCategorias_model');
            $crs = $Cats->get_all();
            if ($crs && method_exists($crs, 'getResult')) {
                foreach ($crs->getResult() as $c) { $cat_map[(int)$c->id] = $c->title; }
            }
        } catch (\Throwable $e) {}
        $rows = [];
        $rs = $Serv->get_all()->getResult();
        foreach ($rs as $s) {
            $tipo = $s->tipo === 'contrato' ? 'Contrato' : 'Ordem de Serviço';
            $cat_name = '';
            if (!empty($s->categoria_receita)) {
                $cat_name = $cat_map[(int)$s->categoria_receita] ?? (string)$s->categoria_receita;
            }
            $rows[] = [
                esc($s->descricao),
                $tipo,
                esc($cat_name),
                to_currency($s->custo, 'R$'),
                number_format((float)$s->margem, 2) . '%',
                to_currency($s->valor_venda, 'R$'),
                modal_anchor(get_uri('ordemservico/services_modal_form'), "<i data-feather='edit' class='icon-16'></i>", [ 'title' => 'Editar serviço', 'data-post-id' => $s->id, 'class' => 'btn btn-sm btn-outline-secondary']) .
                js_anchor("<i data-feather='x' class='icon-16'></i>", [ 'title' => app_lang('delete'), 'class' => 'btn btn-sm btn-outline-danger delete', 'data-id' => $s->id, 'data-action-url' => get_uri('ordemservico/services_delete'), 'data-action' => 'delete-confirmation'])
            ];
        }
        return $this->response->setJSON(['data' => $rows]);
    }

    public function services_modal_form()
    {
        $this->ensure_tables();
        $Serv = model('OrdemServico\\Models\\OsServicos_model');
        $id = (int)($this->request->getPost('id') ?? 0);
        $view_data['model_info'] = $Serv->get_one($id);

        // Categorias de OS (a partir de os_categorias)
        $cats = [['id' => '', 'text' => '-']];
        try {
            $Cats = model('OrdemServico\\Models\\OsCategorias_model');
            $rs = $Cats->get_all();
            if ($rs && method_exists($rs, 'getResult')) {
                foreach ($rs->getResult() as $c) { $cats[] = ['id' => (int)$c->id, 'text' => $c->title]; }
            }
        } catch (\Throwable $e) {}
        $view_data['categories_dropdown'] = json_encode($cats);

        return $this->template->view('OrdemServico\\Views\\services\\modal_form', $view_data);
    }

    public function services_save()
    {
        $this->ensure_tables();
        $Serv = model('OrdemServico\\Models\\OsServicos_model');
        $id = (int)$this->request->getPost('id');
        $data = [
            'tipo' => $this->request->getPost('tipo') ?: 'ordem_servico',
            'descricao' => trim((string)$this->request->getPost('descricao')),
            'categoria_receita' => get_only_numeric_value($this->request->getPost('categoria_receita')),
            'custo' => unformat_currency($this->request->getPost('custo')),
            'margem' => unformat_currency($this->request->getPost('margem')),
            'valor_venda' => unformat_currency($this->request->getPost('valor_venda')),
            'servico_locacao' => $this->request->getPost('servico_locacao') ? 1 : 0,
            'bloquear_inadimplencia' => $this->request->getPost('bloquear_inadimplencia') ? 1 : 0,
            'updated_at' => get_my_local_time(),
        ];
        if (!$data['descricao']) { return $this->response->setJSON(['success'=>false,'message'=>app_lang('field_required')]); }
        if (!$id) { $data['created_at'] = get_my_local_time(); }
        $ok = $Serv->ci_save($data, $id);
        if ($ok === false) { return $this->response->setJSON(['success'=>false]); }
        $save_id = $id ?: (is_int($ok) ? $ok : db_connect('default')->insertID());
        $s = $Serv->get_one($save_id);
        // Resolve category name for display
        $cat_name = '';
        try {
            if (!empty($s->categoria_receita)) {
                $Cats = model('OrdemServico\\Models\\OsCategorias_model');
                $cat = $Cats->get_one((int)$s->categoria_receita);
                if (!empty($cat->id)) { $cat_name = $cat->title; }
            }
        } catch (\Throwable $e) {}
        $row = [
            esc($s->descricao),
            ($s->tipo === 'contrato' ? 'Contrato' : 'Ordem de Serviço'),
            esc($cat_name),
            to_currency($s->custo, 'R$'),
            number_format((float)$s->margem, 2) . '%',
            to_currency($s->valor_venda, 'R$'),
            modal_anchor(get_uri('ordemservico/services_modal_form'), "<i data-feather='edit' class='icon-16'></i>", [ 'title' => 'Editar serviço', 'data-post-id' => $s->id, 'class' => 'btn btn-sm btn-outline-secondary']) .
            js_anchor("<i data-feather='x' class='icon-16'></i>", [ 'title' => app_lang('delete'), 'class' => 'btn btn-sm btn-outline-danger delete', 'data-id' => $s->id, 'data-action-url' => get_uri('ordemservico/services_delete'), 'data-action' => 'delete-confirmation'])
        ];
        return $this->response->setJSON(['success'=>true,'id'=>$save_id,'data'=>$row,'message'=>'Serviço salvo com sucesso!']);
    }

    public function services_delete()
    {
        $this->ensure_tables();
        $Serv = model('OrdemServico\\Models\\OsServicos_model');
        $id = (int)$this->request->getPost('id');
        if (!$id) { return $this->response->setJSON(['success'=>false]); }
        $ok = $Serv->delete($id);
        return $this->response->setJSON(['success'=>$ok?true:false]);
    }
}
