<?php

namespace Organizador\Controllers;

use App\Controllers\Security_Controller;
use Organizador\Models\My_task_tags_model;
use Organizador\Plugin;

class Organizador_tags extends Security_Controller
{
    public $Tags_model;

    function __construct()
    {
        parent::__construct();
        $this->Tags_model = model(My_task_tags_model::class);
    }

    private function _ensure_access()
    {
        if (!Plugin::canManageTags($this->login_user)) {
            app_redirect('forbidden');
        }
    }

    public function index()
    {
        $this->_ensure_access();
        return $this->template->rander('Organizador\\Views\\tags\\index');
    }

    public function list_data()
    {
        $this->_ensure_access();
        $rows = array();
        foreach ($this->Tags_model->get_details()->getResult() as $tag) {
            $color = $tag->color ?: '#6c757d';
            $rows[] = array(
                esc($tag->title),
                '<span class="color-tag border-circle wh10 d-inline-block me-2" style="background-color:' . esc($color) . ';"></span><span class="badge rounded-pill" style="background:' . esc($color) . ';">' . esc($color) . '</span>',
                (int) $tag->sort,
                modal_anchor(get_uri('organizador/tags/modal_form'), "<i data-feather='edit' class='icon-14'></i>", array('class' => 'action-icon', 'title' => app_lang('edit'), 'data-post-id' => $tag->id)) .
                js_anchor("<i data-feather='trash-2' class='icon-14'></i>", array('class' => 'action-icon text-danger', 'title' => app_lang('delete'), 'data-id' => $tag->id, 'data-action-url' => get_uri('organizador/tags/delete'), 'data-action' => 'delete-confirmation')),
            );
        }

        echo json_encode(array('data' => $rows));
    }

    public function modal_form($id = 0)
    {
        $this->_ensure_access();
        $id = $id ? (int) $id : (int) $this->request->getPost('id');
        if ($id) {
            $view_data['model_info'] = $this->Tags_model->get_one($id);
        } else {
            $view_data['model_info'] = (object) array(
                'id' => 0,
                'title' => '',
                'color' => '#4A8AF4',
                'sort' => 0,
            );
        }

        return view('Organizador\\Views\\tags\\modal_form', $view_data);
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
        $data = array(
            'title' => clean_data($title),
            'color' => clean_data($this->request->getPost('color')),
            'sort' => (int) $this->request->getPost('sort'),
            'deleted' => 0,
        );

        $save_id = $this->Tags_model->ci_save($data, $id);
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

        if ($this->Tags_model->is_tag_used($id)) {
            echo json_encode(array('success' => false, 'message' => app_lang('organizador_tag_in_use')));
            return;
        }

        $success = $this->Tags_model->delete($id);
        echo json_encode(array('success' => (bool) $success, 'message' => $success ? app_lang('record_deleted') : app_lang('error_occurred')));
    }

    private function _row_data($id)
    {
        $tag = $this->Tags_model->get_details(array('id' => (int) $id))->getRow();
        if (!$tag) {
            return array();
        }

        $color = $tag->color ?: '#6c757d';

        return array(
            esc($tag->title),
            '<span class="color-tag border-circle wh10 d-inline-block me-2" style="background-color:' . esc($color) . ';"></span><span class="badge rounded-pill" style="background:' . esc($color) . ';">' . esc($color) . '</span>',
            (int) $tag->sort,
            modal_anchor(get_uri('organizador/tags/modal_form'), "<i data-feather='edit' class='icon-14'></i>", array('class' => 'action-icon', 'title' => app_lang('edit'), 'data-post-id' => $tag->id)) .
            js_anchor("<i data-feather='trash-2' class='icon-14'></i>", array('class' => 'action-icon text-danger', 'title' => app_lang('delete'), 'data-id' => $tag->id, 'data-action-url' => get_uri('organizador/tags/delete'), 'data-action' => 'delete-confirmation')),
        );
    }
}
