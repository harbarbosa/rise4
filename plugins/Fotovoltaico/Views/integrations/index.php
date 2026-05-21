<?php
$configuration = $configuration ?: array();
$providers = $providers ?: array();
?>

<div id="page-content" class="page-wrapper clearfix">
    <div class="page-title clearfix">
        <h4 class="float-start mb-0"><?php echo app_lang('fotovoltaico_integrations'); ?></h4>
        <div class="title-button-group float-end">
            <?php if ($can_view_integrations) { ?>
                <?php echo anchor(get_uri('fotovoltaico/belenus/settings'), "<i data-feather='shuffle' class='icon-16'></i> " . app_lang('fotovoltaico_belenus'), array('class' => 'btn btn-default')); ?>
            <?php } ?>
            <?php if ($can_manage_integrations) { ?>
                <button type="button" class="btn btn-primary" id="save-integrations-btn">
                    <i data-feather="save" class="icon-16"></i> <?php echo app_lang('save'); ?>
                </button>
            <?php } ?>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-8">
            <div class="card mb20">
                <div class="card-body">
                    <h5 class="mb15"><?php echo app_lang('fotovoltaico_integrations_description'); ?></h5>
                    <?php echo form_open('', array('id' => 'supplier-integrations-form', 'class' => 'general-form')); ?>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label"><?php echo app_lang('provider'); ?></label>
                                <?php
                                $provider_options = array();
                                foreach ($providers as $provider) {
                                    $provider_options[$provider['key']] = $provider['label'];
                                }
                                echo form_dropdown('provider_key', $provider_options, get_array_value($configuration, 'provider_key') ?: 'mock', 'class="form-select"');
                                ?>
                            </div>
                            <div class="col-md-8">
                                <label class="form-label">Base URL</label>
                                <?php echo form_input(array('name' => 'base_url', 'value' => get_array_value($configuration, 'base_url'), 'class' => 'form-control')); ?>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Auth Type</label>
                                <?php echo form_dropdown('auth_type', array('bearer' => 'Bearer', 'basic' => 'Basic', 'api_key' => 'API Key'), get_array_value($configuration, 'auth_type') ?: 'bearer', 'class="form-select"'); ?>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Token / API Key</label>
                                <?php echo form_password(array('name' => 'token', 'value' => get_array_value($configuration, 'token'), 'class' => 'form-control')); ?>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Username</label>
                                <?php echo form_input(array('name' => 'username', 'value' => get_array_value($configuration, 'username'), 'class' => 'form-control')); ?>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Password</label>
                                <?php echo form_password(array('name' => 'password', 'value' => get_array_value($configuration, 'password'), 'class' => 'form-control')); ?>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Healthcheck Endpoint</label>
                                <?php echo form_input(array('name' => 'healthcheck_endpoint', 'value' => get_array_value($configuration, 'healthcheck_endpoint'), 'class' => 'form-control')); ?>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Products Endpoint</label>
                                <?php echo form_input(array('name' => 'products_endpoint', 'value' => get_array_value($configuration, 'products_endpoint'), 'class' => 'form-control')); ?>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Kits Endpoint</label>
                                <?php echo form_input(array('name' => 'kits_endpoint', 'value' => get_array_value($configuration, 'kits_endpoint'), 'class' => 'form-control')); ?>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Freight Endpoint</label>
                                <?php echo form_input(array('name' => 'freight_endpoint', 'value' => get_array_value($configuration, 'freight_endpoint'), 'class' => 'form-control')); ?>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Quote Endpoint</label>
                                <?php echo form_input(array('name' => 'quote_endpoint', 'value' => get_array_value($configuration, 'quote_endpoint'), 'class' => 'form-control')); ?>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Timeout (s)</label>
                                <?php echo form_input(array('name' => 'timeout_seconds', 'value' => get_array_value($configuration, 'timeout_seconds') ?: 20, 'class' => 'form-control')); ?>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Cache TTL (s)</label>
                                <?php echo form_input(array('name' => 'cache_ttl_seconds', 'value' => get_array_value($configuration, 'cache_ttl_seconds') ?: 300, 'class' => 'form-control')); ?>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Notes</label>
                                <?php echo form_textarea(array('name' => 'notes', 'value' => get_array_value($configuration, 'notes'), 'class' => 'form-control', 'rows' => 3)); ?>
                            </div>
                        </div>
                    <?php echo form_close(); ?>
                </div>
            </div>

            <div class="card mb20">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb15">
                        <h5 class="mb0"><?php echo app_lang('fotovoltaico_integrations'); ?> - Logs</h5>
                        <button type="button" class="btn btn-default btn-sm" id="reload-logs-btn">
                            <i data-feather="refresh-cw" class="icon-16"></i> <?php echo app_lang('refresh'); ?>
                        </button>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="supplier-integration-logs-table">
                            <thead>
                                <tr>
                                    <th><?php echo app_lang('date'); ?></th>
                                    <th><?php echo app_lang('provider'); ?></th>
                                    <th>Endpoint</th>
                                    <th>Method</th>
                                    <th><?php echo app_lang('status'); ?></th>
                                    <th>Cache</th>
                                    <th>OK</th>
                                    <th><?php echo app_lang('error'); ?></th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card mb20">
                <div class="card-body">
                    <h5 class="mb15"><?php echo app_lang('fotovoltaico_integrations'); ?> - Teste</h5>
                    <div class="form-group mb15">
                        <label class="form-label"><?php echo app_lang('provider'); ?></label>
                        <?php echo form_dropdown('test_provider_key', $provider_options, get_array_value($configuration, 'provider_key') ?: 'mock', 'class="form-select" id="test_provider_key"'); ?>
                    </div>
                    <button type="button" class="btn btn-default w100p mb10" id="test-connection-btn">
                        <i data-feather="zap" class="icon-16"></i> <?php echo app_lang('fotovoltaico_integrations'); ?>
                    </button>
                    <button type="button" class="btn btn-primary w100p" id="get-quote-btn">
                        <i data-feather="shopping-cart" class="icon-16"></i> Get Quote
                    </button>
                    <div class="mt15 small text-off" id="integrations-result-box"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(function () {
        var logsTable = $("#supplier-integration-logs-table");

        function loadLogs() {
            appLoader.show();
            $.ajax({
                url: "<?php echo get_uri('fotovoltaico/integrations/logs_list'); ?>",
                type: "POST",
                dataType: "json",
                data: {
                    provider: $("#test_provider_key").val()
                },
                success: function (response) {
                    var rows = [];
                    if (response && response.data) {
                        $.each(response.data, function (_, row) {
                            rows.push("<tr>" +
                                "<td>" + row[0] + "</td>" +
                                "<td>" + row[1] + "</td>" +
                                "<td>" + row[2] + "</td>" +
                                "<td>" + row[3] + "</td>" +
                                "<td>" + row[4] + "</td>" +
                                "<td>" + row[5] + "</td>" +
                                "<td>" + row[6] + "</td>" +
                                "<td>" + row[7] + "</td>" +
                            "</tr>");
                        });
                    }
                    logsTable.find("tbody").html(rows.join(""));
                },
                complete: function () {
                    appLoader.hide();
                }
            });
        }

        $("#save-integrations-btn").on("click", function () {
            $.ajax({
                url: "<?php echo get_uri('fotovoltaico/integrations/save_settings'); ?>",
                type: "POST",
                dataType: "json",
                data: $("#supplier-integrations-form").serialize(),
                success: function (response) {
                    if (response && response.success) {
                        appAlert.success(response.message);
                    } else {
                        appAlert.error((response && response.message) ? response.message : "<?php echo app_lang('error_occurred'); ?>");
                    }
                }
            });
        });

        $("#test-connection-btn").on("click", function () {
            $.ajax({
                url: "<?php echo get_uri('fotovoltaico/integrations/test_connection'); ?>",
                type: "POST",
                dataType: "json",
                data: {
                    provider_key: $("#test_provider_key").val()
                },
                success: function (response) {
                    $("#integrations-result-box").html("<pre class='mb0'>" + JSON.stringify(response, null, 2) + "</pre>");
                    loadLogs();
                }
            });
        });

        $("#get-quote-btn").on("click", function () {
            $.ajax({
                url: "<?php echo get_uri('fotovoltaico/integrations/get_quote'); ?>",
                type: "POST",
                dataType: "json",
                data: {
                    provider_key: $("#test_provider_key").val(),
                    items_json: JSON.stringify([{ quantity: 1, unit_price: 1000 }, { quantity: 2, unit_price: 500 }])
                },
                success: function (response) {
                    $("#integrations-result-box").html("<pre class='mb0'>" + JSON.stringify(response, null, 2) + "</pre>");
                    loadLogs();
                }
            });
        });

        $("#reload-logs-btn").on("click", function () {
            loadLogs();
        });

        loadLogs();
    });
</script>
