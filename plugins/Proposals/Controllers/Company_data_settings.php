<?php

namespace Proposals\Controllers;

use App\Controllers\Security_Controller;

class Company_data_settings extends Security_Controller
{
    public $Settings_model;

    public function __construct()
    {
        parent::__construct(true);
        $this->access_only_admin_or_settings_admin();
        $this->Settings_model = model('App\\Models\\Settings_model');
    }

    public function index()
    {
        $view_data = array(
            "company_data" => array(
                "company_data_name" => get_setting("company_data_name"),
                "company_data_cnpj" => get_setting("company_data_cnpj"),
                "company_data_email" => get_setting("company_data_email"),
                "company_data_phone" => get_setting("company_data_phone"),
                "company_data_address" => get_setting("company_data_address"),
                "company_data_city" => get_setting("company_data_city"),
                "company_data_state" => get_setting("company_data_state"),
                "company_data_zip" => get_setting("company_data_zip"),
                "company_data_website" => get_setting("company_data_website")
            )
        );

        return $this->template->rander("Proposals\\Views\\company_data_settings\\index", $view_data);
    }

    public function save()
    {
        $this->access_only_admin_or_settings_admin();

        $data = array(
            "company_data_name" => trim((string)$this->request->getPost("company_data_name")),
            "company_data_cnpj" => trim((string)$this->request->getPost("company_data_cnpj")),
            "company_data_email" => trim((string)$this->request->getPost("company_data_email")),
            "company_data_phone" => trim((string)$this->request->getPost("company_data_phone")),
            "company_data_address" => trim((string)$this->request->getPost("company_data_address")),
            "company_data_city" => trim((string)$this->request->getPost("company_data_city")),
            "company_data_state" => trim((string)$this->request->getPost("company_data_state")),
            "company_data_zip" => trim((string)$this->request->getPost("company_data_zip")),
            "company_data_website" => trim((string)$this->request->getPost("company_data_website"))
        );

        foreach ($data as $key => $value) {
            $this->Settings_model->save_setting($key, $value);
        }

        return $this->response->setJSON(array(
            "success" => true,
            "message" => app_lang("record_saved")
        ));
    }
}
