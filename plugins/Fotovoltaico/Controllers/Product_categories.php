<?php

namespace Fotovoltaico\Controllers;

use App\Controllers\Security_Controller;
use Fotovoltaico\Plugin;

class Product_categories extends Security_Controller
{
    private $Product_categories_model;

    public function __construct()
    {
        parent::__construct();
        $this->access_only_team_members();

        if (!Plugin::canViewProducts($this->login_user) && !Plugin::canManageProducts($this->login_user)) {
            app_redirect('forbidden');
        }

        $this->Product_categories_model = model('Fotovoltaico\\Models\\Product_categories_model');
    }

    public function index()
    {
        $view_data = array();
        $view_data['can_manage_products'] = Plugin::canManageProducts($this->login_user);
        return $this->template->rander('Fotovoltaico\\Views\\product_categories\\index', $view_data);
    }

    public function list_data()
    {
        $list_data = $this->Product_categories_model->get_details()->getResult();
        $result = array();
        foreach ($list_data as $data) {
            $result[] = $this->_make_row($data);
        }

        echo json_encode(array('data' => $result));
    }

    public function modal_form()
    {
        $this->validate_submitted_data(array(
            'id' => 'numeric'
        ));

        $id = (int) $this->request->getPost('id');
        $view_data = array();
        $view_data['model_info'] = $this->Product_categories_model->get_one($id);

        return $this->template->view('Fotovoltaico\\Views\\product_categories\\modal_form', $view_data);
    }

    public function save()
    {
        $this->validate_submitted_data(array(
            'id' => 'numeric',
            'title' => 'required'
        ));

        if (!Plugin::canManageProducts($this->login_user)) {
            app_redirect('forbidden');
        }

        $id = (int) $this->request->getPost('id');
        $title = trim((string) $this->request->getPost('title'));
        $slug = trim((string) $this->request->getPost('slug'));
        if ($slug === '') {
            $slug = $this->_slugify($title);
        }

        $data = array(
            'title' => $title,
            'slug' => $this->_unique_slug($slug, $id),
            'description' => trim((string) $this->request->getPost('description')) ?: null,
            'active' => $this->request->getPost('active') ? 1 : 0,
            'sort' => (int) $this->request->getPost('sort'),
            'updated_at' => get_my_local_time(),
        );

        $data = clean_data($data);
        if (!$id) {
            $data['created_by'] = $this->login_user->id;
            $data['created_at'] = get_my_local_time();
        }

        $save_id = $this->Product_categories_model->ci_save($data, $id);
        if ($save_id) {
            echo json_encode(array(
                'success' => true,
                'id' => $save_id,
                'data' => $this->_make_row($this->Product_categories_model->get_details(array('id' => $save_id))->getRow()),
                'message' => app_lang('record_saved')
            ));
        } else {
            echo json_encode(array('success' => false, 'message' => app_lang('error_occurred')));
        }
    }

    public function delete()
    {
        $this->validate_submitted_data(array(
            'id' => 'required|numeric'
        ));

        if (!Plugin::canManageProducts($this->login_user)) {
            app_redirect('forbidden');
        }

        $id = (int) $this->request->getPost('id');
        if ($this->Product_categories_model->delete($id)) {
            echo json_encode(array('success' => true, 'message' => app_lang('record_deleted')));
        } else {
            echo json_encode(array('success' => false, 'message' => app_lang('record_cannot_be_deleted')));
        }
    }

    private function _make_row($data)
    {
        return array(
            esc($data->title),
            esc($data->slug ?: '-'),
            $this->_status_badge((int) $data->active),
            Plugin::canManageProducts($this->login_user)
                ? modal_anchor(get_uri('fotovoltaico/product_categories/modal_form'), "<i data-feather='edit' class='icon-16'></i>", array(
                    'class' => 'edit',
                    'title' => app_lang('fotovoltaico_edit_category'),
                    'data-post-id' => $data->id
                )) . js_anchor("<i data-feather='x' class='icon-16'></i>", array(
                    'title' => app_lang('delete'),
                    'class' => 'delete',
                    'data-id' => $data->id,
                    'data-action-url' => get_uri('fotovoltaico/product_categories/delete'),
                    'data-action' => 'delete'
                ))
                : '-'
        );
    }

    private function _status_badge($active)
    {
        if ($active) {
            return "<span class='badge bg-success'>" . esc(app_lang('active')) . "</span>";
        }

        return "<span class='badge bg-secondary'>" . esc(app_lang('inactive')) . "</span>";
    }

    private function _slugify($value)
    {
        $value = trim((string) $value);
        if ($value === '') {
            return 'categoria';
        }

        $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        if ($ascii !== false && $ascii !== '') {
            $value = $ascii;
        }

        $value = strtolower($value);
        $value = preg_replace('/[^a-z0-9]+/i', '-', $value);
        $value = trim($value, '-');

        return $value ?: 'categoria';
    }

    private function _unique_slug($slug, $ignore_id = 0)
    {
        $base = $slug ?: 'categoria';
        $candidate = $base;
        $suffix = 2;
        while ($this->_slug_exists($candidate, $ignore_id)) {
            $candidate = $base . '-' . $suffix;
            $suffix++;
        }

        return $candidate;
    }

    private function _slug_exists($slug, $ignore_id = 0)
    {
        $table = db_connect('default')->prefixTable('fv_product_categories');
        $builder = db_connect('default')->table($table);
        $builder->where('slug', $slug);
        if ($ignore_id) {
            $builder->where('id !=', $ignore_id);
        }

        return $builder->countAllResults() > 0;
    }
}
