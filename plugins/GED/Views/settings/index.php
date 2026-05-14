<?php
$settings = is_array($settings ?? null) ? $settings : array();
$status_options = is_array($status_options ?? null) ? $status_options : array();
$portal_status_options = is_array($portal_status_options ?? null) ? $portal_status_options : array();
$can_manage = !empty($can_manage);

$alert_days = get_array_value($settings, 'alert_days') ?: '30,15,7,0';
$enable_native_notifications = !empty(get_array_value($settings, 'enable_native_notifications'));
$notify_admins = !empty(get_array_value($settings, 'notify_admins'));
$notify_document_creator = !empty(get_array_value($settings, 'notify_document_creator'));
$upload_max_size_mb = (int) (get_array_value($settings, 'upload_max_size_mb') ?: 20);
$allowed_file_extensions = get_array_value($settings, 'allowed_file_extensions') ?: 'pdf,jpg,jpeg,png,doc,docx';
$default_document_status = get_array_value($settings, 'default_document_status') ?: 'pending';
$default_submission_status = get_array_value($settings, 'default_submission_status') ?: 'pending';
?>

<div id="page-content" class="page-wrapper clearfix">
    <div class="card">
        <div class="card-header">
            <div class="d-flex align-items-center justify-content-between">
                <h4 class="mb-0"><?php echo app_lang('ged_settings'); ?></h4>
                <?php if ($can_manage): ?>
                    <button type="button" class="btn btn-primary" id="ged-settings-save">
                        <i data-feather="save" class="icon-16 me-1"></i> <?php echo app_lang('save'); ?>
                    </button>
                <?php endif; ?>
            </div>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-lg-8">
                    <?php echo form_open(get_uri('ged/settings/save'), array('id' => 'ged-settings-form', 'class' => 'general-form', 'role' => 'form')); ?>
                        <div class="mb-3">
                            <label class="form-label" for="alert_days">Dias de alerta de vencimento</label>
                            <input type="text" id="alert_days" name="alert_days" value="<?php echo esc($alert_days); ?>" class="form-control" placeholder="30,15,7,0">
                            <small class="text-muted">Use valores separados por vírgula. Exemplo: 30,15,7,0</small>
                        </div>

                        <div class="mb-3 form-check">
                            <?php echo form_checkbox('enable_native_notifications', '1', $enable_native_notifications, "id='enable_native_notifications' class='form-check-input'"); ?>
                            <label class="form-check-label" for="enable_native_notifications">Ativar notificações nativas</label>
                        </div>

                        <div class="mb-3 form-check">
                            <?php echo form_checkbox('notify_admins', '1', $notify_admins, "id='notify_admins' class='form-check-input'"); ?>
                            <label class="form-check-label" for="notify_admins">Notificar administradores</label>
                        </div>

                        <div class="mb-3 form-check">
                            <?php echo form_checkbox('notify_document_creator', '1', $notify_document_creator, "id='notify_document_creator' class='form-check-input'"); ?>
                            <label class="form-check-label" for="notify_document_creator">Notificar criador do documento</label>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="upload_max_size_mb">Tamanho máximo de upload (MB)</label>
                            <input type="number" id="upload_max_size_mb" name="upload_max_size_mb" min="1" step="1" value="<?php echo (int) $upload_max_size_mb; ?>" class="form-control">
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="allowed_file_extensions">Extensões permitidas</label>
                            <input type="text" id="allowed_file_extensions" name="allowed_file_extensions" value="<?php echo esc($allowed_file_extensions); ?>" class="form-control" placeholder="pdf,jpg,jpeg,png,doc,docx">
                            <small class="text-muted">Separe por vírgula, sem ponto. Exemplo: pdf,jpg,jpeg,png,doc,docx</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="default_document_status">Status padrão de novo documento</label>
                            <?php
                            echo form_dropdown('default_document_status', $status_options, $default_document_status, "id='default_document_status' class='form-control select2'");
                            ?>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="default_submission_status">Status padrão de novo envio para portal</label>
                            <?php
                            echo form_dropdown('default_submission_status', $portal_status_options, $default_submission_status, "id='default_submission_status' class='form-control select2'");
                            ?>
                        </div>
                    <?php echo form_close(); ?>
                </div>

                <div class="col-lg-4">
                    <div class="border rounded p-3 bg-light">
                        <h6 class="mb-3">Resumo das regras</h6>
                        <ul class="mb-0 ps-3">
                            <li>Os alertas usam a tabela <code>ged_settings</code>.</li>
                            <li>As notificações nativas respeitam os dias configurados acima.</li>
                            <li>O upload é validado por tamanho e extensões permitidas.</li>
                            <li>Os status padrão entram como valor inicial no cadastro.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(function () {
        $("#ged-settings-form .select2").select2();

        $("#ged-settings-save").on("click", function () {
            $("#ged-settings-form").trigger("submit");
        });

        $("#ged-settings-form").appForm({
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
