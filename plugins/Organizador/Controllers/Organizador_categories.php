<?php

namespace Organizador\Controllers;

use App\Controllers\Security_Controller;
use Organizador\Models\My_task_categories_model;
use Organizador\Plugin;

class Organizador_categories extends Security_Controller
{
    public $Categories_model;

    function __construct()
    {
        parent::__construct();
        $this->Categories_model = model(My_task_categories_model::class);
    }

    private function _ensure_access()
    {
        if (!Plugin::canManageCategories($this->login_user)) {
            app_redirect('forbidden');
        }
    }

    public function index()
    {
        $this->_ensure_access();
        return $this->template->rander('Organizador\\Views\\categories\\index');
    }

    public function list_data()
    {
        $this->_ensure_access();
        $rows = array();
        foreach ($this->Categories_model->get_details()->getResult() as $category) {
            $color = $category->color ?: '#6c757d';
            $rows[] = array(
                esc($category->title),
                '<span class="color-tag border-circle wh10 d-inline-block me-2" style="background-color:' . esc($color) . ';"></span><span class="badge rounded-pill" style="background:' . esc($color) . ';">' . esc($color) . '</span>',
                (int) $category->sort,
                modal_anchor(get_uri('organizador/categories/modal_form'), "<i data-feather='edit' class='icon-14'></i>", array('class' => 'action-icon', 'title' => app_lang('edit'), 'data-post-id' => $category->id)) .
                js_anchor("<i data-feather='trash-2' class='icon-14'></i>", array('class' => 'action-icon text-danger', 'title' => app_lang('delete'), 'data-id' => $category->id, 'data-action-url' => get_uri('organizador/categories/delete'), 'data-action' => 'delete-confirmation')),
            );
        }

        echo json_encode(array('data' => $rows));
    }

    public function modal_form($id = 0)
    {
        $this->_ensure_access();
        $id = $id ? (int) $id : (int) $this->request->getPost('id');
        if ($id) {
            $view_data['model_info'] = $this->Categories_model->get_one($id);
        } else {
            $view_data['model_info'] = (object) array(
                'id' => 0,
                'title' => '',
                'color' => '#0d6efd',
                'sort' => 0,
            );
        }

        return view('Organizador\\Views\\categories\\modal_form', $view_data);
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

        $save_id = $this->Categories_model->ci_save($data, $id);
        if ($save_id) {
            echo json_encode(array('success' => true, 'data' => $this->_row_data($save_id), 'newData' => $id ? false : true, 'id' => $save_id, 'message' => app_lang('record_saved')));
            return;
        }

        echo json_encode(array('success' => false, 'message' => app_lang('error_occurred')));
    }

    public function delete()
    {
        $this->_ensure_access();
        $id = (int) $this->request->getPost('id');
        $success = $this->Categories_model->delete($id);
        echo json_encode(array('success' => (bool) $success, 'message' => $success ? app_lang('record_deleted') : app_lang('error_occurred')));
    }

    private function _row_data($id)
    {
        $category = $this->Categories_model->get_details(array('id' => (int) $id))->getRow();
        if (!$category) {
            return array();
        }

        $color = $category->color ?: '#6c757d';

        return array(
            esc($category->title),
            '<span class="color-tag border-circle wh10 d-inline-block me-2" style="background-color:' . esc($color) . ';"></span><span class="badge rounded-pill" style="background:' . esc($color) . ';">' . esc($color) . '</span>',
            (int) $category->sort,
            modal_anchor(get_uri('organizador/categories/modal_form'), "<i data-feather='edit' class='icon-14'></i>", array('class' => 'action-icon', 'title' => app_lang('edit'), 'data-post-id' => $category->id)) .
            js_anchor("<i data-feather='trash-2' class='icon-14'></i>", array('class' => 'action-icon text-danger', 'title' => app_lang('delete'), 'data-id' => $category->id, 'data-action-url' => get_uri('organizador/categories/delete'), 'data-action' => 'delete-confirmation')),
        );
    }
}
