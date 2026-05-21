<div id="page-content" class="page-wrapper clearfix">
    <div class="card">
        <div class="page-title clearfix">
            <h4 class="float-start mb-0"><?php echo app_lang('fotovoltaico_tariffs'); ?></h4>
            <div class="title-button-group float-end">
                <?php if ($can_manage_tariffs) { ?>
                    <button type="button" id="import-tariffs-aneel" class="btn btn-default">
                        <i data-feather='download-cloud' class='icon-16'></i> <?php echo app_lang('fotovoltaico_import_aneel'); ?>
                    </button>
                    <?php echo modal_anchor(get_uri("fotovoltaico/tariffs/modal_form"), "<i data-feather='plus-circle' class='icon-16'></i> " . app_lang('fotovoltaico_add_tariff'), array("class" => "btn btn-default", "title" => app_lang('fotovoltaico_add_tariff'))); ?>
                <?php } ?>
            </div>
        </div>
        <div class="table-responsive">
            <table id="tariffs-table" class="display" cellspacing="0" width="100%"></table>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function () {
        $("#tariffs-table").appTable({
            source: '<?php echo_uri("fotovoltaico/tariffs/list_data") ?>',
            filterDropdown: [
                {name: "distributor_id", class: "w200", options: <?php echo $distributors_dropdown; ?>},
                {name: "vigency_status", class: "w200", options: <?php echo $vigency_dropdown; ?>},
                {name: "source", class: "w150", options: <?php echo $source_dropdown; ?>}
            ],
            columns: [
                {title: "<?php echo app_lang('fotovoltaico_distributor_name') ?>"},
                {title: "<?php echo app_lang('fotovoltaico_tariff_class') ?>"},
                {title: "<?php echo app_lang('fotovoltaico_tariff_modality') ?>"},
                {title: "<?php echo app_lang('fotovoltaico_tariff_subgroup') ?>"},
                {title: "<?php echo app_lang('fotovoltaico_tariff_time_slot') ?>"},
                {title: "<?php echo app_lang('fotovoltaico_tariff_te') ?>", "class": "text-right w100"},
                {title: "<?php echo app_lang('fotovoltaico_tariff_tusd') ?>", "class": "text-right w100"},
                {title: "<?php echo app_lang('fotovoltaico_tariff_flag') ?>"},
                {title: "<?php echo app_lang('fotovoltaico_tariff_flag_value') ?>", "class": "text-right w100"},
                {title: "<?php echo app_lang('fotovoltaico_valid_from') ?>", "class": "w100"},
                {title: "<?php echo app_lang('fotovoltaico_valid_to') ?>", "class": "w100"},
                {title: "<?php echo app_lang('fotovoltaico_current_tariff') ?>", "class": "text-center w100"},
                {title: "<?php echo app_lang('fotovoltaico_source') ?>", "class": "text-center w100"},
                {title: "<?php echo app_lang('status') ?>", "class": "text-center w100"},
                {title: "<i data-feather='menu' class='icon-16'></i>", "class": "text-center option w100"}
            ]
        });

        $("#import-tariffs-aneel").on("click", function () {
            var $button = $(this);
            $button.prop("disabled", true);

            $.ajax({
                url: "<?php echo_uri('fotovoltaico/tariffs/import_aneel'); ?>",
                type: "POST",
                dataType: "json",
                success: function (result) {
                    if (result && result.success) {
                        appAlert.success(result.message, {duration: 12000});
                        $("#tariffs-table").appTable({reload: true});
                    } else {
                        var errorMessage = result && result.message ? result.message : "<?php echo app_lang('error_occurred'); ?>";
                        if (result && result.errors && result.errors.length) {
                            errorMessage += " (" + result.errors.join(", ") + ")";
                        }
                        appAlert.error(errorMessage, {duration: 12000});
                    }
                },
                error: function () {
                    appAlert.error("<?php echo app_lang('error_occurred'); ?>", {duration: 10000});
                },
                complete: function () {
                    $button.prop("disabled", false);
                }
            });
        });
    });
</script>
