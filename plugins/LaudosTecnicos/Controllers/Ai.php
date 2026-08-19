<?php

namespace LaudosTecnicos\Controllers;

use LaudosTecnicos\Models\LaudoPlatform_model;
use LaudosTecnicos\Models\LaudoTypes_model;
use LaudosTecnicos\Models\LaudoCategories_model;

class Ai extends LaudosTecnicos_Base_Controller
{
    private LaudoPlatform_model $platform_model;

    public function __construct()
    {
        parent::__construct();
        $this->platform_model = model(LaudoPlatform_model::class);
    }

    public function index()
    {
        $this->ensureAccess();
        if (!\LaudosTecnicos\Plugin::canManageAi($this->login_user) && !empty($this->login_user) && empty($this->login_user->is_admin)) {
            app_redirect('forbidden');
        }

        return $this->template->rander('LaudosTecnicos\\Views\\ai\\index', array(
            'settings' => $this->settings_model->get_all_settings_with_defaults(),
            'prompts' => $this->platform_model->get_prompts(),
            'usage_stats' => $this->platform_model->get_report_summary(),
            'types_dropdown' => model(LaudoTypes_model::class)->get_active_dropdown(true),
            'categories_dropdown' => model(LaudoCategories_model::class)->get_active_dropdown(true),
        ));
    }

    public function save_settings()
    {
        $this->ensureSettingsAccess();
        if (!\LaudosTecnicos\Plugin::canManageAi($this->login_user) && !empty($this->login_user) && empty($this->login_user->is_admin)) {
            app_redirect('forbidden');
        }

        $fields = array(
            'ai_provider', 'ai_endpoint_url', 'ai_api_token', 'ai_model', 'ai_temperature',
            'ai_token_limit', 'ai_timeout', 'ai_prompt_template', 'ai_allowed_resources', 'ai_user_limit'
        );
        foreach ($fields as $field) {
            $value = trim((string) $this->request->getPost($field));
            $this->settings_model->save_setting($field, $value);
        }

        return $this->response->setJSON(array('success' => true, 'message' => app_lang('record_saved')));
    }

    public function save_prompt()
    {
        $this->ensureAccess();
        if (!\LaudosTecnicos\Plugin::canManageAi($this->login_user) && !empty($this->login_user) && empty($this->login_user->is_admin)) {
            app_redirect('forbidden');
        }

        $saved = $this->platform_model->save_prompt(array(
            'id' => (int) $this->request->getPost('id'),
            'name' => trim((string) $this->request->getPost('name')),
            'code' => trim((string) $this->request->getPost('code')),
            'description' => trim((string) $this->request->getPost('description')),
            'template_text' => trim((string) $this->request->getPost('template_text')),
            'category' => trim((string) $this->request->getPost('category')),
            'version' => (int) $this->request->getPost('version') ?: 1,
            'is_active' => $this->request->getPost('is_active') ? 1 : 0,
            'created_by' => (int) $this->login_user->id,
            'updated_by' => (int) $this->login_user->id,
        ));

        return $this->response->setJSON(array('success' => (bool) $saved, 'data' => $saved));
    }

    public function generate()
    {
        $this->ensureAccess();
        if (!\LaudosTecnicos\Plugin::canManageAi($this->login_user) && !empty($this->login_user) && empty($this->login_user->is_admin)) {
            app_redirect('forbidden');
        }

        $prompt_id = (int) $this->request->getPost('prompt_id');
        $prompt_text = trim((string) $this->request->getPost('prompt_text'));
        $context = $this->request->getPost();
        $prompt = $prompt_text;

        if ($prompt_id) {
            foreach ($this->platform_model->get_prompts() as $row) {
                if ((int) $row->id === $prompt_id) {
                    $prompt = laudostecnicos_render_ai_prompt((string) $row->template_text, $context);
                    break;
                }
            }
        }

        $result = $this->generateText($prompt, $context);
        $usage_id = $this->platform_model->log_ai_usage(array(
            'prompt_id' => $prompt_id,
            'user_id' => (int) $this->login_user->id,
            'resource_type' => trim((string) $this->request->getPost('resource_type')),
            'resource_id' => (int) $this->request->getPost('resource_id'),
            'provider' => get_array_value(laudostecnicos_ai_config(), 'provider'),
            'model' => get_array_value(laudostecnicos_ai_config(), 'model'),
            'prompt_text' => $prompt,
            'response_text' => get_array_value($result, 'text'),
            'tokens_input' => (int) get_array_value($result, 'tokens_input'),
            'tokens_output' => (int) get_array_value($result, 'tokens_output'),
            'temperature' => (float) get_array_value(laudostecnicos_ai_config(), 'temperature'),
            'meta_json' => laudostecnicos_safe_json($result),
        ));

        return $this->response->setJSON(array(
            'success' => true,
            'usage_id' => $usage_id,
            'data' => $result,
        ));
    }

    private function generateText(string $prompt, array $context): array
    {
        $config = laudostecnicos_ai_config();
        $system_prompt = $config['prompt_template'] ?: 'Voce auxilia na redacao tecnica de laudos. Nunca aprove documentos. Sempre produza sugestoes revisaveis.';
        $input = trim($system_prompt . "\n\n" . $prompt);

        if (empty($config['endpoint_url']) || empty($config['provider']) || empty($config['api_token'])) {
            return array(
                'text' => $this->fallbackText($prompt, $context),
                'tokens_input' => str_word_count($input),
                'tokens_output' => 0,
                'provider' => 'local',
            );
        }

        $payload = array(
            'model' => $config['model'],
            'temperature' => $config['temperature'],
            'max_tokens' => $config['token_limit'],
            'input' => $input,
            'messages' => array(
                array('role' => 'system', 'content' => $system_prompt),
                array('role' => 'user', 'content' => $prompt),
            ),
        );

        $ch = curl_init($config['endpoint_url']);
        curl_setopt_array($ch, array(
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => $config['timeout'],
            CURLOPT_TIMEOUT => $config['timeout'],
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Authorization: Bearer ' . $config['api_token'],
            ),
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ));
        $body = curl_exec($ch);
        $error = curl_error($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($body === false || $error || $status >= 400) {
            return array(
                'text' => $this->fallbackText($prompt, $context),
                'tokens_input' => str_word_count($input),
                'tokens_output' => 0,
                'provider' => $config['provider'],
                'error' => $error ?: $body,
            );
        }

        $decoded = json_decode($body, true);
        $text = '';
        if (is_array($decoded)) {
            $text = (string) get_array_value($decoded, 'output_text');
            if ($text === '' && isset($decoded['choices'][0]['message']['content'])) {
                $text = (string) $decoded['choices'][0]['message']['content'];
            }
            if ($text === '' && isset($decoded['content'][0]['text'])) {
                $text = (string) $decoded['content'][0]['text'];
            }
        }

        if ($text === '') {
            $text = $this->fallbackText($prompt, $context);
        }

        return array(
            'text' => $text,
            'tokens_input' => str_word_count($input),
            'tokens_output' => str_word_count($text),
            'provider' => $config['provider'],
            'raw' => $decoded,
        );
    }

    private function fallbackText(string $prompt, array $context): string
    {
        return implode("\n\n", array_filter(array(
            'Sugestao gerada localmente para revisao humana.',
            trim((string) $prompt),
            'Contexto: ' . laudostecnicos_safe_json($context),
        )));
    }
}
