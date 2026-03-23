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
    }

    public function index()
    {
        if (!$this->_has_view_permission()) {
            app_redirect('forbidden');
        }

        $view_data = array(
            "statuses_dropdown" => json_encode($this->_get_statuses_dropdown()),
            "can_manage" => $this->_has_manage_permission()
        );

        return $this->template->rander('Proposals\\Views\\proposals\\index', $view_data);
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

        $allowed = array();
        foreach ($this->_get_statuses_dropdown(false) as $row) {
            if (!empty($row['id'])) {
                $allowed[] = $row['id'];
            }
        }
        if (!in_array($status, $allowed, true)) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('invalid_request')));
        }

        $save_id = $this->Proposals_model->ci_save(array(
            'status' => $status,
            'updated_at' => get_my_local_time()
        ), $id);

        if (!$save_id) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('error_occurred')));
        }

        $this->_log_activity('proposal_updated', $id);
        return $this->response->setJSON(array(
            'success' => true,
            'status' => app_lang('proposals_status_' . $status),
            'status_html' => $this->_get_status_label($status)
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
            'title' => trim((string)$this->request->getPost('title'))
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
                'unit_type' => $unit_type
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

        $memory_items_query = $this->Proposal_items_model->get_details(array(
            'proposal_id' => $proposal_id,
            'in_memory' => 1
        ));
        $memory_items = ($memory_items_query && method_exists($memory_items_query, 'getResult')) ? $memory_items_query->getResult() : array();

        if ($memory_items) {
            $next_sort = $this->_get_next_item_sort($proposal_id, null);
            foreach ($memory_items as $item) {
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
            }
        }

        $this->_log_activity('items_copied_to_proposal', $proposal_id);

        return $this->response->setJSON(array(
            'success' => true,
            'message' => app_lang('record_saved')
        ));
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
            'taxes_base' => 'total_sale'
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
        $rows[] = array('id' => 'sent', 'text' => app_lang('proposals_status_sent'));
        $rows[] = array('id' => 'approved', 'text' => app_lang('proposals_status_approved'));
        $rows[] = array('id' => 'rejected', 'text' => app_lang('proposals_status_rejected'));
        $rows[] = array('id' => 'archived', 'text' => app_lang('proposals_status_archived'));

        return $rows;
    }

    private function _make_row($data)
    {
        $code = 'PR-' . str_pad($data->id, 6, '0', STR_PAD_LEFT);
        $client = $data->client_company ? $data->client_company : ($data->client_name ? $data->client_name : '-');
        $status = $data->status ? $data->status : 'draft';
        $status_label = $this->_get_status_label($status);
        $total_value = isset($data->total_sale) ? $data->total_sale : 0;
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
