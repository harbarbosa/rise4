<?php
$configuration = $configuration ?: array();
?>

<div id="page-content" class="page-wrapper clearfix">
    <div class="page-title clearfix">
        <h4 class="float-start mb-0"><?php echo app_lang('fotovoltaico_belenus_settings'); ?></h4>
        <div class="title-button-group float-end">
            <?php if ($can_manage_belenus) { ?>
                <button type="button" class="btn btn-primary" id="save-belenus-settings-btn"><?php echo app_lang('save'); ?></button>
                <button type="button" class="btn btn-default" id="test-belenus-btn"><?php echo app_lang('fotovoltaico_belenus_test_connection'); ?></button>
                <button type="button" class="btn btn-default" id="clear-belenus-cache-btn"><?php echo app_lang('fotovoltaico_belenus_clear_cache'); ?></button>
                <button type="button" class="btn btn-default" id="sync-belenus-products-btn"><?php echo app_lang('fotovoltaico_belenus_sync_products'); ?></button>
                <button type="button" class="btn btn-default" id="sync-belenus-kits-btn"><?php echo app_lang('fotovoltaico_belenus_sync_kits'); ?></button>
            <?php } ?>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card mb20">
                <div class="card-body">
                    <?php echo form_open('', array('id' => 'belenus-settings-form', 'class' => 'general-form')); ?>
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label">Base URL</label>
                            <?php echo form_input(array('name' => 'base_url', 'value' => get_array_value($configuration, 'base_url') ?: 'https://belenus.com.br/api', 'class' => 'form-control')); ?>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">E-mail da API</label>
                            <?php echo form_input(array('name' => 'api_email', 'value' => get_array_value($configuration, 'api_email'), 'class' => 'form-control')); ?>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Senha da API</label>
                            <?php echo form_password(array('name' => 'api_password', 'value' => '', 'class' => 'form-control', 'autocomplete' => 'new-password')); ?>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">TTL token (s)</label>
                            <?php echo form_input(array('name' => 'token_ttl_seconds', 'value' => get_array_value($configuration, 'token_ttl_seconds') ?: 3600, 'class' => 'form-control')); ?>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">TTL produtos (s)</label>
                            <?php echo form_input(array('name' => 'products_cache_ttl_seconds', 'value' => get_array_value($configuration, 'products_cache_ttl_seconds') ?: 900, 'class' => 'form-control')); ?>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">TTL preços (s)</label>
                            <?php echo form_input(array('name' => 'price_cache_ttl_seconds', 'value' => get_array_value($configuration, 'price_cache_ttl_seconds') ?: 300, 'class' => 'form-control')); ?>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">TTL kits (s)</label>
                            <?php echo form_input(array('name' => 'kits_cache_ttl_seconds', 'value' => get_array_value($configuration, 'kits_cache_ttl_seconds') ?: 900, 'class' => 'form-control')); ?>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Timeout (s)</label>
                            <?php echo form_input(array('name' => 'timeout_seconds', 'value' => get_array_value($configuration, 'timeout_seconds') ?: 20, 'class' => 'form-control')); ?>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Status</label>
                            <div class="form-check mt-2">
                                <?php echo form_checkbox('active', '1', (int) get_array_value($configuration, 'active') ? true : false, "id='belenus-active' class='form-check-input'"); ?>
                                <label for="belenus-active" class="form-check-label">Ativo</label>
                            </div>
                        </div>
                    </div>
                    <?php echo form_close(); ?>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb15">
                        <h5 class="mb0"><?php echo app_lang('fotovoltaico_belenus_description'); ?></h5>
                        <div class="btn-group">
                            <a class="btn btn-default" href="<?php echo get_uri('fotovoltaico/belenus/products'); ?>"><?php echo app_lang('fotovoltaico_belenus_products'); ?></a>
                            <a class="btn btn-default" href="<?php echo get_uri('fotovoltaico/belenus/kits'); ?>"><?php echo app_lang('fotovoltaico_belenus_kits'); ?></a>
                            <a class="btn btn-default" href="<?php echo get_uri('fotovoltaico/belenus/logs'); ?>"><?php echo app_lang('fotovoltaico_belenus_logs'); ?></a>
                        </div>
                    </div>
                    <div class="text-muted">
                        <p class="mb-2">1. Configure a API e teste a conexão.</p>
                        <p class="mb-2">2. Sincronize produtos e kits pelas telas dedicadas.</p>
                        <p class="mb-0">3. Os itens importados ficam disponíveis no catálogo local do Fotovoltaico.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
$(function () {
    $("#save-belenus-settings-btn").on("click", function () {
        $.ajax({
            url: "<?php echo get_uri('fotovoltaico/belenus/save_settings'); ?>",
            type: "POST",
            dataType: "json",
            data: $("#belenus-settings-form").serialize(),
            success: function (response) {
                if (response && response.success) {
                    appAlert.success(response.message);
                } else {
                    appAlert.error((response && response.message) ? response.message : "<?php echo app_lang('error_occurred'); ?>");
                }
            }
        });
    });

    $("#test-belenus-btn").on("click", function () {
        $.ajax({
            url: "<?php echo get_uri('fotovoltaico/belenus/test_connection'); ?>",
            type: "POST",
            dataType: "json",
            success: function (response) {
                appAlert.success((response && response.message) ? response.message : "OK");
            },
            error: function () {
                appAlert.error("Falha no teste de conexão.");
            }
        });
    });

    $("#clear-belenus-cache-btn").on("click", function () {
        $.ajax({
            url: "<?php echo get_uri('fotovoltaico/belenus/cache/clear'); ?>",
            type: "POST",
            dataType: "json",
            success: function (response) {
                if (response && response.success) {
                    appAlert.success(response.message);
                } else {
                    appAlert.error((response && response.message) ? response.message : "<?php echo app_lang('error_occurred'); ?>");
                }
            }
        });
    });

    $("#sync-belenus-products-btn").on("click", function () {
        $.ajax({
            url: "<?php echo get_uri('fotovoltaico/belenus/products/sync'); ?>",
            type: "POST",
            dataType: "json",
            data: { pageSize: 50 },
            success: function (response) {
                if (response && response.success) {
                    appAlert.success(response.message || "OK");
                } else {
                    appAlert.error((response && response.message) ? response.message : "<?php echo app_lang('error_occurred'); ?>");
                }
            }
        });
    });

    $("#sync-belenus-kits-btn").on("click", function () {
        $.ajax({
            url: "<?php echo get_uri('fotovoltaico/belenus/kits/sync'); ?>",
            type: "POST",
            dataType: "json",
            data: { pageSize: 25 },
            success: function (response) {
                if (response && response.success) {
                    appAlert.success(response.message || "OK");
                } else {
                    appAlert.error((response && response.message) ? response.message : "<?php echo app_lang('error_occurred'); ?>");
                }
            }
        });
    });
});
</script>
