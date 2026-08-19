<?php
$settings = is_array($settings ?? null) ? $settings : array();
?>

<div id="page-content" class="page-wrapper clearfix">
    <div class="card">
        <div class="page-title clearfix">
            <h1><?php echo app_lang('laudostecnicos_settings_title'); ?></h1>
            <div class="title-button-group">
                <?php if (!empty($can_manage_settings)) { ?>
                    <button type="button" class="btn btn-primary" id="laudostecnicos-settings-save">
                        <i data-feather="save" class="icon-16"></i> <?php echo app_lang('save'); ?>
                    </button>
                <?php } ?>
            </div>
        </div>

        <div class="card-body">
            <?php echo form_open(get_uri('laudostecnicos/configuracoes/save'), array('id' => 'laudostecnicos-settings-form', 'class' => 'general-form', 'role' => 'form')); ?>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="module_name"><?php echo app_lang('laudostecnicos_module_name'); ?></label>
                        <input type="text" id="module_name" name="module_name" value="<?php echo esc(get_array_value($settings, 'module_name')); ?>" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="laudo_prefix"><?php echo app_lang('laudostecnicos_laudo_prefix'); ?></label>
                        <input type="text" id="laudo_prefix" name="laudo_prefix" value="<?php echo esc(get_array_value($settings, 'laudo_prefix')); ?>" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="numbering_format"><?php echo app_lang('laudostecnicos_numbering_format'); ?></label>
                        <input type="text" id="numbering_format" name="numbering_format" value="<?php echo esc(get_array_value($settings, 'numbering_format')); ?>" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="next_number"><?php echo app_lang('laudostecnicos_next_number'); ?></label>
                        <input type="number" id="next_number" name="next_number" min="1" step="1" value="<?php echo esc(get_array_value($settings, 'next_number')); ?>" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="sequence_padding">Tamanho do sequencial</label>
                        <input type="number" id="sequence_padding" name="sequence_padding" min="1" max="12" step="1" value="<?php echo esc(get_array_value($settings, 'sequence_padding')); ?>" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="logo_path"><?php echo app_lang('laudostecnicos_logo_path'); ?></label>
                        <input type="text" id="logo_path" name="logo_path" value="<?php echo esc(get_array_value($settings, 'logo_path')); ?>" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="main_color"><?php echo app_lang('laudostecnicos_main_color'); ?></label>
                        <input type="text" id="main_color" name="main_color" value="<?php echo esc(get_array_value($settings, 'main_color')); ?>" class="form-control" placeholder="#0d6efd">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="pdf_font_family">Fonte PDF</label>
                        <input type="text" id="pdf_font_family" name="pdf_font_family" value="<?php echo esc(get_array_value($settings, 'pdf_font_family')); ?>" class="form-control" placeholder="helvetica">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label" for="pdf_margin_top">Margem topo</label>
                        <input type="number" id="pdf_margin_top" name="pdf_margin_top" min="0" step="1" value="<?php echo esc(get_array_value($settings, 'pdf_margin_top')); ?>" class="form-control">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label" for="pdf_margin_bottom">Margem base</label>
                        <input type="number" id="pdf_margin_bottom" name="pdf_margin_bottom" min="0" step="1" value="<?php echo esc(get_array_value($settings, 'pdf_margin_bottom')); ?>" class="form-control">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label" for="pdf_margin_left">Margem esquerda</label>
                        <input type="number" id="pdf_margin_left" name="pdf_margin_left" min="0" step="1" value="<?php echo esc(get_array_value($settings, 'pdf_margin_left')); ?>" class="form-control">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label" for="pdf_margin_right">Margem direita</label>
                        <input type="number" id="pdf_margin_right" name="pdf_margin_right" min="0" step="1" value="<?php echo esc(get_array_value($settings, 'pdf_margin_right')); ?>" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="pdf_header_text">Cabecalho PDF</label>
                        <input type="text" id="pdf_header_text" name="pdf_header_text" value="<?php echo esc(get_array_value($settings, 'pdf_header_text')); ?>" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="pdf_footer_text">Rodape PDF</label>
                        <input type="text" id="pdf_footer_text" name="pdf_footer_text" value="<?php echo esc(get_array_value($settings, 'pdf_footer_text')); ?>" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="pdf_watermark_text">Marca d'agua</label>
                        <input type="text" id="pdf_watermark_text" name="pdf_watermark_text" value="<?php echo esc(get_array_value($settings, 'pdf_watermark_text')); ?>" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="pdf_confidentiality_text">Texto de confidencialidade</label>
                        <input type="text" id="pdf_confidentiality_text" name="pdf_confidentiality_text" value="<?php echo esc(get_array_value($settings, 'pdf_confidentiality_text')); ?>" class="form-control">
                    </div>
                    <div class="col-md-3 form-check mt-4">
                        <?php echo form_checkbox('pdf_cover_enabled', '1', get_array_value($settings, 'pdf_cover_enabled') === '1', "id='pdf_cover_enabled' class='form-check-input'"); ?>
                        <label class="form-check-label" for="pdf_cover_enabled">Ativar capa</label>
                    </div>
                    <div class="col-md-3 form-check mt-4">
                        <?php echo form_checkbox('pdf_enable_qr', '1', get_array_value($settings, 'pdf_enable_qr') === '1', "id='pdf_enable_qr' class='form-check-input'"); ?>
                        <label class="form-check-label" for="pdf_enable_qr">Ativar QR Code</label>
                    </div>
                    <div class="col-md-3 form-check mt-4">
                        <?php echo form_checkbox('portal_enabled', '1', get_array_value($settings, 'portal_enabled') === '1', "id='portal_enabled' class='form-check-input'"); ?>
                        <label class="form-check-label" for="portal_enabled">Portal do cliente</label>
                    </div>
                    <div class="col-md-3 form-check mt-4">
                        <?php echo form_checkbox('public_validation_enabled', '1', get_array_value($settings, 'public_validation_enabled') === '1', "id='public_validation_enabled' class='form-check-input'"); ?>
                        <label class="form-check-label" for="public_validation_enabled">Validacao publica</label>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="pdf_paper">Papel</label>
                        <input type="text" id="pdf_paper" name="pdf_paper" value="<?php echo esc(get_array_value($settings, 'pdf_paper')); ?>" class="form-control" placeholder="A4">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="pdf_orientation">Orientacao</label>
                        <input type="text" id="pdf_orientation" name="pdf_orientation" value="<?php echo esc(get_array_value($settings, 'pdf_orientation')); ?>" class="form-control" placeholder="P">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="default_document_variant">Variacao padrao do documento</label>
                        <input type="text" id="default_document_variant" name="default_document_variant" value="<?php echo esc(get_array_value($settings, 'default_document_variant')); ?>" class="form-control" placeholder="full">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="timezone"><?php echo app_lang('laudostecnicos_timezone'); ?></label>
                        <input type="text" id="timezone" name="timezone" value="<?php echo esc(get_array_value($settings, 'timezone')); ?>" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="language"><?php echo app_lang('laudostecnicos_language'); ?></label>
                        <input type="text" id="language" name="language" value="<?php echo esc(get_array_value($settings, 'language')); ?>" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="date_format"><?php echo app_lang('laudostecnicos_date_format'); ?></label>
                        <input type="text" id="date_format" name="date_format" value="<?php echo esc(get_array_value($settings, 'date_format')); ?>" class="form-control">
                    </div>
                    <div class="col-md-4 form-check mt-4">
                        <?php echo form_checkbox('module_enabled', '1', get_array_value($settings, 'module_enabled') === '1', "id='module_enabled' class='form-check-input'"); ?>
                        <label class="form-check-label" for="module_enabled"><?php echo app_lang('laudostecnicos_module_enabled'); ?></label>
                    </div>
                    <div class="col-md-4 form-check mt-4">
                        <?php echo form_checkbox('detailed_logs', '1', get_array_value($settings, 'detailed_logs') === '1', "id='detailed_logs' class='form-check-input'"); ?>
                        <label class="form-check-label" for="detailed_logs"><?php echo app_lang('laudostecnicos_detailed_logs'); ?></label>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="default_status"><?php echo app_lang('laudostecnicos_default_status'); ?></label>
                        <input type="text" id="default_status" name="default_status" value="<?php echo esc(get_array_value($settings, 'default_status')); ?>" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="default_priority"><?php echo app_lang('laudostecnicos_default_priority'); ?></label>
                        <input type="text" id="default_priority" name="default_priority" value="<?php echo esc(get_array_value($settings, 'default_priority')); ?>" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="default_template_id"><?php echo app_lang('laudostecnicos_default_template_id'); ?></label>
                        <input type="number" id="default_template_id" name="default_template_id" min="0" step="1" value="<?php echo esc(get_array_value($settings, 'default_template_id')); ?>" class="form-control">
                    </div>
                    <div class="col-md-3 form-check mt-4">
                        <?php echo form_checkbox('api_require_https', '1', get_array_value($settings, 'api_require_https') === '1', "id='api_require_https' class='form-check-input'"); ?>
                        <label class="form-check-label" for="api_require_https">Exigir HTTPS na API</label>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="api_rate_limit_per_minute">Rate limit / min</label>
                        <input type="number" id="api_rate_limit_per_minute" name="api_rate_limit_per_minute" min="1" step="1" value="<?php echo esc(get_array_value($settings, 'api_rate_limit_per_minute')); ?>" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="api_access_token_lifetime">Token acesso (s)</label>
                        <input type="number" id="api_access_token_lifetime" name="api_access_token_lifetime" min="600" step="1" value="<?php echo esc(get_array_value($settings, 'api_access_token_lifetime')); ?>" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="api_refresh_token_lifetime">Token refresh (s)</label>
                        <input type="number" id="api_refresh_token_lifetime" name="api_refresh_token_lifetime" min="600" step="1" value="<?php echo esc(get_array_value($settings, 'api_refresh_token_lifetime')); ?>" class="form-control">
                    </div>
                    <div class="col-md-12">
                        <label class="form-label" for="api_cors_origins">CORS origins</label>
                        <textarea id="api_cors_origins" name="api_cors_origins" class="form-control" rows="2"><?php echo esc(get_array_value($settings, 'api_cors_origins')); ?></textarea>
                    </div>
                </div>
            <?php echo form_close(); ?>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(function () {
        $("#laudostecnicos-settings-save").on("click", function () {
            $("#laudostecnicos-settings-form").trigger("submit");
        });

        $("#laudostecnicos-settings-form").appForm({
            onSuccess: function (result) {
                if (result && result.success) {
                    appAlert.success(result.message || "<?php echo app_lang('record_saved'); ?>");
                } else {
                    appAlert.error((result && result.message) ? result.message : "<?php echo app_lang('error_occurred'); ?>");
                }
            }
        });
    });
</script>
