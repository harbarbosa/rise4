<?php

namespace Proposals\Controllers;

class ProposalsItemsSafe extends Proposals
{
    public function add_item()
    {
        if (!$this->_local_has_manage_permission()) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('permission_denied')));
        }

        $this->validate_submitted_data(array(
            'proposal_id' => 'required|numeric'
        ));

        $proposal_id = (int) $this->request->getPost('proposal_id');
        if (!$proposal_id || !$this->_local_proposal_belongs_to_company($proposal_id)) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('record_not_found')));
        }

        $section_id_raw = $this->request->getPost('section_id');
        $section_id = ($section_id_raw !== null && $section_id_raw !== '') ? (int) $section_id_raw : null;
        if ($section_id && !$this->_local_section_belongs_to_proposal($section_id, $proposal_id)) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('record_not_found')));
        }

        $data = $this->_local_prepare_item_data($proposal_id, $section_id);
        $data['created_by'] = (int) $this->login_user->id;
        $data['created_at'] = get_my_local_time();
        $data['sort'] = $this->_local_get_next_item_sort($proposal_id, $section_id);

        $db = db_connect('default');
        $lock_key = $this->_local_lock_key($proposal_id, $section_id, $data);
        $lock_acquired = false;

        try {
            $lock_row = $db->query('SELECT GET_LOCK(?, 5) AS lck', array($lock_key))->getRow();
            $lock_acquired = $lock_row && (int) ($lock_row->lck ?? 0) === 1;

            if ($lock_acquired) {
                $existing = $this->_local_find_recent_equivalent_item($proposal_id, $section_id, $data, 4);
                if ($existing) {
                    log_message('warning', '[Proposals] Duplicate add blocked. proposal_id=' . $proposal_id . ', item_id=' . ($data['item_id'] ?? 0) . ', existing_id=' . $existing->id);
                    return $this->response->setJSON(array(
                        'success' => true,
                        'data' => (array) $existing,
                        'message' => app_lang('record_saved')
                    ));
                }
            }

            $save_id = $this->Proposal_items_model->ci_save($data, 0);
            if ($save_id === false) {
                log_message('error', '[Proposals] Failed to insert proposal item. proposal_id=' . $proposal_id);
                return $this->response->setJSON(array('success' => false, 'message' => app_lang('error_occurred')));
            }

            $data['id'] = is_numeric($save_id) ? (int) $save_id : (int) $db->insertID();

            $this->_local_update_product_sale_price($data);
            $this->Proposals_model->calculate_totals($proposal_id);

            log_message('info', '[Proposals] Item created. proposal_id=' . $proposal_id . ', item_id=' . ($data['item_id'] ?? 0) . ', proposal_item_id=' . $data['id']);

            return $this->response->setJSON(array(
                'success' => true,
                'data' => $data,
                'message' => app_lang('record_saved')
            ));
        } catch (\Throwable $e) {
            log_message('error', '[Proposals] Safe add item error: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON(array(
                'success' => false,
                'message' => $e->getMessage()
            ));
        } finally {
            if ($lock_acquired) {
                try {
                    $db->query('SELECT RELEASE_LOCK(?)', array($lock_key));
                } catch (\Throwable $e) {
                    log_message('error', '[Proposals] Could not release add item lock: ' . $e->getMessage());
                }
            }
        }
    }

    public function update_item()
    {
        if (!$this->_local_has_manage_permission()) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('permission_denied')));
        }

        $this->validate_submitted_data(array(
            'id' => 'required|numeric'
        ));

        $id = (int) $this->request->getPost('id');
        $item = $this->Proposal_items_model->get_one($id);
        if (!$item || empty($item->id) || !empty($item->deleted)) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('record_not_found')));
        }

        $proposal_id = (int) $item->proposal_id;
        if (!$this->_local_proposal_belongs_to_company($proposal_id)) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('permission_denied')));
        }

        $section_id = (int) $this->request->getPost('section_id');
        if (!$section_id) {
            $section_id = !empty($item->section_id) ? (int) $item->section_id : null;
        }

        if ($section_id && !$this->_local_section_belongs_to_proposal($section_id, $proposal_id)) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('record_not_found')));
        }

        try {
            $data = $this->_local_prepare_item_data($proposal_id, $section_id, $item);
            $ok = $this->Proposal_items_model->ci_save($data, $id);
            if (!$ok) {
                return $this->response->setJSON(array('success' => false, 'message' => app_lang('error_occurred')));
            }

            $this->_local_update_product_sale_price($data);
            $this->Proposals_model->calculate_totals($proposal_id);

            return $this->response->setJSON(array(
                'success' => true,
                'data' => $data,
                'message' => app_lang('record_saved')
            ));
        } catch (\Throwable $e) {
            log_message('error', '[Proposals] Safe update item error: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON(array(
                'success' => false,
                'message' => $e->getMessage()
            ));
        }
    }

    private function _local_has_manage_permission()
    {
        if ($this->login_user->is_admin) {
            return true;
        }

        $permissions = $this->login_user->permissions ?? array();
        return get_array_value($permissions, 'proposals_manage') == '1';
    }

    private function _local_company_id()
    {
        if (isset($this->login_user->company_id) && $this->login_user->company_id) {
            return (int) $this->login_user->company_id;
        }

        return (int) get_default_company_id();
    }

    private function _local_proposal_belongs_to_company($proposal_id)
    {
        $db = db_connect('default');
        $table = $db->prefixTable('proposals_custom');
        $builder = $db->table($table)->select('id')->where('id', (int) $proposal_id)->where('deleted', 0);

        if ($db->fieldExists('company_id', $table)) {
            $builder->where('company_id', $this->_local_company_id());
        }

        return $builder->get()->getRow() ? true : false;
    }

    private function _local_section_belongs_to_proposal($section_id, $proposal_id)
    {
        $section = $this->Proposal_sections_model->get_one((int) $section_id);
        return $section && !empty($section->id) && empty($section->deleted) && (int) $section->proposal_id === (int) $proposal_id;
    }

    private function _local_get_next_item_sort($proposal_id, $section_id)
    {
        $db = db_connect('default');
        $table = $db->prefixTable('proposal_items_custom');
        $builder = $db->table($table)
            ->selectMax('sort', 'sort')
            ->where('deleted', 0)
            ->where('proposal_id', (int) $proposal_id);

        if ($section_id) {
            $builder->where('section_id', (int) $section_id);
        } else {
            $builder->where('section_id', null);
        }

        $row = $builder->get()->getRow();
        return ($row && is_numeric($row->sort)) ? ((int) $row->sort + 1) : 1;
    }

    private function _local_prepare_item_data($proposal_id, $section_id, $existing_item = null)
    {
        $item_id_raw = trim((string) $this->request->getPost('item_id'));
        $item_type = (string) ($this->request->getPost('item_type') ?: 'material');

        if ($item_id_raw !== '' && strpos($item_id_raw, 's-') === 0) {
            $item_type = 'service';
            $item_id = (int) substr($item_id_raw, 2);
        } else {
            $item_id = (int) get_only_numeric_value($item_id_raw);
        }

        $description = trim((string) $this->request->getPost('description'));
        $qty = $this->_local_parse_decimal($this->request->getPost('qty'));
        $qty = $qty > 0 ? $qty : 1;
        $cost_unit = $this->_local_parse_decimal($this->request->getPost('cost_unit'));

        $markup_raw = $this->request->getPost('markup_percent');
        $markup_percent = $this->_local_parse_decimal($markup_raw);
        if (($markup_raw === null || $markup_raw === '') && !$existing_item) {
            $settings = $this->Proposals_module_settings_model->get_settings($this->_local_company_id());
            $markup_percent = (float) ($settings->default_markup_percent ?? 0);
        }

        $sale_unit = $this->_local_parse_decimal($this->request->getPost('sale_unit'));
        if ($sale_unit <= 0) {
            $sale_unit = $cost_unit > 0 ? ($cost_unit * (1 + ($markup_percent / 100))) : 0;
        }

        $show_in_proposal = $this->request->getPost('show_in_proposal');
        if ($show_in_proposal === null || $show_in_proposal === '') {
            $show_in_proposal = $existing_item ? (int) ($existing_item->show_in_proposal ?? 0) : 0;
        }

        $show_values = $this->request->getPost('show_values_in_proposal');
        if ($show_values === null || $show_values === '') {
            $show_values = $existing_item ? (int) ($existing_item->show_values_in_proposal ?? 0) : 0;
        }

        $in_memory = $this->request->getPost('in_memory');
        if ($in_memory === null || $in_memory === '') {
            $in_memory = $existing_item ? (int) ($existing_item->in_memory ?? 1) : 1;
        }

        return array(
            'proposal_id' => (int) $proposal_id,
            'section_id' => $section_id ? (int) $section_id : null,
            'item_id' => $item_id ?: null,
            'item_type' => $item_type,
            'description_override' => $description,
            'cost_unit' => $cost_unit,
            'qty' => $qty,
            'markup_percent' => $markup_percent,
            'sale_unit' => $sale_unit,
            'total' => $qty * $sale_unit,
            'show_in_proposal' => $show_in_proposal ? 1 : 0,
            'show_values_in_proposal' => $show_values ? 1 : 0,
            'in_memory' => $in_memory ? 1 : 0
        );
    }

    private function _local_parse_decimal($value)
    {
        $text = trim((string) $value);
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

        return (float) $text;
    }

    private function _local_update_product_sale_price($data)
    {
        if (empty($data['in_memory']) || empty($data['item_id']) || empty($data['sale_unit']) || ($data['item_type'] ?? 'material') !== 'material') {
            return;
        }

        $db = db_connect('default');
        $items_table = $db->prefixTable('items');
        $update_data = array();

        if ($db->fieldExists('sale', $items_table)) {
            $update_data['sale'] = (float) $data['sale_unit'];
        } elseif ($db->fieldExists('rate', $items_table)) {
            $update_data['rate'] = (float) $data['sale_unit'];
        }

        if (!$update_data) {
            return;
        }

        $items_model = model('App\\Models\\Items_model');
        $items_model->ci_save($update_data, (int) $data['item_id']);
    }

    private function _local_lock_key($proposal_id, $section_id, $data)
    {
        $parts = array(
            'pitem',
            (int) $proposal_id,
            (int) $section_id,
            (int) ($data['item_id'] ?? 0),
            (string) ($data['item_type'] ?? 'material'),
            (int) $this->login_user->id
        );

        return substr(implode('_', $parts), 0, 64);
    }

    private function _local_find_recent_equivalent_item($proposal_id, $section_id, $data, $seconds = 4)
    {
        $db = db_connect('default');
        $table = $db->prefixTable('proposal_items_custom');
        $builder = $db->table($table)
            ->where('proposal_id', (int) $proposal_id)
            ->where('deleted', 0)
            ->where('created_by', (int) $this->login_user->id)
            ->where('item_type', (string) ($data['item_type'] ?? 'material'))
            ->where('qty', (float) ($data['qty'] ?? 0))
            ->where('cost_unit', (float) ($data['cost_unit'] ?? 0))
            ->where('sale_unit', (float) ($data['sale_unit'] ?? 0))
            ->where('created_at >=', date('Y-m-d H:i:s', time() - max(1, (int) $seconds)))
            ->orderBy('id', 'DESC');

        if ($section_id) {
            $builder->where('section_id', (int) $section_id);
        } else {
            $builder->where('section_id', null);
        }

        if (!empty($data['item_id'])) {
            $builder->where('item_id', (int) $data['item_id']);
        } else {
            $builder->where('item_id', null);
            $builder->where('description_override', (string) ($data['description_override'] ?? ''));
        }

        return $builder->get(1)->getRow();
    }
}
