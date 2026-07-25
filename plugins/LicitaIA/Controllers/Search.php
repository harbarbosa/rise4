<?php

namespace LicitaIA\Controllers;

class Search extends Licitaia_Base_Controller
{
    public function index()
    {

       
        $this->ensureAccess();

        $search_result = array(
            'success' => true,
            'results' => array(),
            'summary' => array('total' => 0, 'matched' => 0, 'imported' => 0, 'ignored' => 0, 'pages' => 0),
            'message' => '',
        );
        

        $filters = $this->getDefaultFilters();
        
        log_message('debug', '[LicitaIA][Search] request method: ' . $this->request->getMethod());

        if ($this->request->is('post')) {
            $filters = $this->getFiltersFromRequest();

            $search_client = $this->searchClientForFilters($filters);
            $search_result = $search_client->search($this->normalizeFiltersForApi($filters));

            $keyword_filter = trim((string) get_array_value($filters, 'keyword', ''));
            if ($keyword_filter === '' && empty(get_array_value($search_result, 'results'))) {
                $fallback_filters = $filters;
                $fallback_filters['keyword'] = '';
                $fallback_filters['modality_code'] = (int) get_array_value($fallback_filters, 'modality_code', 0) ?: 6;
                $fallback_filters['page_size'] = 50;
                $fallback_filters['max_pages'] = 1;
                $fallback_result = $search_client->search($this->normalizeFiltersForApi($fallback_filters));
                if (!empty(get_array_value($fallback_result, 'results'))) {
                    $search_result = $fallback_result;
                    $filters = $fallback_filters;
                }
            }
            if (!empty($search_result['success'])) {
                $this->persistSearchResults($search_result, $filters);
            }
        } else {
            $filters = session()->get('licitaia_pncp_search_filters') ?: $filters;
            $stored_results = session()->get('licitaia_pncp_search_results');
            if (is_array($stored_results)) {
                $search_result['results'] = $stored_results;
            }
            $stored_summary = session()->get('licitaia_pncp_search_summary');
            if (is_array($stored_summary)) {
                $search_result['summary'] = $stored_summary;
            }
        }

        return $this->template->rander('LicitaIA\\Views\\search\\index', array(
            'can_import' => \LicitaIA\Plugin::canManageOpportunities($this->login_user),
            'can_run_search' => \LicitaIA\Plugin::canRunSearch($this->login_user),
            'filters' => $filters,
            'search_result' => $search_result,
            'search_message' => $this->buildSearchMessage($search_result),
            'search_success' => !empty($search_result['success']),
            'source_dropdown' => $this->getSearchSourceDropdown(),
            'states_dropdown' => $this->statesDropdown(),
            'has_include_keywords' => true,
            'include_keywords_count' => count((array) $this->keywords_model->get_active_include_keywords()),
            'debug_info' => $this->buildDebugInfo($search_result, $filters),
        ));
        
    }

    public function list_data()
    {
        $this->ensureAccess();

        $stored_results = session()->get('licitaia_pncp_search_results');
        if (!is_array($stored_results)) {
            $stored_results = array();
        }

        $all_rows = array();
        foreach ($stored_results as $index => $row) {
            $row = is_array($row) ? $row : (array) $row;
            $row['_session_index'] = $index;
            $all_rows[] = $row;
        }

        $all_options = append_server_side_filtering_commmon_params(array());
        $search_by = trim((string) get_array_value($all_options, 'search_by', ''));

        if ($search_by !== '') {
            $all_rows = array_values(array_filter($all_rows, function ($row) use ($search_by) {
                $needle = mb_strtolower($search_by);
                $haystack = mb_strtolower(trim(implode(' ', array_filter(array(
                    get_array_value($row, 'title', ''),
                    get_array_value($row, 'public_agency', ''),
                    get_array_value($row, 'notice_number', ''),
                    get_array_value($row, 'process_number', ''),
                    get_array_value($row, 'modality', ''),
                    get_array_value($row, 'state', ''),
                )))));

                return $needle === '' || mb_strpos($haystack, $needle) !== false;
            }));
        }

        $records_filtered = count($all_rows);
        $limit = (int) get_array_value($all_options, 'limit', 10);
        $skip = max(0, (int) get_array_value($all_options, 'skip', 0));
        $page_rows = array_slice($all_rows, $skip, $limit);
        $result_rows = array();

        foreach ($page_rows as $row) {
            $result_rows[] = $this->makeSearchRow($row);
        }

        $response = array(
            'recordsTotal' => $records_filtered,
            'recordsFiltered' => $records_filtered,
            'data' => $result_rows,
        );

        echo json_encode($response);
    }

    public function import_selected()
    {
        if (!\LicitaIA\Plugin::canManageOpportunities($this->login_user)) {
            app_redirect('forbidden');
        }

        $selected = $this->request->getPost('selected');
        $selected = is_array($selected) ? array_map('strval', $selected) : array();
        $stored_results = session()->get('licitaia_pncp_search_results');

        if (!is_array($stored_results) || !$stored_results) {
            session()->setFlashdata('error_message', app_lang('licitaia_empty_state'));
            return app_redirect('licitaia/search');
        }

        $imported = 0;
        $skipped = 0;
        foreach ($selected as $index) {
            if (!isset($stored_results[$index])) {
                $skipped++;
                continue;
            }

            $row = $stored_results[$index];
            if (!empty($row['imported'])) {
                $skipped++;
                continue;
            }

            $result = $this->searchClientForRow($row)->import_opportunity($row);
            if (!empty($result['success']) && !empty($result['imported'])) {
                $stored_results[$index]['imported'] = true;
                $stored_results[$index]['existing_opportunity_id'] = (int) get_array_value($result, 'id');
                $imported++;
            } else {
                $skipped++;
            }
        }

        session()->set('licitaia_pncp_search_results', $stored_results);
        session()->setFlashdata('success_message', sprintf('%s: %d | %s: %d', app_lang('licitaia_imported'), $imported, app_lang('licitaia_search_ignored'), $skipped));

        return app_redirect('licitaia/search');
    }

    public function run_cron()
    {
        $is_cli = PHP_SAPI === 'cli' || (function_exists('is_cli') && is_cli());
        if (!$is_cli && !\LicitaIA\Plugin::canRunSearch($this->login_user)) {
            app_redirect('forbidden');
        }

        $sources = $this->sources_model->get_active_sources();
        if (!$sources) {
            $sources = array((object) array(
                'id' => 0,
                'name' => 'PNCP',
                'base_url' => 'https://pncp.gov.br/api/pncp',
                'source_type' => 'pncp',
            ));
        }

        $imported = 0;
        $searched = 0;
        $messages = array();
        $filters = array(
            'date_from' => date('Y-m-d', strtotime('-2 days')),
            'date_to' => date('Y-m-d'),
            'max_pages' => 3,
            'page_size' => 50,
        );

        foreach ($sources as $source) {
            $filters['source_id'] = (int) ($source->id ?? 0);
            $search_result = $this->searchClientForSource((array) $source)->search($filters);
            $searched += count((array) get_array_value($search_result, 'results', array()));

            foreach ((array) get_array_value($search_result, 'results', array()) as $row) {
                if (!empty($row['imported'])) {
                    continue;
                }

                $result = $this->searchClientForRow($row)->import_opportunity($row);
                if (!empty($result['success']) && !empty($result['imported'])) {
                    $imported++;
                }
            }

            $messages[] = (string) get_array_value((array) $source, 'name', 'PNCP');
        }

        $payload = array(
            'success' => true,
            'searched' => $searched,
            'imported' => $imported,
            'sources' => $messages,
        );

        if ($is_cli) {
            echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            return null;
        }

        return $this->response->setJSON($payload);
    }

    private function getDefaultFilters()
    {
        return array(
            'keyword' => '',
            'state' => '',
            'date_from' => $this->formatDateForDisplay('-7 days'),
            'date_to' => $this->formatDateForDisplay('now'),
            'source_id' => '',
            'modality_code' => '',
        );
    }

    private function getFiltersFromRequest()
    {
        return array(
            'keyword' => trim((string) $this->request->getPost('keyword')),
            'state' => strtoupper(trim((string) $this->request->getPost('state'))),
            'date_from' => trim((string) $this->request->getPost('date_from')),
            'date_to' => trim((string) $this->request->getPost('date_to')),
            'source_id' => (int) $this->request->getPost('source_id'),
            'modality_code' => (int) $this->request->getPost('modality_code'),
            'max_pages' => 5,
            'page_size' => 50,
        );
    }

    private function normalizeFiltersForApi(array $filters)
    {
        $filters['date_from'] = $this->normalizeDateInput(get_array_value($filters, 'date_from'));
        $filters['date_to'] = $this->normalizeDateInput(get_array_value($filters, 'date_to'));

        return $filters;
    }

    private function normalizeDateInput($value)
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }

        $setting_format = get_setting('date_format') ?: 'Y-m-d';
        $formats = array_unique(array_filter(array(
            $setting_format,
            'Y-m-d',
            'd/m/Y',
            'd-m-Y',
            'Y/m/d',
            'Y.m.d',
        )));

        foreach ($formats as $format) {
            $date = \DateTime::createFromFormat($format, $value);
            if ($date instanceof \DateTime && $date->format($format) === $value) {
                return $date->format('Y-m-d');
            }
        }

        $timestamp = strtotime($value);
        return $timestamp !== false ? date('Y-m-d', $timestamp) : $value;
    }

    private function formatDateForDisplay($relative)
    {
        $timestamp = strtotime($relative, strtotime(get_my_local_time('Y-m-d H:i:s')));
        $format = get_setting('date_format') ?: 'Y-m-d';

        return date($format, $timestamp);
    }

    private function persistSearchResults(array $search_result, array $filters)
    {
        $results = array();
        foreach ((array) get_array_value($search_result, 'results', array()) as $row) {
            $row = is_array($row) ? $row : (array) $row;
            unset($row['raw']);
            $results[] = $row;
        }

        session()->set('licitaia_pncp_search_filters', $filters);
        session()->set('licitaia_pncp_search_results', $results);
        session()->set('licitaia_pncp_search_summary', (array) get_array_value($search_result, 'summary', array()));
    }

    private function buildDebugInfo(array $search_result, array $filters)
    {
        $source = get_array_value($search_result, 'source', array());
        $provider = strtolower(trim((string) get_array_value($search_result, 'provider', 'pncp')));
        $source_name = trim((string) get_array_value($source, 'name', $provider === 'paradigma' ? 'Paradigma / SESCSP' : 'PNCP'));
        $source_type = trim((string) get_array_value($source, 'type', get_array_value($source, 'source_type', $provider)));
        $source_label = $source_name !== '' ? $source_name : strtoupper($provider);

        return array(
            'provider' => $provider,
            'source_label' => $source_label,
            'source_type' => $source_type,
            'db_insert_path' => $provider === 'paradigma'
                ? 'LicitaIA\\Libraries\\Paradigma_client::import_opportunity() -> LicitaIA\\Models\\Opportunity_model::ci_save()'
                : 'LicitaIA\\Libraries\\Pncp_client::import_opportunity() -> LicitaIA\\Models\\Opportunity_model::ci_save()',
            'db_search_log_path' => $provider === 'paradigma'
                ? 'LicitaIA\\Libraries\\Paradigma_client::search() -> LicitaIA\\Models\\Search_log_model::log_search()'
                : 'LicitaIA\\Libraries\\Pncp_client::search() -> LicitaIA\\Models\\Search_log_model::log_search()',
            'filters' => $filters,
            'search_success' => !empty($search_result['success']),
            'results_count' => count((array) get_array_value($search_result, 'results', array())),
            'debug_urls' => (array) get_array_value($search_result, 'debug_urls', array()),
            'error_detail' => (string) get_array_value($search_result, 'error_detail', ''),
            'message' => (string) get_array_value($search_result, 'message', ''),
            'request_url' => (string) get_array_value($search_result, 'request_url', ''),
        );
    }

    private function buildSearchMessage(array $search_result)
    {
        $message = (string) get_array_value($search_result, 'message', '');
        $error_detail = trim((string) get_array_value($search_result, 'error_detail', ''));

        if ($error_detail !== '') {
            $message .= ($message !== '' ? ' | ' : '') . $error_detail;
        }

        return $message;
    }

    private function makeSearchRow(array $row)
    {
        $session_index = (string) get_array_value($row, '_session_index', '');
        $imported = !empty($row['imported']);
        $matched_keywords = array_values(array_filter(array_map('trim', (array) get_array_value($row, 'matched_keywords', array()))));
        $excluded_keywords = array_values(array_filter(array_map('trim', (array) get_array_value($row, 'excluded_keywords', array()))));
        $title = (string) get_array_value($row, 'title', '-');
        $public_agency = (string) get_array_value($row, 'public_agency', '-');
        $notice_number = (string) get_array_value($row, 'notice_number', '-');
        $process_number = (string) get_array_value($row, 'process_number', '-');
        $modality = (string) get_array_value($row, 'modality', '-');
        $state = (string) get_array_value($row, 'state', '-');

        $selection = '-';
        if ($this->login_user && \LicitaIA\Plugin::canManageOpportunities($this->login_user)) {
            if ($imported) {
                $selection = "<span class='badge bg-success'>" . app_lang('licitaia_imported') . "</span>";
            } else {
                $selection = form_checkbox('selected[]', $session_index, false, "class='form-check-input licitaia-select-item'");
            }
        }

        $status_badges = array();
        $status_badges[] = $imported
            ? "<span class='badge bg-success'>" . app_lang('licitaia_imported') . "</span>"
            : "<span class='badge bg-info'>" . app_lang('new') . "</span>";

        if ($matched_keywords) {
            $matched_title = esc(implode(', ', $matched_keywords));
            $status_badges[] = "<span class='badge bg-primary' title=\"" . $matched_title . "\">" . app_lang('licitaia_keyword_match') . "</span>";
        }

        if ($excluded_keywords) {
            $excluded_title = esc(implode(', ', $excluded_keywords));
            $status_badges[] = "<span class='badge bg-danger' title=\"" . $excluded_title . "\">" . app_lang('licitaia_keyword_excluded') . "</span>";
        }

        $title_cell = "<div class='d-flex flex-column gap-1'>"
            . $this->makeClampedCell($title, true)
            . ($matched_keywords ? "<div class='d-flex flex-wrap gap-1'>" . $this->buildKeywordTags($matched_keywords, 'primary') . "</div>" : '')
            . "</div>";

        $source_url = '-';
        if (!empty($row['source_url'])) {
            $source_url = anchor($row['source_url'], app_lang('view'), array('target' => '_blank', 'rel' => 'noopener noreferrer'));
        }

        return array(
            $selection,
            $title_cell,
            $this->makeClampedCell($public_agency),
            $this->makeClampedCell($notice_number),
            $this->makeClampedCell($process_number),
            $this->makeClampedCell($modality),
            $this->makeClampedCell($state),
            !empty(get_array_value($row, 'opening_date')) ? esc(format_to_date(get_array_value($row, 'opening_date'), false)) : '-',
            !empty(get_array_value($row, 'submission_deadline')) ? esc(format_to_date(get_array_value($row, 'submission_deadline'), false)) : '-',
            implode(' ', $status_badges),
            $source_url,
        );
    }

    private function buildKeywordTags(array $keywords, $badge_type = 'primary')
    {
        $keywords = array_values(array_filter(array_map('trim', $keywords)));
        if (!$keywords) {
            return '';
        }

        $class_map = array(
            'primary' => 'bg-primary',
            'success' => 'bg-success',
            'warning' => 'bg-warning text-dark',
            'danger' => 'bg-danger',
            'info' => 'bg-info text-dark',
        );
        $badge_class = get_array_value($class_map, $badge_type, 'bg-primary');

        $tags = array();
        foreach ($keywords as $keyword) {
            $tags[] = "<span class='badge {$badge_class}' title=\"" . esc($keyword) . "\">" . esc($keyword) . "</span>";
        }

        return implode(' ', $tags);
    }

    private function makeClampedCell($value, $bold = false)
    {
        $text = trim((string) $value);
        if ($text === '') {
            $text = '-';
        }

        $content = esc($text);
        $class = $bold ? 'licitaia-clamp-two-lines fw-semibold' : 'licitaia-clamp-two-lines';

        return "<span class=\"" . $class . "\" title=\"" . esc($text) . "\">" . $content . "</span>";
    }

    private function getSearchSourceDropdown()
    {
        $dropdown = array(
            '0' => 'PNCP (padrão)',
        );
        $sources = $this->sources_model->get_active_sources();
        foreach ($sources as $source) {
            $id = (int) $source->id;
            $source_type = strtolower(trim((string) ($source->source_type ?? '')));
            $name = trim((string) $source->name);
            $base_url = strtolower(trim((string) get_array_value((array) $source, 'base_url', get_array_value((array) $source, 'url', ''))));
            $api_endpoint = strtolower(trim((string) get_array_value((array) $source, 'api_endpoint', '')));

            $label = $this->formatSearchSourceLabel($source_type, $name, $base_url, $api_endpoint);

            if ($label === '') {
                $label = $name !== '' ? $name : '-';
            }

            $dropdown[$id] = $label;
        }

        return $dropdown;
    }

    private function formatSearchSourceLabel($source_type, $name, $base_url, $api_endpoint)
    {
        $source_type = strtolower(trim((string) $source_type));
        $name = trim((string) $name);
        $base_url = strtolower(trim((string) $base_url));
        $api_endpoint = strtolower(trim((string) $api_endpoint));

        if ($source_type === 'paradigma' || strpos($name, 'sescsp') !== false || strpos($name, 'paradigma') !== false || strpos($base_url, 'paradigmabs') !== false) {
            return 'Paradigma / SESCSP';
        }

        if ($source_type === 'pncp' || strpos($name, 'pncp') !== false || strpos($base_url, 'pncp') !== false) {
            return 'PNCP';
        }

        if ($source_type === 'compras_gov') {
            return app_lang('licitaia_source_type_compras_gov');
        }

        if ($source_type === 'bec_sp') {
            return app_lang('licitaia_source_type_bec_sp');
        }

        if ($source_type === 'portal_municipal') {
            return app_lang('licitaia_source_type_portal_municipal');
        }

        return $name;
    }

    private function statesDropdown()
    {
        return array(
            '' => '-',
            'AC' => 'AC',
            'AL' => 'AL',
            'AP' => 'AP',
            'AM' => 'AM',
            'BA' => 'BA',
            'CE' => 'CE',
            'DF' => 'DF',
            'ES' => 'ES',
            'GO' => 'GO',
            'MA' => 'MA',
            'MT' => 'MT',
            'MS' => 'MS',
            'MG' => 'MG',
            'PA' => 'PA',
            'PB' => 'PB',
            'PR' => 'PR',
            'PE' => 'PE',
            'PI' => 'PI',
            'RJ' => 'RJ',
            'RN' => 'RN',
            'RS' => 'RS',
            'RO' => 'RO',
            'RR' => 'RR',
            'SC' => 'SC',
            'SP' => 'SP',
            'SE' => 'SE',
            'TO' => 'TO',
        );
    }

    private function pncpClient()
    {
        return new \LicitaIA\Libraries\Pncp_client();
    }

    private function searchClientForFilters(array $filters)
    {
        $source_id = (int) get_array_value($filters, 'source_id');
        if ($source_id > 0) {
            $source = $this->sources_model->get_details(array('id' => $source_id))->getRowArray();
            if ($source) {
                return $this->searchClientForSource($source);
            }
        }

        $active_sources = $this->sources_model->get_active_sources();
        if (!empty($active_sources)) {
            return $this->searchClientForSource((array) $active_sources[0]);
        }

        return $this->pncpClient();
    }

    private function searchClientForRow(array $row)
    {
        return $this->searchClientForSource($row);
    }

    private function searchClientForSource(array $source)
    {
        $source_type = strtolower(trim((string) get_array_value($source, 'source_type', '')));
        $name = strtolower(trim((string) get_array_value($source, 'name', '')));
        $base_url = strtolower(trim((string) get_array_value($source, 'base_url', get_array_value($source, 'url', ''))));

        if ($source_type === 'paradigma' || strpos($name, 'paradigma') !== false || strpos($base_url, 'paradigmabs') !== false) {
            return new \LicitaIA\Libraries\Paradigma_client();
        }

        return $this->pncpClient();
    }
}
