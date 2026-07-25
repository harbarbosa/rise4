<?php

namespace LicitaIA\Libraries;

use LicitaIA\Models\Keyword_model;
use LicitaIA\Models\Opportunity_model;
use LicitaIA\Models\Search_log_model;
use LicitaIA\Models\Source_model;

class Pncp_client
{
    private string $defaultBaseUrl = 'https://pncp.gov.br/api/pncp';
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
        $page_size = max(10, min(50, (int) get_array_value($params, 'page_size', 50)));
        $modality_code = (int) get_array_value($params, 'modality_code');
        $state = strtoupper(trim((string) get_array_value($params, 'state')));
        $date_from = $this->normalizeDateForPncp(get_array_value($params, 'date_from'));
        $date_to = $this->normalizeDateForPncp(get_array_value($params, 'date_to'));
        $query_term = $this->resolveQueryTerm($keyword_filter, $include_keywords);
        $search_base_url = 'https://pncp.gov.br/pncp-consulta/v1';
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
            foreach ($this->buildSearchPlan($modality_code) as $plan) {
                $endpoint = $search_base_url . $plan['endpoint'];
                $query = $this->buildSearchQuery(
                    $query_term,
                    $plan['endpoint'],
                    $date_from,
                    $date_to,
                    $page,
                    $page_size,
                    $state,
                    $plan['modality_code']
                );

                for ($current_page = $page; $current_page < ($page + $max_pages); $current_page++) {
                    $query['pagina'] = $current_page;
                    $debug_urls[] = $this->buildRequestUrl($endpoint, $query);
                    $payload = $this->requestJson($endpoint, $query);
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
                        $normalized['source_name'] = (string) get_array_value((array) $source, 'name', 'PNCP');
                        $normalized['search_keyword'] = $keyword_filter;
                        $normalized['pncp_endpoint'] = $plan['endpoint'];
                        $normalized['pncp_modality_code'] = (int) $plan['modality_code'];

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

                        $match_data = $this->matchKeywords(
                            $normalized,
                            $keyword_filter,
                            $include_keywords,
                            $exclude_keywords,
                            $keyword_filter !== ''
                        );
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

                    if (count($items) < $page_size) {
                        $summary['pages'] = max($summary['pages'], $current_page - $page + 1);
                        break;
                    }

                    $summary['pages'] = max($summary['pages'], $current_page - $page + 1);
                }

                if (!empty($results)) {
                    break;
                }
            }

            if (empty($results)) {
                $broad_plan = array('endpoint' => '/contratacoes/publicacao', 'modality_code' => 6);
                $endpoint = $search_base_url . $broad_plan['endpoint'];
                $query = $this->buildSearchQuery(
                    $query_term,
                    $broad_plan['endpoint'],
                    $date_from,
                    $date_to,
                    $page,
                    min($page_size, 50),
                    '',
                    $broad_plan['modality_code']
                );
                $query['pagina'] = 1;
                $debug_urls[] = $this->buildRequestUrl($endpoint, $query);
                $payload = $this->requestJson($endpoint, $query);
                $items = $this->extractItems($payload);

                foreach ($items as $item) {
                    $normalized = $this->normalize_result($item);
                    if (!$normalized) {
                        continue;
                    }

                    $normalized['source_id'] = (int) get_array_value((array) $source, 'id', 0);
                    $normalized['source_name'] = (string) get_array_value((array) $source, 'name', 'PNCP');
                    $normalized['search_keyword'] = $keyword_filter;
                    $normalized['pncp_endpoint'] = $broad_plan['endpoint'];
                    $normalized['pncp_modality_code'] = (int) $broad_plan['modality_code'];
                    $normalized['matched_keywords'] = array();
                    $normalized['excluded_keywords'] = array();
                    $normalized['import_key'] = $this->buildImportKey($normalized);

                    $raw_key = $normalized['import_key'];
                    if (isset($seen[$raw_key])) {
                        continue;
                    }
                    $seen[$raw_key] = true;

                    $results[] = $normalized;
                    $summary['total']++;
                    $summary['matched']++;
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
                        'name' => (string) get_array_value((array) $source, 'name', 'PNCP'),
                    ),
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
            log_message('error', '[LicitaIA][PNCP] Search error: ' . $e->getMessage());

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
                'message' => app_lang('licitaia_pncp_search_failed'),
            );
        }
    }

    public function get_documents($purchase_id_or_identifier)
    {
        $identifier = $this->normalizeIdentifier($purchase_id_or_identifier);
        if (!$identifier['cnpj'] || !$identifier['year'] || !$identifier['sequencial']) {
            return array();
        }

        $base_url = 'https://pncp.gov.br/pncp-consulta/v1';
        $endpoint = rtrim($base_url, '/') . '/orgaos/' . $identifier['cnpj'] . '/compras/' . $identifier['year'] . '/' . $identifier['sequencial'];

        try {
            $payload = $this->requestJson($endpoint, array());
            $documents = $this->extractPurchaseDocuments($payload, $identifier);

            return $documents;
        } catch (\Throwable $e) {
            log_message('error', '[LicitaIA][PNCP] get_documents error: ' . $e->getMessage());
            return array();
        }
    }

    public function normalize_result($result)
    {
        $result = is_array($result) ? $result : (array) $result;
        if (!$result) {
            return null;
        }

        $public_agency = $this->firstValue($result, array(
            'orgaoEntidade.razaoSocial',
            'orgaoEntidadeRazaoSocial',
            'orgaoEntidade.nome',
            'orgaoNome',
            'nomeOrgao',
            'razaoSocialOrgao',
        ));

        $notice_number = $this->firstValue($result, array(
            'numeroEdital',
            'numeroCompra',
            'numeroAviso',
            'numero',
            'numeroControlePNCP',
            'numeroControle',
        ));

        $process_number = $this->firstValue($result, array(
            'processo',
            'numeroProcesso',
            'processoAdministrativo',
        ));

        $modality = $this->firstValue($result, array(
            'modalidadeContratacao.nome',
            'modalidadeNome',
            'modalidade',
            'tipoModalidade',
        ));

        $object_description = $this->firstValue($result, array(
            'objetoCompra',
            'objetoContrato',
            'objeto',
            'descricao',
            'description',
        ));

        $title = trim((string) $this->firstValue($result, array(
            'titulo',
            'title',
            'nome',
        )));
        if ($title === '') {
            $title = trim((string) $object_description);
        }
        if ($title === '') {
            $title = trim((string) $notice_number);
        }
        if ($title === '') {
            $title = 'Edital PNCP';
        }

        $source_url = $this->firstValue($result, array(
            'linkSistemaOrigem',
            'linkPNCP',
            'urlSistemaOrigem',
            'url',
            'link',
        ));

        $purchase_id = $this->firstValue($result, array(
            'idCompra',
            'id',
            'numeroControlePNCP',
        ));

        $org_cnpj = $this->firstValue($result, array(
            'orgaoEntidade.cnpj',
            'orgaoEntidadeCnpj',
            'cnpj',
        ));

        $year = $this->firstValue($result, array(
            'anoCompra',
            'ano',
            'anoPublicacao',
        ));

        $sequencial = $this->firstValue($result, array(
            'sequencialCompra',
            'sequencial',
            'sequencialPNCP',
        ));

        $state = strtoupper(substr(trim((string) $this->firstValue($result, array('uf', 'siglaUf', 'estado', 'orgaoEntidade.uf'))), 0, 2));
        $city = $this->firstValue($result, array('municipioNome', 'municipio', 'nomeMunicipio', 'cidade'));

        $opening_date = $this->normalizeDateValue($this->firstValue($result, array(
            'dataAberturaProposta',
            'dataAbertura',
            'dataDivulgacao',
            'dataPublicacao',
            'dataInicioRecebimentoPropostas',
        )));

        $submission_deadline = $this->normalizeDateValue($this->firstValue($result, array(
            'dataEncerramentoProposta',
            'dataLimiteRecebimentoProposta',
            'dataEncerramentoRecebimentoProposta',
            'dataFinalRecebimentoPropostas',
        )));

        $estimated_value = $this->normalizeMoney($this->firstValue($result, array(
            'valorTotalEstimado',
            'valorEstimado',
            'valorGlobal',
            'valorTotal',
        )));

        return array(
            'purchase_id' => trim((string) $purchase_id),
            'title' => trim((string) $title),
            'public_agency' => trim((string) $public_agency),
            'notice_number' => trim((string) $notice_number),
            'process_number' => trim((string) $process_number),
            'modality' => trim((string) $modality),
            'object_description' => trim((string) $object_description),
            'city' => trim((string) $city),
            'state' => $state,
            'estimated_value' => $estimated_value,
            'opening_date' => $opening_date,
            'submission_deadline' => $submission_deadline,
            'source_url' => trim((string) $source_url),
            'org_cnpj' => trim((string) $org_cnpj),
            'year' => trim((string) $year),
            'sequencial' => trim((string) $sequencial),
            'identity_key' => $this->buildImportKey(array(
                'notice_number' => trim((string) $notice_number),
                'public_agency' => trim((string) $public_agency),
                'source_url' => trim((string) $source_url),
            )),
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
        $normalized = isset($data['title']) && isset($data['notice_number'])
            ? $data
            : $this->normalize_result($data);

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
        $opportunity_id = (int) $opportunity_id;
        if ($opportunity_id <= 0) {
            return array('imported' => 0, 'documents' => array());
        }

        $documents = $this->get_documents($normalized);
        if (!$documents) {
            $documents = $this->fallbackDocumentCandidates($normalized);
        }

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
        $title = trim((string) get_array_value($document, 'title', ''));
        if ($title === '') {
            $title = 'Documento PNCP';
        }

        $extension = strtolower((string) pathinfo(parse_url($source_url, PHP_URL_PATH) ?: $source_url, PATHINFO_EXTENSION));
        $allowed_extensions = array('pdf', 'doc', 'docx', 'xls', 'xlsx', 'png', 'jpg', 'jpeg', 'gif', 'bmp', 'webp');
        $can_download = $source_url !== '' && in_array($extension, $allowed_extensions, true);

        $storage_dir = $this->buildImportedDocumentStorageDir($opportunity_id);
        $file_name = $this->normalizeImportedDocumentFileName($title, $source_url, $extension);
        $absolute_path = $storage_dir . $file_name;
        $stored_file_name = $file_name;
        $file_saved = false;

        if ($can_download) {
            $file_saved = $this->downloadRemoteFile($source_url, $absolute_path);
        }

        $document_data = array(
            'opportunity_id' => $opportunity_id,
            'file_name' => $stored_file_name,
            'original_file_name' => $title . ($extension !== '' ? '.' . $extension : ''),
            'file_path' => $file_saved ? $stored_file_name : null,
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

        $document_id = $this->opportunities_model ? null : null;
        $document_id = model(\LicitaIA\Models\Document_model::class)->ci_save($document_data, 0);
        if (!$document_id) {
            if ($file_saved && is_file($absolute_path)) {
                @unlink($absolute_path);
            }

            return array('document_id' => 0, 'message' => app_lang('error_occurred'));
        }

        if ($file_saved && is_file($absolute_path)) {
            $extractor = new Document_extractor();
            $extract_result = $extractor->extract_text($absolute_path, $extension);
            if (!empty($extract_result['success']) && trim((string) get_array_value($extract_result, 'text', '')) !== '') {
                model(\LicitaIA\Models\Document_model::class)->save_extracted_text($document_id, $extract_result['text']);
            } elseif (!empty($extract_result['needs_ocr'])) {
                $document_update = array(
                    'status' => 'pending_extraction',
                    'updated_at' => get_my_local_time(),
                );
                model(\LicitaIA\Models\Document_model::class)->ci_save($document_update, $document_id);
            }
        }

        return array(
            'document_id' => (int) $document_id,
            'title' => $title,
            'source_url' => $source_url,
            'saved_file' => $file_saved,
        );
    }

    private function fallbackDocumentCandidates(array $normalized)
    {
        $raw = (array) get_array_value($normalized, 'raw', array());
        $candidates = array();

        foreach (array('linkProcessoEletronico', 'linkSistemaOrigem', 'urlEdital', 'editalUrl', 'editalUrl') as $field) {
            $url = trim((string) get_array_value($raw, $field, ''));
            if ($url !== '') {
                $candidates[] = array(
                    'title' => $this->guessDocumentTitle($raw, $field),
                    'file_url' => $url,
                    'source_url' => $url,
                );
            }
        }

        return $candidates;
    }

    private function extractPurchaseDocuments(array $payload, array $identifier)
    {
        $documents = array();

        foreach (array('arquivos', 'anexos', 'documentos', 'edital', 'docs') as $key) {
            $items = get_array_value($payload, $key, array());
            if (!is_array($items) || empty($items)) {
                continue;
            }

            foreach ($items as $item) {
                $documents[] = $this->normalizeDocument($item, $identifier);
            }
        }

        if ($documents) {
            return $documents;
        }

        foreach (array('linkProcessoEletronico', 'linkSistemaOrigem') as $field) {
            $url = trim((string) get_array_value($payload, $field, ''));
            if ($url === '') {
                continue;
            }

            $documents[] = array(
                'title' => $this->guessDocumentTitle($payload, $field),
                'file_url' => $url,
                'source_url' => $url,
                'document_type_id' => 0,
                'org_cnpj' => $identifier['cnpj'],
                'year' => $identifier['year'],
                'sequencial' => $identifier['sequencial'],
                'raw' => $payload,
            );
        }

        return $documents;
    }

    private function buildImportedDocumentStorageDir($opportunity_id)
    {
        $base_path = rtrim(WRITEPATH, '/\\') . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'licitaia' . DIRECTORY_SEPARATOR . 'documents' . DIRECTORY_SEPARATOR . 'opportunity_' . (int) $opportunity_id . DIRECTORY_SEPARATOR . 'imported' . DIRECTORY_SEPARATOR;
        if (!is_dir($base_path) && !@mkdir($base_path, 0755, true)) {
            return rtrim(WRITEPATH, '/\\') . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'licitaia' . DIRECTORY_SEPARATOR . 'documents' . DIRECTORY_SEPARATOR . 'opportunity_' . (int) $opportunity_id . DIRECTORY_SEPARATOR;
        }

        return $base_path;
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
                'Referer: https://pncp.gov.br/',
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36',
            ),
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36',
        ));

        $ok = curl_exec($ch);
        $errno = curl_errno($ch);
        $http_code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $content_type = strtolower((string) curl_getinfo($ch, CURLINFO_CONTENT_TYPE));

        curl_close($ch);
        fclose($fp);

        if ($errno || $http_code >= 400) {
            @unlink($destination);
            return false;
        }

        if ($content_type !== '' && strpos($content_type, 'text/html') !== false) {
            @unlink($destination);
            return false;
        }

        return (bool) $ok && is_file($destination) && filesize($destination) > 0;
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

    private function guessDocumentTitle(array $payload, $field)
    {
        $title = trim((string) get_array_value($payload, 'tipoInstrumentoConvocatorioNome', ''));
        if ($title !== '') {
            return $title;
        }

        $title = trim((string) get_array_value($payload, 'objetoCompra', ''));
        if ($title !== '') {
            return mb_substr($title, 0, 180);
        }

        return 'Edital PNCP';
    }

    private function normalizeImportedDocumentFileName($title, $url, $extension)
    {
        $title = trim((string) $title);
        $base = preg_replace('/[^A-Za-z0-9._-]+/', '-', strtolower($title));
        $base = trim((string) $base, '-_.');
        if ($base === '') {
            $base = 'documento-pncp';
        }

        if ($extension === '') {
            $path_extension = strtolower((string) pathinfo(parse_url($url, PHP_URL_PATH) ?: $url, PATHINFO_EXTENSION));
            $extension = $path_extension;
        }

        $suffix = substr(sha1($url . '|' . microtime(true)), 0, 10);
        return $base . '-' . $suffix . ($extension !== '' ? '.' . $extension : '');
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

        $active_sources = $this->sources_model->get_active_pncp_sources();
        if (!empty($active_sources)) {
            return $this->normalizeSourceRow((array) $active_sources[0]);
        }

        return array(
            'id' => 0,
            'name' => 'PNCP',
            'source_type' => 'pncp',
            'base_url' => $this->defaultBaseUrl,
            'url' => $this->defaultBaseUrl,
        );
    }

    private function normalizeSourceRow(array $row)
    {
        $row['base_url'] = trim((string) (($row['base_url'] ?? '') ?: ($row['url'] ?? $this->defaultBaseUrl)));
        $row['url'] = trim((string) (($row['url'] ?? '') ?: ($row['base_url'] ?? $this->defaultBaseUrl)));
        return $row;
    }

    private function buildFiltersPayload(array $params, $source)
    {
        return array(
            'keyword' => trim((string) get_array_value($params, 'keyword')),
            'state' => trim((string) get_array_value($params, 'state')),
            'date_from' => trim((string) get_array_value($params, 'date_from')),
            'date_to' => trim((string) get_array_value($params, 'date_to')),
            'modality_code' => (int) get_array_value($params, 'modality_code'),
            'tipos_documento' => (int) get_array_value($params, 'tipos_documento', 1),
            'source_id' => (int) get_array_value((array) $source, 'id', 0),
            'source_name' => (string) get_array_value((array) $source, 'name', 'PNCP'),
        );
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

    private function normalizeDateForPncp($value)
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }

        $formats = array('Ymd', 'Y-m-d', 'd/m/Y', 'd-m-Y', 'Y/m/d', 'Y.m.d');
        foreach ($formats as $format) {
            $date = \DateTime::createFromFormat($format, $value);
            if ($date instanceof \DateTime && $date->format($format) === $value) {
                return $date->format('Ymd');
            }
        }

        $timestamp = strtotime($value);
        if ($timestamp !== false) {
            return date('Ymd', $timestamp);
        }

        return preg_replace('/\D+/', '', $value);
    }

    private function requestJson($url, array $query = array())
    {
        $request_url = $this->buildRequestUrl($url, $query);

        if (!function_exists('curl_init')) {
            throw new \RuntimeException('cURL extension not available.');
        }

        $ch = curl_init($request_url);
        curl_setopt_array($ch, array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CONNECTTIMEOUT => 20,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_HTTPHEADER => array(
                'Accept: application/json',
                'Accept-Language: pt-BR,pt;q=0.9,en;q=0.8',
                'Cache-Control: no-cache',
                'Pragma: no-cache',
                'Referer: https://pncp.gov.br/',
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36',
            ),
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36',
        ));

        $response = curl_exec($ch);
        $errno = curl_errno($ch);
        $error = curl_error($ch);
        $http_code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($errno) {
            throw new \RuntimeException('PNCP request failed: ' . $error);
        }

        if ($http_code >= 400) {
            $body = trim((string) $response);
            if (strlen($body) > 500) {
                $body = substr($body, 0, 500) . '...';
            }

            throw new \RuntimeException('PNCP returned HTTP ' . $http_code . ' for ' . $request_url . ($body !== '' ? ' | ' . $body : ''));
        }

        $decoded = json_decode((string) $response, true);
        if (!is_array($decoded)) {
            throw new \RuntimeException('Invalid JSON response from PNCP.');
        }

        return $decoded;
    }

    private function buildRequestUrl($url, array $query = array())
    {
        $request_url = $url;
        if ($query) {
            $request_url .= (strpos($request_url, '?') === false ? '?' : '&') . http_build_query($query);
        }

        return $request_url;
    }

    private function extractItems($payload)
    {
        if (!is_array($payload)) {
            return array();
        }

        foreach (array('data', 'items', 'content', 'results', 'resultado') as $key) {
            if (!empty($payload[$key]) && is_array($payload[$key])) {
                return $this->isSequentialArray($payload[$key]) ? $payload[$key] : array_values($payload[$key]);
            }
        }

        return $this->isSequentialArray($payload) ? $payload : array();
    }

    private function isSequentialArray(array $array)
    {
        return array_keys($array) === range(0, count($array) - 1);
    }

    private function normalizeDateValue($value)
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }

        $timestamp = strtotime($value);
        if ($timestamp === false) {
            return $value;
        }

        return date('Y-m-d', $timestamp);
    }

    private function normalizeMoney($value)
    {
        if (is_numeric($value)) {
            return (float) $value;
        }

        $value = trim((string) $value);
        if ($value === '') {
            return 0;
        }

        $value = str_replace(array('.', 'R$', ' '), '', $value);
        $value = str_replace(',', '.', $value);
        return (float) $value;
    }

    private function normalizeIdentifier($value)
    {
        if (is_array($value)) {
            return array(
                'cnpj' => preg_replace('/\D+/', '', (string) get_array_value($value, 'org_cnpj', get_array_value($value, 'cnpj', ''))),
                'year' => (int) get_array_value($value, 'year', get_array_value($value, 'ano', 0)),
                'sequencial' => (int) get_array_value($value, 'sequencial', get_array_value($value, 'purchase_id', 0)),
            );
        }

        $value = trim((string) $value);
        if ($value === '') {
            return array('cnpj' => '', 'year' => 0, 'sequencial' => 0);
        }

        $parts = explode('/', $value);
        return array(
            'cnpj' => preg_replace('/\D+/', '', (string) ($parts[0] ?? '')),
            'year' => (int) ($parts[1] ?? 0),
            'sequencial' => (int) ($parts[2] ?? 0),
        );
    }

    private function normalizeDocument($item, array $identifier)
    {
        $item = is_array($item) ? $item : (array) $item;

        return array(
            'title' => $this->firstValue($item, array('tituloDocumento', 'titulo', 'nomeDocumento', 'nome', 'descricao')) ?: 'Documento PNCP',
            'file_url' => $this->firstValue($item, array('url', 'link', 'linkDocumento', 'linkSistemaOrigem')),
            'document_type_id' => (int) $this->firstValue($item, array('tipoDocumentoId', 'tipoDocumento', 'codigoTipoDocumento')),
            'org_cnpj' => $identifier['cnpj'],
            'year' => $identifier['year'],
            'sequencial' => $identifier['sequencial'],
            'raw' => $item,
        );
    }

    private function firstValue(array $source, array $paths, $default = '')
    {
        foreach ($paths as $path) {
            $value = $this->getByPath($source, $path);
            if ($value !== null && $value !== '') {
                return $value;
            }
        }

        return $default;
    }

    private function getByPath($source, $path)
    {
        $segments = explode('.', (string) $path);
        $value = $source;

        foreach ($segments as $segment) {
            if (is_array($value) && array_key_exists($segment, $value)) {
                $value = $value[$segment];
                continue;
            }

            if (is_object($value) && isset($value->$segment)) {
                $value = $value->$segment;
                continue;
            }

            return null;
        }

        return $value;
    }

    private function buildImportKey(array $normalized)
    {
        return sha1(
            strtolower(trim((string) get_array_value($normalized, 'notice_number'))) . '|' .
            strtolower(trim((string) get_array_value($normalized, 'public_agency'))) . '|' .
            strtolower(trim((string) get_array_value($normalized, 'source_url')))
        );
    }

    private function normalizeBaseUrl($url)
    {
        $url = trim((string) $url);
        if ($url === '') {
            $url = $this->defaultBaseUrl;
        }

        return rtrim($url, '/');
    }

    private function buildSearchPlan($modality_code)
    {
        $modality_code = (int) $modality_code;
        if ($modality_code > 0) {
            return array(
                array('endpoint' => '/contratacoes/publicacao', 'modality_code' => $modality_code),
            );
        }

        return array(
            array('endpoint' => '/contratacoes/publicacao', 'modality_code' => 6),
            array('endpoint' => '/contratacoes/publicacao', 'modality_code' => 4),
            array('endpoint' => '/contratacoes/publicacao', 'modality_code' => 1),
            array('endpoint' => '/contratacoes/publicacao', 'modality_code' => 3),
            array('endpoint' => '/contratacoes/publicacao', 'modality_code' => 2),
            array('endpoint' => '/contratacoes/publicacao', 'modality_code' => 5),
            array('endpoint' => '/contratacoes/publicacao', 'modality_code' => 7),
            array('endpoint' => '/contratacoes/publicacao', 'modality_code' => 8),
            array('endpoint' => '/contratacoes/publicacao', 'modality_code' => 9),
            array('endpoint' => '/contratacoes/proposta', 'modality_code' => 0),
        );
    }

    private function buildSearchQuery($query_term, $endpoint, $date_from, $date_to, $page, $page_size, $state, $modality_code)
    {
        $query = array(
            'q' => trim((string) $query_term),
            'pagina' => (int) $page,
            'tamanhoPagina' => (int) $page_size,
        );

        if ($endpoint === '/contratacoes/publicacao') {
            $query['dataInicial'] = $date_from ?: $date_to;
            $query['dataFinal'] = $date_to ?: $date_from;
            $query['codigoModalidadeContratacao'] = (int) $modality_code;
        } else {
            $query['dataFinal'] = $date_to ?: $date_from;
        }

        if ($state !== '') {
            $query['uf'] = substr($state, 0, 2);
        }

        return $query;
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
