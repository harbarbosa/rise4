<?php

namespace Engenharia\Controllers;

use Engenharia\Models\Checklists_model;
use Engenharia\Models\Laudo_types_model;
use Engenharia\Models\Laudos_model;
use Engenharia\Services\LaudoDomainService;
use Engenharia\Services\LaudoWorkflowService;
use Engenharia\Models\Status_history_model;
use Engenharia\Models\Checklist_groups_model;
use Engenharia\Models\Checklist_items_model;
use Engenharia\Models\Checklist_responses_model;
use Engenharia\Models\Laudo_areas_model;
use Engenharia\Models\Nonconformities_model;
use Engenharia\Models\Photos_model;
use Engenharia\Models\Measurements_model;
use Engenharia\Models\Instruments_model;
use Engenharia\Models\Settings_model;
use Engenharia\Services\LaudoReportService;

class Engenharia extends Engenharia_Base_Controller
{
    private $laudos_model;
    private $types_model;
    private $checklists_model;
    private $history_model;
    private $groups_model;
    private $items_model;
    private $responses_model;
    private $areas_model;
    private $nonconformities_model;
    private $photos_model;
    private $measurements_model;
    private $instruments_model;
    private $settings_model;

    public function __construct()
    {
        parent::__construct();
        $this->laudos_model = model(Laudos_model::class);
        $this->types_model = model(Laudo_types_model::class);
        $this->checklists_model = model(Checklists_model::class);
        $this->history_model = model(Status_history_model::class);
        $this->groups_model = model(Checklist_groups_model::class);
        $this->items_model = model(Checklist_items_model::class);
        $this->responses_model = model(Checklist_responses_model::class);
        $this->areas_model = model(Laudo_areas_model::class);
        $this->nonconformities_model = model(Nonconformities_model::class);
        $this->photos_model = model(Photos_model::class);
        $this->measurements_model = model(Measurements_model::class);
        $this->instruments_model = model(Instruments_model::class);
        $this->settings_model = model(Settings_model::class);
    }

    public function index()
    {
        $this->requirePermission('engenharia_access');
        return $this->renderPluginView('dashboard/index', $this->getDashboardData());
    }

    public function laudos()
    {
        $this->requirePermission('engenharia_view_laudos');
        return $this->renderPluginView('laudos/index', array(
            'types_dropdown' => $this->getTypesDropdown(),
            'clients_dropdown' => $this->getClientsDropdown(),
            'team_members_dropdown' => $this->getTeamMembersDropdown(),
            'status_dropdown' => engenharia_status_dropdown(),
            'can_create' => \Engenharia\Plugin::hasPermission($this->login_user, 'engenharia_create_laudos'),
        ));
    }

    public function checklists()
    {
        $this->requirePermission('engenharia_manage_checklists');
        return $this->renderPluginView('checklists/index', array('types_dropdown' => $this->getTypesDropdown(), 'checklists' => $this->checklists_model->get_details()->getResult()));
    }

    public function checklist_modal_form($id = 0)
    {
        $this->requirePermission('engenharia_manage_checklists');
        $info = $id ? $this->checklists_model->get_version((int) $id) : (object) array('id'=>0,'name'=>'','code'=>'','description'=>'','type_id'=>'','status'=>'draft','is_enabled'=>1);
        if (!$info || ($id && ($info->status === 'published' || $this->checklists_model->isUsed((int) $id)))) { app_redirect('forbidden'); }
        return $this->renderPluginModalView('checklists/modal_form', array('model_info'=>$info, 'types_dropdown'=>$this->getTypesDropdown()));
    }

    public function checklist_save()
    {
        $this->requirePermission('engenharia_manage_checklists');
        $id=(int)$this->request->getPost('id'); $data=array('name'=>trim((string)$this->request->getPost('name')),'code'=>trim((string)$this->request->getPost('code')),'description'=>trim((string)$this->request->getPost('description')),'type_id'=>(int)$this->request->getPost('type_id'),'updated_by'=>(int)$this->login_user->id);
        if (!$data['name'] || !$data['code'] || !$data['type_id']) return $this->response->setJSON(array('success'=>false,'message'=>app_lang('field_required')));
        try { if (!$id) {$data['created_by']=(int)$this->login_user->id;} $saved=$this->checklists_model->save_record($data,$id); return $this->response->setJSON(array('success'=>true,'id'=>$saved,'message'=>app_lang('record_saved'))); } catch(\Throwable $e){log_message('error','[Engenharia] checklist save: '.$e->getMessage());return $this->response->setJSON(array('success'=>false,'message'=>$e->getMessage()));}
    }

    public function checklist_manage($id=0)
    {
        $this->requirePermission('engenharia_manage_checklists'); $checklist=$this->checklists_model->get_version((int)$id); if(!$checklist)show_404();
        return $this->renderPluginView('checklists/manage', array('checklist'=>$checklist,'groups'=>$this->groups_model->forChecklist((int)$id)->getResult(),'items'=>$this->items_model->forChecklist((int)$id)->getResult()));
    }

    public function group_modal_form($checklist_id=0,$id=0){$this->requirePermission('engenharia_manage_checklists');$info=$id?$this->groups_model->get_one((int)$id):(object)array('id'=>0,'checklist_id'=>(int)$checklist_id,'title'=>'','description'=>'','sort'=>0,'is_active'=>1);return $this->renderPluginModalView('checklists/group_modal_form',array('model_info'=>$info));}
    public function group_save(){ $this->requirePermission('engenharia_manage_checklists');$data=array('checklist_id'=>(int)$this->request->getPost('checklist_id'),'title'=>trim((string)$this->request->getPost('title')),'description'=>trim((string)$this->request->getPost('description')),'sort'=>(int)$this->request->getPost('sort'),'is_active'=>$this->request->getPost('is_active')?1:0);if(!$data['checklist_id']||!$data['title'])return $this->response->setJSON(array('success'=>false,'message'=>app_lang('field_required')));if(!$this->checklistEditable($data['checklist_id']))return $this->response->setJSON(array('success'=>false,'message'=>app_lang('engenharia_checklist_locked')));$id=(int)$this->request->getPost('id');$saved=$this->groups_model->save_record($data,$id);return $this->response->setJSON(array('success'=>true,'id'=>$saved,'message'=>app_lang('record_saved'))); }
    public function item_modal_form($checklist_id=0,$id=0){$this->requirePermission('engenharia_manage_checklists');$group_id=(int)$this->request->getPost('group_id');$info=$id?$this->items_model->get_one((int)$id):(object)array('id'=>0,'checklist_id'=>(int)$checklist_id,'group_id'=>$group_id,'code'=>'','title'=>'','question'=>'','inspector_instruction'=>'','norm_reference'=>'','response_type'=>'conformity','required'=>0,'allow_observation'=>1,'requires_photo'=>0,'requires_measurement'=>0,'measurement_unit'=>'','criticality'=>'medium','default_recommendation'=>'','sort'=>0,'is_active'=>1);$groups=$this->groups_model->forChecklist((int)$checklist_id)->getResult();return $this->renderPluginModalView('checklists/item_modal_form',array('model_info'=>$info,'groups'=>$groups));}
    public function item_save(){ $this->requirePermission('engenharia_manage_checklists');$data=array('checklist_id'=>(int)$this->request->getPost('checklist_id'),'group_id'=>(int)$this->request->getPost('group_id')?:null,'code'=>trim((string)$this->request->getPost('code')),'title'=>trim((string)$this->request->getPost('title')),'question'=>trim((string)$this->request->getPost('question')),'inspector_instruction'=>trim((string)$this->request->getPost('inspector_instruction')),'norm_reference'=>trim((string)$this->request->getPost('norm_reference')),'response_type'=>trim((string)$this->request->getPost('response_type')),'required'=>$this->request->getPost('required')?1:0,'allow_observation'=>$this->request->getPost('allow_observation')?1:0,'requires_photo'=>$this->request->getPost('requires_photo')?1:0,'requires_measurement'=>$this->request->getPost('requires_measurement')?1:0,'measurement_unit'=>trim((string)$this->request->getPost('measurement_unit')),'criticality'=>trim((string)$this->request->getPost('criticality')),'default_recommendation'=>trim((string)$this->request->getPost('default_recommendation')),'sort'=>(int)$this->request->getPost('sort'),'is_active'=>$this->request->getPost('is_active')?1:0);if(!$data['checklist_id']||!$data['code']||!$data['title']||!$data['question'])return $this->response->setJSON(array('success'=>false,'message'=>app_lang('field_required')));if(!$this->checklistEditable($data['checklist_id']))return $this->response->setJSON(array('success'=>false,'message'=>app_lang('engenharia_checklist_locked')));$id=(int)$this->request->getPost('id');$saved=$this->items_model->save_record($data,$id);return $this->response->setJSON(array('success'=>true,'id'=>$saved,'message'=>app_lang('record_saved'))); }
    public function checklist_version(){ $this->requirePermission('engenharia_manage_checklists');$source=(int)$this->request->getPost('id');$new=$this->checklists_model->create_version($source,array('name'=>$this->request->getPost('name')?:null,'description'=>$this->request->getPost('description')?:null),(int)$this->login_user->id);return $this->response->setJSON(array('success'=>(bool)$new,'id'=>$new,'message'=>$new?app_lang('record_saved'):app_lang('error_occurred'))); }
    public function checklist_duplicate(){ $this->requirePermission('engenharia_manage_checklists');$new=$this->checklists_model->duplicate((int)$this->request->getPost('id'),(int)$this->login_user->id);return $this->response->setJSON(array('success'=>(bool)$new,'id'=>$new,'message'=>$new?app_lang('record_saved'):app_lang('error_occurred'))); }
    public function checklist_toggle(){ $this->requirePermission('engenharia_manage_checklists');$id=(int)$this->request->getPost('id');$ok=$this->checklists_model->setEnabled($id,(bool)$this->request->getPost('enabled'));return $this->response->setJSON(array('success'=>$ok,'message'=>$ok?app_lang('record_saved'):app_lang('error_occurred'))); }
    public function checklist_delete(){ $this->requirePermission('engenharia_manage_checklists');$id=(int)$this->request->getPost('id');$info=$this->checklists_model->get_version($id);if(!$info||$info->status==='published'||$this->checklists_model->isUsed($id))return $this->response->setJSON(array('success'=>false,'message'=>app_lang('engenharia_checklist_locked')));$ok=$this->checklists_model->update_domain($id,array('deleted'=>1,'updated_by'=>(int)$this->login_user->id));return $this->response->setJSON(array('success'=>$ok,'message'=>$ok?app_lang('record_deleted'):app_lang('error_occurred'))); }
    public function checklist_sort(){ $this->requirePermission('engenharia_manage_checklists');$type=$this->request->getPost('type');$id=(int)$this->request->getPost('id');$delta=(int)$this->request->getPost('delta');if(!$this->checklistEditable((int)$this->request->getPost('checklist_id')))return $this->response->setJSON(array('success'=>false,'message'=>app_lang('engenharia_checklist_locked')));$ok=$type==='group'?$this->groups_model->changeSort($id,$delta):$this->items_model->changeSort($id,$delta);return $this->response->setJSON(array('success'=>$ok,'message'=>app_lang('record_saved'))); }
    public function checklist_preview($id=0){$this->requirePermission('engenharia_manage_checklists');$checklist=$this->checklists_model->get_version((int)$id);if(!$checklist)show_404();return $this->renderPluginView('checklists/preview',array('checklist'=>$checklist,'groups'=>$this->groups_model->forChecklist((int)$id)->getResult(),'items'=>$this->items_model->forChecklist((int)$id)->getResult()));}

    private function checklistEditable(int $id): bool { $info=$this->checklists_model->get_version($id); return (bool)$info && $info->status!=='published' && !$this->checklists_model->isUsed($id); }

    public function modelos()
    {
        $this->requirePermission('engenharia_manage_templates');
        app_redirect('engenharia/configuracoes');
    }

    public function configuracoes()
    {
        $this->requirePermission('engenharia_manage_settings');
        return $this->renderPluginView('settings/index', array('settings'=>$this->settings_model->get_all_settings(),'types'=>$this->types_model->get_enabled(true)->getResult(),'instruments'=>$this->instruments_model->get_details(array('deleted'=>0))->getResult()));
    }

    public function list_data()
    {
        $this->requirePermission('engenharia_view_laudos');
        $options = append_server_side_filtering_commmon_params(array(
            'type_id' => (int) $this->request->getPost('type_id'),
            'client_id' => (int) $this->request->getPost('client_id'),
            'technical_responsible_id' => (int) $this->request->getPost('technical_responsible_id'),
            'inspection_technician_id' => (int) $this->request->getPost('inspection_technician_id'),
            'status' => $this->request->getPost('status'),
            'inspection_date_from' => $this->request->getPost('inspection_date_from'),
            'inspection_date_to' => $this->request->getPost('inspection_date_to'),
        ));
        $result = $this->laudos_model->get_details($options);
        $rows = get_array_value($options, 'server_side') ? get_array_value($result, 'data') : $result->getResult();
        if (!get_array_value($options, 'server_side')) { $result = array(); }
        foreach ($rows as $row) { $result['data'][] = $this->makeLaudoRow($row); }
        return $this->response->setJSON($result);
    }

    public function modal_form($id = 0)
    {
        $id = (int) $id ?: (int) $this->request->getPost('id');
        $id ? $this->requirePermission('engenharia_edit_laudos') : $this->requirePermission('engenharia_create_laudos');
        $model_info = $id ? $this->laudos_model->get_one($id) : (object) array(
            'id' => 0, 'client_id' => '', 'contact_id' => '', 'project_id' => '', 'type_id' => '', 'title' => '',
            'inspection_address' => '', 'technical_responsible_id' => '', 'inspection_technician_id' => '',
            'checklist_id' => '', 'scheduled_date' => '', 'validity_date' => '', 'objective' => '', 'scope' => '',
            'installation_description' => '', 'internal_notes' => '', 'status' => 'draft',
        );
        if ($id && (empty($model_info->id) || in_array($model_info->status, array('finalized', 'canceled'), true))) { app_redirect('forbidden'); }
        return $this->renderPluginModalView('laudos/modal_form', array(
            'model_info' => $model_info,
            'types_dropdown' => $this->getTypesDropdown(true),
            'clients_dropdown' => $this->getClientsDropdown(),
            'team_members_dropdown' => $this->getTeamMembersDropdown(),
            'contacts_dropdown' => $this->getContactsDropdown((int) ($model_info->client_id ?? 0)),
            'projects_dropdown' => $this->getProjectsDropdown((int) ($model_info->client_id ?? 0)),
            'checklists_dropdown' => $this->getChecklistsDropdown((int) ($model_info->type_id ?? 0)),
        ));
    }

    public function save()
    {
        $id = (int) $this->request->getPost('id');
        $id ? $this->requirePermission('engenharia_edit_laudos') : $this->requirePermission('engenharia_create_laudos');
        $data = array(
            'client_id' => (int) $this->request->getPost('client_id'),
            'contact_id' => (int) $this->request->getPost('contact_id') ?: null,
            'project_id' => (int) $this->request->getPost('project_id') ?: null,
            'type_id' => (int) $this->request->getPost('type_id'),
            'title' => trim((string) $this->request->getPost('title')),
            'inspection_address' => trim((string) $this->request->getPost('inspection_address')),
            'technical_responsible_id' => (int) $this->request->getPost('technical_responsible_id') ?: null,
            'inspection_technician_id' => (int) $this->request->getPost('inspection_technician_id') ?: null,
            'checklist_id' => (int) $this->request->getPost('checklist_id') ?: null,
            'scheduled_date' => $this->request->getPost('scheduled_date') ?: null,
            'validity_date' => $this->request->getPost('validity_date') ?: null,
            'objective' => trim((string) $this->request->getPost('objective')),
            'scope' => trim((string) $this->request->getPost('scope')),
            'installation_description' => trim((string) $this->request->getPost('installation_description')),
            'internal_notes' => trim((string) $this->request->getPost('internal_notes')),
        );
        if (!$data['client_id'] || !$data['type_id'] || $data['title'] === '') { return $this->response->setJSON(array('success' => false, 'message' => app_lang('field_required'))); }
        foreach (array('scheduled_date', 'validity_date') as $date_field) {
            if ($data[$date_field] !== null && (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data[$date_field]) || !\DateTime::createFromFormat('!Y-m-d', $data[$date_field]))) {
                return $this->response->setJSON(array('success' => false, 'message' => app_lang('engenharia_invalid_date')));
            }
        }
        try {
            $domain = new LaudoDomainService($this->laudos_model, $this->types_model, $this->checklists_model);
            if ($id) {
                if (!$domain->updateDraft($id, $data, $this->login_user)) { throw new \RuntimeException(app_lang('error_occurred')); }
                $this->logActivity('updated', $id, $data['title'], $data['client_id']);
                return $this->response->setJSON(array('success' => true, 'message' => app_lang('record_updated')));
            }
            $new_id = $domain->createDraft($data, $this->login_user);
            if (!$new_id) { throw new \RuntimeException(app_lang('error_occurred')); }
            $this->logActivity('created', $new_id, $data['title'], $data['client_id']);
            return $this->response->setJSON(array('success' => true, 'id' => $new_id, 'message' => app_lang('record_saved')));
        } catch (\Throwable $e) {
            log_message('error', '[Engenharia] Laudo save error: ' . $e->getMessage());
            return $this->response->setJSON(array('success' => false, 'message' => $e->getMessage()));
        }
    }

    public function view($id = 0)
    {
        $this->requirePermission('engenharia_view_laudos');
        $laudo = $this->laudos_model->get_details(array('id' => (int) $id))->getRow();
        if (!$laudo) { show_404(); }
        $workflow = new LaudoWorkflowService($this->laudos_model, $this->history_model);
        $allowed = array();
        foreach (LaudoWorkflowService::allowedTransitions((string) $laudo->status) as $status) {
            if ($status === LaudoWorkflowService::REVIEW && $laudo->status === LaudoWorkflowService::FINALIZED) {
                $permission = 'engenharia_reopen_laudos';
            } elseif ($status === LaudoWorkflowService::INSPECTION) {
                $permission = 'engenharia_inspect_laudos';
            } elseif ($status === LaudoWorkflowService::REVIEW) {
                $permission = 'engenharia_review_laudos';
            } elseif ($status === LaudoWorkflowService::FINALIZED) {
                $permission = 'engenharia_finalize_laudos';
            } else {
                $permission = 'engenharia_edit_laudos';
            }
            if (\Engenharia\Plugin::hasPermission($this->login_user, $permission)) {
                $allowed[] = array('code' => $status, 'label' => engenharia_status_labels()[$status] ?? $status);
            }
        }
        $history = $this->history_model->get_for_laudo((int) $id)->getResult();
        $audit = array();
        try {
            $activity = $this->Activity_logs_model->get_details(array('log_type' => 'engenharia_laudo', 'log_type_id' => (int) $id, 'limit' => 10));
            $audit = $activity->result ?? array();
        } catch (\Throwable $e) { log_message('error', '[Engenharia] Activity read error: ' . $e->getMessage()); }
        return $this->renderPluginView('laudos/view', array(
            'laudo' => $laudo,
            'can_edit' => \Engenharia\Plugin::hasPermission($this->login_user, 'engenharia_edit_laudos') && !in_array($laudo->status, array('finalized', 'canceled'), true),
            'can_delete' => \Engenharia\Plugin::hasPermission($this->login_user, 'engenharia_delete_laudos') && $laudo->status === 'draft',
            'allowed_transitions' => $allowed,
            'history' => $history,
            'audit' => $audit,
            'summary' => $this->getOperationalSummary((int) $id),
            'nc_summary' => $this->nonconformities_model->summary((int) $id),
        ));
    }

    public function change_status()
    {
        $this->requirePermission('engenharia_view_laudos');
        $id = (int) $this->request->getPost('id');
        $to_status = trim((string) $this->request->getPost('to_status'));
        $comment = trim((string) $this->request->getPost('comment'));
        try {
            $workflow = new LaudoWorkflowService($this->laudos_model, $this->history_model);
            if (!$workflow->changeStatus($id, $to_status, $this->login_user, $comment, 'web')) {
                throw new \RuntimeException(app_lang('error_occurred'));
            }
            $laudo = $this->laudos_model->get_one($id);
            $this->logActivity('status_changed', $id, $laudo->title ?? '', (int) ($laudo->client_id ?? 0));
            return $this->response->setJSON(array('success' => true, 'message' => app_lang('record_saved')));
        } catch (\Throwable $e) {
            log_message('error', '[Engenharia] Status change error: ' . $e->getMessage());
            return $this->response->setJSON(array('success' => false, 'message' => $e->getMessage()));
        }
    }

    public function delete()
    {
        $this->requirePermission('engenharia_delete_laudos');
        $id = (int) $this->request->getPost('id'); $laudo = $this->laudos_model->get_one($id);
        if (!$laudo || $laudo->status !== 'draft') { return $this->response->setJSON(array('success' => false, 'message' => app_lang('record_cannot_be_deleted'))); }
        $success = $this->laudos_model->soft_delete($id, (int) $this->login_user->id);
        if ($success) { $this->logActivity('deleted', $id, $laudo->title, (int) $laudo->client_id); }
        return $this->response->setJSON(array('success' => $success, 'message' => $success ? app_lang('record_deleted') : app_lang('error_occurred')));
    }

    public function get_contacts()
    {
        $this->requirePermission('engenharia_view_laudos');
        return $this->response->setJSON($this->getContactsDropdown((int) $this->request->getPost('client_id')));
    }

    public function get_projects()
    {
        $this->requirePermission('engenharia_view_laudos');
        return $this->response->setJSON($this->getProjectsDropdown((int) $this->request->getPost('client_id')));
    }

    public function get_checklists()
    {
        $this->requirePermission('engenharia_view_laudos');
        return $this->response->setJSON($this->getChecklistsDropdown((int) $this->request->getPost('type_id')));
    }

    public function inspection($id = 0)
    {
        $this->requirePermission('engenharia_inspect_laudos');
        $laudo = $this->laudos_model->get_details(array('id' => (int) $id))->getRow();
        if (!$laudo || in_array($laudo->status, array('finalized', 'canceled'), true)) { app_redirect('forbidden'); }
        $items = array(); $groups = array();
        if (!empty($laudo->checklist_snapshot_json)) { $snapshot = json_decode($laudo->checklist_snapshot_json, true); $groups = $snapshot['groups'] ?? array(); $items = $snapshot['items'] ?? array(); }
        if (!$items && $laudo->checklist_id) { $groups = $this->groups_model->forChecklist((int)$laudo->checklist_id)->getResultArray(); $items = $this->items_model->forChecklist((int)$laudo->checklist_id)->getResultArray(); }
        $responses = array(); foreach ($this->responses_model->forLaudo((int)$id)->getResult() as $response) { $responses[$response->item_id . ':' . ($response->area_id ?: 0)] = $response; }
        return $this->renderPluginView('laudos/inspection', array('laudo'=>$laudo,'groups'=>$groups,'items'=>$items,'areas'=>$this->areas_model->forLaudo((int)$id)->getResult(),'responses'=>$responses,'can_finish'=>\Engenharia\Plugin::hasPermission($this->login_user,'engenharia_review_laudos'),'summary'=>$this->inspectionSummary($items,$responses),'nonconformities'=>$this->nonconformities_model));
    }

    public function save_response()
    {
        $this->requirePermission('engenharia_inspect_laudos');
        $laudo_id = (int) $this->request->getPost('laudo_id');
        $item_id = (int) $this->request->getPost('item_id');
        $area_id = (int) $this->request->getPost('area_id') ?: null;
        $laudo = $this->laudos_model->get_one($laudo_id);
        $item = $this->findChecklistItem($laudo, $item_id);
        if (!$laudo || !$item || in_array($laudo->status, array('finalized', 'canceled'), true)) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('engenharia_inspection_locked')));
        }

        $value = trim((string) $this->request->getPost('response_value'));
        $not_verified = $this->request->getPost('not_verified') ? 1 : 0;
        if ($not_verified && empty($item->not_verified_allowed)) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('engenharia_not_verified_not_allowed')));
        }
        if (!$this->isValidChecklistResponse($item, $value, $not_verified)) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('engenharia_invalid_response')));
        }
        if (!empty($item->required) && $value === '' && !$not_verified) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('field_required')));
        }

        $numeric = $this->request->getPost('numeric_value');
        if ($numeric !== '' && $numeric !== null && !is_numeric($numeric)) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('engenharia_invalid_measurement')));
        }
        $data = array(
            'area_id' => $area_id,
            'response_value' => $value,
            'observation' => trim((string) $this->request->getPost('observation')),
            'numeric_value' => $numeric !== '' ? $numeric : null,
            'is_conforming' => $not_verified ? null : ($value === 'not_conforming' ? 0 : ($value !== '' ? 1 : null)),
        );
        $response_id = $this->responses_model->save_response($laudo_id, $item_id, $data, (int) $this->login_user->id);
        if ($data['is_conforming'] === 0) {
            $nc = $this->nonconformities_model->forResponse($response_id);
            $nc_data = array(
                'laudo_id' => $laudo_id,
                'response_id' => $response_id,
                'area_id' => $area_id,
                'title' => $item->title ?? $item->question,
                'description' => trim((string) $this->request->getPost('nc_description')),
                'severity' => trim((string) $this->request->getPost('nc_criticality')) ?: 'medium',
                'recommendation' => trim((string) $this->request->getPost('nc_recommendation')),
                'due_date' => $this->request->getPost('nc_due_date') ?: null,
                'updated_by' => (int) $this->login_user->id,
            );
            $this->nonconformities_model->saveForResponse($nc_data, (int) ($nc->id ?? 0));
        }
        return $this->response->setJSON(array('success' => true, 'response_id' => $response_id, 'message' => app_lang('record_saved'), 'summary' => $this->inspectionSummary(array($item), array())));
    }

    public function start_inspection(){ $this->requirePermission('engenharia_inspect_laudos');$id=(int)$this->request->getPost('laudo_id');try{$workflow=new LaudoWorkflowService($this->laudos_model,$this->history_model);$workflow->changeStatus($id,'inspection',$this->login_user,trim((string)$this->request->getPost('comment')),'inspection');return $this->response->setJSON(array('success'=>true,'message'=>app_lang('record_saved')));}catch(\Throwable $e){return $this->response->setJSON(array('success'=>false,'message'=>$e->getMessage()));}}

    public function save_area(){ $this->requirePermission('engenharia_inspect_laudos');$laudo_id=(int)$this->request->getPost('laudo_id');$laudo=$this->laudos_model->get_one($laudo_id);if(!$laudo||in_array($laudo->status,array('finalized','canceled'),true))return $this->response->setJSON(array('success'=>false,'message'=>app_lang('engenharia_inspection_locked')));$name=trim((string)$this->request->getPost('name'));if(!$name)return $this->response->setJSON(array('success'=>false,'message'=>app_lang('field_required')));$id=$this->areas_model->saveArea(array('laudo_id'=>$laudo_id,'name'=>$name,'address'=>trim((string)$this->request->getPost('address')),'description'=>trim((string)$this->request->getPost('description')),'created_by'=>(int)$this->login_user->id), (int)$this->request->getPost('id'));return $this->response->setJSON(array('success'=>(bool)$id,'id'=>$id,'message'=>app_lang('record_saved'))); }
    public function save_photo(){ $this->requirePermission('engenharia_inspect_laudos');$laudo_id=(int)$this->request->getPost('laudo_id');$laudo=$this->laudos_model->get_one($laudo_id);if(!$laudo||in_array($laudo->status,array('finalized','canceled'),true))return $this->response->setJSON(array('success'=>false,'message'=>app_lang('engenharia_inspection_locked')));$files=move_files_from_temp_dir_to_permanent_dir(get_setting('timeline_file_path'),'engenharia_laudo');$saved=array();foreach((array)@unserialize($files) as $file){if(empty($file['file_name']))continue;$saved[]=$this->photos_model->add(array('laudo_id'=>$laudo_id,'area_id'=>(int)$this->request->getPost('area_id')?:null,'response_id'=>(int)$this->request->getPost('response_id')?:null,'file_id'=>$file['file_id']??null,'file_name'=>$file['file_name'],'storage_path'=>get_setting('timeline_file_path'),'caption'=>trim((string)$this->request->getPost('caption')),'uploaded_by'=>(int)$this->login_user->id));}return $this->response->setJSON(array('success'=>true,'ids'=>$saved,'message'=>app_lang('record_saved'))); }

    public function finish_inspection(){ $this->requirePermission('engenharia_inspect_laudos');$id=(int)$this->request->getPost('laudo_id');$laudo=$this->laudos_model->get_one($id);if(!$laudo||in_array($laudo->status,array('finalized','canceled'),true))return $this->response->setJSON(array('success'=>false,'message'=>app_lang('engenharia_inspection_locked')));$items=$this->inspectionItems($laudo);$responses=array();foreach($this->responses_model->forLaudo($id)->getResult() as $r){$responses[$r->item_id.':0']=$r;}if(!$this->canFinishInspection($items,$responses))return $this->response->setJSON(array('success'=>false,'message'=>app_lang('engenharia_required_pending')));try{$workflow=new LaudoWorkflowService($this->laudos_model,$this->history_model);$workflow->changeStatus($id,'review',$this->login_user,trim((string)$this->request->getPost('comment')),'inspection');return $this->response->setJSON(array('success'=>true,'message'=>app_lang('record_saved')));}catch(\Throwable $e){return $this->response->setJSON(array('success'=>false,'message'=>$e->getMessage()));}}

    private function findChecklistItem($laudo,int $item_id){if(!$laudo)return null;$snapshot=json_decode($laudo->checklist_snapshot_json??'',true);foreach(($snapshot['items']??array()) as $item){if((int)($item['id']??0)===$item_id)return(object)$item;}return $this->items_model->get_one($item_id);}
    private function inspectionItems($laudo): array { $snapshot=json_decode($laudo->checklist_snapshot_json??'',true);if(!empty($snapshot['items']))return array_map(function($item){return is_array($item)?(object)$item:$item;},$snapshot['items']);return $laudo->checklist_id?$this->items_model->forChecklist((int)$laudo->checklist_id)->getResult():array(); }
    private function isValidChecklistResponse($item,string $value,int $not_verified): bool { if($not_verified)return true;$type=(string)($item->response_type??'text');if($value==='' )return true;if(in_array($type,array('conformity','yes_no'),true))return in_array($value,$type==='conformity'?array('conforming','not_conforming','not_applicable'):array('yes','no','not_applicable'),true);if($type==='number')return is_numeric($value);if($type==='date'){$d=\DateTime::createFromFormat('Y-m-d',$value);return $d&&$d->format('Y-m-d')===$value;}if(in_array($type,array('single_select','multi_select'),true)){ $options=json_decode((string)($item->response_options_json??''),true);$allowed=is_array($options)?array_map('strval',$options):array();if(!$allowed)return true;$values=$type==='multi_select'?preg_split('/\s*,\s*/',$value,-1,PREG_SPLIT_NO_EMPTY):array($value);return !array_diff($values,$allowed);}return true;}
    private function canFinishInspection(array $items,array $responses):bool{foreach($items as $item){$required=!empty($item->required)||!empty($item['required']);$id=(int)($item->id??$item['id']??0);if($required&&!isset($responses[$id.':0']))return false;}return true;}
    private function inspectionSummary(array $items,array $responses):array{$required=0;$answered=0;foreach($items as $item){$id=(int)($item->id??$item['id']??0);if(!empty($item->required)||!empty($item['required']))$required++;if(isset($responses[$id.':0']))$answered++;}return array('total'=>count($items),'answered'=>$answered,'pending'=>max(0,count($items)-$answered),'required'=>$required);}

    private function makeLaudoRow($data)
    {
        $actions = '';
        if (\Engenharia\Plugin::hasPermission($this->login_user, 'engenharia_edit_laudos') && !in_array($data->status, array('finalized', 'canceled'), true)) { $actions .= modal_anchor(get_uri('engenharia/laudos/modal_form'), "<i data-feather='edit' class='icon-16'></i>", array('class' => 'edit', 'title' => app_lang('edit'), 'data-post-id' => $data->id)); }
        if (\Engenharia\Plugin::hasPermission($this->login_user, 'engenharia_delete_laudos') && $data->status === 'draft') { $actions .= js_anchor("<i data-feather='x' class='icon-16'></i>", array('class' => 'delete', 'title' => app_lang('delete'), 'data-id' => $data->id, 'data-action-url' => get_uri('engenharia/laudos/delete'), 'data-action' => 'delete-confirmation')); }
        return array(
            anchor(get_uri('engenharia/laudos/view/' . $data->id), esc($data->number), array('class' => 'strong')),
            anchor(get_uri('engenharia/laudos/view/' . $data->id), esc($data->title)), esc($data->type_name ?: $data->type_code), esc($data->client_name ?: '-'), esc($data->technical_responsible_name ?: '-'),
            $data->inspection_date ? format_to_date($data->inspection_date, false) : '-', engenharia_status_badge($data->status), $data->updated_at ? format_to_date($data->updated_at, false) : '-', $actions,
        );
    }

    private function getTypesDropdown($include_disabled = false)
    {
        $result = array('' => '-'); foreach ($this->types_model->get_enabled($include_disabled)->getResult() as $type) { if (!$include_disabled && !$type->is_enabled) { continue; } $result[$type->id] = $type->name; } return $result;
    }

    private function getClientsDropdown()
    {
        $result = array('' => '-'); $db = db_connect('default'); foreach ($db->table($db->prefixTable('clients'))->where('deleted', 0)->where('is_lead', 0)->orderBy('company_name', 'ASC')->get()->getResult() as $row) { $result[$row->id] = $row->company_name; } return $result;
    }

    private function getTeamMembersDropdown()
    {
        $result = array('' => '-'); $db = db_connect('default'); foreach ($db->table($db->prefixTable('users'))->where('deleted', 0)->where('user_type', 'staff')->where('status', 'active')->orderBy('first_name', 'ASC')->get()->getResult() as $row) { $result[$row->id] = trim($row->first_name . ' ' . $row->last_name); } return $result;
    }

    private function getContactsDropdown(int $client_id)
    {
        $result = array('' => '-'); if (!$client_id) { return $result; } $db = db_connect('default'); foreach ($db->table($db->prefixTable('users'))->where('client_id', $client_id)->where('deleted', 0)->where('user_type', 'client')->orderBy('first_name', 'ASC')->get()->getResult() as $row) { $result[$row->id] = trim($row->first_name . ' ' . $row->last_name); } return $result;
    }

    private function getProjectsDropdown(int $client_id)
    {
        $result = array('' => '-'); if (!$client_id) { return $result; } $db = db_connect('default'); foreach ($db->table($db->prefixTable('projects'))->where('client_id', $client_id)->where('deleted', 0)->orderBy('title', 'ASC')->get()->getResult() as $row) { $result[$row->id] = $row->title; } return $result;
    }

    private function getChecklistsDropdown(int $type_id)
    {
        $result = array('' => '-'); foreach ($this->checklists_model->get_published_for_type($type_id)->getResult() as $row) { $result[$row->id] = $row->name . ' v' . $row->version; } return $result;
    }

    private function logActivity(string $action, int $laudo_id, string $title, int $client_id = 0)
    {
        try { $this->Activity_logs_model->ci_save(array('action' => $action, 'log_type' => 'engenharia_laudo', 'log_type_title' => $title, 'log_type_id' => $laudo_id, 'log_for' => 'client', 'log_for_id' => $client_id)); } catch (\Throwable $e) { log_message('error', '[Engenharia] Activity log error: ' . $e->getMessage()); }
        log_message('info', '[Engenharia] action=' . $action . ' laudo_id=' . $laudo_id . ' user_id=' . (int) $this->login_user->id);
    }

    public function save_measurement(){ $this->requirePermission('engenharia_inspect_laudos');$id=(int)$this->request->getPost('laudo_id');if(!$this->editableInspection($id))return$this->lockedJson();$data=array('laudo_id'=>$id,'area_id'=>(int)$this->request->getPost('area_id')?:null,'instrument_id'=>(int)$this->request->getPost('instrument_id')?:null,'measurement_type'=>trim((string)$this->request->getPost('measurement_type')),'name'=>trim((string)$this->request->getPost('name')),'point_identifier'=>trim((string)$this->request->getPost('point_identifier')),'value'=>$this->request->getPost('value')!==''?$this->request->getPost('value'):null,'unit'=>trim((string)$this->request->getPost('unit')),'result_classification'=>trim((string)$this->request->getPost('result_classification')),'measured_at'=>$this->request->getPost('measured_at')?:date('Y-m-d H:i:s'),'measured_by'=>(int)$this->login_user->id,'observation'=>trim((string)$this->request->getPost('observation')),'checklist_item_id'=>(int)$this->request->getPost('checklist_item_id')?:null);if(!$data['name']||$data['value']===null)return$this->response->setJSON(array('success'=>false,'message'=>app_lang('field_required')));$saved=$this->measurements_model->saveRecord($data,(int)$this->request->getPost('id'));return$this->response->setJSON(array('success'=>(bool)$saved,'id'=>$saved,'message'=>app_lang('record_saved')));}
    public function save_instrument(){ $this->requirePermission('engenharia_manage_settings');$data=array('name'=>trim((string)$this->request->getPost('name')),'manufacturer'=>trim((string)$this->request->getPost('manufacturer')),'model'=>trim((string)$this->request->getPost('model')),'serial_number'=>trim((string)$this->request->getPost('serial_number')),'calibration_certificate'=>trim((string)$this->request->getPost('calibration_certificate')),'calibration_due_date'=>$this->request->getPost('calibration_due_date')?:null,'calibration_valid_until'=>$this->request->getPost('calibration_valid_until')?:null,'is_active'=>$this->request->getPost('is_active')?1:0,'updated_by'=>(int)$this->login_user->id);if(!$data['name'])return$this->response->setJSON(array('success'=>false,'message'=>app_lang('field_required')));$saved=$this->instruments_model->saveRecord($data,(int)$this->request->getPost('id'));return$this->response->setJSON(array('success'=>(bool)$saved,'id'=>$saved,'message'=>app_lang('record_saved')));}
    public function delete_photo(){ $this->requirePermission('engenharia_inspect_laudos');$photo=$this->photos_model->get_one((int)$this->request->getPost('id'));if(!$photo||!$this->editableInspection((int)$photo->laudo_id))return$this->lockedJson();$ok=$this->photos_model->remove((int)$photo->id);return$this->response->setJSON(array('success'=>$ok,'message'=>$ok?app_lang('record_deleted'):app_lang('error_occurred')));}
    public function save_nonconformity(){ $this->requirePermission('engenharia_inspect_laudos');$id=(int)$this->request->getPost('laudo_id');if(!$this->editableInspection($id))return$this->lockedJson();$code='NC-'.date('Y').'-'.str_pad((string)($this->nonconformities_model->forLaudo($id)->getNumRows()+1),4,'0',STR_PAD_LEFT);$data=array('laudo_id'=>$id,'area_id'=>(int)$this->request->getPost('area_id')?:null,'title'=>trim((string)$this->request->getPost('title')),'code'=>$code,'description'=>trim((string)$this->request->getPost('description')),'evidence'=>trim((string)$this->request->getPost('evidence')),'reference'=>trim((string)$this->request->getPost('reference')),'severity'=>trim((string)$this->request->getPost('severity'))?:'medium','recommendation'=>trim((string)$this->request->getPost('recommendation')),'due_date'=>$this->request->getPost('due_date')?:null,'status'=>'open','created_by'=>(int)$this->login_user->id);if(!$data['title']||!$data['description'])return$this->response->setJSON(array('success'=>false,'message'=>app_lang('field_required')));$saved=$this->nonconformities_model->saveForResponse($data,(int)$this->request->getPost('id'));return$this->response->setJSON(array('success'=>(bool)$saved,'id'=>$saved,'code'=>$code,'message'=>app_lang('record_saved')));}
    private function editableInspection(int $id): bool { $row=$this->laudos_model->get_one($id); return (bool)$row && !in_array($row->status,array('finalized','canceled'),true); }
    private function lockedJson(){return $this->response->setJSON(array('success'=>false,'message'=>app_lang('engenharia_inspection_locked')));}

    public function report_preview($id=0){$this->requirePermission('engenharia_view_laudos');try{$result=(new LaudoReportService())->generate((int)$id,false,(int)$this->login_user->id);return $this->response->setHeader('Content-Type','application/pdf')->setBody($result['content']);}catch(\Throwable $e){log_message('error','[Engenharia] report preview: '.$e->getMessage());show_error($e->getMessage());}}
    public function report_final($id=0){$this->requirePermission('engenharia_finalize_laudos');try{$result=(new LaudoReportService())->generate((int)$id,true,(int)$this->login_user->id);return $this->response->setHeader('Content-Type','application/pdf')->setHeader('Content-Disposition','attachment; filename="'.$result['file_name'].'"')->setBody($result['content']);}catch(\Throwable $e){log_message('error','[Engenharia] final report: '.$e->getMessage());show_error($e->getMessage());}}
    public function report_versions($id=0){$this->requirePermission('engenharia_view_laudos');$versions=model('Engenharia\\Models\\Report_versions_model')->forLaudo((int)$id)->getResult();return $this->response->setJSON($versions);}
    public function save_report_settings(){ $this->requirePermission('engenharia_manage_settings');$settings=model('Engenharia\\Models\\Settings_model');foreach(array('report_logo','report_company_data','report_primary_color','report_header','report_footer','report_photos_per_page','report_signatures','report_default_electrico','report_default_spda') as $key){if($this->request->getPost($key)!==null)$settings->save_value($key,(string)$this->request->getPost($key));}$settings->save_value('report_show_conforming',$this->request->getPost('report_show_conforming')?'1':'0');return$this->response->setJSON(array('success'=>true,'message'=>app_lang('record_saved'))); }

    private function getOperationalSummary(int $laudo_id): array
    {
        $db = db_connect('default');
        $summary = array('responses' => 0, 'conforming' => 0, 'nonconforming' => 0, 'measurements' => 0, 'photos' => 0);
        foreach (array('eng_checklist_responses' => 'responses', 'eng_measurements' => 'measurements', 'eng_photos' => 'photos', 'eng_nonconformities' => 'nonconforming') as $table => $key) {
            $full = $db->prefixTable($table);
            if ($db->tableExists($full)) { $summary[$key] = (int) ($db->table($full)->where('laudo_id', $laudo_id)->where('deleted', 0)->countAllResults()); }
        }
        $full = $db->prefixTable('eng_checklist_responses');
        if ($db->tableExists($full)) { $summary['conforming'] = (int) $db->table($full)->where('laudo_id', $laudo_id)->where('deleted', 0)->where('is_conforming', 1)->countAllResults(); }
        return $summary;
    }

    private function getDashboardData(): array
    {
        $stats = $this->laudos_model->get_dashboard_stats();
        $db = db_connect('default');
        $today = date('Y-m-d');
        $laudos = $db->prefixTable('eng_laudos');
        $scheduled = $db->table($laudos)->where('deleted', 0)->where('scheduled_date >=', $today)->whereNotIn('status', array('finalized', 'canceled'))->countAllResults();
        $instruments = $this->instruments_model->get_details(array('deleted' => 0))->getResult();
        $expired = 0; $soon = 0; $soon_date = date('Y-m-d', strtotime('+30 days'));
        foreach ($instruments as $instrument) { if (!empty($instrument->calibration_valid_until)) { if ($instrument->calibration_valid_until < $today) { $expired++; } elseif ($instrument->calibration_valid_until <= $soon_date) { $soon++; } } }
        $recent = $this->laudos_model->get_details(array('limit' => 5, 'order_by' => 'updated_at', 'order_dir' => 'DESC'))->getResult();
        return array('stats' => $stats, 'scheduled' => $scheduled, 'nc_summary' => $this->getNcDashboardSummary(), 'instruments_expired' => $expired, 'instruments_soon' => $soon, 'recent' => $recent);
    }

    private function getNcDashboardSummary(): array
    {
        $db = db_connect('default'); $table = $db->prefixTable('eng_nonconformities'); $summary = array('low'=>0,'medium'=>0,'high'=>0,'critical'=>0);
        if (!$db->tableExists($table)) { return $summary; }
        foreach ($db->table($table)->select('severity, COUNT(*) AS total')->where('deleted',0)->groupBy('severity')->get()->getResult() as $row) { if (isset($summary[$row->severity])) { $summary[$row->severity] = (int) $row->total; } }
        return $summary;
    }
}
