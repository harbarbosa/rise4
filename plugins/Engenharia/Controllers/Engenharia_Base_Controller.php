<?php

namespace Engenharia\Controllers;

use App\Controllers\Security_Controller;

class Engenharia_Base_Controller extends Security_Controller
{
    public function __construct()
    {
        parent::__construct(!is_cli());
        \Engenharia\Plugin::runMigrations();
    }

    protected function requirePermission(string $permission)
    {
        if (!\Engenharia\Plugin::hasPermission($this->login_user, $permission)) {
            app_redirect('forbidden');
        }
    }

    protected function renderPluginView(string $view, array $data = array())
    {
        return $this->template->rander('Engenharia\\Views\\' . trim($view, "\\/"), $data);
    }

    protected function renderPluginModalView(string $view, array $data = array())
    {
        return $this->template->view('Engenharia\\Views\\' . trim($view, "\\/"), $data);
    }
}
