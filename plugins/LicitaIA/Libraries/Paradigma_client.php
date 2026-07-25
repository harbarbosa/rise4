<?php

namespace LicitaIA\Libraries;

use LicitaIA\Models\Keyword_model;
use LicitaIA\Models\Opportunity_model;
use LicitaIA\Models\Search_log_model;
use LicitaIA\Models\Source_model;

class Paradigma_client
{
    private string $defaultBaseUrl = 'https://scr360.paradigmabs.com.br/sescsp/portal/WebService/Servicos.asmx';
    private string $portalBaseUrl = 'https://scr360.paradigmabs.com.br/sescsp/portal/';
    private ?string $cookieJarPath = null;
    private bool $sessionWarm = false;
    private Source_model $sources_model;
    private Keyword_model $keywords_model;
    private Opportunity_model $opportunities_model;
    private Search_log_model $search_log_model;

    public function __construct()
    {
        $this->sources_model = model(Source_model::class);
        $this->keywords_model = model(Keyword_model::class);
        $this->opportunities_model = model(Opportunity_model::class);
        $this->search_log_model = model(Search_log_model::class);
    }

    public function search($params = array())
    {
        $params = is_array($params) ? $params : array();
        $source = $this->resolveSource($params);
        $started_at = get_my_local_time();

        $include_keywords = $this->keywords_model->get_active_include_keywords();
        $exclude_keywords = $this->keywords_model->get_active_exclude_keywords();
        $keyword_filter = trim((string) get_array_value($params, 'keyword'));
        $page = max(1, (int) get_array_value($params, 'page', 1));
        $max_pages = max(1, (int) get_array_value($params, 'max_pages', 5));
        $page_size = max(10, min(100, (int) get_array_value($params, 'page_size', 50)));
        $state = strtoupper(trim((string) get_array_value($params, 'state')));
        $date_from = $this->normalizeDateForDisplay(get_array_value($params, 'date_from'));
        $date_to = $this->normalizeDateForDisplay(get_array_value($params, 'date_to'));
        $query_term = $this->resolveQueryTerm($keyword_filter, $include_keywords);
        $base_url = trim((string) get_array_value((array) $source, 'base_url', $this->defaultBaseUrl));
        $search_endpoint = trim((string) get_array_value((array) $source, 'api_endpoint', ''));
        if ($search_endpoint === '') {
            $search_endpoint = '/PesquisarProcessos';
        }
        $request_url = $base_url . $search_endpoint;
        $this->warmSession();

        $results = array();
        $seen = array();
        $debug_urls = array();
        $summary = array(
            'total' => 0,
            'matched' => 0,
            'imported' => 0,
            'ignored' => 0,
            'pages' => 0,
        );

        try {
            for ($current_page = $page; $current_page < ($page + $max_pages); $current_page++) {
                $request_payload = $this->buildSearchPayload($query_term, $current_page, $page_size, $date_from, $date_to, $state, $source);
                $payload = $this->requestJson($request_url, $request_payload);
                $debug_urls[] = $request_url;
                $items = $this->extractItems($payload);

                if (!$items) {
                    $summary['pages'] = max($summary['pages'], $current_page - $page + 1);
                    break;
                }

                $summary['total'] += count($items);
                foreach ($items as $item) {
                    $normalized = $this->normalize_result($item);
                    if (!$normalized) {
                        $summary['ignored']++;
                        continue;
                    }

                    $normalized['source_id'] = (int) get_array_value((array) $source, 'id', 0);
                    $normalized['source_type'] = 'paradigma';
                    $normalized['source_name'] = (string) get_array_value((array) $source, 'name', 'Paradigma');
                    $normalized['search_keyword'] = $keyword_filter;
                    $normalized['search_page'] = $current_page;
                    $normalized['search_page_size'] = $page_size;
                    $normalized['search_state'] = $state;

                    $normalized_date = trim((string) get_array_value($normalized, 'opening_date'));
                    if ($date_from !== '' && $normalized_date !== '') {
                        $normalized_ts = strtotime($normalized_date);
                        $date_from_ts = strtotime($date_from);
                        $date_to_ts = strtotime($date_to ?: $date_from);
                        if ($normalized_ts !== false && $date_from_ts !== false && $date_to_ts !== false) {
                            if ($normalized_ts < $date_from_ts || $normalized_ts > $date_to_ts) {
                                $summary['ignored']++;
                                continue;
                            }
                        }
                    }

                    $match_data = $this->matchKeywords($normalized, $keyword_filter, $include_keywords, $exclude_keywords, $keyword_filter !== '');
                    if (!$match_data['should_import']) {
                        $summary['ignored']++;
                        continue;
                    }

                    $duplicate = $this->opportunities_model->find_duplicate(
                        $normalized['notice_number'],
                        $normalized['public_agency'],
                        $normalized['source_url']
                    );
                    if ($duplicate) {
                        $normalized['imported'] = true;
                        $normalized['duplicate'] = true;
                        $normalized['existing_opportunity_id'] = (int) $duplicate->id;
                    }

                    $normalized['matched_keywords'] = $match_data['matched_keywords'];
                    $normalized['excluded_keywords'] = $match_data['excluded_keywords'];
                    $normalized['import_key'] = $this->buildImportKey($normalized);

                    $raw_key = $normalized['import_key'];
                    if (isset($seen[$raw_key])) {
                        continue;
                    }
                    $seen[$raw_key] = true;

                    $results[] = $normalized;
                    $summary['matched']++;
                    if (!empty($normalized['imported'])) {
                        $summary['imported']++;
                    }
                }

                $summary['pages'] = max($summary['pages'], $current_page - $page + 1);
                if (count($items) < $page_size) {
                    break;
                }
            }

            $this->search_log_model->log_search(array(
                'source_id' => (int) get_array_value((array) $source, 'id', 0),
                'query_text' => $keyword_filter,
                'filters_json' => $this->buildFiltersPayload($params, $source),
                'results_count' => count($results),
                'status' => 'completed',
                'response_json' => array(
                    'summary' => $summary,
                    'source' => array(
                        'id' => (int) get_array_value((array) $source, 'id', 0),
                        'name' => (string) get_array_value((array) $source, 'name', 'Paradigma'),
                        'type' => (string) get_array_value((array) $source, 'source_type', 'paradigma'),
                    ),
                    'provider' => 'paradigma',
                    'request_url' => $request_url,
                ),
                'started_at' => $started_at,
                'finished_at' => get_my_local_time(),
            ));

            return array(
                'success' => true,
                'source' => $source,
                'results' => $results,
                'summary' => $summary,
                'debug_urls' => $debug_urls,
                'message' => $this->buildSearchMessage($results, $include_keywords),
            );
        } catch (\Throwable $e) {
            log_message('error', '[LicitaIA][Paradigma] Search error: ' . $e->getMessage());

            $this->search_log_model->log_search(array(
                'source_id' => (int) get_array_value((array) $source, 'id', 0),
                'query_text' => $keyword_filter,
                'filters_json' => $this->buildFiltersPayload($params, $source),
                'results_count' => 0,
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'started_at' => $started_at,
                'finished_at' => get_my_local_time(),
            ));

            return array(
                'success' => false,
                'source' => $source,
                'results' => array(),
                'summary' => array(
                    'total' => 0,
                    'matched' => 0,
                    'imported' => 0,
                    'ignored' => 0,
                    'pages' => 0,
                ),
                'debug_urls' => $debug_urls,
                'error_detail' => $e->getMessage(),
                'message' => app_lang('licitaia_search_failed'),
                'provider' => 'paradigma',
                'request_url' => $request_url,
            );
        }
    }

    public function get_documents($process)
    {
        $process = is_array($process) ? $process : (array) $process;
        $documents = array();

        foreach (array('lstAnexos', 'lstArquivos', 'lstDocumentos', 'arquivos', 'anexos', 'documentos') as $key) {
            $items = get_array_value($process, $key, array());
            if (!is_array($items) || empty($items)) {
                continue;
            }

            foreach ($items as $item) {
                $item = is_array($item) ? $item : (array) $item;
                $url = trim((string) get_array_value($item, 'sDsLink', get_array_value($item, 'url', get_array_value($item, 'link', ''))));
                if ($url === '') {
                    continue;
                }

                $title = trim((string) get_array_value($item, 'sDsTitulo', get_array_value($item, 'titulo', get_array_value($item, 'title', 'Documento Paradigma'))));
                $documents[] = array(
                    'title' => $title,
                    'file_url' => $url,
                    'source_url' => $url,
                    'document_type_id' => 0,
                    'raw' => $item,
                );
            }
        }

        return $documents;
    }

    public function normalize_result($result)
    {
        $result = is_array($result) ? $result : (array) $result;
        if (!$result) {
            return null;
        }

        $process_id = trim((string) get_array_value($result, 'nCdProcesso', get_array_value($result, 'nCdEdital', '')));
        $notice_number = trim((string) get_array_value($result, 'sNrEdital', get_array_value($result, 'sNrProcessoDisplay', get_array_value($result, 'sNrProcesso', $process_id))));
        $process_number = trim((string) get_array_value($result, 'sNrProcessoDisplay', get_array_value($result, 'sNrProcesso', $process_id)));
        $public_agency = trim((string) get_array_value($result, 'sNmEmpresa', get_array_value($result, 'sNmEntidade', 'Paradigma')));
        $object_description = trim((string) get_array_value($result, 'sDsObjeto', get_array_value($result, 'sDsTitulo', '')));
        $modality = trim((string) get_array_value($result, 'sNmModalidade', get_array_value($result, 'sNmModalidadeTipo', '')));
        $state = strtoupper(trim((string) get_array_value($result, 'sSgUf', get_array_value($result, 'uf', ''))));
        $city = trim((string) get_array_value($result, 'sNmCidade', get_array_value($result, 'cidade', '')));
        $source_url = trim((string) get_array_value($result, 'sDsLink', ''));
        $opening_date = $this->normalizeDateValue(get_array_value($result, 'tDtAberturaSessao', get_array_value($result, 'tDtInicial', '')));
        $submission_deadline = $this->normalizeDateValue(get_array_value($result, 'tDtFinal', get_array_value($result, 'tDtEncerrado', '')));
        $estimated_value = $this->normalizeMoney(get_array_value($result, 'dVlEstimado', get_array_value($result, 'dVlTotal', 0)));

        $title = trim((string) get_array_value($result, 'sDsTitulo', ''));
        if ($title === '') {
            $title = $object_description !== '' ? $object_description : ($notice_number !== '' ? $notice_number : 'Edital Paradigma');
        }

        return array(
            'purchase_id' => $process_id,
            'title' => $title,
            'public_agency' => $public_agency,
            'notice_number' => $notice_number,
            'process_number' => $process_number,
            'modality' => $modality,
            'object_description' => $object_description,
            'city' => $city,
            'state' => $state,
            'estimated_value' => $estimated_value,
            'opening_date' => $opening_date,
            'submission_deadline' => $submission_deadline,
            'source_url' => $source_url !== '' ? $source_url : $this->defaultBaseUrl,
            'keyword_text' => trim(implode(' ', array_filter(array(
                $title,
                $object_description,
                $public_agency,
                $notice_number,
                $process_number,
                $modality,
            )))),
            'raw' => $result,
        );
    }

    public function import_opportunity($data)
    {
        $data = is_array($data) ? $data : (array) $data;
        $normalized = isset($data['title']) && isset($data['notice_number']) ? $data : $this->normalize_result($data);
        if (!$normalized) {
            return array('success' => false, 'message' => app_lang('error_occurred'));
        }

        $duplicate = $this->opportunities_model->find_duplicate(
            $normalized['notice_number'],
            $normalized['public_agency'],
            $normalized['source_url']
        );
        if ($duplicate) {
            return array(
                'success' => true,
                'imported' => false,
                'duplicate' => true,
                'id' => (int) $duplicate->id,
                'message' => app_lang('record_saved'),
            );
        }

        $now = get_my_local_time();
        $payload = array(
            'title' => $normalized['title'],
            'description' => $normalized['object_description'],
            'public_agency' => $normalized['public_agency'],
            'public_body' => $normalized['public_agency'],
            'edital_number' => $normalized['notice_number'],
            'notice_number' => $normalized['notice_number'],
            'process_number' => $normalized['process_number'],
            'modality' => $normalized['modality'],
            'object' => $normalized['object_description'],
            'object_description' => $normalized['object_description'],
            'city' => $normalized['city'],
            'state' => $normalized['state'],
            'estimated_value' => (float) $normalized['estimated_value'],
            'opening_date' => $normalized['opening_date'],
            'submission_deadline' => $normalized['submission_deadline'],
            'document_url' => $normalized['source_url'],
            'original_link' => $normalized['source_url'],
            'source_url' => $normalized['source_url'],
            'source_id' => (int) get_array_value($normalized, 'source_id', 0) ?: null,
            'status' => 'new',
            'ai_status' => 'pending',
            'created_by' => $this->currentUserId(),
            'created_at' => $now,
            'updated_at' => $now,
        );

        $save_id = $this->opportunities_model->ci_save($payload, 0);
        if (!$save_id) {
            return array('success' => false, 'message' => app_lang('error_occurred'));
        }

        $documents_result = $this->importOpportunityDocuments((int) $save_id, $normalized);

        return array(
            'success' => true,
            'imported' => true,
            'duplicate' => false,
            'id' => $save_id,
            'documents_imported' => (int) get_array_value($documents_result, 'imported', 0),
            'documents_created' => (array) get_array_value($documents_result, 'documents', array()),
            'message' => app_lang('record_saved'),
        );
    }

    private function importOpportunityDocuments($opportunity_id, array $normalized)
    {
        $documents = $this->get_documents($normalized);
        $created = array();
        $imported = 0;

        foreach ($documents as $document) {
            $result = $this->storeImportedDocument($opportunity_id, $normalized, (array) $document);
            if (!empty($result['document_id'])) {
                $created[] = $result;
                $imported++;
            }
        }

        return array('imported' => $imported, 'documents' => $created);
    }

    private function storeImportedDocument($opportunity_id, array $normalized, array $document)
    {
        $source_url = trim((string) get_array_value($document, 'file_url', get_array_value($document, 'source_url', '')));
        $title = trim((string) get_array_value($document, 'title', 'Documento Paradigma'));
        $extension = strtolower((string) pathinfo(parse_url($source_url, PHP_URL_PATH) ?: $source_url, PATHINFO_EXTENSION));
        $allowed_extensions = array('pdf', 'doc', 'docx', 'xls', 'xlsx', 'png', 'jpg', 'jpeg', 'gif', 'bmp', 'webp');
        $can_download = $source_url !== '' && in_array($extension, $allowed_extensions, true);

        $storage_dir = $this->buildImportedDocumentStorageDir($opportunity_id);
        $file_name = $this->normalizeImportedDocumentFileName($title, $source_url, $extension);
        $absolute_path = $storage_dir . $file_name;
        $file_saved = false;

        if ($can_download) {
            $file_saved = $this->downloadRemoteFile($source_url, $absolute_path);
        }

        $document_data = array(
            'opportunity_id' => $opportunity_id,
            'file_name' => $file_name,
            'original_file_name' => $title . ($extension !== '' ? '.' . $extension : ''),
            'file_path' => $file_saved ? $file_name : null,
            'mime_type' => $this->guessMimeType($extension),
            'file_size' => $file_saved && is_file($absolute_path) ? (int) filesize($absolute_path) : 0,
            'source_url' => $source_url,
            'extracted_text' => null,
            'status' => $file_saved ? 'uploaded' : 'external_link',
            'created_by' => $this->currentUserId(),
            'created_at' => get_my_local_time(),
            'updated_at' => get_my_local_time(),
            'deleted' => 0,
        );

        $document_id = model(\LicitaIA\Models\Document_model::class)->ci_save($document_data, 0);
        if (!$document_id) {
            return array('document_id' => 0, 'message' => app_lang('error_occurred'));
        }

        if ($file_saved && is_file($absolute_path)) {
            $extractor = new Document_extractor();
            $extract_result = $extractor->extract_text($absolute_path, $extension);
            if (!empty($extract_result['success']) && trim((string) get_array_value($extract_result, 'text', '')) !== '') {
                model(\LicitaIA\Models\Document_model::class)->save_extracted_text($document_id, $extract_result['text']);
            }
        }

        return array(
            'document_id' => (int) $document_id,
            'title' => $title,
            'source_url' => $source_url,
            'saved_file' => $file_saved,
        );
    }

    private function buildSearchPayload($keyword, $page, $page_size, $date_from, $date_to, $state, array $source = array())
    {
        return $this->buildSearchRequestBody($keyword, $page, $page_size, $date_from, $date_to, $state, $source);
    }

    private function buildSearchRequestBody($keyword, $page, $page_size, $date_from, $date_to, $state, array $source = array())
    {
        $source = $this->normalizeSourceRow($source ?: array());
        $keyword = trim((string) $keyword);
        $query_term = $keyword !== '' ? $keyword : '';

        $dto_processo = array(
            'nAnoFinalizacao' => 0,
            'tmpTipoMuralProcesso' => 2,
            'nCdModulo' => (int) get_array_value($source, 'default_module_id', 0),
            'nCdModalidade' => (int) get_array_value($source, 'default_modality_id', 0),
            'nCdModalidadeFase' => 0,
            'nCdTipoModalidade' => (int) get_array_value($source, 'default_modality_type_id', 0),
            'tmpTipoMuralVisao' => 0,
            'nCdSituacao' => 0,
            'nCdTipoProcesso' => 0,
            'nCdEmpresa' => (int) get_array_value($source, 'default_company_id', 2),
            'nCdEntidade' => (int) get_array_value($source, 'default_company_id', 2),
            'sNrProcesso' => '',
            'nCdProcesso' => 0,
            'sDsObjeto' => $query_term,
            'sDtPeriodoDe' => $date_from,
            'sDtPeriodoAte' => $date_to,
            'sOrdenarPor' => 'NCDPROCESSO',
            'sOrdenarPorDirecao' => 'DESC',
            'dtoPaginacao' => array(
                'nPaginaDe' => max(1, (int) $page),
                'nPaginaAte' => max(1, (int) $page_size),
            ),
            'dtoIdioma' => array(
                'nCdIdioma' => 1,
            ),
        );

        $json = json_encode($dto_processo, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (!is_string($json) || $json === '') {
            throw new \RuntimeException('Could not encode Paradigma request payload.');
        }

        return '{dtoProcesso:' . $json . '}';
    }

    private function requestJson($url, string $body = '')
    {
        if (!function_exists('curl_init')) {
            throw new \RuntimeException('cURL extension not available.');
        }

        $this->warmSession();
        $ch = curl_init($url);
        curl_setopt_array($ch, array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json; charset=utf-8',
                'Accept: application/json, text/plain, */*',
                'Origin: https://scr360.paradigmabs.com.br',
                'Referer: https://scr360.paradigmabs.com.br/',
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36',
            ),
            CURLOPT_TIMEOUT => 120,
            CURLOPT_CONNECTTIMEOUT => 20,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_COOKIEJAR => $this->getCookieJarPath(),
            CURLOPT_COOKIEFILE => $this->getCookieJarPath(),
        ));

        $response = curl_exec($ch);
        $error = curl_error($ch);
        $http_code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false || $error !== '') {
            throw new \RuntimeException($error ?: 'Request failed.');
        }

        if ($http_code >= 400) {
            $snippet = trim((string) $response);
            if ($snippet !== '') {
                $snippet = preg_replace('/\s+/', ' ', $snippet);
                $snippet = mb_substr($snippet, 0, 500);
            }

            throw new \RuntimeException('HTTP ' . $http_code . ' returned by provider.' . ($snippet !== '' ? ' | ' . $snippet : ''));
        }

        $decoded = json_decode($response, true);
        if (!is_array($decoded)) {
            throw new \RuntimeException('Invalid JSON response.');
        }

        return $decoded;
    }

    private function extractItems(array $payload)
    {
        if (isset($payload['d']) && is_array($payload['d'])) {
            return $payload['d'];
        }

        if (isset($payload['d']) && is_object($payload['d'])) {
            return (array) $payload['d'];
        }

        return is_array($payload) ? $payload : array();
    }

    private function matchKeywords(array $normalized, $optional_keyword, array $include_keywords, array $exclude_keywords, $enforce_include = true)
    {
        $text = $this->normalizeSearchText(get_array_value($normalized, 'keyword_text'));
        $optional_keyword = $this->normalizeSearchText($optional_keyword);

        $matched_include = array();
        foreach ($include_keywords as $keyword) {
            $normalized_keyword = $this->normalizeSearchText($keyword);
            if ($normalized_keyword !== '' && strpos($text, $normalized_keyword) !== false) {
                $matched_include[] = trim((string) $keyword);
            }
        }

        $matched_exclude = array();
        foreach ($exclude_keywords as $keyword) {
            $normalized_keyword = $this->normalizeSearchText($keyword);
            if ($normalized_keyword !== '' && strpos($text, $normalized_keyword) !== false) {
                $matched_exclude[] = trim((string) $keyword);
            }
        }

        if ($optional_keyword !== '' && strpos($text, $optional_keyword) === false) {
            return array(
                'should_import' => false,
                'matched_keywords' => $matched_include,
                'excluded_keywords' => $matched_exclude,
            );
        }

        if (!$include_keywords || !$enforce_include) {
            return array(
                'should_import' => true,
                'matched_keywords' => array(),
                'excluded_keywords' => array(),
            );
        }

        return array(
            'should_import' => (bool) $matched_include && !$matched_exclude,
            'matched_keywords' => $matched_include,
            'excluded_keywords' => $matched_exclude,
        );
    }

    private function normalizeSearchText($text)
    {
        $text = trim((string) $text);
        if ($text === '') {
            return '';
        }

        if (function_exists('transliterator_transliterate')) {
            $normalized = transliterator_transliterate('Any-Latin; Latin-ASCII; Lower()', $text);
            if (is_string($normalized) && $normalized !== '') {
                return trim($normalized);
            }
        }

        $normalized = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
        if ($normalized === false || $normalized === '') {
            $normalized = $text;
        }

        return mb_strtolower(trim((string) $normalized));
    }

    private function resolveQueryTerm($keyword_filter, array $include_keywords)
    {
        $keyword_filter = trim((string) $keyword_filter);
        if ($keyword_filter !== '') {
            return $keyword_filter;
        }

        $include_keywords = array_values(array_filter(array_map('trim', $include_keywords)));
        if ($include_keywords) {
            return implode(' ', array_slice($include_keywords, 0, 3));
        }

        return 'edital';
    }

    private function buildSearchMessage(array $results, array $include_keywords)
    {
        $count = count($results);
        if (!$count) {
            return app_lang('licitaia_empty_state');
        }

        $message = sprintf('%s: %d', app_lang('licitaia_search_results'), $count);
        if (!$include_keywords) {
            $message .= ' - ' . app_lang('licitaia_no_include_keywords');
        }

        return $message;
    }

    private function buildFiltersPayload(array $params, $source)
    {
        return array(
            'keyword' => trim((string) get_array_value($params, 'keyword')),
            'state' => trim((string) get_array_value($params, 'state')),
            'date_from' => trim((string) get_array_value($params, 'date_from')),
            'date_to' => trim((string) get_array_value($params, 'date_to')),
            'source_id' => (int) get_array_value((array) $source, 'id', 0),
            'source_name' => (string) get_array_value((array) $source, 'name', 'Paradigma'),
        );
    }

    private function resolveSource(array $params)
    {
        $source_id = (int) get_array_value($params, 'source_id');
        if ($source_id > 0) {
            $source = $this->sources_model->get_details(array('id' => $source_id))->getRowArray();
            if ($source) {
                return $this->normalizeSourceRow($source);
            }
        }

        $sources = $this->sources_model->get_details(array('source_type' => 'paradigma', 'active' => 1))->getResultArray();
        if (!empty($sources)) {
            return $this->normalizeSourceRow($sources[0]);
        }

        return array(
            'id' => 0,
            'name' => 'Paradigma',
            'source_type' => 'paradigma',
            'base_url' => $this->defaultBaseUrl,
            'api_endpoint' => '/PesquisarProcessos',
        );
    }

    private function normalizeSourceRow(array $row)
    {
        $row['base_url'] = trim((string) (($row['base_url'] ?? '') ?: $this->defaultBaseUrl));
        $row['api_endpoint'] = trim((string) (($row['api_endpoint'] ?? '') ?: '/PesquisarProcessos'));
        if (empty($row['default_company_id'])) {
            $row['default_company_id'] = 2;
        }
        if (empty($row['default_module_id'])) {
            $row['default_module_id'] = 0;
        }
        if (empty($row['default_modality_id'])) {
            $row['default_modality_id'] = 0;
        }
        if (empty($row['default_modality_type_id'])) {
            $row['default_modality_type_id'] = 0;
        }
        return $row;
    }

    private function warmSession()
    {
        if ($this->sessionWarm) {
            return;
        }

        if (!function_exists('curl_init')) {
            return;
        }

        $ch = curl_init($this->portalBaseUrl . 'Mural.aspx?nNmTela=E');
        curl_setopt_array($ch, array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_COOKIEJAR => $this->getCookieJarPath(),
            CURLOPT_COOKIEFILE => $this->getCookieJarPath(),
            CURLOPT_HTTPHEADER => array(
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language: pt-BR,pt;q=0.9,en;q=0.8',
                'Cache-Control: no-cache',
                'Pragma: no-cache',
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36',
            ),
        ));
        curl_exec($ch);
        curl_close($ch);
        $this->sessionWarm = true;
    }

    private function getCookieJarPath()
    {
        if ($this->cookieJarPath !== null) {
            return $this->cookieJarPath;
        }

        $this->cookieJarPath = tempnam(sys_get_temp_dir(), 'licitaia_par_');
        if ($this->cookieJarPath === false || $this->cookieJarPath === '') {
            $this->cookieJarPath = rtrim(sys_get_temp_dir(), '/\\') . DIRECTORY_SEPARATOR . 'licitaia_par_' . md5((string) microtime(true)) . '.txt';
        }

        return $this->cookieJarPath;
    }

    private function normalizeDateForDisplay($value)
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }

        $timestamp = strtotime($value);
        if ($timestamp !== false) {
            return date('Y-m-d', $timestamp);
        }

        if (preg_match('/\/Date\((-?\d+)\)\//', $value, $matches)) {
            $ms = (int) $matches[1];
            if ($ms > 0) {
                return date('Y-m-d', (int) floor($ms / 1000));
            }
        }

        return $value;
    }

    private function normalizeDateValue($value)
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }

        if (preg_match('/\/Date\((-?\d+)\)\//', $value, $matches)) {
            $ms = (int) $matches[1];
            if ($ms > 0) {
                return date('Y-m-d', (int) floor($ms / 1000));
            }
            return '';
        }

        $timestamp = strtotime($value);
        return $timestamp !== false ? date('Y-m-d', $timestamp) : $value;
    }

    private function normalizeMoney($value)
    {
        if (is_array($value) || is_object($value)) {
            $value = 0;
        }

        $value = str_replace(array('.', ','), array('', '.'), (string) $value);
        return (float) $value;
    }

    private function buildImportKey(array $normalized)
    {
        return sha1(trim((string) get_array_value($normalized, 'notice_number', '')) . '|' . trim((string) get_array_value($normalized, 'public_agency', '')) . '|' . trim((string) get_array_value($normalized, 'source_url', '')));
    }

    private function buildImportedDocumentStorageDir($opportunity_id)
    {
        $base_path = rtrim(WRITEPATH, '/\\') . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'licitaia' . DIRECTORY_SEPARATOR . 'documents' . DIRECTORY_SEPARATOR . 'opportunity_' . (int) $opportunity_id . DIRECTORY_SEPARATOR . 'imported' . DIRECTORY_SEPARATOR;
        if (!is_dir($base_path) && !@mkdir($base_path, 0755, true)) {
            return rtrim(WRITEPATH, '/\\') . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'licitaia' . DIRECTORY_SEPARATOR . 'documents' . DIRECTORY_SEPARATOR . 'opportunity_' . (int) $opportunity_id . DIRECTORY_SEPARATOR;
        }

        return $base_path;
    }

    private function normalizeImportedDocumentFileName($title, $url, $extension)
    {
        $title = trim((string) $title);
        $base = preg_replace('/[^A-Za-z0-9._-]+/', '-', strtolower($title));
        $base = trim((string) $base, '-_.');
        if ($base === '') {
            $base = 'documento-paradigma';
        }

        if ($extension === '') {
            $path_extension = strtolower((string) pathinfo(parse_url($url, PHP_URL_PATH) ?: $url, PATHINFO_EXTENSION));
            $extension = $path_extension;
        }

        $suffix = substr(sha1($url . '|' . microtime(true)), 0, 10);
        return $base . '-' . $suffix . ($extension !== '' ? '.' . $extension : '');
    }

    private function downloadRemoteFile($url, $destination)
    {
        $url = trim((string) $url);
        if ($url === '' || !function_exists('curl_init')) {
            return false;
        }

        $ch = curl_init($url);
        $fp = @fopen($destination, 'w+b');
        if (!$fp) {
            curl_close($ch);
            return false;
        }

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
                'Origin: https://scr360.paradigmabs.com.br',
                'Referer: https://scr360.paradigmabs.com.br/',
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

    private function guessMimeType($extension)
    {
        $map = array(
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'bmp' => 'image/bmp',
            'webp' => 'image/webp',
        );

        return get_array_value($map, strtolower(trim((string) $extension)), 'application/octet-stream');
    }

    private function currentUserId()
    {
        try {
            $users_model = model('App\\Models\\Users_model');
            return (int) $users_model->login_user_id();
        } catch (\Throwable $e) {
            return 0;
        }
    }
}
