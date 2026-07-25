<?php

namespace LicitaIA\Controllers;

class Ai_analysis extends Licitaia_Base_Controller
{
    public function index()
    {
        $this->ensureAccess();

        return $this->template->rander('LicitaIA\\Views\\ai_analysis\\index', array(
            'summary' => $this->opportunities_model->get_dashboard_summary(),
            'recent_opportunities' => $this->opportunities_model->get_recent_opportunities(5),
            'can_manage' => \LicitaIA\Plugin::canManageOpportunities($this->login_user),
            'can_manage_settings' => \LicitaIA\Plugin::canManageSettings($this->login_user),
            'can_use_ai' => \LicitaIA\Plugin::canUseAi($this->login_user),
        ));
    }

    public function analyze($opportunity_id = 0)
    {
        return $this->runAnalysis((int) $opportunity_id, false);
    }

    public function reanalyze($opportunity_id = 0)
    {
        return $this->runAnalysis((int) $opportunity_id, true);
    }

    private function runAnalysis($opportunity_id, $force = false)
    {
        if (!\LicitaIA\Plugin::canUseAi($this->login_user) && !\LicitaIA\Plugin::canManageOpportunities($this->login_user)) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('forbidden')));
        }

        $opportunity = $this->opportunities_model->get_details(array('id' => $opportunity_id))->getRow();
        if (!$opportunity) {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('error_occurred')));
        }

        $documents = $this->documentsModel()->get_by_opportunity($opportunity_id)->getResult();
        $prepared = $this->prepareDocumentsForAnalysis($opportunity_id, $documents);
        $documents_payload = $prepared['documents_payload'];
        $prompt_documents = $prepared['prompt_documents'];

        if (empty($prompt_documents)) {
            return $this->response->setJSON(array(
                'success' => false,
                'message' => app_lang('licitaia_ai_requires_extracted_documents'),
                'debug' => $prepared['debug'],
            ));
        }

        $settings = $this->settings_model->get_ai_settings();
        if (empty($settings['ai_enabled']) || trim((string) get_array_value($settings, 'ai_enabled')) === '0') {
            return $this->response->setJSON(array('success' => false, 'message' => app_lang('licitaia_ai_disabled')));
        }

        $prompt = $this->buildAnalysisPrompt($opportunity, $prompt_documents, $force);
        $request_payload = array(
            'provider' => get_array_value($settings, 'ai_provider', 'openai'),
            'model' => get_array_value($settings, 'ai_model', 'gpt-4o-mini'),
            'prompt' => $prompt,
            'opportunity_id' => (int) $opportunity->id,
        );

        $log_id = $this->aiLogModel()->log_request(array(
            'opportunity_id' => (int) $opportunity->id,
            'provider' => get_array_value($settings, 'ai_provider', 'openai'),
            'model_name' => get_array_value($settings, 'ai_model', 'gpt-4o-mini'),
            'request_type' => $force ? 'reanalyze' : 'analysis',
            'request_json' => $request_payload,
            'status' => 'processing',
            'created_by' => $this->login_user->id,
        ));

        $api_result = $this->callAiProvider($settings, $prompt);
        if (!$api_result['success']) {
            $failure_data = array(
                'opportunity_id' => (int) $opportunity->id,
                'provider' => get_array_value($settings, 'ai_provider', 'openai'),
                'model_name' => get_array_value($settings, 'ai_model', 'gpt-4o-mini'),
                'request_type' => $force ? 'reanalyze' : 'analysis',
                'request_json' => $request_payload,
                'response_json' => $api_result,
                'status' => 'failed',
                'error_message' => $api_result['message'],
                'created_by' => $this->login_user->id,
            );
            $this->aiLogModel()->log_request($failure_data);

            return $this->response->setJSON(array(
                'success' => false,
                'message' => app_lang('licitaia_ai_request_failed'),
                'debug' => array(
                    'provider' => get_array_value($settings, 'ai_provider', 'openai'),
                    'model' => get_array_value($settings, 'ai_model', 'gpt-4o-mini'),
                    'error_detail' => get_array_value($api_result, 'error_detail', ''),
                    'http_code' => get_array_value($api_result, 'http_code', 0),
                    'request_debug' => get_array_value($api_result, 'request_debug', ''),
                ),
            ));
        }

        $parsed = $this->parseAiResponse($api_result['text']);
        if (!$parsed['success']) {
            $this->aiLogModel()->log_request(array(
                'opportunity_id' => (int) $opportunity->id,
                'provider' => get_array_value($settings, 'ai_provider', 'openai'),
                'model_name' => get_array_value($settings, 'ai_model', 'gpt-4o-mini'),
                'request_type' => $force ? 'reanalyze' : 'analysis',
                'request_json' => $request_payload,
                'response_json' => $api_result,
                'status' => 'failed',
                'error_message' => $parsed['message'],
                'created_by' => $this->login_user->id,
            ));

            return $this->response->setJSON(array(
                'success' => false,
                'message' => app_lang('licitaia_ai_invalid_response'),
            ));
        }

        $normalized = $this->normalizeAnalysisPayload($parsed['data']);
        $normalized['ai_status'] = 'completed';
        $normalized['ai_analyzed_at'] = get_my_local_time();
        $normalized['ai_result_json'] = array(
            'prompt' => $request_payload,
            'response' => $parsed['data'],
            'documents' => $documents_payload,
            'log_id' => $log_id,
        );

        $save_ok = $this->opportunities_model->update_ai_result((int) $opportunity->id, $normalized);
        if (!$save_ok) {
            return $this->response->setJSON(array(
                'success' => false,
                'message' => app_lang('error_occurred'),
            ));
        }

        $usage = get_array_value($api_result, 'usage', array());
        $this->aiLogModel()->log_request(array(
            'opportunity_id' => (int) $opportunity->id,
            'provider' => get_array_value($settings, 'ai_provider', 'openai'),
            'model_name' => get_array_value($settings, 'ai_model', 'gpt-4o-mini'),
            'request_type' => $force ? 'reanalyze' : 'analysis',
            'request_json' => $request_payload,
            'response_json' => $api_result,
            'status' => 'completed',
            'tokens_input' => (int) get_array_value($usage, 'prompt_tokens', get_array_value($usage, 'input_tokens', 0)),
            'tokens_output' => (int) get_array_value($usage, 'completion_tokens', get_array_value($usage, 'output_tokens', 0)),
            'created_by' => $this->login_user->id,
        ));

        return $this->response->setJSON(array(
            'success' => true,
            'message' => app_lang('licitaia_ai_analysis_completed'),
        ));
    }

    private function buildAnalysisPrompt($opportunity, array $documents)
    {
        $payload = array(
            'opportunity' => array(
                'title' => $opportunity->title,
                'public_body' => $opportunity->public_body,
                'edital_number' => $opportunity->edital_number,
                'process_number' => $opportunity->process_number,
                'modality' => $opportunity->modality,
                'object' => $opportunity->object,
                'city' => $opportunity->city,
                'state' => $opportunity->state,
                'estimated_value' => $opportunity->estimated_value,
                'submission_deadline' => $opportunity->submission_deadline,
            ),
            'documents' => $this->truncateTextForAi(implode("\n\n", $documents), 35000),
            'required_output' => array(
                'summary' => '',
                'object' => '',
                'technical_requirements' => array(),
                'habilitation_requirements' => array(),
                'documents_required' => array(),
                'deadlines' => array(),
                'risks' => array(),
                'restrictive_clauses' => array(),
                'financial_points' => array(),
                'operational_points' => array(),
                'technical_score' => 0,
                'risk_level' => 'baixo|medio|alto',
                'recommendation' => 'participar|analisar_melhor|nao_participar',
                'recommendation_text' => '',
            ),
        );

        $system = "Voce e um analista de licitacoes. Responda exclusivamente em JSON valido, sem markdown, sem texto extra.";
        $user = "Analise o edital e os documentos vinculados.\n\nRetorne exatamente no formato:\n" . json_encode($payload['required_output'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n\nContexto da oportunidade:\n" . json_encode($payload['opportunity'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n\nTextos dos documentos:\n" . $payload['documents'];

        return array(
            'system' => $system,
            'user' => $user,
        );
    }

    private function sanitizeTextForAi($text)
    {
        $text = trim((string) $text);
        if ($text === '') {
            return '';
        }

        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', ' ', $text);
        $text = preg_replace('/\s+/u', ' ', $text);
        $text = trim((string) $text);

        if ($text === '' || stripos($text, '%PDF-') !== false) {
            return '';
        }

        $sample = substr($text, 0, 1000);
        $printable_count = preg_match_all('/[\x20-\x7EÀ-ÿ]/u', $sample, $matches);
        $sample_length = max(1, strlen($sample));
        $ratio = $printable_count / $sample_length;
        if ($ratio < 0.75) {
            return '';
        }

        return $text;
    }

    private function truncateTextForAi($text, $max_length)
    {
        $text = (string) $text;
        $max_length = max(0, (int) $max_length);
        if ($max_length <= 0 || strlen($text) <= $max_length) {
            return $text;
        }

        return substr($text, 0, $max_length) . "\n\n[Texto truncado para envio à IA]";
    }

    private function documentsModel()
    {
        return model(\LicitaIA\Models\Document_model::class);
    }

    private function aiLogModel()
    {
        return model(\LicitaIA\Models\Ai_log_model::class);
    }

    private function prepareDocumentsForAnalysis($opportunity_id, array $documents)
    {
        $documents_payload = array();
        $prompt_documents = array();
        $debug = array(
            'total_documents' => count($documents),
            'documents_with_text' => 0,
            'documents_extracted_now' => 0,
            'documents_without_text' => 0,
        );

        foreach ($documents as $document) {
            $document_id = (int) ($document->id ?? 0);
            $file_label = trim((string) ($document->original_file_name ?: $document->file_name ?: ('#' . $document_id)));
            $text = $this->sanitizeTextForAi($document->extracted_text ?? '');

            if ($text === '') {
                $extraction = $this->extractDocumentTextForAnalysis($opportunity_id, $document);
                if (!empty($extraction['success']) && trim((string) get_array_value($extraction, 'text', '')) !== '') {
                    $text = $this->sanitizeTextForAi($extraction['text']);
                    if ($text !== '') {
                        $this->documentsModel()->save_extracted_text($document_id, $text);
                        $debug['documents_extracted_now']++;
                    }
                } elseif (!empty($extraction['needs_ocr'])) {
                    $document_update = array(
                        'status' => 'pending_extraction',
                        'updated_at' => get_my_local_time(),
                    );
                    $this->documentsModel()->ci_save($document_update, $document_id);
                }
            }

            if ($text !== '') {
                $text = $this->sanitizeTextForAi($text);
                if ($text === '') {
                    $debug['documents_without_text']++;
                    continue;
                }

                $text = $this->truncateTextForAi($text, 12000);
                $debug['documents_with_text']++;
                $documents_payload[] = array(
                    'id' => $document_id,
                    'file_name' => $file_label,
                    'status' => $document->status ?? '',
                    'extracted_text' => $text,
                );
                $prompt_documents[] = "Documento #" . $document_id . " (" . $file_label . "):\n" . $text;
            } else {
                $debug['documents_without_text']++;
            }
        }

        return array(
            'documents_payload' => $documents_payload,
            'prompt_documents' => $prompt_documents,
            'debug' => $debug,
        );
    }

    private function extractDocumentTextForAnalysis($opportunity_id, $document)
    {
        $local_path = $this->getDocumentAbsolutePath($opportunity_id, $document);
        if ($local_path && is_file($local_path)) {
            $extension = strtolower((string) pathinfo($local_path, PATHINFO_EXTENSION));
            $extract_result = $this->documentExtractor()->extract_text($local_path, $extension);
            return is_array($extract_result) ? $extract_result : array(
                'success' => false,
                'text' => '',
                'message' => 'Falha ao extrair texto do arquivo local.',
                'needs_ocr' => false,
            );
        }

        $source_url = trim((string) ($document->source_url ?? ''));
        if ($source_url !== '') {
            return $this->extractTextFromSourceUrl($source_url);
        }

        return array(
            'success' => false,
            'text' => '',
            'message' => 'Documento sem origem local ou remota.',
            'needs_ocr' => false,
        );
    }

    private function getDocumentAbsolutePath($opportunity_id, $document)
    {
        $opportunity_id = (int) $opportunity_id;
        $file_path = trim((string) ($document->file_path ?? ''));
        if ($opportunity_id <= 0 || $file_path === '') {
            return '';
        }

        return rtrim(WRITEPATH, '/\\') . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'licitaia' . DIRECTORY_SEPARATOR . 'documents' . DIRECTORY_SEPARATOR . 'opportunity_' . $opportunity_id . DIRECTORY_SEPARATOR . $file_path;
    }

    private function extractTextFromSourceUrl($source_url)
    {
        $source_url = trim((string) $source_url);
        if ($source_url === '' || !function_exists('curl_init')) {
            return array(
                'success' => false,
                'text' => '',
                'message' => 'Fonte remota indisponivel.',
                'needs_ocr' => false,
            );
        }

        $temp_file = tempnam(sys_get_temp_dir(), 'licitaia_src_');
        if (!$temp_file) {
            return array(
                'success' => false,
                'text' => '',
                'message' => 'Nao foi possivel criar arquivo temporario.',
                'needs_ocr' => false,
            );
        }

        $downloaded = $this->downloadRemoteDocument($source_url, $temp_file);
        if (!$downloaded) {
            @unlink($temp_file);
            return array(
                'success' => false,
                'text' => '',
                'message' => 'Nao foi possivel baixar o documento remoto.',
                'needs_ocr' => false,
            );
        }

        $extension = strtolower((string) pathinfo(parse_url($source_url, PHP_URL_PATH) ?: $source_url, PATHINFO_EXTENSION));
        if ($extension === '') {
            $extension = 'pdf';
        }

        $extract_result = $this->documentExtractor()->extract_text($temp_file, $extension);
        @unlink($temp_file);

        return is_array($extract_result) ? $extract_result : array(
            'success' => false,
            'text' => '',
            'message' => 'Falha na extracao do documento remoto.',
            'needs_ocr' => false,
        );
    }

    private function downloadRemoteDocument($url, $destination)
    {
        $url = trim((string) $url);
        $destination = trim((string) $destination);
        if ($url === '' || $destination === '' || !function_exists('curl_init')) {
            return false;
        }

        $fp = @fopen($destination, 'w+b');
        if (!$fp) {
            return false;
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, array(
            CURLOPT_FILE => $fp,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CONNECTTIMEOUT => 20,
            CURLOPT_TIMEOUT => 120,
            CURLOPT_HTTPHEADER => array(
                'Accept: application/pdf,application/octet-stream,*/*',
                'Accept-Language: pt-BR,pt;q=0.9,en;q=0.8',
                'Cache-Control: no-cache',
                'Pragma: no-cache',
                'Referer: https://pncp.gov.br/',
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36',
            ),
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36',
        ));

        $result = curl_exec($ch);
        $http_code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        fclose($fp);

        if ($result === false || $http_code >= 400 || !is_file($destination) || filesize($destination) === 0) {
            @unlink($destination);
            return false;
        }

        return true;
    }

    private function documentExtractor()
    {
        return new \LicitaIA\Libraries\Document_extractor();
    }

    private function callAiProvider(array $settings, array $prompt)
    {
        $provider = strtolower(trim((string) get_array_value($settings, 'ai_provider', 'openai')));
        $base_url = trim((string) get_array_value($settings, 'ai_api_base_url', ''));
        $api_key = trim((string) get_array_value($settings, 'ai_api_key', ''));
        $model = trim((string) get_array_value($settings, 'ai_model', 'gpt-4o-mini'));

        if ($provider === 'openrouter' && $base_url === '') {
            $base_url = 'https://openrouter.ai/api/v1';
        }

        if ($base_url === '' || !function_exists('curl_init')) {
            return array(
                'success' => false,
                'message' => 'AI configuration missing.',
            );
        }

        if ($provider !== 'local' && $api_key === '') {
            return array(
                'success' => false,
                'message' => 'AI configuration missing.',
            );
        }

        $endpoint = rtrim($base_url, '/') . '/chat/completions';
        $body = array(
            'model' => $model,
            'temperature' => 0.1,
            'messages' => array(
                array('role' => 'system', 'content' => $prompt['system']),
                array('role' => 'user', 'content' => $prompt['user']),
            ),
        );

        if ($provider !== 'openrouter') {
            $body['response_format'] = array('type' => 'json_object');
        }

        $headers = array('Content-Type: application/json');
        if ($provider === 'azure_openai') {
            $headers[] = 'api-key: ' . $api_key;
        } elseif ($provider === 'openrouter') {
            $headers[] = 'Authorization: Bearer ' . $api_key;
            $headers[] = 'HTTP-Referer: ' . base_url();
            $headers[] = 'X-Title: ' . app_lang('licitaia_menu');
        } elseif ($api_key !== '') {
            $headers[] = 'Authorization: Bearer ' . $api_key;
        }

        $request_body = json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $request_debug = $this->buildAiRequestDebug($endpoint, $headers, $request_body);

        $ch = curl_init($endpoint);
        curl_setopt_array($ch, array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POSTFIELDS => $request_body,
            CURLOPT_TIMEOUT => 120,
            CURLOPT_CONNECTTIMEOUT => 20,
        ));

        $response = curl_exec($ch);
        $curl_error = curl_error($ch);
        $status_code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false || $curl_error !== '') {
            return array(
                'success' => false,
                'message' => 'AI request failed.',
                'error_detail' => $curl_error !== '' ? $curl_error : 'Empty response from provider.',
                'http_code' => $status_code,
                'request_debug' => $request_debug,
            );
        }

        $decoded = json_decode($response, true);
        if (!is_array($decoded)) {
            return array(
                'success' => false,
                'message' => 'AI response could not be parsed.',
                'error_detail' => 'Provider returned non-JSON payload.',
                'http_code' => $status_code,
                'request_debug' => $request_debug,
            );
        }

        $text = '';
        if (!empty($decoded['choices'][0]['message']['content'])) {
            $text = (string) $decoded['choices'][0]['message']['content'];
        } elseif (!empty($decoded['output_text'])) {
            $text = (string) $decoded['output_text'];
        }

        if ($status_code >= 400 || trim((string) $text) === '') {
            return array(
                'success' => false,
                'message' => 'AI request failed.',
                'error_detail' => 'HTTP ' . $status_code . ' returned by provider.',
                'http_code' => $status_code,
                'request_debug' => $request_debug,
            );
        }

        return array(
            'success' => true,
            'text' => (string) $text,
            'raw' => $decoded,
            'request_debug' => $request_debug,
        );
    }

    private function buildAiRequestDebug($endpoint, array $headers, $body)
    {
        $debug = array(
            'endpoint' => $endpoint,
            'headers' => $headers,
            'body' => $body,
        );

        return json_encode($debug, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function parseAiResponse($text)
    {
        $text = trim((string) $text);
        if ($text === '') {
            return array('success' => false, 'message' => 'Empty response.', 'data' => array());
        }

        $decoded = json_decode($text, true);
        if (!is_array($decoded)) {
            $start = strpos($text, '{');
            $end = strrpos($text, '}');
            if ($start !== false && $end !== false && $end > $start) {
                $decoded = json_decode(substr($text, $start, $end - $start + 1), true);
            }
        }

        if (!is_array($decoded)) {
            return array('success' => false, 'message' => 'Invalid JSON.', 'data' => array());
        }

        return array('success' => true, 'message' => '', 'data' => $decoded);
    }

    private function normalizeAnalysisPayload(array $data)
    {
        $summary = trim((string) get_array_value($data, 'summary', ''));
        $object = trim((string) get_array_value($data, 'object', ''));
        $technical_requirements = $this->normalizeList(get_array_value($data, 'technical_requirements', array()));
        $habilitation_requirements = $this->normalizeList(get_array_value($data, 'habilitation_requirements', array()));
        $documents_required = $this->normalizeList(get_array_value($data, 'documents_required', array()));
        $deadlines = $this->normalizeList(get_array_value($data, 'deadlines', array()));
        $risks = $this->normalizeList(get_array_value($data, 'risks', array()));
        $restrictive_clauses = $this->normalizeList(get_array_value($data, 'restrictive_clauses', array()));
        $financial_points = $this->normalizeList(get_array_value($data, 'financial_points', array()));
        $operational_points = $this->normalizeList(get_array_value($data, 'operational_points', array()));
        $technical_score = (float) get_array_value($data, 'technical_score', 0);
        $risk_level = strtolower(trim((string) get_array_value($data, 'risk_level', '')));
        $recommendation = strtolower(trim((string) get_array_value($data, 'recommendation', '')));
        $recommendation_text = trim((string) get_array_value($data, 'recommendation_text', ''));

        if (!in_array($risk_level, array('baixo', 'medio', 'alto'), true)) {
            $risk_level = 'medio';
        }

        if (!in_array($recommendation, array('participar', 'analisar_melhor', 'nao_participar'), true)) {
            $recommendation = 'analisar_melhor';
        }

        return array(
            'ai_summary' => $summary ?: $object,
            'ai_risks' => $risks,
            'ai_requirements' => array(
                'technical_requirements' => $technical_requirements,
                'habilitation_requirements' => $habilitation_requirements,
                'documents_required' => $documents_required,
                'deadlines' => $deadlines,
                'financial_points' => $financial_points,
                'operational_points' => $operational_points,
                'restrictive_clauses' => $restrictive_clauses,
            ),
            'ai_recommendation' => $recommendation_text,
            'technical_score' => $technical_score,
            'risk_level' => $risk_level,
            'recommendation' => $recommendation,
        );
    }

    private function normalizeList($value)
    {
        if (!is_array($value)) {
            if (is_string($value) && trim($value) !== '') {
                return array(trim($value));
            }

            return array();
        }

        $items = array();
        foreach ($value as $entry) {
            if (is_array($entry)) {
                $entry = json_encode($entry, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }

            $entry = trim((string) $entry);
            if ($entry !== '') {
                $items[] = $entry;
            }
        }

        return $items;
    }
}
