<?php

namespace Organizador\Controllers;

use App\Controllers\Security_Controller;
use Organizador\Models\My_task_phases_model;
use Organizador\Plugin;

class Organizador_phases extends Security_Controller
{
    public $Phases_model;

    function __construct()
    {
        parent::__construct();
        $this->Phases_model = model(My_task_phases_model::class);
    }

    private function _ensure_access()
    {
        if (!Plugin::canManagePhases($this->login_user)) {
            app_redirect('forbidden');
        }
    }

    public function index()
    {
        $this->_ensure_access();
        return $this->template->rander('Organizador\\Views\\phases\\index');
    }

    public function list_data()
    {
        $this->_ensure_access();
        $rows = array();
        foreach ($this->Phases_model->get_details()->getResult() as $phase) {
            $color = $phase->color ?: '#6c757d';
            $rows[] = array(
                esc($phase->title),
                '<span class="color-tag border-circle wh10 d-inline-block me-2" style="background-color:' . esc($color) . ';"></span><span class="badge rounded-pill" style="background:' . esc($color) . ';">' . esc($color) . '</span>',
                (int) $phase->sort,
                modal_anchor(get_uri('organizador/phases/modal_form'), "<i data-feather='edit' class='icon-14'></i>", array('class' => 'action-icon', 'title' => app_lang('edit'), 'data-post-id' => $phase->id)) .
                js_anchor("<i data-feather='trash-2' class='icon-14'></i>", array('class' => 'action-icon text-danger', 'title' => app_lang('delete'), 'data-id' => $phase->id, 'data-action-url' => get_uri('organizador/phases/delete'), 'data-action' => 'delete-confirmation')),
            );
        }

        echo json_encode(array('data' => $rows));
    }

    public function modal_form($id = 0)
    {
        $this->_ensure_access();
        $id = $id ? (int) $id : (int) $this->request->getPost('id');
        if ($id) {
            $view_data['model_info'] = $this->Phases_model->get_one($id);
        } else {
            $view_data['model_info'] = (object) array(
                'id' => 0,
                'key_name' => '',
                'title' => '',
                'color' => '#4A8AF4',
                'sort' => 0,
            );
        }

        return view('Organizador\\Views\\phases\\modal_form', $view_data);
    }

    public function save()
    {
        $this->_ensure_access();

        $title = trim((string) $this->request->getPost('title'));
        if ($title === '') {
            echo json_encode(array('success' => false, 'message' => app_lang('field_required')));
            return;
        }

        $id = (int) $this->request->getPost('id');
        $phase = $id ? $this->Phases_model->get_one($id) : null;
        $key_name = $phase && $phase->key_name ? $phase->key_name : $this->_build_key_name($title, $id);

        if (!$id) {
            $key_name = $this->_ensure_unique_key_name($key_name);
        }

        $data = array(
            'key_name' => $key_name,
            'title' => clean_data($title),
            'color' => clean_data($this->request->getPost('color')),
            'sort' => (int) $this->request->getPost('sort'),
            'deleted' => 0,
        );

        $save_id = $this->Phases_model->ci_save($data, $id);
        if ($save_id) {
            echo json_encode(array('success' => true, 'data' => $this->_row_data($save_id), 'id' => $save_id, 'message' => app_lang('record_saved')));
            return;
        }

        echo json_encode(array('success' => false, 'message' => app_lang('error_occurred')));
    }

    public function delete()
    {
        $this->_ensure_access();
        $id = (int) $this->request->getPost('id');
        $phase = $this->Phases_model->get_one($id);
        if ($phase && $phase->key_name && $this->Phases_model->is_phase_used($phase->key_name)) {
            echo json_encode(array('success' => false, 'message' => app_lang('organizador_phase_in_use')));
            return;
        }

        $success = $this->Phases_model->delete($id);
        echo json_encode(array('success' => (bool) $success, 'message' => $success ? app_lang('record_deleted') : app_lang('error_occurred')));
    }

    private function _build_key_name($title, $id = 0)
    {
        $slug = ltrim(generate_slug_from_title($title), '-');
        $slug = strtolower(str_replace('-', '_', $slug));
        return $slug ?: ('phase_' . ($id ? (int) $id : time()));
    }

    private function _ensure_unique_key_name($key_name)
    {
        $base = $key_name;
        $suffix = 1;

        while ($this->Phases_model->get_details(array('key_name' => $key_name))->getRow()) {
            $key_name = $base . '_' . $suffix;
            $suffix++;
        }

        return $key_name;
    }

    private function _row_data($id)
    {
        $phase = $this->Phases_model->get_details(array('id' => (int) $id))->getRow();
        if (!$phase) {
            return array();
        }

        $color = $phase->color ?: '#6c757d';

        return array(
            esc($phase->title),
            '<span class="color-tag border-circle wh10 d-inline-block me-2" style="background-color:' . esc($color) . ';"></span><span class="badge rounded-pill" style="background:' . esc($color) . ';">' . esc($color) . '</span>',
            (int) $phase->sort,
            modal_anchor(get_uri('organizador/phases/modal_form'), "<i data-feather='edit' class='icon-14'></i>", array('class' => 'action-icon', 'title' => app_lang('edit'), 'data-post-id' => $phase->id)) .
            js_anchor("<i data-feather='trash-2' class='icon-14'></i>", array('class' => 'action-icon text-danger', 'title' => app_lang('delete'), 'data-id' => $phase->id, 'data-action-url' => get_uri('organizador/phases/delete'), 'data-action' => 'delete-confirmation')),
        );
    }
}
