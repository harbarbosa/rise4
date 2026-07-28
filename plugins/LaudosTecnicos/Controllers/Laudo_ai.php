<?php

namespace LaudosTecnicos\Controllers;

use App\Controllers\Security_Controller;
use LaudosTecnicos\Models\Laudo_ai_model;

class Laudo_ai extends Security_Controller
{
    protected $ai_model;

    public function __construct()
    {
        parent::__construct(true);
        $this->access_only_team_members();
        
        $this->ai_model = model('LaudosTecnicos\Models\Laudo_ai_model');
    }

    public function index()
    {
        if (!$this->_has_edit_permission()) {
            app_redirect('forbidden');
        }

        $config = $this->ai_model->get_active_config();
        
        $view_data = array(
            'config' => $config,
            'providers' => $this->_get_providers()
        );

        return $this->template->rander('LaudosTecnicos\Views\ai\config', $view_data);
    }

    public function save_config()
    {
        if (!$this->_has_edit_permission()) {
            return $this->_json_permission_denied();
        }

        $id = $this->request->getPost('id');
        
        $data = array(
            'provider' => $this->request->getPost('provider'),
            'name' => $this->request->getPost('name'),
            'api_url' => $this->request->getPost('api_url'),
            'api_key' => $this->request->getPost('api_key'),
            'model' => $this->request->getPost('model'),
            'temperature' => $this->request->getPost('temperature') ?: 0.7,
            'max_tokens' => $this->request->getPost('max_tokens') ?: 2000,
            'timeout' => $this->request->getPost('timeout') ?: 30,
            'system_prompt' => $this->request->getPost('system_prompt'),
            'monthly_limit' => $this->request->getPost('monthly_limit') ?: 1000,
            'is_active' => $this->request->getPost('is_active') ? 1 : 0,
            'updated_at' => get_my_local_time()
        );

        $save_id = $this->ai_model->save($data, $id);

        if ($save_id) {
            return $this->response->setJSON(array('success' => true, 'message' => app_lang('record_saved')));
        }

        return $this->response->setJSON(array('success' => false, 'message' => app_lang('error_occurred')));
    }

    public function generate()
    {
        if (!$this->_has_edit_permission()) {
            return $this->_json_permission_denied();
        }

        $feature = $this->request->getPost('feature');
        $prompt = $this->request->getPost('prompt');
        $laudo_id = $this->request->getPost('laudo_id');

        if (!$feature || !$prompt) {
            return $this->response->setJSON(array(
                'success' => false,
                'message' => 'Recurso e prompt são obrigatórios'
            ));
        }

        try {
            // Limitar funcionalidades por permissão
            $allowed_features = $this->_get_allowed_features();
            
            if (!in_array($feature, $allowed_features)) {
                return $this->response->setJSON(array(
                    'success' => false,
                    'message' => 'Recurso não autorizado'
                ));
            }

            $response = $this->ai_model->generate($feature, $prompt, $this->login_user->id);

            return $this->response->setJSON(array(
                'success' => true,
                'data' => array(
                    'response' => $response,
                    'feature' => $feature
                )
            ));
        } catch (\Exception $e) {
            return $this->response->setJSON(array(
                'success' => false,
                'message' => $e->getMessage()
            ));
        }
    }

    public function test()
    {
        if (!$this->_has_edit_permission()) {
            return $this->_json_permission_denied();
        }

        try {
            $response = $this->ai_model->generate('test', 'Responda apenas "OK"', $this->login_user->id);

            return $this->response->setJSON(array(
                'success' => true,
                'message' => 'Conexão OK: ' . substr($response, 0, 100)
            ));
        } catch (\Exception $e) {
            return $this->response->setJSON(array(
                'success' => false,
                'message' => 'Erro: ' . $e->getMessage()
            ));
        }
    }

    private function _get_providers()
    {
        return array(
            'openrouter' => array(
                'name' => 'OpenRouter',
                'url' => 'https://openrouter.ai/api/v1/chat/completions',
                'models' => array(
                    'openai/gpt-3.5-turbo' => 'GPT-3.5 Turbo',
                    'openai/gpt-4' => 'GPT-4',
                    'anthropic/claude-3-haiku' => 'Claude 3 Haiku',
                    'anthropic/claude-3-sonnet' => 'Claude 3 Sonnet',
                    'google/gemini-pro' => 'Gemini Pro'
                )
            ),
            'openai' => array(
                'name' => 'OpenAI',
                'url' => 'https://api.openai.com/v1/chat/completions',
                'models' => array(
                    'gpt-3.5-turbo' => 'GPT-3.5 Turbo',
                    'gpt-4' => 'GPT-4',
                    'gpt-4-turbo' => 'GPT-4 Turbo'
                )
            ),
            'anthropic' => array(
                'name' => 'Anthropic',
                'url' => 'https://api.anthropic.com/v1/messages',
                'models' => array(
                    'claude-3-haiku-20240307' => 'Claude 3 Haiku',
                    'claude-3-sonnet-20240229' => 'Claude 3 Sonnet',
                    'claude-3-opus-20240229' => 'Claude 3 Opus'
                )
            ),
            'custom' => array(
                'name' => 'Endpoint Personalizado',
                'url' => '',
                'models' => array()
            )
        );
    }

    private function _get_allowed_features()
    {
        // Lista completa - filtrar por permissão em produção
        return array(
            'organize_notes',
            'improve_text',
            'create_objective',
            'create_scope',
            'create_methodology',
            'create_diagnosis',
            'create_conclusion',
            'create_recommendations',
            'executive_summary',
            'describe_photo',
            'check_gaps',
            'check_inconsistencies',
            'suggest_action_plan'
        );
    }

    private function _has_edit_permission()
    {
        if ($this->login_user->is_admin) return true;
        return get_array_value($this->login_user->permissions ?? array(), 'laudos_edit') == '1';
    }

    private function _json_permission_denied()
    {
        return $this->response->setStatusCode(403)->setJSON(array('success' => false, 'message' => app_lang('access_denied')));
    }
}