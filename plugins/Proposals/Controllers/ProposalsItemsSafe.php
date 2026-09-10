<?php

namespace Proposals\Controllers;

class ProposalsItemsSafe extends Proposals
{
    public function add_item()
    {
        if (!$this->_local_has_manage_permission()) {
            return parent::add_item();
        }

        $proposal_id = (int) $this->request->getPost('proposal_id');
        $section_id = (int) $this->request->getPost('section_id');
        $item_id = (string) ($this->request->getPost('item_id') ?? '');
        $item_type = (string) ($this->request->getPost('item_type') ?? 'material');

        if (!$proposal_id || !$this->_local_proposal_belongs_to_company($proposal_id)) {
            return parent::add_item();
        }

        $db = db_connect('default');
        $lock_key = 'proposal_item_add_' . $proposal_id . '_' . $section_id . '_' . preg_replace('/[^a-zA-Z0-9_-]/', '', $item_id) . '_' . (int) $this->login_user->id;
        $lock_key = substr($lock_key, 0, 64);
        $lock_acquired = false;

        try {
            $lock_row = $db->query('SELECT GET_LOCK(?, 5) AS lck', array($lock_key))->getRow();
            $lock_acquired = $lock_row && (int) ($lock_row->lck ?? 0) === 1;

            if ($lock_acquired && $item_id !== '') {
                $existing = $this->_find_recent_equivalent_item($proposal_id, $section_id, $item_id, $item_type);
                if ($existing) {
                    log_message('warning', '[Proposals] Duplicate add blocked. proposal_id=' . $proposal_id . ', section_id=' . $section_id . ', item_id=' . $item_id . ', existing_id=' . $existing->id);
                    return $this->response->setJSON(array(
                        'success' => true,
                        'data' => (array) $existing,
                        'message' => app_lang('record_saved')
                    ));
                }
            }

            try {
                return parent::add_item();
            } catch (\Throwable $e) {
                if (strpos($e->getMessage(), 'could not be passed by reference') === false) {
                    throw $e;
                }

                $created = $this->_find_recent_equivalent_item($proposal_id, $section_id, $item_id, $item_type, 10);
                if (!$created) {
                    throw $e;
                }

                $sale_unit = $this->request->getPost('sale_unit');
                if ($item_id !== '' && $sale_unit !== null && $sale_unit !== '') {
                    $items_model = model('App\\Models\\Items_model');
                    $item_update_data = array('unit' => $sale_unit);
                    $items_model->ci_save($item_update_data, preg_replace('/\D+/', '', $item_id));
                }

                $this->Proposals_model->calculate_totals($proposal_id);
                log_message('warning', '[Proposals] Recovered item add after ci_save reference error. proposal_id=' . $proposal_id . ', item_id=' . $item_id . ', created_id=' . $created->id);

                return $this->response->setJSON(array(
                    'success' => true,
                    'data' => (array) $created,
                    'message' => app_lang('record_saved')
                ));
            }
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

    private function _find_recent_equivalent_item($proposal_id, $section_id, $item_id, $item_type, $seconds = 3)
    {
        $db = db_connect('default');
        $table = $db->prefixTable('proposal_items_custom');
        $builder = $db->table($table)
            ->where('proposal_id', (int) $proposal_id)
            ->where('deleted', 0)
            ->where('created_by', (int) $this->login_user->id)
            ->where('item_type', $item_type)
            ->orderBy('id', 'DESC');

        if ($section_id) {
            $builder->where('section_id', (int) $section_id);
        } else {
            $builder->groupStart()->where('section_id', 0)->orWhere('section_id IS NULL', null, false)->groupEnd();
        }

        if ($item_id !== '') {
            $numeric_item_id = preg_replace('/\D+/', '', $item_id);
            if ($numeric_item_id !== '') {
                $builder->where('item_id', (int) $numeric_item_id);
            }
        }

        $created_at = date('Y-m-d H:i:s', time() - max(1, (int) $seconds));
        $builder->where('created_at >=', $created_at);

        $qty = $this->request->getPost('qty');
        $cost_unit = $this->request->getPost('cost_unit');
        $sale_unit = $this->request->getPost('sale_unit');
        if ($qty !== null && $qty !== '') {
            $builder->where('qty', (float) $qty);
        }
        if ($cost_unit !== null && $cost_unit !== '') {
            $builder->where('cost_unit', (float) $cost_unit);
        }
        if ($sale_unit !== null && $sale_unit !== '') {
            $builder->where('sale_unit', (float) $sale_unit);
        }

        return $builder->get(1)->getRow();
    }
}
