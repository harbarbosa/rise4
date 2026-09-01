<?php

namespace AssistenteIA\Controllers;

use App\Controllers\Security_Controller;

class Settings extends Security_Controller
{
    public function index()
    {
        if (!\AssistenteIA\Plugin::canManageSettings($this->login_user)) return \app_redirect('forbidden');
        return $this->template->rander('AssistenteIA\\Views\\settings', []);
    }

    public function save()
    {
        if (!\AssistenteIA\Plugin::canManageSettings($this->login_user)) return $this->response->setStatusCode(403);
        $key = trim((string)$this->request->getPost('openrouter_key'));
        $model = trim((string)$this->request->getPost('model'));
        if ($key !== '') $this->Settings_model->save_setting('assistente_ia_openrouter_key', $key);
        if ($model !== '') $this->Settings_model->save_setting('assistente_ia_model', $model);
        return $this->response->setJSON(['success' => true]);
    }
}
