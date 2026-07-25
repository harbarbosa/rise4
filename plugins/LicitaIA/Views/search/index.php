<?php
$filters = $filters ?? array();
$search_result = $search_result ?? array();
$search_message = $search_message ?? '';
$search_success = isset($search_success) ? (bool) $search_success : true;
$has_include_keywords = isset($has_include_keywords) ? (bool) $has_include_keywords : true;
$success_message = session()->getFlashdata('success_message');
$error_message = session()->getFlashdata('error_message');
$debug_urls = (array) get_array_value($search_result, 'debug_urls', array());
$debug_info = (array) ($debug_info ?? array());

$keyword_value = get_array_value($filters, 'keyword', '');
$state_value = get_array_value($filters, 'state', '');
$date_from_value = get_array_value($filters, 'date_from', '');
$date_to_value = get_array_value($filters, 'date_to', '');
$source_value = get_array_value($filters, 'source_id', '');
$modality_value = get_array_value($filters, 'modality_code', '');
$modalities = array(
    '' => '-',
    '1' => 'Leilao - Eletronico',
    '2' => 'Dialogo Competitivo',
    '3' => 'Concurso',
    '4' => 'Concorrencia - Eletronica',
    '5' => 'Concorrencia - Presencial',
    '6' => 'Pregao - Eletronico',
    '7' => 'Pregao - Presencial',
    '8' => 'Dispensa de Licitacao',
    '9' => 'Inexigibilidade',
);
?>

<div id="page-content" class="page-wrapper clearfix grid-button">
    <style>
        .licitaia-clamp-two-lines {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            white-space: normal;
            word-break: break-word;
            line-height: 1.35;
            max-height: 2.7em;
        }
    </style>
    <div class="card">
        <div class="page-title clearfix">
            <div class="d-flex flex-column">
                <h1 class="mb-1"><?php echo app_lang('licitaia_search_notices'); ?></h1>
                <div class="text-muted"><?php echo app_lang('licitaia_dashboard_intro'); ?></div>
            </div>
            <div class="title-button-group">
                <button type="submit" form="licitaia-pncp-search-form" name="do_search" value="1" class="btn btn-primary">
                    <i data-feather="search" class="icon-16"></i> <?php echo app_lang('licitaia_search_button'); ?>
                </button>
            </div>
        </div>

        <div class="card-body">
            <?php if ($success_message) { ?>
                <div class="alert alert-success"><?php echo esc($success_message); ?></div>
            <?php } ?>
            <?php if ($error_message) { ?>
                <div class="alert alert-danger"><?php echo esc($error_message); ?></div>
            <?php } ?>
            <?php if ($search_message) { ?>
                <div class="alert alert-<?php echo $search_success ? 'info' : 'danger'; ?>">
                    <?php echo esc($search_message); ?>
                </div>
            <?php } ?>
            <?php if (!empty($debug_urls)) { ?>
                <div class="alert alert-light border">
                    <div class="fw-bold mb-2">PNCP URL de busca</div>
                    <div class="small text-muted mb-2">A primeira URL gerada pela pesquisa:</div>
                    <code class="d-block text-break"><?php echo esc($debug_urls[0]); ?></code>
                </div>
            <?php } ?>
            <?php if (!empty($debug_info)) { ?>
                <div class="card border mb-3">
                    <div class="card-body">
                        <div class="fw-bold mb-2">Diagnostico de busca e gravacao</div>
                        <div class="small text-muted mb-2">Fonte utilizada:</div>
                        <div class="mb-2"><code class="d-block text-break"><?php echo esc(get_array_value($debug_info, 'source_label', '-')); ?><?php echo get_array_value($debug_info, 'source_type') ? ' (' . esc(get_array_value($debug_info, 'source_type')) . ')' : ''; ?></code></div>
                        <div class="small text-muted mb-2">Provider:</div>
                        <div class="mb-2"><code class="d-block text-break"><?php echo esc(get_array_value($debug_info, 'provider', '-')); ?></code></div>
                        <div class="small text-muted mb-2">Onde o sistema insere no banco:</div>
                        <div class="mb-2"><code class="d-block text-break"><?php echo esc(get_array_value($debug_info, 'db_insert_path', '-')); ?></code></div>
                        <div class="small text-muted mb-2">Onde o log da busca e salvo:</div>
                        <div class="mb-2"><code class="d-block text-break"><?php echo esc(get_array_value($debug_info, 'db_search_log_path', '-')); ?></code></div>
                        <?php if (!empty(get_array_value($debug_info, 'request_url'))) { ?>
                            <div class="small text-muted mb-2">URL de request:</div>
                            <div class="mb-2"><code class="d-block text-break"><?php echo esc(get_array_value($debug_info, 'request_url')); ?></code></div>
                        <?php } ?>
                        <div class="small text-muted mb-2">Quantidade retornada pela consulta:</div>
                        <div class="mb-2"><strong><?php echo (int) get_array_value($debug_info, 'results_count', 0); ?></strong></div>
                        <?php if (!empty(get_array_value($debug_info, 'error_detail'))) { ?>
                            <div class="small text-muted mb-2">Erro bruto do PNCP:</div>
                            <div><code class="d-block text-break"><?php echo esc(get_array_value($debug_info, 'error_detail')); ?></code></div>
                        <?php } ?>
                        <?php if (!empty(get_array_value($debug_info, 'message'))) { ?>
                            <div class="small text-muted mb-2">Mensagem do sistema:</div>
                            <div><code class="d-block text-break"><?php echo esc(get_array_value($debug_info, 'message')); ?></code></div>
                        <?php } ?>
                    </div>
                </div>
            <?php } ?>

            <?php echo form_open(get_uri('licitaia/search'), array('id' => 'licitaia-pncp-search-form', 'class' => 'general-form', 'role' => 'form')); ?>
            <div class="card border shadow-sm mb-0">
                <div class="card-body">
                    <div class="row g-3 align-items-end">
                        <div class="col-lg-4 col-md-6">
                            <label class="form-label"><?php echo app_lang('licitaia_search_keywords_optional'); ?></label>
                            <?php echo form_input(array(
                                'name' => 'keyword',
                                'class' => 'form-control',
                                'value' => $keyword_value,
                                'placeholder' => app_lang('licitaia_search_keywords_optional'),
                                'autocomplete' => 'off',
                            )); ?>
                        </div>

                        <div class="col-lg-2 col-md-6">
                            <label class="form-label"><?php echo app_lang('licitaia_search_uf'); ?></label>
                            <?php echo form_dropdown('state', $states_dropdown, $state_value, "class='form-control select2'"); ?>
                        </div>

                        <div class="col-lg-2 col-md-6">
                            <label class="form-label"><?php echo app_lang('licitaia_search_date_from'); ?></label>
                            <?php echo form_input(array(
                                'name' => 'date_from',
                                'class' => 'form-control datepicker',
                                'value' => $date_from_value,
                                'placeholder' => app_lang('date'),
                                'autocomplete' => 'off',
                            )); ?>
                        </div>

                        <div class="col-lg-2 col-md-6">
                            <label class="form-label"><?php echo app_lang('licitaia_search_date_to'); ?></label>
                            <?php echo form_input(array(
                                'name' => 'date_to',
                                'class' => 'form-control datepicker',
                                'value' => $date_to_value,
                                'placeholder' => app_lang('date'),
                                'autocomplete' => 'off',
                            )); ?>
                        </div>

                        <div class="col-lg-2 col-md-6">
                            <label class="form-label"><?php echo app_lang('licitaia_search_source'); ?></label>
                            <?php echo form_dropdown('source_id', $source_dropdown, $source_value, "class='form-control select2'"); ?>
                        </div>

                        <div class="col-lg-3 col-md-6">
                            <label class="form-label"><?php echo app_lang('licitaia_search_modality'); ?></label>
                            <?php echo form_dropdown('modality_code', $modalities, $modality_value, "class='form-control select2'"); ?>
                        </div>

                        <div class="col-lg-5 col-md-6"></div>

                        <div class="col-lg-4 col-md-12 text-end">
                            <button type="submit" name="do_search" value="1" class="btn btn-primary">
                                <i data-feather="search" class="icon-16"></i> <?php echo app_lang('licitaia_search_button'); ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <?php echo form_close(); ?>
        </div>
    </div>

    <?php if (!empty(get_array_value($search_result, 'results', array()))) { ?>
        <?php echo form_open(get_uri('licitaia/search/import_selected'), array('id' => 'licitaia-pncp-import-form', 'class' => 'general-form mt-3', 'role' => 'form')); ?>
        <div class="card grid-button">
            <div class="page-title clearfix">
                <h1><?php echo app_lang('licitaia_search_results'); ?></h1>
                <div class="title-button-group">
                    <?php if ($can_import ?? false) { ?>
                        <button type="submit" class="btn btn-primary">
                            <i data-feather="download" class="icon-16"></i> <?php echo app_lang('licitaia_import_selected'); ?>
                        </button>
                    <?php } ?>
                </div>
            </div>

            <div class="card-body">
                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <div class="card border h-100">
                            <div class="card-body">
                                <div class="text-muted"><?php echo app_lang('licitaia_total_opportunities'); ?></div>
                                <div class="font-24 fw-bold"><?php echo (int) get_array_value($search_result['summary'], 'total', 0); ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border h-100">
                            <div class="card-body">
                                <div class="text-muted"><?php echo app_lang('licitaia_search_results'); ?></div>
                                <div class="font-24 fw-bold"><?php echo (int) get_array_value($search_result['summary'], 'matched', 0); ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border h-100">
                            <div class="card-body">
                                <div class="text-muted"><?php echo app_lang('licitaia_imported'); ?></div>
                                <div class="font-24 fw-bold"><?php echo (int) get_array_value($search_result['summary'], 'imported', 0); ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border h-100">
                            <div class="card-body">
                                <div class="text-muted"><?php echo app_lang('licitaia_search_ignored'); ?></div>
                                <div class="font-24 fw-bold"><?php echo (int) get_array_value($search_result['summary'], 'ignored', 0); ?></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table id="licitaia-pncp-table" class="display" cellspacing="0" width="100%"></table>
                </div>
            </div>
        </div>
        <?php echo form_close(); ?>
    <?php } elseif ($search_result && array_key_exists('results', $search_result)) { ?>
        <div class="card mt-3">
            <div class="card-body text-muted">
                <?php echo app_lang('licitaia_empty_state'); ?>
            </div>
        </div>
    <?php } ?>
</div>

<script type="text/javascript">
    $(document).ready(function () {
        <?php if (!empty($debug_urls)) { ?>
        console.log('LicitaIA PNCP search URLs:', <?php echo json_encode(array_values($debug_urls), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>);
        <?php } ?>

        $(".select2").select2();
        $(".datepicker").datepicker({
            format: getJsDateFormat(),
            autoclose: true,
            clearBtn: true
        });

        $("#licitaia-pncp-table").appTable({
            source: '<?php echo_uri("licitaia/search/list_data") ?>',
            serverSide: true,
            order: [[1, "asc"]],
            columns: [
                {title: "<input type='checkbox' id='licitaia-select-all' class='form-check-input' />", "class": "w50 text-center"},
                {title: "<?php echo app_lang('title'); ?>", "class": "all", order_by: "title"},
                {title: "<?php echo app_lang('licitaia_public_body'); ?>", order_by: "public_agency"},
                {title: "<?php echo app_lang('licitaia_edital_number'); ?>", order_by: "notice_number"},
                {title: "<?php echo app_lang('licitaia_process_number'); ?>", order_by: "process_number"},
                {title: "<?php echo app_lang('licitaia_modality'); ?>", order_by: "modality"},
                {title: "<?php echo app_lang('licitaia_source_state'); ?>", order_by: "state"},
                {title: "<?php echo app_lang('licitaia_opening_date'); ?>", order_by: "opening_date"},
                {title: "<?php echo app_lang('licitaia_deadline'); ?>", order_by: "submission_deadline"},
                {title: "<?php echo app_lang('status'); ?>", order_by: "status"},
                {title: "<?php echo app_lang('licitaia_source_url'); ?>", "class": "w100"}
            ]
        });

        $(document).on("change", "#licitaia-select-all", function () {
            $(".licitaia-select-item").prop("checked", $(this).is(":checked"));
        });
    });
</script>
