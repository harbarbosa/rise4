<?php
$plugin_request_id = isset($plugin_request_id) ? (int)$plugin_request_id : 0;
$hide_form = isset($hide_form) ? $hide_form : false;
$reminder_id_prefix = "purchase-request-";

if ($hide_form) {
    echo "<a href='javascript:;' id='" . $reminder_id_prefix . "show-add-reminder-form' class='add-reminder-btn'><i data-feather=\"plus\" class=\"icon-16\"></i> " . app_lang("add_reminder") . "</a>";
}
?>

<div id="<?php echo $reminder_id_prefix . 'reminder-form-container'; ?>" class="<?php echo $hide_form ? "hide" : ""; ?>">
    <?php echo form_open(get_uri("events/save"), array("id" => $reminder_id_prefix . "reminder_form", "class" => "general-form", "role" => "form")); ?>
    <input type="hidden" name="type" value="reminder" />
    <input type="hidden" name="plugin_purchase_request_id" value="<?php echo $plugin_request_id; ?>" />
    <div class="form-group">
        <div class="mt5 p0">
            <?php
            echo form_input(array(
                "id" => $reminder_id_prefix . "title",
                "name" => "title",
                "class" => "form-control",
                "placeholder" => app_lang('title'),
                "data-rule-required" => true,
                "data-msg-required" => app_lang("field_required"),
                "autocomplete" => "off"
            ));
            ?>
        </div>
    </div>
    <div class="clearfix">
        <div class="row">
            <div class="col-md-6 col-sm-6 form-group">
                <?php
                echo form_input(array(
                    "id" => $reminder_id_prefix . "start_date",
                    "name" => "start_date",
                    "class" => "form-control",
                    "placeholder" => app_lang('date'),
                    "autocomplete" => "off",
                    "data-rule-required" => true,
                    "data-msg-required" => app_lang("field_required"),
                ));
                ?>
            </div>
            <div class=" col-md-6 col-sm-6 form-group">
                <?php
                echo form_input(array(
                    "id" => $reminder_id_prefix . "start_time",
                    "name" => "start_time",
                    "class" => "form-control",
                    "placeholder" => app_lang('time'),
                    "autocomplete" => "off",
                    "data-rule-required" => true,
                    "data-msg-required" => app_lang("field_required"),
                ));
                ?>
            </div>
        </div>
    </div>

    <div class="form-group">
        <div class="row">
            <label for="<?php echo $reminder_id_prefix; ?>event_recurring" class=" col-md-4 col-xs-5 col-sm-4"><?php echo app_lang('repeat'); ?> <span class="help" data-bs-toggle="tooltip" title="<?php echo app_lang('cron_job_required'); ?>"><i data-feather="help-circle" class="icon-16"></i></span></label>
            <div class=" col-md-8 col-xs-7 col-sm-8">
                <?php
                echo form_checkbox("recurring", "1", false, "id= '$reminder_id_prefix" . "event_recurring' class='form-check-input'");
                ?>
            </div>
        </div>
    </div>

    <div id="<?php echo $reminder_id_prefix; ?>recurring_fields" class="hide">
        <div class="form-group">
            <div class="row">
                <label for="<?php echo $reminder_id_prefix; ?>repeat_every" class=" col-md-3 col-xs-12"><?php echo app_lang('repeat_every'); ?></label>
                <div class="col-md-4 col-xs-6">
                    <?php
                    echo form_input(array(
                        "id" => $reminder_id_prefix . "repeat_every",
                        "name" => "repeat_every",
                        "type" => "number",
                        "value" => 1,
                        "min" => 1,
                        "class" => "form-control recurring_element",
                        "placeholder" => app_lang('repeat_every'),
                        "data-rule-required" => true,
                        "data-msg-required" => app_lang("field_required")
                    ));
                    ?>
                </div>
                <div class="col-md-5 col-xs-6">
                    <?php
                    echo form_dropdown(
                        "repeat_type",
                        array(
                            "days" => app_lang("interval_days"),
                            "weeks" => app_lang("interval_weeks"),
                            "months" => app_lang("interval_months"),
                            "years" => app_lang("interval_years"),
                        ),
                        "days",
                        "class='select2 recurring_element' id='$reminder_id_prefix" . "repeat_type'"
                    );
                    ?>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="<?php echo $reminder_id_prefix; ?>no_of_cycles" class=" col-md-3"><?php echo app_lang('cycles'); ?></label>
                <div class="col-md-4">
                    <?php
                    echo form_input(array(
                        "id" => $reminder_id_prefix . "no_of_cycles",
                        "name" => "no_of_cycles",
                        "type" => "number",
                        "min" => 1,
                        "value" => "",
                        "class" => "form-control",
                        "placeholder" => app_lang('cycles')
                    ));
                    ?>
                </div>
                <div class="col-md-5 mt5">
                    <span class="help" data-bs-toggle="tooltip" title="<?php echo app_lang('recurring_cycle_instructions'); ?>"><i data-feather="help-circle" class="icon-14"></i></span>
                </div>
            </div>
        </div>

    </div>

    <div class="mb20 p0">
        <button type="submit" class="btn btn-primary w100p"><span data-feather="check-circle" class="icon-16"></span> <?php echo app_lang('add'); ?></button>
    </div>

    <?php echo form_close(); ?>

</div>

<div class="table-responsive">
    <table id="<?php echo $reminder_id_prefix . 'reminders-table'; ?>" class="display no-thead b-t b-b-only no-hover" cellspacing="0" width="100%"></table>
</div>

<script type="text/javascript">
    var reminderTableId = "#<?php echo $reminder_id_prefix . 'reminders-table'; ?>";
    var $tableSelector = $(reminderTableId);
    var requestId = "<?php echo $plugin_request_id; ?>";

    $(document).ready(function() {
        initScrollbar('#reminder-modal-body', {
            setHeight: $(window).height() - 139
        });

        loadReminderTable = function(type) {
            type = type || "reminders";

            if (type === "all") {
                $tableSelector.DataTable().destroy();
                $tableSelector = $("#<?php echo $reminder_id_prefix . 'reminders-table'; ?>");
            }

            $tableSelector.appTable({
                source: '<?php echo_uri("purchases_requests/reminders/list_data"); ?>/' + requestId + '/' + type,
                hideTools: true,
                order: [[0, "asc"]],
                displayLength: 100,
                columns: [
                    {visible: false},
                    {title: '<?php echo app_lang("title"); ?>', "class": "reminder-title-section"},
                    {title: '<i data-feather="menu" class="icon-16"></i>', "class": "text-center dropdown-option w35 reminder-option-section"}
                ],
                onInitComplete: function() {
                    appLoader.hide();
                }
            });
        };

        $('body').on('click', "#show-all-reminders-btn", function() {
            loadReminderTable("all");
            appLoader.show({container: reminderTableId, css: "left:0; top:170px"});
            $(this).addClass("disabled");
        });

        loadReminderTable();

        setDatePicker("#<?php echo $reminder_id_prefix; ?>start_date");
        setTimePicker("#<?php echo $reminder_id_prefix; ?>start_time");

        feather.replace();

        $("#<?php echo $reminder_id_prefix . 'reminder_form'; ?>").appForm({
            isModal: false,
            onSuccess: function(result) {
                $tableSelector.appTable({
                    newData: result.data,
                    dataId: result.id
                });

                $("#<?php echo $reminder_id_prefix; ?>title").val("");
                if ($("#<?php echo $reminder_id_prefix; ?>event_recurring").is(":checked")) {
                    $("#<?php echo $reminder_id_prefix; ?>event_recurring").trigger("click");
                }

                $("#<?php echo $reminder_id_prefix; ?>title").focus();
            }
        });

        $("#<?php echo $reminder_id_prefix; ?>event_recurring").click(function() {
            if ($(this).is(":checked")) {
                $("#<?php echo $reminder_id_prefix; ?>recurring_fields").removeClass("hide");
            } else {
                $("#<?php echo $reminder_id_prefix; ?>recurring_fields").addClass("hide");
            }
        });

        $("#<?php echo $reminder_id_prefix . 'show-add-reminder-form'; ?>").click(function() {
            $(this).addClass("hide");
            $("#<?php echo $reminder_id_prefix . 'reminder-form-container'; ?>").removeClass("hide");
        });

        $('[data-bs-toggle="tooltip"]').tooltip();

        $("#<?php echo $reminder_id_prefix . 'reminder_form'; ?> .select2").select2();
    });
</script>
