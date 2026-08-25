<?php

namespace Proposals\Controllers;

use App\Controllers\Security_Controller;

class Proposals extends Security_Controller
{
    public $Proposals_model;
    public $Proposals_module_settings_model;
    public $Proposal_sections_model;
    public $Proposal_items_model;
    public $Proposal_snapshots_model;
    public $Clients_model;
    public $Invoice_items_model;
    public $Notes_model;
    public $Note_category_model;
    public $Events_model;

    public function __construct()
    {
        parent::__construct(true);
        $this->access_only_team_members();
        $this->Proposals_model = model('Proposals\\Models\\Proposals_model');
        $this->Proposals_module_settings_model = model('Proposals\\Models\\Proposals_module_settings_model');
        $this->Proposal_sections_model = model('Proposals\\Models\\Proposal_sections_model');
        $this->Proposal_items_model = model('Proposals\\Models\\Proposal_items_model');
        $this->Proposal_snapshots_model = model('Proposals\\Models\\Proposal_snapshots_model');
        $this->Clients_model = model('App\\Models\\Clients_model');
        $this->Invoice_items_model = model('App\\Models\\Invoice_items_model');
        $this->Notes_model = model('App\\Models\\Notes_model');
        $this->Note_category_model = model('App\\Models\\Note_category_model');
        $this->Events_model = model('App\\Models\\Events_model');
    }

    public function index()
    {
        if (!$this->_has_view_permission()) {
            app_redirect('forbidden');
        }

        $view_data = array(
            "statuses_dropdown" => json_encode($this->_get_statuses_dropdown()),
            "statuses_kanban" => $this->_get_statuses(),
            "can_manage" => $this->_has_manage_permission()
        );

        return $this->template->rander('Proposals\\Views\\proposals\\index', $view_data);
    }

    public function kanban_data()
    {
        if (!$this->_has_view_permission()) {
            return $this->response->setJSON(['success' => false, 'message' => 'Permission denied']);
        }

        $search = $this->request->getGet('search');
        
        $options = array(
            "company_id" => $this->_get_company_id()
        );
        
        if ($search) {
            $options["search"] = $search;
        }

        $query = $this->Proposals_model->get_details($options);
        $proposals = ($query && method_exists($query, 'getResult')) ? $query->getResult() : array();

        $proposals_by_status = array();
        $counts = array();
        $totals = array();

        // Inicializar exatamente os mesmos status usados pelo cadastro/lista.
        foreach ($this->_get_statuses() as $status) {
            $status_key = (string) ($status->id ?? '');
            if ($status_key === '') {
                continue;
            }
            $proposals_by_status[$status_key] = array();
            $counts[$status_key] = 0;
            $totals[$status_key] = 0;
        }

        foreach ($proposals as $proposal) {
            // Buscar o status da proposal - pode ser 'status' ou 'status_id'
            $status_id = null;
            if (isset($proposal->status) && !empty($proposal->status)) {
                $status_id = $proposal->status;
            } elseif (isset($proposal->status_id) && !empty($proposal->status_id)) {
                $status_id = $proposal->status_id;
            } else {
                $status_id = 'draft';
            }
            
            // Normalizar status para string
            if (is_numeric($status_id)) {
                $status_id = 'draft';
            }
            
            if (!isset($proposals_by_status[$status_id])) {
                $proposals_by_status[$status_id] = array();
            }
            $proposal_total = (float) $this->Proposals_model->get_items_total($proposal->id);
            $proposals_by_status[$status_id][] = array(
                'id' => $proposal->id,
                'title' => $proposal->title,
                'client_name' => $proposal->client_company ?? ($proposal->client_name ?? ''),
                'total_sale' => $proposal_total,
                'total_sale_formatted' => to_currency($proposal_total)
            );
            $counts[$status_id] = ($counts[$status_id] ?? 0) + 1;
            $totals[$status_id] = ($totals[$status_id] ?? 0) + $proposal_total;
        }

        return $this->response->setJSON([
            'success' => true,
            'data' => [
                'proposals' => $proposals_by_status,
                'counts' => $counts,
                'totals' => $totals,
                'totals_formatted' => array_map(function ($total) {
                    return to_currency($total);
                }, $totals)
            ]
        ]);
    }

    public function save_notes()
    {
        if (!$this->_has_manage_permission()) {
            return $this->response->setJSON(['success' => false, 'message' => 'Permission denied']);
        }

        $id = $this->request->getPost('id');
        $notes = $this->request->getPost('notes');

        if (!$id) {
            return $this->response->setJSON(['success' => false, 'message' => 'Invalid parameters']);
        }

        $data = array('notes' => $notes);
        $result = $this->Proposals_model->ci_save($data, $id);

        if ($result) {
            return $this->response->setJSON(['success' => true, 'message' => 'Notes saved']);
        }

        return $this->response->setJSON(['success' => false, 'message' => 'Error saving']);
    }

    public function get_followup_events($proposal_id = 0)
    {
        if (!$this->_has_view_permission()) {
            return $this->response->setJSON(['success' => false, 'message' => 'Permission denied']);
        }

        $proposal_id = (int) $proposal_id;

        try {
            // Follow-ups use the native agenda/events table. This keeps the
            // proposal timeline and the calendar synchronized.
            $followups = $this->Events_model->get_details(array(
                'proposal_id' => $proposal_id,
                'type' => 'all'
            ))->getResult();
        } catch (Exception $e) {
            log_message('error', 'Error in get_followup_events: ' . $e->getMessage());
            return $this->response->setJSON(['success' => false, 'message' => app_lang('error_occurred')]);
        }

        $html = '';
        if (empty($followups)) {
            $html = '<p class="text-muted">' . app_lang('proposals_no_followup') . '</p>';
        } else {
            $html .= '<div class="table-responsive"><table class="table table-bordered">';
            $html .= '<thead><tr><th>' . app_lang('title') . '</th><th>' . app_lang('date') . '</th><th>' . app_lang('status') . '</th><th>' . app_lang('created_by') . '</th><th></th></tr></thead>';
            $html .= '<tbody>';
            foreach ($followups as $followup) {
                $is_reminder = ($followup->type ?? '') === 'reminder';
                $is_done = $is_reminder && in_array(($followup->reminder_status ?? ''), array('done', 'shown'), true);
                $is_rejected = !$is_reminder && !empty($followup->rejected_by);
                $is_confirmed = !$is_reminder && !empty($followup->confirmed_by);
                $status_class = $is_done ? 'bg-success' : ($is_rejected ? 'bg-danger' : ($is_confirmed ? 'bg-info' : 'bg-warning'));
                $status_label = $is_done ? app_lang('done') : ($is_rejected ? app_lang('rejected') : ($is_confirmed ? app_lang('confirmed') : app_lang('pending')));
                $event_datetime = trim(($followup->start_date ?? '') . ' ' . ($followup->start_time ?? ''));
                $event_id = encode_id($followup->id, 'event_id');
                
                $html .= '<tr>';
                $html .= '<td>' . esc($followup->title) . '</td>';
                $html .= '<td>' . ($event_datetime ? format_to_datetime($event_datetime) : '-') . '</td>';
                $html .= '<td><span class="badge ' . $status_class . '">' . $status_label . '</span></td>';
                $html .= '<td>' . esc($followup->created_by_name ?? '-') . '</td>';
                $html .= '<td class="text-center">';
                $html .= modal_anchor(get_uri('events/modal_form'), '<i data-feather="edit" class="icon-16"></i>', array(
                    'title' => app_lang('edit_event'),
                    'data-post-encrypted_event_id' => $event_id,
                    'data-post-proposal_id' => $proposal_id,
                    'class' => 'me-2'
                ));
                $html .= js_anchor('<i data-feather="trash-2" class="icon-16"></i>', array(
                    'title' => app_lang('delete_event'),
                    'data-action-url' => get_uri('events/delete'),
                    'data-encrypted_event_id' => $event_id,
                    'data-action' => 'delete-confirmation'
                ));
                $html .= '</td>';
                $html .= '</tr>';
            }
            $html .= '</tbody></table></div>';
        }

        $html .= '<script>if (typeof feather !== "undefined") { feather.replace(); }</script>';
        return $this->response->setJSON(['success' => true, 'html' => $html]);
    }

    public function followup_modal_form($proposal_id = 0)
    {
        if (!$this->_has_manage_permission()) {
            return $this->response->setJSON(['success' => false, 'message' => 'Permission denied']);
        }

        $proposal_id = (int) $proposal_id;
        
        $view_data['proposal_id'] = $proposal_id;
        
        return $this->template->view('Proposals\\Views\\proposals\\followup_modal_form', $view_data);
    }

    public function save_followup()
    {
        if (!$this->_has_manage_permission()) {
            return $this->response->setJSON(['success' => false, 'message' => 'Permission denied']);
        }

        $proposal_id = (int) $this->request->getPost('proposal_id');
        $title = $this->request->getPost('title');
        $description = $this->request->getPost('description');
        $event_date = $this->request->getPost('event_date');

        $db = db_connect('default');
        $followup_table = $db->prefixTable('proposal_followups');
        
        // Criar tabela se não existir
        if (!$db->tableExists($followup_table)) {
            $sql = "CREATE TABLE IF NOT EXISTS `{$followup_table}` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `proposal_id` INT(11) NOT NULL,
                `title` VARCHAR(255) NOT NULL,
                `description` TEXT,
                `event_date` DATETIME NOT NULL,
                `event_id` INT(11) DEFAULT NULL,
                `status` VARCHAR(20) DEFAULT 'pending',
                `created_by` INT(11) DEFAULT NULL,
                `created_at` DATETIME DEFAULT NULL,
                `deleted` TINYINT(1) DEFAULT 0,
                PRIMARY KEY (`id`),
                KEY `proposal_id` (`proposal_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
            $db->query($sql);
        }
        
        $data = array(
            'proposal_id' => $proposal_id,
            'title' => $title,
            'description' => $description,
            'event_date' => $event_date,
            'status' => 'pending',
            'created_by' => $this->login_user->id,
            'created_at' => get_current_utc_time()
        );
        
        $db->table($followup_table)->insert($data);
        
        return $this->response->setJSON(['success' => true, 'message' => 'Follow-up agendado']);
    }

    public function complete_followup($followup_id = 0)
    {
        if (!$this->_has_manage_permission()) {
            return $this->response->setJSON(['success' => false, 'message' => 'Permission denied']);
        }

        $followup_id = (int) $followup_id;
        $db = db_connect('default');
        $followup_table = $db->prefixTable('proposal_followups');
        
        $db->table($followup_table)->where('id', $followup_id)->update(['status' => 'completed']);
        
        return $this->response->setJSON(['success' => true, 'message' => 'Follow-up concluído']);
    }

    public function delete_followup($followup_id = 0)
    {
        if (!$this->_has_manage_permission()) {
            return $this->response->setJSON(['success' => false, 'message' => 'Permission denied']);
        }

        $followup_id = (int) $followup_id;
        $db = db_connect('default');
        $followup_table = $db->prefixTable('proposal_followups');
        
        $db->table($followup_table)->where('id', $followup_id)->update(['deleted' => 1]);
        
        return $this->response->setJSON(['success' => true, 'message' => 'Follow-up excluído']);
    }

    public function notes_list_data($proposal_id = 0)
    {
        if (!$this->_has_view_permission()) {
            return $this->response->setJSON(['success' => false, 'message' => 'Permission denied']);
        }

        $proposal_id = (int) $proposal_id;
        $db = db_connect('default');
        $notes_table = $db->prefixTable('notes');

        // Adicionar coluna proposal_id se não existir
        if (!$db->fieldExists('proposal_id', $notes_table)) {
            $db->query("ALTER TABLE `{$notes_table}` ADD `proposal_id` INT(11) DEFAULT NULL AFTER `client_id`");
        }

        $notes = $db->query("
            SELECT * FROM $notes_table 
            WHERE proposal_id = $proposal_id AND deleted = 0 
            ORDER BY created_at DESC
        ")->getResult();

        $result = [];
        foreach ($notes as $note) {
            $actions = modal_anchor(
                get_uri("propostas/note_modal_form/" . $proposal_id . "/" . $note->id),
                "<i data-feather='edit' class='icon-16'></i>",
                ['class' => 'edit', 'title' => app_lang('edit_note')]
            );
            $actions .= js_anchor(
                "<i data-feather='x' class='icon-16'></i>",
                [
                    'title' => app_lang('delete_note'),
                    'class' => 'delete',
                    'data-id' => $note->id,
                    'data-action-url' => get_uri('propostas/delete_note/' . $note->id),
                    'data-action' => 'delete-confirmation'
                ]
            );

            $result[] = array(
                format_to_datetime($note->created_at),
                $note->id,
                modal_anchor(
                    get_uri("propostas/note_modal_form/" . $proposal_id . "/" . $note->id),
                    $note->title,
                    ['title' => app_lang('note')]
                ),
                $note->is_public ? app_lang('yes') : app_lang('no'),
                $note->created_by,
                $actions
            );
        }

        return $this->response->setJSON(['data' => $result]);
    }

    public function note_modal_form($proposal_id = 0, $note_id = 0)
    {
        if (!$this->_has_manage_permission()) {
            return $this->response->setJSON(['success' => false, 'message' => 'Permission denied']);
        }

        // O botão de inclusão envia o proposal_id via POST; a edição também
        // pode receber os IDs pela URL.
        $proposal_id = (int) ($proposal_id ?: $this->request->getPost('proposal_id'));
        $note_id = (int) ($note_id ?: $this->request->getPost('note_id'));
        
        $db = db_connect('default');
        $notes_table = $db->prefixTable('notes');
        
        $note_info = $this->Notes_model->get_one($note_id);

        if (!$note_info) {
            return $this->response->setJSON(['success' => false, 'message' => 'Anotação não encontrada']);
        }

        $note_categories = $this->Note_category_model
            ->get_details(['user_id' => $this->login_user->id])
            ->getResult();
        $note_categories_dropdown = ['' => '- ' . app_lang('category') . ' -'];
        foreach ($note_categories as $note_category) {
            $note_categories_dropdown[$note_category->id] = $note_category->name;
        }
        
        $view_data['proposal_id'] = $proposal_id;
        $view_data['note_id'] = $note_id;
        $view_data['note_info'] = $note_info;
        // Os componentes padrão do modal (ex.: paleta de cores) usam
        // model_info, como no modal de notas dos projetos.
        $view_data['model_info'] = $note_info;
        $view_data['project_id'] = 0;
        $view_data['client_id'] = 0;
        $view_data['user_id'] = 0;
        $view_data['note_categories_dropdown'] = $note_categories_dropdown;
        $view_data['label_suggestions'] = $this->make_labels_dropdown('note', $note_info->labels, false);
        
        return $this->template->view('Proposals\\Views\\proposals\\note_modal_form', $view_data);
    }

    public function save_note()
    {
        if (!$this->_has_manage_permission()) {
            return $this->response->setJSON(['success' => false, 'message' => 'Permission denied']);
        }

        $proposal_id = (int) $this->request->getPost('proposal_id');
        $note_id = (int) $this->request->getPost('note_id');
        $title = $this->request->getPost('title');
        $description = $this->request->getPost('description');
        $is_public = $this->request->getPost('is_public') ? 1 : 0;

        $db = db_connect('default');
        $notes_table = $db->prefixTable('notes');

        if (!$db->tableExists($notes_table)) {
            return $this->response->setJSON(['success' => false, 'message' => 'Tabela de anotações não encontrada']);
        }

        if (!$db->fieldExists('proposal_id', $notes_table)) {
            $db->query("ALTER TABLE `{$notes_table}` ADD `proposal_id` INT(11) DEFAULT NULL AFTER `client_id`");
        }

        // Em uma edição, preserve o vínculo existente mesmo que o formulário
        // antigo não tenha enviado o proposal_id.
        if (!$proposal_id && $note_id) {
            $existing_note = $db->table($notes_table)
                ->select('proposal_id')
                ->where('id', $note_id)
                ->get()
                ->getRow();
            $proposal_id = (int) ($existing_note->proposal_id ?? 0);
        }

        if (!$proposal_id) {
            return $this->response->setJSON(['success' => false, 'message' => 'A proposta da anotação não foi informada']);
        }
        
        $target_path = get_setting('timeline_file_path');
        $files_data = move_files_from_temp_dir_to_permanent_dir($target_path, 'note');
        $new_files = unserialize($files_data);
        if (!is_array($new_files)) {
            $new_files = [];
        }

        $labels = $this->request->getPost('labels');
        validate_list_of_numbers($labels);

        $data = array(
            'proposal_id' => $proposal_id,
            'title' => $title,
            'description' => $description,
            'labels' => $labels,
            'color' => $this->request->getPost('color'),
            'project_id' => 0,
            'client_id' => 0,
            'user_id' => 0,
            'category_id' => $this->request->getPost('category_id') ?: 0,
            'is_public' => $is_public,
            'files' => serialize($new_files)
        );

        $data = clean_data($data);
        
        if ($note_id) {
            $note_info = $this->Notes_model->get_one($note_id);
            if (!$note_info || (int) ($note_info->proposal_id ?? 0) !== $proposal_id) {
                return $this->response->setJSON(['success' => false, 'message' => 'Anotação não pertence a esta proposta']);
            }
            $data['files'] = serialize(update_saved_files($target_path, $note_info->files, $new_files));
            $save_id = $this->Notes_model->ci_save($data, $note_id);
        } else {
            $data['created_by'] = $this->login_user->id;
            $data['created_at'] = get_current_utc_time();
            $save_id = $this->Notes_model->ci_save($data);
        }
        
        if (!$save_id) {
            return $this->response->setJSON(['success' => false, 'message' => app_lang('error_occurred')]);
        }

        return $this->response->setJSON(['success' => true, 'id' => $save_id, 'message' => app_lang('record_saved')]);
    }

    public function delete_note($note_id = 0)
    {
        if (!$this->_has_manage_permission()) {
            return $this->response->setJSON(['success' => false, 'message' => 'Permission denied']);
        }

        $note_id = (int) $note_id;
        $db = db_connect('default');
        $notes_table = $db->prefixTable('notes');
        
        $db->table($notes_table)->where('id', $note_id)->update(['deleted' => 1]);
        
        return $this->response->setJSON(['success' => true, 'message' => 'Note deleted']);
    }

    public function files_list_data($proposal_id = 0)
    {
        if (!$this->_has_view_permission()) {
            return $this->response->setJSON(['success' => false, 'message' => 'Permission denied']);
        }

        $proposal_id = (int) $proposal_id;
        $db = db_connect('default');
        $files_table = $this->_ensure_proposal_files_table($db);
        
        // Verificar se tabela existe
        if (!$db->tableExists($files_table)) {
            return $this->response->setJSON(['data' => []]);
        }
        
        // Adicionar coluna proposal_id se não existir
        if (!$db->fieldExists('proposal_id', $files_table)) {
            $db->query("ALTER TABLE `{$files_table}` ADD `proposal_id` INT(11) DEFAULT NULL");
        }

        $files = $db->query("
            SELECT * FROM $files_table 
            WHERE proposal_id = $proposal_id AND deleted = 0
            ORDER BY created_at DESC
        ")->getResult();

        $result = [];
        foreach ($files as $file) {
            $file_url = get_uri('propostas/download_file/' . $file->id);
            $result[] = array(
                format_to_datetime($file->created_at),
                $file->id,
                anchor($file_url, $file->file_name, ['target' => '_blank']),
                $file->file_size ? number_format($file->file_size / 1024, 2, ',', '.') . ' KB' : '-',
                $file->uploaded_by,
                '<div class="text-center">' . 
                js_anchor('<i data-feather="download" class="icon-16"></i>', array('title' => app_lang('download'), 'href' => $file_url)) .
                js_anchor('<i data-feather="trash-2" class="icon-16"></i>', array('title' => app_lang('delete'), 'data-action-url' => get_uri('propostas/delete_file/' . $file->id), 'data-action' => 'delete-confirmation')) .
                '</div>'
            );
        }

        return $this->response->setJSON(['data' => $result]);
    }

    public function file_modal_form($proposal_id = 0, $file_id = 0)
    {
        if (!$this->_has_manage_permission()) {
            return $this->response->setJSON(['success' => false, 'message' => 'Permission denied']);
        }

        $proposal_id = (int) ($proposal_id ?: $this->request->getPost('proposal_id'));
        $file_id = (int) $file_id;

        if (!$proposal_id) {
            return $this->response->setJSON(['success' => false, 'message' => 'A proposta não foi informada']);
        }
        
        $view_data['proposal_id'] = $proposal_id;
        $view_data['file_id'] = $file_id;
        
        return $this->template->view('Proposals\\Views\\proposals\\file_modal_form', $view_data);
    }

    public function save_file()
    {
        if (!$this->_has_manage_permission()) {
            return $this->response->setJSON(['success' => false, 'message' => 'Permission denied']);
        }

        $proposal_id = (int) $this->request->getPost('proposal_id');

        if (!$proposal_id) {
            return $this->response->setJSON(['success' => false, 'message' => 'A proposta não foi informada']);
        }
        
        // Processar upload
        $upload_file = get_array_value($_FILES, 'file');
        if (!$upload_file || $upload_file['error'] !== UPLOAD_ERR_OK) {
            return $this->response->setJSON(['success' => false, 'message' => 'Upload failed']);
        }

        $file_name = $upload_file['name'];
        $upload_path = 'proposals/' . $proposal_id . '/';
        $dir = getcwd() . '/private/uploads/' . $upload_path;
        
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $new_name = uniqid() . '_' . $file_name;
        $target = $dir . $new_name;
        
        if (move_uploaded_file($upload_file['tmp_name'], $target)) {
            $db = db_connect('default');
            $files_table = $this->_ensure_proposal_files_table($db);
            
            if (!$db->tableExists($files_table)) {
                $sql = "CREATE TABLE IF NOT EXISTS `{$files_table}` (
                    `id` INT(11) NOT NULL AUTO_INCREMENT,
                    `proposal_id` INT(11) NOT NULL,
                    `file_name` VARCHAR(255) NOT NULL,
                    `file_path` VARCHAR(500) NOT NULL,
                    `file_size` INT(11) DEFAULT 0,
                    `uploaded_by` INT(11) DEFAULT NULL,
                    `created_at` DATETIME DEFAULT NULL,
                    PRIMARY KEY (`id`),
                    KEY `proposal_id` (`proposal_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
                $db->query($sql);
            }

            if (!$db->fieldExists('proposal_id', $files_table)) {
                $db->query("ALTER TABLE `{$files_table}` ADD `proposal_id` INT(11) DEFAULT NULL");
            }
            
            $insert_data = array(
                'proposal_id' => $proposal_id,
                'file_name' => $file_name,
                'file_path' => $upload_path . $new_name,
                'file_size' => $upload_file['size'],
                'uploaded_by' => $this->login_user->id,
                'created_at' => get_current_utc_time()
            );
            
            $db->table($files_table)->insert($insert_data);
            
            return $this->response->setJSON(['success' => true, 'message' => 'File uploaded']);
        }

        return $this->response->setJSON(['success' => false, 'message' => 'Error saving file']);
    }

    public function delete_file($file_id = 0)
    {
        if (!$this->_has_manage_permission()) {
            return $this->response->setJSON(['success' => false, 'message' => 'Permission denied']);
        }

        $file_id = (int) $file_id;
        $db = db_connect('default');
        $files_table = $this->_ensure_proposal_files_table($db);
        
        $file = $db->query("SELECT * FROM $files_table WHERE id = $file_id")->getRow();
        
        if ($file) {
            // Deletar arquivo físico
            $file_path = getcwd() . '/private/uploads/' . $file->file_path;
            if (file_exists($file_path)) {
                unlink($file_path);
            }
            
            $db->table($files_table)->where('id', $file_id)->delete();
            
            return $this->response->setJSON(['success' => true, 'message' => 'File deleted']);
        }

        return $this->response->setJSON(['success' => false, 'message' => 'File not found']);
    }

    public function download_file($file_id = 0)
    {
        if (!$this->_has_view_permission()) {
            return $this->response->setStatusCode(403);
        }

        $file_id = (int) $file_id;
        $db = db_connect('default');
        $files_table = $this->_ensure_proposal_files_table($db);
        $file = $db->table($files_table)
            ->where('id', $file_id)
            ->where('deleted', 0)
            ->get()
            ->getRow();

        if (!$file || !$file->file_path) {
            return $this->response->setStatusCode(404);
        }

        $file_data = serialize([['file_name' => $file->file_path]]);
        return $this->download_app_files('private/uploads/', $file_data);
    }

    public function upload_file($proposal_id = 0)
    {
        if (!$this->_has_manage_permission()) {
            return $this->response->setJSON(['success' => false, 'message' => 'Permission denied']);
        }

        $proposal_id = (int) $proposal_id;
        if (!$proposal_id) {
            return $this->response->setJSON(['success' => false, 'message' => 'Invalid proposal']);
        }

        // Processar upload
        $upload_file = get_array_value($_FILES, 'file');
        if (!$upload_file || $upload_file['error'] !== UPLOAD_ERR_OK) {
            return $this->response->setJSON(['success' => false, 'message' => 'Upload failed']);
        }

        $file_name = $upload_file['name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        // Validar extensão
        $allowed_ext = array('pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png', 'zip');
        if (!in_array($file_ext, $allowed_ext)) {
            return $this->response->setJSON(['success' => false, 'message' => 'Invalid file type']);
        }

        // Criar pasta de uploads
        $upload_path = 'proposals/' . $proposal_id . '/';
        $dir = getcwd() . '/private/uploads/' . $upload_path;
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        // Mover arquivo
        $new_name = uniqid() . '_' . $file_name;
        $target = $dir . $new_name;
        
        if (move_uploaded_file($upload_file['tmp_name'], $target)) {
            // Salvar no banco
            $db = db_connect('default');
            $files_table = $this->_ensure_proposal_files_table($db);
            
            // Verificar se tabela existe
            if (!$db->tableExists($files_table)) {
                $sql = "CREATE TABLE IF NOT EXISTS `{$files_table}` (
                    `id` INT(11) NOT NULL AUTO_INCREMENT,
                    `proposal_id` INT(11) NOT NULL,
                    `file_name` VARCHAR(255) NOT NULL,
                    `file_path` VARCHAR(500) NOT NULL,
                    `file_size` INT(11) DEFAULT 0,
                    ` uploaded_by` INT(11) DEFAULT NULL,
                    `created_at` DATETIME DEFAULT NULL,
                    PRIMARY KEY (`id`),
                    KEY `proposal_id` (`proposal_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
                $db->query($sql);
            }
            
            $insert_data = array(
                'proposal_id' => $proposal_id,
                'file_name' => $file_name,
                'file_path' => $upload_path . $new_name,
                'file_size' => $upload_file['size'],
                'uploaded_by' => $this->login_user->id,
                'created_at' => get_current_utc_time()
            );
            
            $db->table($files_table)->insert($insert_data);
            
            return $this->response->setJSON([
                'success' => true,
                'data' => array(
                    'id' => $db->insertID(),
                    'name' => $file_name,
                    'url' => get_uri('propostas/download_file/' . $db->insertID())
                )
            ]);
        }

        return $this->response->setJSON(['success' => false, 'message' => 'Error moving file']);
    }

    public function get_files($proposal_id = 0)
    {
        if (!$this->_has_view_permission()) {
            return $this->response->setJSON(['success' => false, 'message' => 'Permission denied']);
        }

        $proposal_id = (int) $proposal_id;
        $db = db_connect('default');
        $files_table = $this->_ensure_proposal_files_table($db);
        
        $files = array();
        if ($db->tableExists($files_table)) {
            $files = $db->query("SELECT * FROM $files_table WHERE proposal_id = $proposal_id AND deleted = 0 ORDER BY created_at DESC")->getResult();
        }
        
        return $this->response->setJSON(['success' => true, 'data' => $files]);
    }

    /**
     * Retorna a tabela exclusiva de arquivos do plugin Proposals.
     * Ela não reutiliza a tabela nativa project_files.
     */
    private function _ensure_proposal_files_table($db)
    {
        $table = $db->prefixTable('proposal_files_custom');

        if (!$db->tableExists($table)) {
            $db->query("CREATE TABLE IF NOT EXISTS `{$table}` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `proposal_id` INT(11) NOT NULL,
                `file_name` VARCHAR(255) NOT NULL,
                `file_path` VARCHAR(500) NOT NULL,
                `file_size` INT(11) DEFAULT 0,
                `description` TEXT,
                `category_id` INT(11) DEFAULT NULL,
                `uploaded_by` INT(11) DEFAULT NULL,
                `created_at` DATETIME DEFAULT NULL,
                `deleted` TINYINT(1) NOT NULL DEFAULT 0,
                PRIMARY KEY (`id`),
                KEY `proposal_id` (`proposal_id`),
                KEY `deleted` (`deleted`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
        }

        // Atualiza instalações antigas/incompletas do plugin sem tocar na
        // tabela nativa project_files.
        $columns = [
            'proposal_id' => 'INT(11) DEFAULT NULL',
            'file_name' => "VARCHAR(255) NOT NULL DEFAULT ''",
            'file_path' => "VARCHAR(500) NOT NULL DEFAULT ''",
            'file_size' => 'INT(11) DEFAULT 0',
            'description' => 'TEXT',
            'category_id' => 'INT(11) DEFAULT NULL',
            'uploaded_by' => 'INT(11) DEFAULT NULL',
            'created_at' => 'DATETIME DEFAULT NULL',
            'deleted' => 'TINYINT(1) NOT NULL DEFAULT 0'
        ];
        foreach ($columns as $column => $definition) {
            if (!$db->fieldExists($column, $table)) {
                $db->query("ALTER TABLE `{$table}` ADD `{$column}` {$definition}");
            }
        }

        return $table;
    }

    private function _get_statuses()
    {
        $colors = array(
            'draft' => '#6c757d',
            'levantamento' => '#6f42c1',
            'proposta' => '#fd7e14',
            'sent' => '#17a2b8',
            'approved' => '#28a745',
            'rejected' => '#dc3545',
            'archived' => '#343a40'
        );
        $statuses = array();
        foreach ($this->_get_statuses_dropdown(false) as $row) {
            $id = (string) ($row['id'] ?? '');
            if ($id !== '') {
                $statuses[] = (object) array(
                    'id' => $id,
                    'name' => $row['text'] ?? $id,
                    'color' => $colors[$id] ?? '#6c757d'
                );
            }
        }

        return $statuses;
    }

    public function list_data()
    {
        if (!$this->_has_view_permission()) {
            return $this->_json_permission_denied();
        }

        $options = array(
            "company_id" => $this->_get_company_id()
        );

        $status = $this->request->getPost('status');
        if ($status) {
            $options['status'] = $status;
        }

        $query = $this->Proposals_model->get_details($options);
        $list_data = ($query && method_exists($query, 'getResult')) ? $query->getResult() : array();
        $result = array();
        foreach ($list_data as $data) {
            $result[] = $this->_make_row($data);
        }

        return $this->response->setJSON(array('data' => $result));
    }

    public function form($id = 0)
    {
        if (!$this->_has_manage_permission()) {
            app_redirect('forbidden');
        }

        $id = (int)$id;
        $company_id = $this->_get_company_id();
        $view_data = array();

        if ($id) {
            $proposal = $this->Proposals_model->get_details(array(
                'id' => $id,
                'company_id' => $company_id
            ))->getRow();
            if (!$proposal) {
                show_404();
            }
            $settings = $this->Proposals_module_settings_model->get_settings($company_id);
            $proposal->tax_product_percent = $proposal->tax_product_percent ?? 0;
            $proposal->tax_service_percent = $proposal->tax_service_percent ?? 0;
            if (!$proposal->tax_product_percent && !empty($settings->taxes_json)) {
                $decoded = json_decode($settings->taxes_json, true);
                if (is_array($decoded)) {
                    foreach ($decoded as $tax) {
                        $name = strtolower(trim((string)($tax['name'] ?? '')));
                        if ($name === 'imposto produto') {
                            $proposal->tax_product_percent = (float)($tax['percent'] ?? 0);
                        } elseif ($name === 'imposto servico') {
                            $proposal->tax_service_percent = (float)($tax['percent'] ?? 0);
                        }
                    }
                }
            }
            $view_data['proposal_info'] = $proposal;
        } else {
            $settings = $this->Proposals_module_settings_model->get_settings($company_id);
            $tax_product_percent = 0;
            $tax_service_percent = 0;
            if (!empty($settings->taxes_json)) {
                $decoded = json_decode($settings->taxes_json, true);
                if (is_array($decoded)) {
                    foreach ($decoded as $tax) {
                        $name = strtolower(trim((string)($tax['name'] ?? '')));
                        if ($name === 'imposto produto') {
                            $tax_product_percent = (float)($tax['percent'] ?? 0);
                        } elseif ($name === 'imposto servico') {
                            $tax_service_percent = (float)($tax['percent'] ?? 0);
                        }
                    }
                }
            }
            $view_data['proposal_info'] = (object) array(
                'id' => 0,
                'client_id' => '',
                'client_name' => '',
                'title' => '',
                'description' => '',
                'payment_terms' => '',
                'observations' => '',
                'validity_days' => '',
                'status' => 'draft',
                'commission_type' => $settings->default_commission_type,
                'commission_value' => $settings->default_commission_value,
                'tax_product_percent' => $tax_product_percent,
                'tax_service_percent' => $tax_service_percent,
                'tax_service_only' => 0
            );
        }

        $clients_dropdown = $this->Clients_model->get_dropdown_list(array('company_name'), 'id', array('is_lead' => 0));
        $view_data['clients_dropdown'] = $clients_dropdown;
        $view_data['status_options'] = $this->_get_statuses_dropdown(false);
        $view_data['commission_types'] = array(
            'percent' => app_lang('proposals_commission_type_percent'),
            'fixed' => app_lang('proposals_commission_type_fixed')
        );

        return $this->template->rander('Proposals\\Views\\proposals\\form', $view_data);
    }

    public function modal_form($id = 0)
    {
        if (!$this->_has_manage_permission()) {
            app_redirect('forbidden');
        }

        $id = (int)$id;
        $company_id = $this->_get_company_id();
        $view_data = array();

        if ($id) {
            $proposal = $this->Proposals_model->get_details(array(
                'id' => $id,
                'company_id' => $company_id
            ))->getRow();
            if (!$proposal) {
                show_404();
            }
            $settings = $this->Proposals_module_settings_model->get_settings($company_id);
            $proposal->tax_product_percent = $proposal->tax_product_percent ?? 0;
            $proposal->tax_service_percent = $proposal->tax_service_percent ?? 0;
            if (!$proposal->tax_product_percent && !empty($settings->taxes_json)) {
                $decoded = json_decode($settings->taxes_json, true);
                if (is_array($decoded)) {
                    foreach ($decoded as $tax) {
                        $name = strtolower(trim((string)($tax['name'] ?? '')));
                        if ($name === 'imposto produto') {
                            $proposal->tax_product_percent = (float)($tax['percent'] ?? 0);
                        } elseif ($name === 'imposto servico') {
                            $proposal->tax_service_percent = (float)($tax['percent'] ?? 0);
                        }
                    }
                }
            }
            $view_data['proposal_info'] = $proposal;
        } else {
            $settings = $this->Proposals_module_settings_model->get_settings($company_id);
            $tax_product_percent = 0;
            $tax_service_percent = 0;
            if (!empty($settings->taxes_json)) {
                $decoded = json_decode($settings->taxes_json, true);
                if (is_array($decoded)) {
                    foreach ($decoded as $tax) {
                        $name = strtolower(trim((string)($tax['name'] ?? '')));
                        if ($name === 'imposto produto') {
                            $tax_product_percent = (float)($tax['percent'] ?? 0);
                        } elseif ($name === 'imposto servico') {
                            $tax_service_percent = (float)($tax['percent'] ?? 0);
                        }
                    }
                }
            }
            $view_data['proposal_info'] = (object) array(
                'id' => 0,
                'client_id' => '',
                'client_name' => '',
                'title' => '',
                'description' => '',
                'payment_terms' => '',
                'observations' => '',
                'validity_days' => '',
                'status' => 'draft',
                'commission_type' => $settings->default_commission_type,
                'commission_value' => $settings->default_commission_value,
                'tax_product_percent' => $tax_product_percent,
                'tax_service_percent' => $tax_service_percent,
                'tax_service_only' => 0
            );
        }

        $clients_dropdown = $this->Clients_model->get_dropdown_list(array('company_name'), 'id', array('is_lead' => 0));
        $view_data['clients_dropdown'] = $clients_dropdown;
        $view_data['status_options'] = $this->_get_statuses_dropdown(false);
        $view_data['commission_types'] = array(
            'percent' => app_lang('proposals_commission_type_percent'),
            'fixed' => app_lang('proposals_commission_type_fixed')
        );

        return $this->template->view('Proposals\\Views\\proposals\\modal_form', $view_data);
    }

    public function save()
    {
        if (!$this->_has_manage_permission()) {
            return $this->_json_permission_denied();
        }

        $this->validate_submitted_data(array(
            'id' => 'numeric',
            'title' => 'required'
        ));

        $id = (int)$this->request->getPost('id');
        $company_id = $this->_get_company_id();

        if ($id) {
            $proposal = $this->Proposals_model->get_details(array(
                'id' => $id,
                'company_id' => $company_id
            ))->getRow();
            if (!$proposal) {
                return $this->response->setJSON(array('success' => false, 'message' => app_lang('record_not_found')));
            }
        }

        $client_id = get_only_numeric_value($this->request->getPost('client_id'));
        $client_name = trim((string)$this->request->getPost('client_name'));
        if ($client_id) {
            $client_name = '';
        }

        $commission_type = $this->request->getPost('commission_type') ?: 'percent';
        $commission_value = $this->_parse_decimal($this->request->getPost('commission_value'));
        $tax_product_percent = $this->_parse_decimal($this->request->getPost('tax_product_percent'));
        $tax_service_percent = $this->_parse_decimal($this->request->getPost('tax_service_percent'));
        $tax_service_only = $this->request->getPost('tax_service_only') ? 1 : 0;
        $old_status = $id && isset($proposal->status) ? (string)$proposal->status : '';
        $new_status = (string)($this->request->getPost('status') ?: 'draft');

        $data = array(
            'company_id' => $company_id,
            'client_id' => $client_id ? $client_id : null,
            'client_name' => $client_name,
            'title' => trim((string)$this->request->getPost('title')),
            'description' => trim((string)$this->request->getPost('description')),
            'payment_terms' => trim((string)$this->request->getPost('payment_terms')),
            'observations' => trim((string)$this->request->getPost('observations')),
            'validity_days' => get_only_numeric_value($this->request->getPost('validity_days')),
            'status' => $this->request->getPost('status') ?: 'draft',
            'commission_type' => $commission_type,
            'commission_value' => $commission_value,
            'tax_product_percent' => $tax_product_percent,
            'tax_service_percent' => $tax_service_percent,
            'tax_service_only' => $tax_service_only,
            'taxes_snapshot_json' => json_encode(array(
                array('name' => 'Imposto Produto', 'percent' => $tax_product_percent, 'active' => 1),
                array('name' => 'Imposto Servico', 'percent' => $tax_service_percent, 'active' => 1)
            )),
            'updated_at' => get_my_local_time()
        );
        $db = db_connect('default');
        $proposals_table = $db->prefixTable('proposals_custom');
        if (!$db->fieldExists('client_name', $proposals_table)) {
            unset($data['client_name']);
        }

        if (!$id) {
            $data['created_at'] = get_my_local_time();
            $data['created_by'] = $this->login_user->id;
        }

        $save_id = $this->Proposals_model->ci_save($data, $id);
        if ($save_id === false) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('error_occurred')));
        }

        $new_id = $id ? $id : (is_int($save_id) ? $save_id : db_connect('default')->insertID());
        $this->Proposals_model->calculate_totals($new_id);
        if ($old_status !== $new_status) {
            $this->_notify_status_members($new_id, $new_status);
        }
        $this->_log_activity($id ? 'proposal_updated' : 'proposal_created', $new_id);
        return $this->response->setJSON(array(
            'success' => true,
            'id' => $new_id,
            'message' => app_lang('record_saved'),
            'redirect_to' => get_uri('propostas/view/' . $new_id)
        ));
    }

    public function delete()
    {
        if (!$this->_has_manage_permission()) {
            return $this->_json_permission_denied();
        }

        $id = (int)$this->request->getPost('id');
        if (!$id) {
            return $this->response->setJSON(array('success' => false));
        }

        $ok = $this->Proposals_model->delete($id);
        if ($ok) {
            $this->_log_activity('proposal_deleted', $id);
        }
        return $this->response->setJSON(array('success' => $ok ? true : false));
    }

    public function view($id = 0)
    {
        if (!$this->_has_view_permission()) {
            app_redirect('forbidden');
        }

        $id = (int)$id;
        $proposal = $this->Proposals_model->get_details(array(
            'id' => $id,
            'company_id' => $this->_get_company_id()
        ))->getRow();
        if (!$proposal) {
            show_404();
        }

        $sections_query = $this->Proposal_sections_model->get_details(array('proposal_id' => $id));
        $items_query = $this->Proposal_items_model->get_details(array('proposal_id' => $id));
        $memory_items_query = $this->Proposal_items_model->get_details(array(
            'proposal_id' => $id,
            'in_memory' => 1
        ));
        $proposal_items_query = $this->Proposal_items_model->get_details(array(
            'proposal_id' => $id,
            'show_in_proposal' => 1,
            'in_memory' => 0
        ));
        $sections = ($sections_query && method_exists($sections_query, 'getResult')) ? $sections_query->getResult() : array();
        $items = ($items_query && method_exists($items_query, 'getResult')) ? $items_query->getResult() : array();
        $memory_items = ($memory_items_query && method_exists($memory_items_query, 'getResult')) ? $memory_items_query->getResult() : array();
        $proposal_items = ($proposal_items_query && method_exists($proposal_items_query, 'getResult')) ? $proposal_items_query->getResult() : array();
        $dashboard_data = $this->_get_dashboard_data($proposal);
        $settings = $this->Proposals_module_settings_model->get_settings($this->_get_company_id());
        $default_markup_percent = $settings && isset($settings->default_markup_percent) ? (float)$settings->default_markup_percent : 0;
        $Custom_fields_model = model('App\\Models\\Custom_fields_model');
        $custom_field_headers_of_task = $Custom_fields_model->get_custom_field_headers_for_table(
            "tasks",
            $this->login_user->is_admin,
            $this->login_user->user_type
        );

        $view_data = array(
            'proposal_info' => $proposal,
            'proposal_id' => $id,
            'sections' => $sections,
            'items' => $items,
            'memory_items' => $memory_items,
            'proposal_items' => $proposal_items,
            'default_markup_percent' => $default_markup_percent,
            'can_manage' => $this->_has_manage_permission(),
            'status_options' => $this->_get_statuses_dropdown(false),
            'items_options_html' => $this->_get_items_options_html(),
            'dashboard_data' => $dashboard_data,
            'document_html' => $this->_render_document_html($proposal, $sections, $proposal_items),
            'custom_field_headers_of_task' => $custom_field_headers_of_task
        );

        return $this->template->rander('Proposals\\Views\\proposals\\view', $view_data);
    }

    public function update_status()
    {
        if (!$this->_has_manage_permission()) {
            return $this->_json_permission_denied();
        }

        $this->validate_submitted_data(array(
            'id' => 'required|numeric',
            'status' => 'required'
        ));

        $id = (int)$this->request->getPost('id');
        $status = trim((string)$this->request->getPost('status'));
        $proposal = $this->_get_proposal_for_company($id);
        if (!$proposal) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('record_not_found')));
        }

        $old_status = (string)($proposal->status ?? '');
        $allowed = array();
        foreach ($this->_get_statuses_dropdown(false) as $row) {
            if (!empty($row['id'])) {
                $allowed[] = $row['id'];
            }
        }
        if (!in_array($status, $allowed, true)) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('invalid_request')));
        }

        $status_data = array(
            'status' => $status,
            'updated_at' => get_my_local_time()
        );
        $save_id = $this->Proposals_model->ci_save($status_data, $id);

        if (!$save_id) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('error_occurred')));
        }

        if ($old_status !== $status) {
            $this->_notify_status_members($id, $status);
        }

        $this->_log_activity('proposal_updated', $id);
        return $this->response->setJSON(array(
            'success' => true,
            'status' => app_lang('proposals_status_' . $status),
            'status_html' => $this->_get_status_label($status)
        ));
    }

    public function approve()
    {
        if (!$this->_has_manage_permission()) {
            return $this->_json_permission_denied();
        }

        $this->validate_submitted_data(array(
            'id' => 'required|numeric'
        ));

        $proposal_id = (int)$this->request->getPost('id');
        $proposal = $this->_get_proposal_for_company($proposal_id);
        if (!$proposal) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('record_not_found')));
        }

        $create_project = $this->request->getPost('create_project') ? true : false;
        $create_purchase_request = $this->request->getPost('create_purchase_request') ? true : false;

        $request_item_rows = array();
        if ($create_purchase_request) {
            $request_items_result = $this->_prepare_purchase_request_items_from_post($proposal_id);
            if (!$request_items_result['success']) {
                return $this->response->setJSON($request_items_result);
            }
            $request_item_rows = $request_items_result['rows'];
        }

        $db = db_connect('default');
        $db->transStart();

        $approval_data = array(
            'status' => 'approved',
            'updated_at' => get_my_local_time()
        );
        $save_id = $this->Proposals_model->ci_save($approval_data, $proposal_id);

        if (!$save_id) {
            $db->transRollback();
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('error_occurred')));
        }

        $project_id = 0;
        if ($create_project) {
            $project_id = $this->_create_project_from_proposal($proposal);
            if (!$project_id) {
                $db->transRollback();
                return $this->response->setJSON(array('success' => false, 'message' => app_lang('error_occurred')));
            }
        }

        $purchase_request_id = 0;
        if ($create_purchase_request) {
            $purchase_request_id = $this->_create_purchase_request_from_proposal($proposal, $project_id, $request_item_rows);
            if (!$purchase_request_id) {
                $db->transRollback();
                return $this->response->setJSON(array('success' => false, 'message' => app_lang('error_occurred')));
            }
        }

        $db->transComplete();
        if (!$db->transStatus()) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('error_occurred')));
        }

        $this->_log_activity('proposal_approved', $proposal_id);

        $redirect_to = get_uri('propostas/view/' . $proposal_id);
        if ($purchase_request_id) {
            $redirect_to = get_uri('purchases_requests/view/' . $purchase_request_id);
        } else if ($project_id) {
            $redirect_to = get_uri('projects/view/' . $project_id);
        }

        return $this->response->setJSON(array(
            'success' => true,
            'message' => app_lang('record_saved'),
            'redirect_to' => $redirect_to,
            'project_id' => $project_id,
            'purchase_request_id' => $purchase_request_id
        ));
    }

    public function duplicate()
    {
        if (!$this->_has_manage_permission()) {
            return $this->_json_permission_denied();
        }

        $this->validate_submitted_data(array(
            'id' => 'required|numeric'
        ));

        $proposal_id = (int)$this->request->getPost('id');
        $proposal = $this->_get_proposal_for_company($proposal_id);
        if (!$proposal) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('record_not_found')));
        }

        $new_id = $this->_duplicate_proposal($proposal);
        if (!$new_id) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('error_occurred')));
        }

        $this->_log_activity('proposal_duplicated', $proposal_id, $new_id);

        return $this->response->setJSON(array(
            'success' => true,
            'message' => app_lang('record_saved'),
            'redirect_to' => get_uri('propostas/view/' . $new_id),
            'id' => $new_id
        ));
    }

    public function add_section()
    {
        if (!$this->_has_manage_permission()) {
            return $this->_json_permission_denied();
        }

        $this->validate_submitted_data(array(
            'proposal_id' => 'required|numeric',
            'title' => 'required'
        ));

        $proposal_id = (int)$this->request->getPost('proposal_id');
        $proposal = $this->_get_proposal_for_company($proposal_id);
        if (!$proposal) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('record_not_found')));
        }

        $parent_id = (int)$this->request->getPost('parent_id');
        if ($parent_id && !$this->_section_belongs_to_proposal($parent_id, $proposal_id)) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('record_not_found')));
        }

        $sort = $this->_get_next_section_sort($proposal_id, $parent_id ? $parent_id : null);
        $data = array(
            'proposal_id' => $proposal_id,
            'parent_id' => $parent_id ? $parent_id : null,
            'title' => trim((string)$this->request->getPost('title')),
            'description' => trim((string)$this->request->getPost('description')),
            'sort' => $sort,
            'created_by' => $this->login_user->id,
            'created_at' => get_my_local_time()
        );

        $save_id = $this->Proposal_sections_model->ci_save($data, 0);
        if ($save_id === false) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('error_occurred')));
        }

        $data['id'] = is_int($save_id) ? $save_id : db_connect('default')->insertID();
        $this->_log_activity('section_created', $proposal_id, $data['id']);

        return $this->response->setJSON(array(
            'success' => true,
            'data' => $data,
            'message' => app_lang('record_saved')
        ));
    }

    public function update_section()
    {
        if (!$this->_has_manage_permission()) {
            return $this->_json_permission_denied();
        }

        $this->validate_submitted_data(array(
            'id' => 'required|numeric',
            'title' => 'required'
        ));

        $id = (int)$this->request->getPost('id');
        $section = $this->Proposal_sections_model->get_one($id);
        if (!$section || $section->deleted) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('record_not_found')));
        }

        if (!$this->_proposal_belongs_to_company($section->proposal_id)) {
            return $this->_json_permission_denied();
        }

        $data = array(
            'title' => trim((string)$this->request->getPost('title')),
            'description' => trim((string)$this->request->getPost('description'))
        );

        $ok = $this->Proposal_sections_model->ci_save($data, $id);
        if ($ok) {
            $this->_log_activity('section_updated', (int)$section->proposal_id, $id);
        }
        return $this->response->setJSON(array(
            'success' => $ok ? true : false,
            'message' => $ok ? app_lang('record_saved') : app_lang('error_occurred')
        ));
    }

    public function delete_section()
    {
        if (!$this->_has_manage_permission()) {
            return $this->_json_permission_denied();
        }

        $id = (int)$this->request->getPost('id');
        $section = $this->Proposal_sections_model->get_one($id);
        if (!$section || $section->deleted) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('record_not_found')));
        }

        $proposal_id = (int)$section->proposal_id;
        if (!$this->_proposal_belongs_to_company($proposal_id)) {
            return $this->_json_permission_denied();
        }

        $section_ids = $this->_collect_section_descendants($proposal_id, $id);
        $section_ids[] = $id;
        $section_ids = array_unique(array_map('intval', $section_ids));

        if ($section_ids) {
            $db = db_connect('default');
            $sections_table = $db->prefixTable('proposal_sections_custom');
            $items_table = $db->prefixTable('proposal_items_custom');

            $ids_sql = implode(',', $section_ids);
            $db->query("UPDATE $sections_table SET deleted=1 WHERE id IN ($ids_sql)");
            $db->query("UPDATE $items_table SET deleted=1 WHERE section_id IN ($ids_sql)");
        }

        $this->Proposals_model->calculate_totals($proposal_id);
        $this->_log_activity('section_deleted', $proposal_id, $id);

        return $this->response->setJSON(array('success' => true, 'message' => app_lang('record_deleted')));
    }

    public function add_item()
    {
        if (!$this->_has_manage_permission()) {
            return $this->_json_permission_denied();
        }

        $this->validate_submitted_data(array(
            'proposal_id' => 'required|numeric'
        ));

        $proposal_id = (int)$this->request->getPost('proposal_id');
        $proposal = $this->_get_proposal_for_company($proposal_id);
        if (!$proposal) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('record_not_found')));
        }

        $section_id_raw = $this->request->getPost('section_id');
        $section_id = $section_id_raw !== null && $section_id_raw !== '' ? (int)$section_id_raw : null;
        if ($section_id) {
            if (!$this->_section_belongs_to_proposal($section_id, $proposal_id)) {
                return $this->response->setJSON(array('success' => false, 'message' => app_lang('record_not_found')));
            }
        }

        $data = $this->_prepare_item_data($proposal_id, $section_id);
        $data['created_by'] = $this->login_user->id;
        $data['created_at'] = get_my_local_time();
        $data['sort'] = $this->_get_next_item_sort($proposal_id, $section_id);

        $save_id = $this->Proposal_items_model->ci_save($data, 0);
        if ($save_id === false) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('error_occurred')));
        }

        $data['id'] = is_int($save_id) ? $save_id : db_connect('default')->insertID();
        $this->Proposals_model->calculate_totals($proposal_id);
        $this->_log_activity('item_created', $proposal_id, $data['id']);

        return $this->response->setJSON(array(
            'success' => true,
            'data' => $data,
            'message' => app_lang('record_saved')
        ));
    }

    public function update_item()
    {
        if (!$this->_has_manage_permission()) {
            return $this->_json_permission_denied();
        }

        $this->validate_submitted_data(array(
            'id' => 'required|numeric'
        ));

        $id = (int)$this->request->getPost('id');
        $item = $this->Proposal_items_model->get_one($id);
        if (!$item || $item->deleted) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('record_not_found')));
        }

        $proposal_id = (int)$item->proposal_id;
        if (!$this->_proposal_belongs_to_company($proposal_id)) {
            return $this->_json_permission_denied();
        }

        $data = $this->_prepare_item_data($proposal_id, (int)$item->section_id, $item);

        $ok = $this->Proposal_items_model->ci_save($data, $id);
        $this->Proposals_model->calculate_totals($proposal_id);
        if ($ok) {
            $this->_log_activity('item_updated', $proposal_id, $id);
        }

        return $this->response->setJSON(array(
            'success' => $ok ? true : false,
            'data' => $data,
            'message' => $ok ? app_lang('record_saved') : app_lang('error_occurred')
        ));
    }

    public function delete_item()
    {
        if (!$this->_has_manage_permission()) {
            return $this->_json_permission_denied();
        }

        $id = (int)$this->request->getPost('id');
        $item = $this->Proposal_items_model->get_one($id);
        if (!$item || $item->deleted) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('record_not_found')));
        }

        if (!$this->_proposal_belongs_to_company((int)$item->proposal_id)) {
            return $this->_json_permission_denied();
        }

        $ok = $this->Proposal_items_model->delete($id);
        $this->Proposals_model->calculate_totals((int)$item->proposal_id);
        if ($ok) {
            $this->_log_activity('item_deleted', (int)$item->proposal_id, $id);
        }

        return $this->response->setJSON(array(
            'success' => $ok ? true : false,
            'message' => $ok ? app_lang('record_deleted') : app_lang('error_occurred')
        ));
    }

    public function reorder()
    {
        if (!$this->_has_manage_permission()) {
            return $this->_json_permission_denied();
        }

        $type = $this->request->getPost('type');
        $order = $this->request->getPost('order');

        if (!$type || !$order) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('error_occurred')));
        }

        $ids = is_array($order) ? $order : explode(',', $order);
        $ids = array_values(array_filter(array_map('intval', $ids)));

        if (!$ids) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('error_occurred')));
        }

        $db = db_connect('default');
        if ($type === 'section') {
            $table = $db->prefixTable('proposal_sections_custom');
        } elseif ($type === 'item') {
            $table = $db->prefixTable('proposal_items_custom');
        } else {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('error_occurred')));
        }

        foreach ($ids as $index => $id) {
            $sort = $index + 1;
            $db->query("UPDATE $table SET sort=$sort WHERE id=$id");
        }

        return $this->response->setJSON(array('success' => true));
    }

    public function items_search()
    {
        if (!$this->_has_manage_permission() && !$this->_has_view_permission()) {
            return $this->_json_permission_denied();
        }

        $db = db_connect('default');
        $keyword = trim((string)($this->request->getPost('q') ?: $this->request->getGet('q')));
        $items_table = $db->prefixTable('items');
        $has_cost = $db->fieldExists("cost", $items_table);
        $has_sale = $db->fieldExists("sale", $items_table);
        $has_markup = $db->fieldExists("markup", $items_table);
        $keyword_like = $keyword ? $db->escapeLikeString($keyword) : '';
        $where = '';

        if ($keyword_like) {
            $where = " AND $items_table.title LIKE '%$keyword_like%' ESCAPE '!'";
        }

        $select = "$items_table.id, $items_table.title, $items_table.rate, $items_table.unit_type";
        if ($has_cost) {
            $select .= ", $items_table.cost";
        }
        if ($has_sale) {
            $select .= ", $items_table.sale";
        }
        if ($has_markup) {
            $select .= ", $items_table.markup";
        }

        $sql = "SELECT $select
            FROM $items_table
            WHERE $items_table.deleted=0 $where
            ORDER BY $items_table.id DESC
            LIMIT 20";

        $rows = $db->query($sql)->getResult();
        $results = array();
        foreach ($rows as $row) {
            $rate = ($has_cost && isset($row->cost) && is_numeric($row->cost))
                ? $row->cost
                : (is_numeric($row->rate) ? $row->rate : 0);
            $sale = ($has_sale && isset($row->sale) && is_numeric($row->sale)) ? $row->sale : 0;
            $results[] = array(
                'id' => $row->id,
                'text' => $row->title,
                'rate' => $rate,
                'sale' => $sale,
                'unit_type' => $row->unit_type,
                'item_type' => 'material'
            );
        }

        if ($this->_os_services_table_exists($db)) {
            $services_table = $db->prefixTable('os_servicos');
            $services_where = '';
            if ($keyword_like) {
                $services_where = " AND $services_table.descricao LIKE '%$keyword_like%' ESCAPE '!'";
            }
            $services_sql = "SELECT $services_table.id, $services_table.descricao, $services_table.custo, $services_table.valor_venda
                FROM $services_table
                WHERE $services_table.deleted=0 $services_where
                ORDER BY $services_table.id DESC
                LIMIT 20";
            $services = $db->query($services_sql)->getResult();
            foreach ($services as $service) {
                    $results[] = array(
                        'id' => 's-' . $service->id,
                        'text' => $service->descricao,
                        'rate' => $service->custo,
                        'sale' => $service->valor_venda,
                        'item_type' => 'service'
                    );
            }
        }

        return $this->response->setJSON($results);
    }

    public function create_item_quick()
    {
        if (!$this->_has_manage_permission()) {
            return $this->_json_permission_denied();
        }

        $this->validate_submitted_data(array(
            'title' => 'required'
        ));

        $title = trim((string)$this->request->getPost('title'));
        $rate = unformat_currency($this->request->getPost('rate'));
        $sale = unformat_currency($this->request->getPost('sale'));
        $markup = $this->_parse_decimal($this->request->getPost('markup'));
        $unit_type = trim((string)$this->request->getPost('unit_type'));
        $unit_type = $unit_type ? $unit_type : 'UN';
        $item_type = trim((string)$this->request->getPost('item_type'));
        $item_type = $item_type ? $item_type : 'material';

        $category_id = $this->_get_default_item_category_id();
        if (!$category_id) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('error_occurred')));
        }

        $items_model = model('App\\Models\\Items_model');
        $db = db_connect('default');
        $items_table = $db->prefixTable('items');
        $has_cost = $db->fieldExists("cost", $items_table);
        $has_sale = $db->fieldExists("sale", $items_table);
        $has_markup = $db->fieldExists("markup", $items_table);
        $has_item_type = $db->fieldExists("item_type", $items_table);
        $item_data = array(
            'title' => $title,
            'description' => '',
            'category_id' => $category_id,
            'unit_type' => $unit_type,
            'rate' => $rate,
            'show_in_client_portal' => ''
        );
        if ($has_cost) {
            $item_data['cost'] = $rate;
        }
        if ($has_sale) {
            $item_data['sale'] = $sale;
        }
        if ($has_markup) {
            $item_data['markup'] = $markup;
        }
        if ($has_item_type) {
            $item_data['item_type'] = $item_type;
        }

        $item_id = $items_model->ci_save($item_data, 0);
        if (!$item_id) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('error_occurred')));
        }

        return $this->response->setJSON(array(
            'success' => true,
            'data' => array(
                'id' => (int)$item_id,
                'title' => $title,
                'rate' => $rate,
                'sale' => $sale,
                'markup' => $markup,
                'unit_type' => $unit_type,
                'item_type' => $item_type
            )
        ));
    }

    public function document_preview()
    {
        if (!$this->_has_view_permission()) {
            return $this->_json_permission_denied();
        }

        $proposal_id = (int)$this->request->getPost('proposal_id');
        if (!$proposal_id) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('invalid_request')));
        }

        $proposal = $this->_get_proposal_for_company($proposal_id);
        if (!$proposal) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('record_not_found')));
        }

        $mode = $this->request->getPost('display_mode');
        $sections = $this->Proposal_sections_model->get_details(array('proposal_id' => $proposal_id))->getResult();
        $items = $this->Proposal_items_model->get_details(array(
            'proposal_id' => $proposal_id,
            'in_memory' => 0
        ))->getResult();
        $html = $this->_render_document_html($proposal, $sections, $items, $mode);

        return $this->response->setJSON(array(
            'success' => true,
            'html' => $html
        ));
    }

    public function save_document()
    {
        if (!$this->_has_manage_permission()) {
            return $this->_json_permission_denied();
        }

        if (strtolower($this->request->getMethod()) !== 'post') {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('invalid_request')));
        }

        $proposal_id = (int)$this->request->getPost('proposal_id');
        if (!$proposal_id) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('invalid_request')));
        }

        $proposal = $this->_get_proposal_for_company($proposal_id);
        if (!$proposal) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('record_not_found')));
        }

        $display_mode = $this->request->getPost('display_mode') ?: 'detailed';

        $data = array(
            'description' => trim((string)$this->request->getPost('description')),
            'payment_terms' => trim((string)$this->request->getPost('payment_terms')),
            'observations' => trim((string)$this->request->getPost('observations')),
            'validity_days' => get_only_numeric_value($this->request->getPost('validity_days')),
            'display_mode' => $display_mode,
            'updated_at' => get_my_local_time()
        );

        $save_id = $this->Proposals_model->ci_save($data, $proposal_id);
        if ($save_id === false) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('error_occurred')));
        }

        $sections = $this->Proposal_sections_model->get_details(array('proposal_id' => $proposal_id))->getResult();
        $items = $this->Proposal_items_model->get_details(array(
            'proposal_id' => $proposal_id,
            'in_memory' => 0
        ))->getResult();

        $snapshot = array(
            'proposal_id' => $proposal_id,
            'display_mode' => $display_mode,
            'description' => $data['description'],
            'payment_terms' => $data['payment_terms'],
            'observations' => $data['observations'],
            'validity_days' => $data['validity_days'],
            'sections' => $sections,
            'items' => $items
        );

        $snapshot_data = array(
            'proposal_id' => $proposal_id,
            'snapshot_json' => json_encode($snapshot),
            'created_by' => $this->login_user->id,
            'created_at' => get_my_local_time()
        );
        $this->Proposal_snapshots_model->ci_save($snapshot_data, 0);
        $this->_log_activity('document_saved', $proposal_id);

        $proposal = $this->_get_proposal_for_company($proposal_id);
        $html = $this->_render_document_html($proposal, $sections, $items, $display_mode);

        return $this->response->setJSON(array(
            'success' => true,
            'message' => app_lang('record_saved'),
            'html' => $html
        ));
    }

    public function download_pdf($proposal_id = 0)
    {
        if (!$this->_has_view_permission()) {
            app_redirect('forbidden');
        }

        $proposal_id = (int)$proposal_id;
        if (!$proposal_id) {
            show_404();
        }

        $proposal = $this->_get_proposal_for_company($proposal_id);
        if (!$proposal) {
            show_404();
        }

        $sections_query = $this->Proposal_sections_model->get_details(array('proposal_id' => $proposal_id));
        $items_query = $this->Proposal_items_model->get_details(array(
            'proposal_id' => $proposal_id,
            'in_memory' => 0
        ));
        $sections = ($sections_query && method_exists($sections_query, 'getResult')) ? $sections_query->getResult() : array();
        $items = ($items_query && method_exists($items_query, 'getResult')) ? $items_query->getResult() : array();

        $renderer = new \Proposals\Libraries\Proposals_document();
        $html = $renderer->render_pdf($proposal, $sections, $items);
        $code = "PR-" . str_pad((int)$proposal->id, 6, "0", STR_PAD_LEFT);
        $pdf = new \App\Libraries\Pdf("proposal");
        return $pdf->PreparePDF($html, $code, "download");
    }

    public function update_item_visibility()
    {
        if (!$this->_has_manage_permission()) {
            return $this->_json_permission_denied();
        }

        $id = (int)$this->request->getPost('id');
        if (!$id) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('invalid_request')));
        }

        $item = $this->Proposal_items_model->get_one($id);
        if (!$item || $item->deleted) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('record_not_found')));
        }

        if (!$this->_proposal_belongs_to_company((int)$item->proposal_id)) {
            return $this->_json_permission_denied();
        }

        $data = array(
            'show_in_proposal' => $this->request->getPost('show_in_proposal') ? 1 : 0,
            'show_values_in_proposal' => $this->request->getPost('show_values_in_proposal') ? 1 : 0
        );

        $ok = $this->Proposal_items_model->ci_save($data, $id);

        return $this->response->setJSON(array(
            'success' => $ok ? true : false,
            'message' => $ok ? app_lang('record_saved') : app_lang('error_occurred')
        ));
    }

    public function copy_items_from_memory()
    {
        if (!$this->_has_manage_permission()) {
            return $this->_json_permission_denied();
        }

        $proposal_id = (int)$this->request->getPost('proposal_id');
        if (!$proposal_id) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('invalid_request')));
        }

        $proposal = $this->_get_proposal_for_company($proposal_id);
        if (!$proposal) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('record_not_found')));
        }

        // Buscar itens da memória de cálculo
        $memory_items_query = $this->Proposal_items_model->get_details(array(
            'proposal_id' => $proposal_id,
            'in_memory' => 1
        ));
        $memory_items = ($memory_items_query && method_exists($memory_items_query, 'getResult')) ? $memory_items_query->getResult() : array();

        // Primeiro: excluir todos os itens existentes da proposta (não-memória)
        $existing_items_query = $this->Proposal_items_model->get_details(array(
            'proposal_id' => $proposal_id,
            'in_memory' => 0
        ));
        $existing_items = ($existing_items_query && method_exists($existing_items_query, 'getResult')) ? $existing_items_query->getResult() : array();

        $deleted_count = 0;
        foreach ($existing_items as $existing) {
            $this->Proposal_items_model->delete($existing->id);
            $deleted_count++;
        }

        // Agrupar itens da memória por item_id e somar quantidades
        $grouped_items = array();
        foreach ($memory_items as $item) {
            $key = (int)$item->item_id;
            if ($key > 0) {
                if (isset($grouped_items[$key])) {
                    // Somar quantidade
                    $grouped_items[$key]->qty += $item->qty;
                    $grouped_items[$key]->total = $grouped_items[$key]->qty * $grouped_items[$key]->sale_unit;
                } else {
                    $grouped_items[$key] = $item;
                }
            }
        }

        $next_sort = 0;
        $items_copied = 0;

        // Agora copiar os itens da memória
        foreach ($grouped_items as $item) {
            $data = array(
                'proposal_id' => $proposal_id,
                'section_id' => null,
                'item_id' => $item->item_id,
                'item_type' => $item->item_type,
                'description_override' => $item->description_override,
                'cost_unit' => $item->cost_unit,
                'qty' => $item->qty,
                'markup_percent' => $item->markup_percent,
                'sale_unit' => $item->sale_unit,
                'total' => $item->total,
                'show_in_proposal' => 1,
                'show_values_in_proposal' => 1,
                'in_memory' => 0,
                'sort' => $next_sort,
                'created_by' => $this->login_user->id,
                'created_at' => get_my_local_time()
            );
            $this->Proposal_items_model->ci_save($data, 0);
            $next_sort++;
            $items_copied++;
        }

        $this->_log_activity('items_copied_to_proposal', $proposal_id);

        $message = "$items_copied itens copiados ($deleted_count excluídos anteriormente)";

        return $this->response->setJSON(array(
            'success' => true,
            'message' => $message
        ));
    }

    public function send_memory_to_quotation()
    {
        if (!$this->_has_manage_permission()) {
            return $this->_json_permission_denied();
        }

        $proposal_id = (int)$this->request->getPost('proposal_id');
        $proposal = $this->_get_proposal_for_company($proposal_id);
        if (!$proposal) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('record_not_found')));
        }

        try {
            $items_query = $this->Proposal_items_model->get_details(array('proposal_id' => $proposal_id, 'in_memory' => 1));
            $memory_items = ($items_query && method_exists($items_query, 'getResult')) ? $items_query->getResult() : array();
            $grouped_items = array();

            foreach ($memory_items as $item) {
                $item_type = (string)($item->item_type ?? 'material');
                $item_id = (int)($item->item_id ?? 0);
                $description = trim((string)($item->description_override ?? ''));
                if (!$description) {
                    $description = trim((string)($item->item_title ?? ''));
                }
                if (!$description) {
                    $description = app_lang('item');
                }

                $group_key = ($item_id > 0 && $item_type !== 'service')
                    ? 'item:' . $item_id
                    : 'description:' . $item_type . ':' . mb_strtolower($description);

                if (!isset($grouped_items[$group_key])) {
                    $grouped_items[$group_key] = array(
                        'item_id' => ($item_type === 'service' || !$item_id) ? null : $item_id,
                        'description' => $description,
                        'quantity' => 0,
                        'unit' => trim((string)($item->item_unit ?? '')),
                        'note' => ''
                    );
                }
                $grouped_items[$group_key]['quantity'] += (float)($item->qty ?? 0);
            }

            if (!count($grouped_items)) {
                return $this->response->setJSON(array('success' => false, 'message' => app_lang('proposals_no_proposal_items')));
            }

            $db = db_connect('default');
            $quotations_model = model('Purchases\\Models\\Purchases_quotations_model');
            $quotation_items_model = model('Purchases\\Models\\Purchases_quotation_items_model');
            $company_id = $this->_get_company_id();
            $quotation_fields = $db->getFieldNames($db->prefixTable('purchases_quotations'));
            $quotation_item_fields = $db->getFieldNames($db->prefixTable('purchases_quotation_items'));
            $required_quotation_fields = array('company_id', 'quotation_type', 'quotation_code_number', 'quotation_code', 'title', 'note', 'status', 'created_at', 'created_by');
            $required_item_fields = array('company_id', 'quotation_id', 'item_id', 'description', 'qty', 'unit', 'desired_date', 'note', 'created_at', 'created_by');
            if (!is_array($quotation_fields) || count(array_diff($required_quotation_fields, $quotation_fields)) || !is_array($quotation_item_fields) || count(array_diff($required_item_fields, $quotation_item_fields))) {
                return $this->response->setJSON(array('success' => false, 'message' => 'O banco do plugin Purchases ainda não está preparado para cotações avulsas.'));
            }

            $code_data = $quotations_model->get_next_quotation_code_data($company_id);
            $proposal_code = 'PR-' . str_pad($proposal_id, 6, '0', STR_PAD_LEFT);
            $quotation_data = array(
                'company_id' => $company_id,
                'quotation_type' => 'standalone',
                'quotation_code_number' => $code_data['quotation_code_number'],
                'quotation_code' => $code_data['quotation_code'],
                'title' => 'Cotação ' . $proposal_code,
                'note' => 'Gerada a partir da proposta ' . $proposal_code . '.',
                'status' => 'draft',
                'created_at' => get_my_local_time(),
                'created_by' => $this->login_user->id
            );

            $db->transBegin();
            $quotation_id = $quotations_model->ci_save($quotation_data, 0);
            if (!is_int($quotation_id)) {
                $quotation_id = (int)$db->insertID();
            }
            if (!$quotation_id) {
                $db->transRollback();
                return $this->response->setJSON(array('success' => false, 'message' => app_lang('error_occurred')));
            }

            foreach ($grouped_items as $item) {
                $item_data = array(
                    'company_id' => $company_id,
                    'quotation_id' => $quotation_id,
                    'item_id' => $item['item_id'],
                    'description' => $item['description'],
                    'qty' => $item['quantity'],
                    'unit' => $item['unit'],
                    'desired_date' => null,
                    'note' => $item['note'],
                    'created_at' => get_my_local_time(),
                    'created_by' => $this->login_user->id
                );
                if (in_array('request_item_id', $quotation_item_fields, true)) {
                    $item_data['request_item_id'] = null;
                }
                $quotation_items_model->ci_save($item_data, 0);
            }

            if ($db->transStatus() === false) {
                $db->transRollback();
                return $this->response->setJSON(array('success' => false, 'message' => app_lang('error_occurred')));
            }
            $db->transCommit();

            return $this->response->setJSON(array('success' => true, 'id' => $quotation_id, 'items_count' => count($grouped_items), 'redirect' => get_uri('purchases_quotations/view/' . $quotation_id)));
        } catch (\Throwable $e) {
            log_message('error', 'Error sending proposal memory to quotation: ' . $e->getMessage());
            return $this->response->setJSON(array('success' => false, 'message' => $e->getMessage()));
        }
    }
    public function dashboard_data()
    {
        if (!$this->_has_view_permission()) {
            return $this->_json_permission_denied();
        }

        $proposal_id = (int)$this->request->getPost('proposal_id');
        if (!$proposal_id) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('invalid_request')));
        }

        $proposal = $this->_get_proposal_for_company($proposal_id);
        if (!$proposal) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('record_not_found')));
        }

        return $this->response->setJSON(array(
            'success' => true,
            'data' => $this->_get_dashboard_data($proposal)
        ));
    }

    public function tasks_list_data($proposal_id = 0)
    {
        if (!$this->_has_view_permission()) {
            return $this->_json_permission_denied();
        }

        $proposal_id = (int)$proposal_id;
        if (!$proposal_id || !$this->_proposal_belongs_to_company($proposal_id)) {
            return $this->response->setJSON(array('data' => array()));
        }

        $proposal = $this->_get_proposal_for_company($proposal_id);
        $prefix = "";
        if ($proposal) {
            $code = "PR-" . str_pad($proposal->id, 6, "0", STR_PAD_LEFT);
            $prefix = $code . " - ";
        }

        $task_ids = $this->_get_linked_task_ids($proposal_id);
        if (!$task_ids) {
            return $this->response->setJSON(array('data' => array()));
        }

        $Custom_fields_model = model('App\\Models\\Custom_fields_model');
        $Tasks_model = model('App\\Models\\Tasks_model');
        $custom_fields = $Custom_fields_model->get_available_fields_for_table("tasks", $this->login_user->is_admin, $this->login_user->user_type);

        $options = array(
            "task_ids" => implode(",", $task_ids),
            "custom_fields" => $custom_fields,
            "unread_status_user_id" => $this->login_user->id
        );

        $list_data = $Tasks_model->get_details($options);
        $rows = array();
        $tasks = ($list_data && method_exists($list_data, 'getResult')) ? $list_data->getResult() : array();
        foreach ($tasks as $task) {
            $rows[] = $this->_make_task_row_simple($task);
        }

        return $this->response->setJSON(array("data" => $rows));
    }

    public function reminders_list_data($proposal_id = 0, $type = "reminders")
    {
        if (!$this->_has_view_permission()) {
            return $this->_json_permission_denied();
        }

        if (!function_exists('can_access_reminders_module') || !can_access_reminders_module()) {
            return $this->_json_permission_denied();
        }

        $proposal_id = (int)$proposal_id;
        if (!$proposal_id || !$this->_proposal_belongs_to_company($proposal_id)) {
            return $this->response->setJSON(array('data' => array()));
        }

        $proposal = $this->_get_proposal_for_company($proposal_id);
        $prefix = "";
        if ($proposal) {
            $code = "PR-" . str_pad($proposal->id, 6, "0", STR_PAD_LEFT);
            $prefix = $code . " - ";
        }

        $event_ids = $this->_get_linked_reminder_ids($proposal_id);
        if (!$event_ids) {
            return $this->response->setJSON(array('data' => array()));
        }

        $db = db_connect('default');
        $events_table = $db->prefixTable('events');
        $ids_sql = implode(',', $event_ids);

        $sql = "SELECT * FROM $events_table WHERE $events_table.deleted=0 AND $events_table.type='reminder' AND $events_table.id IN ($ids_sql) AND $events_table.created_by=" . (int)$this->login_user->id;
        $list_data = $db->query($sql)->getResult();

        $rows = array();
        foreach ($list_data as $data) {
            $rows[] = $this->_make_reminder_row($data);
        }

        return $this->response->setJSON(array("data" => $rows));
    }

    public function settings()
    {
        if (!$this->_has_settings_permission()) {
            app_redirect('forbidden');
        }

        $company_id = $this->_get_company_id();
        $settings = $this->Proposals_module_settings_model->get_settings($company_id);
        $taxes = array();
        if (!empty($settings->taxes_json)) {
            $decoded = json_decode($settings->taxes_json, true);
            if (is_array($decoded)) {
                $taxes = $decoded;
            }
        }

        $view_data = array(
            'settings' => $settings,
            'taxes' => $taxes,
            'proposal_statuses' => $this->_get_statuses_dropdown(false),
            'status_notification_assignments' => !empty($settings->status_notification_assignments_json)
                ? (json_decode($settings->status_notification_assignments_json, true) ?: array())
                : array(),
            'team_members' => model('App\\Models\\Users_model')->get_all_where(array(
                'deleted' => 0,
                'user_type' => 'staff'
            ))->getResult(),
            'commission_types' => array(
                'percent' => app_lang('proposals_commission_type_percent'),
                'fixed' => app_lang('proposals_commission_type_fixed')
            )
        );

        return $this->template->rander('Proposals\\Views\\settings\\index', $view_data);
    }

    public function save_settings()
    {
        if (!$this->_has_settings_permission()) {
            return $this->_json_permission_denied();
        }

        if (strtolower($this->request->getMethod()) !== 'post') {
            app_redirect('propostas/settings');
        }

        $company_id = $this->_get_company_id();
        $default_commission_type = $this->request->getPost('default_commission_type') ?: 'percent';
        $default_commission_value = $this->request->getPost('default_commission_value');
        $default_commission_value = $this->_parse_decimal($default_commission_value);
        $default_markup_percent = $this->_parse_decimal($this->request->getPost('default_markup_percent'));

        $db = db_connect('default');
        $settings_table = $db->prefixTable('proposals_module_settings_custom');
        if ($db->tableExists($settings_table) && !$db->fieldExists('status_notification_assignments_json', $settings_table)) {
            $db->query("ALTER TABLE `{$settings_table}` ADD `status_notification_assignments_json` TEXT NULL");
        }

        $allowed_statuses = array();
        foreach ($this->_get_statuses_dropdown(false) as $status_row) {
            $allowed_statuses[] = (string)$status_row['id'];
        }
        $allowed_member_ids = array();
        $members = model('App\\Models\\Users_model')->get_all_where(array('deleted' => 0, 'user_type' => 'staff'))->getResult();
        foreach ($members as $member) {
            $allowed_member_ids[] = (string)$member->id;
        }
        $posted_assignments = $this->request->getPost('status_notification_members');
        $status_assignments = array();
        if (is_array($posted_assignments)) {
            foreach ($posted_assignments as $status => $member_ids) {
                if (!in_array((string)$status, $allowed_statuses, true) || !is_array($member_ids)) {
                    continue;
                }
                $member_ids = array_values(array_unique(array_filter(array_map('strval', $member_ids), function ($member_id) use ($allowed_member_ids) {
                    return in_array($member_id, $allowed_member_ids, true);
                })));
                $status_assignments[(string)$status] = $member_ids;
            }
        }

        $tax_names = $this->request->getPost('tax_name');
        $tax_percents = $this->request->getPost('tax_percent');
        $tax_active = $this->request->getPost('tax_active');
        $taxes = array();

        if (is_array($tax_names)) {
            $count = count($tax_names);
            for ($i = 0; $i < $count; $i++) {
                $name = trim((string)$tax_names[$i]);
                $percent = isset($tax_percents[$i]) ? (float)str_replace(",", ".", $tax_percents[$i]) : 0;
                $active = isset($tax_active[$i]) && $tax_active[$i] ? 1 : 0;
                if ($name === '' && !$percent) {
                    continue;
                }
                $taxes[] = array(
                    'name' => $name,
                    'percent' => $percent,
                    'active' => $active ? 1 : 0
                );
            }
        }

        $data = array(
            'company_id' => $company_id,
            'default_commission_type' => $default_commission_type,
            'default_commission_value' => $default_commission_value,
            'default_markup_percent' => $default_markup_percent,
            'taxes_json' => json_encode($taxes),
            'taxes_base' => 'total_sale',
            'status_notification_assignments_json' => json_encode($status_assignments)
        );

        $existing_query = $this->Proposals_module_settings_model->get_details(array("company_id" => $company_id));
        $existing = ($existing_query && method_exists($existing_query, 'getRow')) ? $existing_query->getRow() : null;
        if ($existing_query === false) {
            return $this->response->setJSON(array(
                'success' => false,
                'message' => app_lang('error_occurred')
            ));
        }

        $save_id = $this->Proposals_module_settings_model->ci_save($data, $existing ? $existing->id : 0);

        if ($save_id === false) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('error_occurred')));
        }

        $this->_log_activity('settings_saved', 0);
        return $this->response->setJSON(array(
            'success' => true,
            'message' => app_lang('record_saved')
        ));
    }

    private function _prepare_purchase_request_items_from_post($proposal_id)
    {
        $proposal_items_query = $this->Proposal_items_model->get_details(array('proposal_id' => (int)$proposal_id));
        $proposal_items = ($proposal_items_query && method_exists($proposal_items_query, 'getResult')) ? $proposal_items_query->getResult() : array();

        $proposal_items_map = array();
        foreach ($proposal_items as $item) {
            $proposal_items_map[(int)$item->id] = $item;
        }

        $selected_rows = $this->request->getPost('request_item_selected');
        if (!is_array($selected_rows)) {
            $selected_rows = array();
        }

        $quantities = $this->request->getPost('request_item_quantity');
        $units = $this->request->getPost('request_item_unit');
        $desired_dates = $this->request->getPost('request_item_desired_date');
        $notes = $this->request->getPost('request_item_note');

        $rows = array();
        foreach ($selected_rows as $item_id => $selected) {
            if (!$selected) {
                continue;
            }

            $proposal_item = get_array_value($proposal_items_map, (int)$item_id);
            if (!$proposal_item) {
                continue;
            }
            if (($proposal_item->item_type ?? 'material') !== 'material') {
                continue;
            }

            $description = trim((string)($proposal_item->description_override ?: $proposal_item->item_title));
            $quantity = $this->_parse_decimal(get_array_value($quantities, $item_id));
            $unit = trim((string)get_array_value($units, $item_id));
            $desired_date = trim((string)get_array_value($desired_dates, $item_id));
            $note = trim((string)get_array_value($notes, $item_id));

            $rows[] = array(
                'item_id' => $proposal_item->item_type === 'material' ? ((int)$proposal_item->item_id ?: null) : null,
                'description' => $description,
                'quantity' => $quantity > 0 ? $quantity : (float)$proposal_item->qty,
                'unit' => $unit ?: ($proposal_item->item_unit ?: 'UN'),
                'desired_date' => $desired_date,
                'note' => $note
            );
        }

        $new_item_ids = $this->request->getPost('new_item_id');
        $new_descriptions = $this->request->getPost('new_item_description');
        $new_quantities = $this->request->getPost('new_item_quantity');
        $new_units = $this->request->getPost('new_item_unit');
        $new_desired_dates = $this->request->getPost('new_item_desired_date');
        $new_notes = $this->request->getPost('new_item_note');

        if (!is_array($new_item_ids)) {
            $new_item_ids = array();
        }

        foreach ($new_item_ids as $index => $raw_item_id) {
            $description = trim((string)get_array_value($new_descriptions, $index));
            $quantity = $this->_parse_decimal(get_array_value($new_quantities, $index));
            $unit = trim((string)get_array_value($new_units, $index));
            $desired_date = trim((string)get_array_value($new_desired_dates, $index));
            $note = trim((string)get_array_value($new_notes, $index));
            $item_id = get_only_numeric_value($raw_item_id);

            if (!$item_id && !$description) {
                continue;
            }

            $rows[] = array(
                'item_id' => $item_id ? (int)$item_id : null,
                'description' => $description,
                'quantity' => $quantity > 0 ? $quantity : 1,
                'unit' => $unit ?: 'UN',
                'desired_date' => $desired_date,
                'note' => $note
            );
        }

        $final_rows = $rows;

        if (!$final_rows) {
            return array('success' => false, 'message' => 'Selecione pelo menos um item para a requisição.');
        }

        foreach ($final_rows as $row) {
            if (empty($row['desired_date'])) {
                return array('success' => false, 'message' => app_lang('purchases_desired_date_required'));
            }
        }

        return array('success' => true, 'rows' => $final_rows);
    }

    private function _create_project_from_proposal($proposal)
    {
        $Projects_model = model('App\\Models\\Projects_model');
        $Project_members_model = model('App\\Models\\Project_members_model');
        $db = db_connect('default');
        $projects_table = $db->prefixTable('projects');

        $data = array(
            'title' => trim((string)$proposal->title),
            'description' => trim((string)$proposal->description),
            'client_id' => !empty($proposal->client_id) ? (int)$proposal->client_id : 0,
            'project_type' => !empty($proposal->client_id) ? 'client_project' : 'internal_project',
            'price' => (float)($proposal->total_sale ?? 0),
            'created_date' => get_current_utc_time(),
            'created_by' => $this->login_user->id,
            'status_id' => 1
        );

        if ($db->fieldExists('proposal_id', $projects_table)) {
            $data['proposal_id'] = (int)$proposal->id;
        }

        $project_data = clean_data($data);
        $project_id = $Projects_model->ci_save($project_data);
        if (!$project_id || !is_numeric($project_id)) {
            $project_id = $db->insertID();
        }

        $project_id = (int)$project_id;
        if (!$project_id) {
            return 0;
        }

        $Project_members_model->save_member(array(
            'project_id' => $project_id,
            'user_id' => $this->login_user->id,
            'is_leader' => 1
        ));

        $proposals_table = $db->prefixTable('proposals_custom');
        if ($db->fieldExists('project_id', $proposals_table)) {
            $proposal_project_data = array('project_id' => $project_id);
            $this->Proposals_model->ci_save($proposal_project_data, (int)$proposal->id);
        }

        // Criar centro de custo no Conta Azul
        $this->_create_contaazul_cost_center($project_id);

        return $project_id;
    }

    private function _create_contaazul_cost_center($project_id)
    {
        $project_id = (int) $project_id;
        if (!$project_id) {
            return;
        }

        if (!class_exists('\\ContaAzul\\Libraries\\ContaAzulClient')) {
            return;
        }

        $Projects_model = model('App\\Models\\Projects_model');
        $project = $Projects_model->get_one($project_id);
        if (!$project || empty($project->id)) {
            return;
        }

        $db = db_connect('default');
        $projects_table = $db->prefixTable('projects');
        $cost_centers_table = $db->prefixTable('contaazul_cost_centers');

        // Criar tabela e coluna se não existirem
        if (!$db->fieldExists('cost_center_id', $projects_table)) {
            $db->query("ALTER TABLE `{$projects_table}` ADD `cost_center_id` INT(11) DEFAULT NULL");
        }
        if (!$db->tableExists($cost_centers_table)) {
            $db->query("CREATE TABLE IF NOT EXISTS `{$cost_centers_table}` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `project_id` INT(11) DEFAULT NULL,
                `contaazul_id` VARCHAR(50) DEFAULT NULL,
                `code` VARCHAR(20) DEFAULT NULL,
                `name` VARCHAR(255) DEFAULT NULL,
                `is_active` TINYINT(1) DEFAULT 1,
                `created_at` DATETIME DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `project_id` (`project_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
        }

        if (!$db->fieldExists('cost_center_id', $projects_table) || !$db->tableExists($cost_centers_table)) {
            return;
        }

        $title = trim((string) ($project->title ?? ''));
        if ($title === '') {
            return;
        }

        $costCenterTitle = 'PROJETO - ' . $title;

        $clientId = get_setting("contaazul_client_id");
        $clientSecret = get_setting("contaazul_client_secret");
        $redirectUri = get_setting("contaazul_redirect_uri") ?: get_uri("contaazul/callback");
        $scope = get_setting("contaazul_scope") ?: "openid profile aws.cognito.signin.user.admin";

        if (!$clientId || !$clientSecret) {
            return;
        }

        $client = new \ContaAzul\Libraries\ContaAzulClient(
            $clientId,
            $clientSecret,
            $redirectUri,
            $scope,
            get_setting("contaazul_access_token"),
            get_setting("contaazul_refresh_token"),
            get_setting("contaazul_token_expires_at")
        );

        if ($client->isExpired() && get_setting("contaazul_refresh_token")) {
            $refresh = $client->refreshAccessToken(get_setting("contaazul_refresh_token"));
            if ($refresh["ok"]) {
                $tokens = $client->getTokens();
                $settingsModel = model('App\\Models\\Settings_model');
                $settingsModel->save_setting("contaazul_access_token", $tokens["access_token"] ?? "");
                $settingsModel->save_setting("contaazul_refresh_token", $tokens["refresh_token"] ?? "");
                $settingsModel->save_setting("contaazul_token_expires_at", $tokens["expires_at"] ?? "");
            } else {
                log_message('error', 'ContaAzul cost center create: token refresh failed for project ' . $project_id . ' - ' . ($refresh['body'] ?? ''));
                return;
            }
        }

        $response = $client->createCostCenter($costCenterTitle);
        if (!$response["ok"]) {
            log_message('error', 'ContaAzul cost center create failed for project ' . $project_id . ' - HTTP ' . ($response['status'] ?? 0) . ' - ' . ($response['body'] ?? ''));
            return;
        }

        $payload = is_array($response["data"]) ? $response["data"] : array();
        $caId = $payload["id"] ?? ($payload["uuid"] ?? null);
        $code = $payload["codigo"] ?? ($payload["code"] ?? null);
        $isActive = isset($payload["ativo"]) ? (int) !!$payload["ativo"] : (isset($payload["active"]) ? (int) !!$payload["active"] : 1);
        $savedTitle = trim((string) ($payload["descricao"] ?? ($payload["description"] ?? ($payload["nome"] ?? ($payload["name"] ?? $costCenterTitle)))));

        $insert_data = array(
            'project_id' => $project_id,
            'contaazul_id' => $caId,
            'code' => $code,
            'name' => $savedTitle,
            'is_active' => $isActive,
            'created_at' => get_current_utc_time()
        );

        $db->table($cost_centers_table)->insert($insert_data);
        $cc_id = $db->insertID();

        if ($cc_id) {
            $db->table($projects_table)->where('id', $project_id)->update(['cost_center_id' => $cc_id]);
            log_message('info', 'Centro de custo criado no Conta Azul para o projeto ' . $project_id);
        }
    }

    private function _create_purchase_request_from_proposal($proposal, $project_id, $rows)
    {
        $Purchases_requests_model = model('Purchases\\Models\\Purchases_requests_model');
        $Purchases_request_items_model = model('Purchases\\Models\\Purchases_request_items_model');

        $code_data = $Purchases_requests_model->get_next_request_code_data($this->_get_company_id());
        $request_data = array(
            'company_id' => $this->_get_company_id(),
            'project_id' => $project_id ? (int)$project_id : null,
            'os_id' => null,
            'is_internal' => $project_id ? 0 : 1,
            'cost_center' => '',
            'priority' => 'medium',
            'note' => 'Gerada a partir da proposta PR-' . str_pad((int)$proposal->id, 6, '0', STR_PAD_LEFT),
            'updated_at' => get_my_local_time(),
            'request_code_number' => $code_data['request_code_number'],
            'request_code' => $code_data['request_code'],
            'requested_by' => $this->login_user->id,
            'requester_id' => $this->login_user->id,
            'request_date' => get_my_local_time(),
            'created_at' => get_my_local_time(),
            'created_by' => $this->login_user->id,
            'status' => 'draft'
        );

        $request_id = $Purchases_requests_model->ci_save($request_data);
        if (!$request_id || !is_numeric($request_id)) {
            $request_id = db_connect('default')->insertID();
        }

        $request_id = (int)$request_id;
        if (!$request_id) {
            return 0;
        }

        foreach ($rows as $row) {
            $item_data = array(
                'company_id' => $this->_get_company_id(),
                'request_id' => $request_id,
                'item_id' => get_array_value($row, 'item_id'),
                'description' => get_array_value($row, 'description'),
                'unit' => get_array_value($row, 'unit') ?: 'UN',
                'quantity' => (float)get_array_value($row, 'quantity'),
                'rate' => 0,
                'total' => 0,
                'desired_date' => get_array_value($row, 'desired_date'),
                'note' => get_array_value($row, 'note'),
                'created_at' => get_my_local_time(),
                'created_by' => $this->login_user->id
            );

            if (!$Purchases_request_items_model->save($item_data)) {
                return 0;
            }
        }

        return $request_id;
    }

    private function _duplicate_proposal($proposal)
    {
        $db = db_connect('default');
        $db->transStart();

        $new_proposal_data = array(
            'company_id' => (int)$proposal->company_id,
            'client_id' => !empty($proposal->client_id) ? (int)$proposal->client_id : null,
            'client_name' => $proposal->client_name ?? '',
            'title' => trim((string)$proposal->title) . ' (Cópia)',
            'description' => $proposal->description ?? '',
            'payment_terms' => $proposal->payment_terms ?? '',
            'observations' => $proposal->observations ?? '',
            'validity_days' => $proposal->validity_days ?? null,
            'status' => 'draft',
            'commission_type' => $proposal->commission_type ?? 'percent',
            'commission_value' => $proposal->commission_value ?? 0,
            'tax_product_percent' => $proposal->tax_product_percent ?? 0,
            'tax_service_percent' => $proposal->tax_service_percent ?? 0,
            'tax_service_only' => $proposal->tax_service_only ?? 0,
            'taxes_snapshot_json' => $proposal->taxes_snapshot_json ?? '',
            'created_at' => get_my_local_time(),
            'created_by' => $this->login_user->id,
            'updated_at' => get_my_local_time()
        );

        $proposals_table = $db->prefixTable('proposals_custom');
        if (!$db->fieldExists('client_name', $proposals_table)) {
            unset($new_proposal_data['client_name']);
        }

        $new_proposal_id = $this->Proposals_model->ci_save($new_proposal_data);
        if (!$new_proposal_id || !is_numeric($new_proposal_id)) {
            $new_proposal_id = $db->insertID();
        }

        $new_proposal_id = (int)$new_proposal_id;
        if (!$new_proposal_id) {
            $db->transRollback();
            return 0;
        }

        $sections_query = $this->Proposal_sections_model->get_details(array('proposal_id' => (int)$proposal->id));
        $sections = ($sections_query && method_exists($sections_query, 'getResult')) ? $sections_query->getResult() : array();
        $section_map = array();
        $pending_sections = $sections;

        while ($pending_sections) {
            $progress = false;
            foreach ($pending_sections as $index => $section) {
                $old_parent_id = (int)($section->parent_id ?? 0);
                if ($old_parent_id && !isset($section_map[$old_parent_id])) {
                    continue;
                }

                $section_data = array(
                    'proposal_id' => $new_proposal_id,
                    'parent_id' => $old_parent_id ? $section_map[$old_parent_id] : null,
                    'title' => $section->title ?? '',
                    'sort' => $section->sort ?? 0
                );

                $new_section_id = $this->Proposal_sections_model->ci_save($section_data);
                if (!$new_section_id || !is_numeric($new_section_id)) {
                    $new_section_id = $db->insertID();
                }

                $section_map[(int)$section->id] = (int)$new_section_id;
                unset($pending_sections[$index]);
                $progress = true;
            }

            if (!$progress) {
                break;
            }
        }

        $items_query = $this->Proposal_items_model->get_details(array('proposal_id' => (int)$proposal->id));
        $items = ($items_query && method_exists($items_query, 'getResult')) ? $items_query->getResult() : array();
        foreach ($items as $item) {
            $item_data = array(
                'proposal_id' => $new_proposal_id,
                'section_id' => !empty($item->section_id) ? get_array_value($section_map, (int)$item->section_id) : null,
                'item_id' => $item->item_id ?: null,
                'item_type' => $item->item_type ?? 'material',
                'description_override' => $item->description_override ?? '',
                'cost_unit' => $item->cost_unit ?? 0,
                'qty' => $item->qty ?? 0,
                'markup_percent' => $item->markup_percent ?? 0,
                'sale_unit' => $item->sale_unit ?? 0,
                'total' => $item->total ?? 0,
                'show_in_proposal' => $item->show_in_proposal ?? 0,
                'show_values_in_proposal' => $item->show_values_in_proposal ?? 0,
                'in_memory' => $item->in_memory ?? 0,
                'sort' => $item->sort ?? 0
            );

            $new_item_id = $this->Proposal_items_model->ci_save($item_data);
            if (!$new_item_id && !$db->insertID()) {
                $db->transRollback();
                return 0;
            }
        }

        $this->Proposals_model->calculate_totals($new_proposal_id);

        $db->transComplete();
        if (!$db->transStatus()) {
            return 0;
        }

        return $new_proposal_id;
    }

    private function _has_view_permission()
    {
        if ($this->login_user->is_admin) {
            return true;
        }

        $permissions = $this->login_user->permissions ?? array();
        return get_array_value($permissions, 'proposals_view') == '1'
            || get_array_value($permissions, 'proposals_manage') == '1'
            || get_array_value($permissions, 'proposals_export_pdf') == '1'
            || get_array_value($permissions, 'proposals_settings_manage') == '1';
    }

    private function _has_manage_permission()
    {
        if ($this->login_user->is_admin) {
            return true;
        }

        $permissions = $this->login_user->permissions ?? array();
        return get_array_value($permissions, 'proposals_manage') == '1';
    }

    private function _has_settings_permission()
    {
        if ($this->login_user->is_admin) {
            return true;
        }

        $permissions = $this->login_user->permissions ?? array();
        return get_array_value($permissions, 'proposals_settings_manage') == '1';
    }

    private function _get_company_id()
    {
        if (isset($this->login_user->company_id) && $this->login_user->company_id) {
            return $this->login_user->company_id;
        }

        return get_default_company_id();
    }

    private function _get_statuses_dropdown($include_blank = true)
    {
        $rows = array();
        if ($include_blank) {
            $rows[] = array('id' => '', 'text' => '- ' . app_lang('status') . ' -');
        }
        $rows[] = array('id' => 'draft', 'text' => app_lang('proposals_status_draft'));
        $rows[] = array('id' => 'levantamento', 'text' => app_lang('proposals_status_levantamento'));
        $rows[] = array('id' => 'proposta', 'text' => app_lang('proposals_status_proposta'));
        $rows[] = array('id' => 'sent', 'text' => app_lang('proposals_status_sent'));
        $rows[] = array('id' => 'approved', 'text' => app_lang('proposals_status_approved'));
        $rows[] = array('id' => 'rejected', 'text' => app_lang('proposals_status_rejected'));
        $rows[] = array('id' => 'archived', 'text' => app_lang('proposals_status_archived'));

        return $rows;
    }

    private function _notify_status_members($proposal_id, $status)
    {
        $settings = $this->Proposals_module_settings_model->get_settings($this->_get_company_id());
        $assignments = !empty($settings->status_notification_assignments_json)
            ? json_decode($settings->status_notification_assignments_json, true)
            : array();
        $member_ids = is_array($assignments) && isset($assignments[$status]) ? $assignments[$status] : array();
        $member_ids = array_values(array_unique(array_filter(array_map('intval', (array)$member_ids))));
        if (!$member_ids) {
            return;
        }

        helper('notifications');
        log_notification('proposal_status_changed', array(
            'proposal_id' => (int)$proposal_id,
            'multiple_tasks_notify_to_user_ids' => implode(',', $member_ids),
            'plugin_proposal_status' => $status
        ), $this->login_user->id);
    }

    private function _make_row($data)
    {
        $code = 'PR-' . str_pad($data->id, 6, '0', STR_PAD_LEFT);
        $client = $data->client_company ? $data->client_company : ($data->client_name ? $data->client_name : '-');
        $status = $data->status ? $data->status : 'draft';
        $status_label = $this->_get_status_label($status);
        $total_value = $this->Proposals_model->get_items_total($data->id);
        $total = to_currency($total_value);
        $updated = isset($data->updated_at) && $data->updated_at ? $data->updated_at : (isset($data->created_at) ? $data->created_at : '');

        $actions = anchor(get_uri('propostas/view/' . $data->id), "<i data-feather='eye' class='icon-16'></i>", array(
            'title' => app_lang('view'),
            'class' => 'btn btn-sm btn-outline-secondary'
        ));

        if ($this->_has_manage_permission()) {
            $actions .= ' ' . modal_anchor(get_uri('propostas/modal_form/' . $data->id), "<i data-feather='edit' class='icon-16'></i>", array(
                'title' => app_lang('edit'),
                'class' => 'btn btn-sm btn-outline-secondary'
            ));
            $actions .= ' ' . js_anchor("<i data-feather='x' class='icon-16'></i>", array(
                'title' => app_lang('delete'),
                'class' => 'btn btn-sm btn-outline-danger delete',
                'data-id' => $data->id,
                'data-action-url' => get_uri('propostas/delete'),
                'data-action' => 'delete-confirmation'
            ));
        }

        $title = isset($data->title) ? $data->title : '-';

        return array(
            $code,
            esc($title),
            esc($client),
            $status_label,
            $total,
            format_to_date($updated, false),
            $actions
        );
    }

    private function _json_permission_denied()
    {
        return $this->response->setJSON(array('success' => false, 'message' => app_lang('permission_denied')));
    }

    private function _get_status_label($status)
    {
        $class_map = array(
            'draft' => 'secondary',
            'sent' => 'info',
            'approved' => 'success',
            'rejected' => 'danger',
            'archived' => 'dark'
        );

        $class = get_array_value($class_map, $status, 'secondary');

        return "<span class='badge bg-" . $class . "'>" . app_lang('proposals_status_' . $status) . "</span>";
    }

    private function _get_linked_task_ids($proposal_id)
    {
        $proposal_id = (int)$proposal_id;
        if (!$proposal_id) {
            return array();
        }

        $db = db_connect('default');
        $table = $db->prefixTable('proposal_task_links_custom');
        if (!$db->tableExists($table)) {
            return array();
        }

        $rows = $db->table($table)->select('task_id')->where('proposal_id', $proposal_id)->where('deleted', 0)->get()->getResult();
        if (!$rows) {
            return array();
        }

        return array_values(array_filter(array_map(function ($row) {
            return (int)($row->task_id ?? 0);
        }, $rows)));
    }

    private function _get_linked_reminder_ids($proposal_id)
    {
        $proposal_id = (int)$proposal_id;
        if (!$proposal_id) {
            return array();
        }

        $db = db_connect('default');
        $table = $db->prefixTable('proposal_reminder_links_custom');
        if (!$db->tableExists($table)) {
            return array();
        }

        $rows = $db->table($table)->select('event_id')->where('proposal_id', $proposal_id)->where('deleted', 0)->get()->getResult();
        if (!$rows) {
            return array();
        }

        return array_values(array_filter(array_map(function ($row) {
            return (int)($row->event_id ?? 0);
        }, $rows)));
    }

    private function _make_task_row_simple($data)
    {
        $title_value = $data->title;
        $title = modal_anchor(get_uri("tasks/view"), $title_value, array(
            "title" => app_lang('task_info') . " #$data->id",
            "data-post-id" => $data->id,
            "data-modal-lg" => "1"
        ));

        $assigned_to = "-";
        if (!empty($data->assigned_to)) {
            $assigned_name = $data->assigned_to_user ?? "";
            if ($assigned_name) {
                if (!empty($data->user_type) && $data->user_type !== "staff") {
                    $assigned_to = get_client_contact_profile_link($data->assigned_to, $assigned_name);
                } else {
                    $assigned_to = get_team_member_profile_link($data->assigned_to, $assigned_name);
                }
            }
        }

        $status_text = $data->status_key_name ? app_lang($data->status_key_name) : ($data->status_title ?? "-");
        $status = "<span class='badge bg-secondary'>" . esc($status_text) . "</span>";

        $options = "";
        if ($this->login_user->is_admin || (int)$data->created_by === (int)$this->login_user->id) {
            $options .= modal_anchor(get_uri("tasks/modal_form"), "<i data-feather='edit' class='icon-16'></i>", array(
                "class" => "edit",
                "title" => app_lang('edit_task'),
                "data-post-id" => $data->id
            ));
            $options .= js_anchor("<i data-feather='x' class='icon-16'></i>", array(
                'title' => app_lang('delete_task'),
                "class" => "delete",
                "data-id" => $data->id,
                "data-action-url" => get_uri("tasks/delete"),
                "data-action" => "delete-confirmation"
            ));
        }

        return array(
            $data->id,
            $title,
            $assigned_to,
            $status,
            $options
        );
    }

    private function _make_reminder_row($data)
    {
        $context_info = get_reminder_context_info($data);
        $context_icon = get_array_value($context_info, "context_icon");
        $context_icon = $context_icon ? "<i class='icon-14 text-off' data-feather='$context_icon'></i> " : "";
        $title_text = $data->title;
        $title_value = "<span class='strong'>$context_icon" . link_it($title_text) . "</span>";

        $icon = "";
        $target_date = "";
        if ($data->snoozing_time) {
            $icon = "<span class='icon-14 text-off'>" . view("reminders/svg_icons/snooze") . "</span>";
            $target_date = new \DateTime($data->snoozing_time);
        } else if ($data->recurring) {
            $icon = "<i class='icon-14 text-off' data-feather='repeat'></i>";
            if ($data->next_recurring_time) {
                $target_date = new \DateTime($data->next_recurring_time);
            }
        }

        if ($target_date) {
            $data->start_date = $target_date->format("Y-m-d");
            $data->start_time = $target_date->format("H:i:s");
        }

        $data->end_date = $data->start_date;
        $time_value = view("events/event_time", array("model_info" => $data, "is_reminder" => true));
        $time_value = "<div class='small'>$icon " . $time_value . "</div>";

        $missed_reminder_class = "";
        $local_time = get_my_local_time("Y-m-d H:i") . ":00";

        if ($data->reminder_status === 'new' && ($data->start_date . ' ' . $data->start_time) < $local_time) {
            $missed_reminder_class = "missed-reminder";
        }

        $title = "<span class='$missed_reminder_class'>" . $title_value . $time_value . "</span>";

        $delete = '<li role="presentation">' . js_anchor("<i data-feather='x' class='icon-16'></i>" . app_lang('delete'), array('title' => app_lang('delete_reminder'), "class" => "delete dropdown-item reminder-action", "data-id" => $data->id, "data-post-id" => $data->id, "data-action-url" => get_uri("events/delete"), "data-action" => "delete", "data-undo" => "0")) . '</li>';
        $status = '<li role="presentation">' . js_anchor("<i data-feather='check-circle' class='icon-16'></i> " . app_lang('mark_as_done'), array('title' => app_lang('mark_as_done'), "class" => "dropdown-item reminder-action", "data-action-url" => get_uri("events/save_reminder_status/$data->id/done"), "data-action" => "delete", "data-undo" => "0")) . '</li>';
        if ($data->reminder_status === "done" || $data->reminder_status === "shown") {
            $status = "";
        }

        $options = '<span class="dropdown inline-block">
                        <div class="dropdown-toggle clickable p10" type="button" data-bs-toggle="dropdown" aria-expanded="true" data-bs-display="static">
                            <i data-feather="more-horizontal" class="icon-16"></i>
                        </div>
                        <ul class="dropdown-menu dropdown-menu-end" role="menu">' . $status . $delete . '</ul>
                    </span>';

        if ($missed_reminder_class) {
            $options = js_anchor("<i data-feather='check-circle' class='icon-16'></i>", array('title' => app_lang('mark_as_done'), "class" => "reminder-action p10", "data-action-url" => get_uri("events/save_reminder_status/$data->id/done"), "data-action" => "delete", "data-undo" => "0"));
        }

        return array(
            $data->start_date . " " . $data->start_time,
            $title,
            $options
        );
    }

    private function _get_proposal_for_company($proposal_id)
    {
        $proposal_id = (int)$proposal_id;
        if (!$proposal_id) {
            return null;
        }

        return $this->Proposals_model->get_details(array(
            'id' => $proposal_id,
            'company_id' => $this->_get_company_id()
        ))->getRow();
    }

    private function _proposal_belongs_to_company($proposal_id)
    {
        return $this->_get_proposal_for_company($proposal_id) ? true : false;
    }

    private function _get_items_options_html()
    {
        $db = db_connect('default');
        $items_table = $db->prefixTable('items');
        $has_cost = $db->fieldExists("cost", $items_table);
        $has_sale = $db->fieldExists("sale", $items_table);
        $select = 'id, title, rate, unit_type';
        if ($has_cost) {
            $select .= ', cost';
        }
        if ($has_sale) {
            $select .= ', sale';
        }
        $rows = $db->table($items_table)
            ->select($select)
            ->where('deleted', 0)
            ->orderBy('title', 'ASC')
            ->get()
            ->getResult();

        $options = "<option value=''>-</option>";
        foreach ($rows as $row) {
            $title = esc($row->title);
            $rate = ($has_cost && isset($row->cost) && is_numeric($row->cost))
                ? $row->cost
                : (is_numeric($row->rate) ? $row->rate : 0);
            $sale = ($has_sale && isset($row->sale) && is_numeric($row->sale)) ? $row->sale : 0;
            $unit = $row->unit_type ? esc($row->unit_type) : '';
            $options .= "<option value='{$row->id}' data-rate='{$rate}' data-sale='{$sale}' data-unit='{$unit}' data-type='material'>{$title}</option>";
        }

        if ($this->_os_services_table_exists($db)) {
            $services_table = $db->prefixTable('os_servicos');
            $services = $db->table($services_table)
                ->select('id, descricao, custo, valor_venda')
                ->where('deleted', 0)
                ->orderBy('descricao', 'ASC')
                ->get()
                ->getResult();
            foreach ($services as $service) {
                $title = esc($service->descricao);
                $cost = is_numeric($service->custo) ? $service->custo : 0;
                $sale = is_numeric($service->valor_venda) ? $service->valor_venda : 0;
                $options .= "<option value='s-{$service->id}' data-rate='{$cost}' data-sale='{$sale}' data-type='service'>{$title}</option>";
            }
        }

        return $options;
    }

    private function _os_services_table_exists($db)
    {
        if (!$db) {
            $db = db_connect('default');
        }
        $table = $db->prefixTable('os_servicos');
        $query = $db->query("SHOW TABLES LIKE " . $db->escape($table));
        return $query && $query->getRow() ? true : false;
    }

    private function _get_default_item_category_id()
    {
        $db = db_connect('default');
        $categories_table = $db->prefixTable('item_categories');
        $row = $db->table($categories_table)
            ->select('id')
            ->where('deleted', 0)
            ->orderBy('id', 'ASC')
            ->get()
            ->getRow();

        if ($row && $row->id) {
            return (int)$row->id;
        }

        $insert_data = array(
            'title' => 'Geral',
            'deleted' => 0
        );

        $db->table($categories_table)->insert($insert_data);
        $new_id = $db->insertID();
        return $new_id ? (int)$new_id : 0;
    }

    private function _section_belongs_to_proposal($section_id, $proposal_id)
    {
        $section = $this->Proposal_sections_model->get_one((int)$section_id);
        if (!$section || $section->deleted) {
            return false;
        }

        return ((int)$section->proposal_id === (int)$proposal_id);
    }

    private function _collect_section_descendants($proposal_id, $parent_id)
    {
        $proposal_id = (int)$proposal_id;
        $parent_id = (int)$parent_id;
        if (!$proposal_id || !$parent_id) {
            return array();
        }

        $sections_query = $this->Proposal_sections_model->get_details(array('proposal_id' => $proposal_id));
        $sections = ($sections_query && method_exists($sections_query, 'getResult')) ? $sections_query->getResult() : array();

        $children = array();
        foreach ($sections as $section) {
            if ((int)$section->parent_id === $parent_id) {
                $children[] = (int)$section->id;
                $children = array_merge($children, $this->_collect_section_descendants($proposal_id, (int)$section->id));
            }
        }

        return $children;
    }

    private function _get_next_section_sort($proposal_id, $parent_id = null)
    {
        $proposal_id = (int)$proposal_id;
        $db = db_connect('default');
        $table = $db->prefixTable('proposal_sections_custom');
        $parent_sql = $parent_id ? "AND $table.parent_id=" . (int)$parent_id : "AND $table.parent_id IS NULL";
        $query = $db->query("SELECT MAX($table.sort) AS sort FROM $table WHERE $table.deleted=0 AND $table.proposal_id=$proposal_id $parent_sql");
        if (!$query) {
            return 1;
        }
        $row = $query->getRow();
        return $row && $row->sort ? ((int)$row->sort + 1) : 1;
    }

    private function _get_next_item_sort($proposal_id, $section_id)
    {
        $proposal_id = (int)$proposal_id;
        $db = db_connect('default');
        $table = $db->prefixTable('proposal_items_custom');
        if ($section_id) {
            $section_id = (int)$section_id;
            $query = $db->query("SELECT MAX($table.sort) AS sort FROM $table WHERE $table.deleted=0 AND $table.proposal_id=$proposal_id AND $table.section_id=$section_id");
        } else {
            $query = $db->query("SELECT MAX($table.sort) AS sort FROM $table WHERE $table.deleted=0 AND $table.proposal_id=$proposal_id AND $table.section_id IS NULL");
        }
        if (!$query) {
            return 1;
        }
        $row = $query->getRow();
        return $row && $row->sort ? ((int)$row->sort + 1) : 1;
    }

    private function _prepare_item_data($proposal_id, $section_id, $existing_item = null)
    {
        $item_id_raw = trim((string)$this->request->getPost('item_id'));
        $item_type = $this->request->getPost('item_type') ?: 'material';
        if ($item_id_raw !== '' && strpos($item_id_raw, 's-') === 0) {
            $item_type = 'service';
            $item_id = (int)substr($item_id_raw, 2);
        } else {
            $item_id = get_only_numeric_value($item_id_raw);
        }
        $description = trim((string)$this->request->getPost('description'));
        $qty = (float)str_replace(",", ".", $this->request->getPost('qty'));
        $qty = $qty > 0 ? $qty : 1;

        $cost_unit = (float)str_replace(",", ".", $this->request->getPost('cost_unit'));
        $markup_percent_raw = $this->request->getPost('markup_percent');
        $markup_percent = $this->_parse_decimal($markup_percent_raw);
        if (($markup_percent_raw === null || $markup_percent_raw === '') && !$existing_item) {
            $settings = $this->Proposals_module_settings_model->get_settings($this->_get_company_id());
            $markup_percent = (float)($settings->default_markup_percent ?? 0);
        }
        $sale_unit = (float)str_replace(",", ".", $this->request->getPost('sale_unit'));
        if ($sale_unit <= 0) {
            $sale_unit = $cost_unit > 0 ? ($cost_unit * (1 + ($markup_percent / 100))) : 0;
        }

        $total = $qty * $sale_unit;

        $show_in_proposal = $this->request->getPost('show_in_proposal');
        if ($show_in_proposal === null || $show_in_proposal === '') {
            $show_in_proposal = $existing_item ? (int)($existing_item->show_in_proposal ?? 0) : 0;
        }
        $show_values = $this->request->getPost('show_values_in_proposal');
        if ($show_values === null || $show_values === '') {
            $show_values = $existing_item ? (int)($existing_item->show_values_in_proposal ?? 0) : 0;
        }
        $in_memory = $this->request->getPost('in_memory');
        if ($in_memory === null || $in_memory === '') {
            $in_memory = $existing_item ? (int)($existing_item->in_memory ?? 1) : 1;
        }

        return array(
            'proposal_id' => (int)$proposal_id,
            'section_id' => $section_id ? (int)$section_id : null,
            'item_id' => $item_id ? $item_id : null,
            'item_type' => $item_type,
            'description_override' => $description,
            'cost_unit' => $cost_unit,
            'qty' => $qty,
            'markup_percent' => $markup_percent,
            'sale_unit' => $sale_unit,
            'total' => $total,
            'show_in_proposal' => $show_in_proposal ? 1 : 0,
            'show_values_in_proposal' => $show_values ? 1 : 0,
            'in_memory' => $in_memory ? 1 : 0
        );
    }

    private function _parse_decimal($value)
    {
        $text = trim((string)$value);
        if ($text === '') {
            return 0;
        }

        $text = preg_replace('/[^\d,\.\-]/', '', $text);
        $last_comma = strrpos($text, ',');
        $last_dot = strrpos($text, '.');

        if ($last_comma !== false && $last_dot !== false) {
            if ($last_comma > $last_dot) {
                $text = str_replace('.', '', $text);
                $text = str_replace(',', '.', $text);
            } else {
                $text = str_replace(',', '', $text);
            }
        } elseif ($last_comma !== false) {
            $text = str_replace('.', '', $text);
            $text = str_replace(',', '.', $text);
        } else {
            $text = str_replace(',', '', $text);
        }

        return (float)$text;
    }

    private function _render_document_html($proposal, $sections, $items, $mode = null)
    {
        $display_mode = $mode ? $mode : ($proposal->display_mode ?? 'detailed');
        $renderer = new \Proposals\Libraries\Proposals_document();
        return $renderer->render($proposal, $sections, $items, $display_mode);
    }

    private function _log_activity($action, $proposal_id = 0, $context_id = 0)
    {
        $user_id = $this->login_user->id ?? 0;
        $message = "[Proposals] action={$action} user_id={$user_id} proposal_id={$proposal_id}";
        if ($context_id) {
            $message .= " context_id={$context_id}";
        }
        log_message('info', $message);
    }

    private function _get_dashboard_data($proposal)
    {
        $total_cost_material = (float)($proposal->total_cost_material ?? 0);
        $total_cost_service = (float)($proposal->total_cost_service ?? 0);
        $total_sale = (float)($proposal->total_sale ?? 0);
        $taxes_total = (float)($proposal->taxes_total ?? 0);
        $commission_total = (float)($proposal->commission_total ?? 0);
        $cost_total = $total_cost_material + $total_cost_service;
        $gross_profit = $total_sale - $cost_total;
        $net_profit = $gross_profit - $taxes_total - $commission_total;
        $markup_avg = $cost_total > 0 ? (($total_sale / $cost_total) - 1) * 100 : 0;

        $status = $proposal->status ?? 'draft';
        $status_label = $this->_get_status_label($status);
        $updated_at = $proposal->updated_at ?? $proposal->created_at ?? '';

        return array(
            'total_cost_material' => to_currency($total_cost_material),
            'total_cost_service' => to_currency($total_cost_service),
            'total_sale' => to_currency($total_sale),
            'taxes_total' => to_currency($taxes_total),
            'commission_total' => to_currency($commission_total),
            'gross_profit' => to_currency($gross_profit),
            'net_profit' => to_currency($net_profit),
            'markup_avg' => number_format($markup_avg, 2, ",", ".") . '%',
            'total_cost_material_n' => $total_cost_material,
            'total_cost_service_n' => $total_cost_service,
            'total_sale_n' => $total_sale,
            'taxes_total_n' => $taxes_total,
            'commission_total_n' => $commission_total,
            'gross_profit_n' => $gross_profit,
            'net_profit_n' => $net_profit,
            'status' => $status_label,
            'updated_at' => $updated_at ? format_to_date($updated_at, false) : '-',
            'created_by' => $proposal->created_by_name ?? '-'
        );
    }
}
