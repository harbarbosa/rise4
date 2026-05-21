<div id="page-content" class="page-wrapper clearfix">
    <div class="card">
        <div class="page-title clearfix">
            <h4 class="float-start mb-0"><?php echo app_lang('fotovoltaico_distributors'); ?></h4>
            <div class="title-button-group float-end">
                <?php if ($can_manage_distributors) { ?>
                    <button type="button" id="sync-distributors-api" class="btn btn-default">
                        <i data-feather='download-cloud' class='icon-16'></i> <?php echo app_lang('fotovoltaico_import_aneel'); ?>
                    </button>
                    <button type="button" id="reprocess-distributor-eligibility" class="btn btn-default">
                        <i data-feather='filter' class='icon-16'></i> <?php echo app_lang('fotovoltaico_reprocess_eligibility'); ?>
                    </button>
                    <?php echo modal_anchor(get_uri("fotovoltaico/distributors/modal_form"), "<i data-feather='plus-circle' class='icon-16'></i> " . app_lang('fotovoltaico_add_distributor'), array("class" => "btn btn-default", "title" => app_lang('fotovoltaico_add_distributor'))); ?>
                <?php } ?>
            </div>
        </div>
        <div class="table-responsive">
            <table id="distributors-table" class="display" cellspacing="0" width="100%"></table>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function () {
        $("#distributors-table").appTable({
            source: '<?php echo_uri("fotovoltaico/distributors/list_data") ?>',
            filterDropdown: [
                {name: "status", class: "w150", options: [{id: "", text: "-"}, {id: "1", text: "<?php echo app_lang("active"); ?>"}, {id: "0", text: "<?php echo app_lang("inactive"); ?>"}]},
                {name: "source", class: "w150", options: [{id: "", text: "-"}, {id: "manual", text: "<?php echo app_lang("fotovoltaico_source_manual"); ?>"}, {id: "aneel", text: "<?php echo app_lang("fotovoltaico_source_aneel"); ?>"}]},
                {name: "show_in_registration", class: "w170", options: [{id: "", text: "-"}, {id: "1", text: "<?php echo app_lang("fotovoltaico_show_in_registration"); ?>"}, {id: "0", text: "<?php echo app_lang("fotovoltaico_hidden_in_registration"); ?>"}]}
            ],
            columns: [
                {title: "<?php echo app_lang('fotovoltaico_distributor_name') ?>"},
                {title: "<?php echo app_lang('fotovoltaico_acronym') ?>", "class": "w100"},
                {title: "<?php echo app_lang('fotovoltaico_state_code') ?>", "class": "w100"},
                {title: "<?php echo app_lang('fotovoltaico_document') ?>", "class": "w150"},
                {title: "<?php echo app_lang('fotovoltaico_agent_type') ?>", "class": "w120"},
                {title: "<?php echo app_lang('fotovoltaico_source') ?>", "class": "text-center w120"},
                {title: "<?php echo app_lang('fotovoltaico_current_tariff') ?>", "class": "text-center w120"},
                {title: "<?php echo app_lang('fotovoltaico_show_in_registration') ?>", "class": "text-center w130"},
                {title: "<?php echo app_lang('status') ?>", "class": "text-center w100"},
                {title: "<i data-feather='menu' class='icon-16'></i>", "class": "text-center option w100"}
            ]
        });

        $("#sync-distributors-api").on("click", function () {
            var $button = $(this);
            $button.prop("disabled", true);

            $.ajax({
                url: "<?php echo_uri('fotovoltaico/distributors/sync_from_api'); ?>",
                type: "POST",
                dataType: "json",
                success: function (result) {
                    if (result && result.success) {
                        appAlert.success(result.message, {duration: 10000});
                        setTimeout(function () {
                            window.location.reload();
                        }, 700);
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

        $("#reprocess-distributor-eligibility").on("click", function () {
            var $button = $(this);
            $button.prop("disabled", true);

            $.ajax({
                url: "<?php echo_uri('fotovoltaico/distributors/reprocess_eligibility'); ?>",
                type: "POST",
                dataType: "json",
                success: function (result) {
                    if (result && result.success) {
                        appAlert.success(result.message, {duration: 10000});
                        $("#distributors-table").appTable({reload: true});
                    } else {
                        appAlert.error(result && result.message ? result.message : "<?php echo app_lang('error_occurred'); ?>", {duration: 10000});
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
