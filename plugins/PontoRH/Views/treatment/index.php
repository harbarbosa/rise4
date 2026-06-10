<?php
$summary = $summary ?? array();
$filters = $filters ?? array();
$team_members_dropdown = $team_members_dropdown ?? array();
$status_dropdown = $status_dropdown ?? array();
$pending_type_dropdown = $pending_type_dropdown ?? array();
$month_dropdown = $month_dropdown ?? array();
$year_dropdown = $year_dropdown ?? array();
?>

<div id="page-content" class="page-wrapper clearfix">
    <div class="card">
        <div class="page-title clearfix">
            <div>
                <h1><?php echo app_lang('pontorh_treatment_dashboard_title'); ?></h1>
                <div class="text-muted"><?php echo app_lang('pontorh_treatment_dashboard_intro'); ?></div>
                <div class="text-muted small mt5"><?php echo esc($dashboard_period ?? ''); ?></div>
            </div>
            <div class="title-button-group">
                <?php echo modal_anchor(get_uri('pontorh/tratamento/modal_form'), "<i data-feather='plus-circle' class='icon-16'></i> " . app_lang('pontorh_add_manual_mark'), array('class' => 'btn btn-primary', 'title' => app_lang('pontorh_add_manual_mark'))); ?>
            </div>
        </div>

        <div class="card-body border-bottom">
            <form method="get" action="<?php echo get_uri('pontorh/tratamento'); ?>" class="general-form">
                <div class="row g-2 align-items-end">
                    <div class="col-md-3 col-sm-6">
                        <label class="form-label"><?php echo app_lang('start_date'); ?></label>
                        <?php echo form_input(array('name' => 'date_from', 'id' => 'pontorh-treatment-date-from', 'class' => 'form-control datepicker', 'autocomplete' => 'off', 'value' => $filters['date_from'] ?? get_my_local_time('Y-m-01'))); ?>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <label class="form-label"><?php echo app_lang('end_date'); ?></label>
                        <?php echo form_input(array('name' => 'date_to', 'id' => 'pontorh-treatment-date-to', 'class' => 'form-control datepicker', 'autocomplete' => 'off', 'value' => $filters['date_to'] ?? get_my_local_time('Y-m-t'))); ?>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <label class="form-label"><?php echo app_lang('pontorh_employee'); ?></label>
                        <?php echo form_dropdown('team_member_id', $team_members_dropdown, $filters['team_member_id'] ?? '', 'class="form-control select2" id="pontorh-treatment-team-member"'); ?>
                    </div>
                    <div class="col-md-2 col-sm-6">
                        <label class="form-label"><?php echo app_lang('pontorh_status'); ?></label>
                        <?php echo form_dropdown('status', $status_dropdown, $filters['status'] ?? '', 'class="form-control select2" id="pontorh-treatment-status"'); ?>
                    </div>
                    <div class="col-md-2 col-sm-6">
                        <label class="form-label"><?php echo app_lang('pontorh_type'); ?></label>
                        <?php echo form_dropdown('pending_type', $pending_type_dropdown, $filters['pending_type'] ?? '', 'class="form-control select2" id="pontorh-treatment-pending-type"'); ?>
                    </div>
                    <div class="col-md-12">
                        <button type="submit" class="btn btn-primary btn-sm"><?php echo app_lang('filter'); ?></button>
                        <a href="<?php echo get_uri('pontorh/tratamento'); ?>" class="btn btn-default btn-sm"><?php echo app_lang('clear'); ?></a>
                    </div>
                </div>
            </form>
        </div>

        <div class="card-body">
            <div class="row g-3 mb-4">
                <div class="col-lg-3 col-md-6">
                    <div class="card h-100"><div class="card-body"><div class="text-muted"><?php echo app_lang('pontorh_treatment_status_incomplete'); ?></div><div class="font-26 fw-bold"><?php echo (int) get_array_value($summary, 'incomplete_days', 0); ?></div></div></div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="card h-100"><div class="card-body"><div class="text-muted"><?php echo app_lang('pontorh_treatment_status_inconsistent'); ?></div><div class="font-26 fw-bold"><?php echo (int) get_array_value($summary, 'inconsistent_days', 0); ?></div></div></div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="card h-100"><div class="card-body"><div class="text-muted"><?php echo app_lang('pontorh_treatment_status_outside_area'); ?></div><div class="font-26 fw-bold"><?php echo (int) get_array_value($summary, 'outside_area', 0); ?></div></div></div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="card h-100"><div class="card-body"><div class="text-muted"><?php echo app_lang('pontorh_treatment_status_awaiting_justification'); ?></div><div class="font-26 fw-bold"><?php echo (int) get_array_value($summary, 'awaiting_justification', 0); ?></div></div></div>
                </div>
            </div>

            <div class="table-responsive">
                <table id="pontorh-treatment-table" class="display" cellspacing="0" width="100%"></table>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function () {
        $(".page-wrapper .select2").select2();
        setDatePicker("#pontorh-treatment-date-from, #pontorh-treatment-date-to");

        $("#pontorh-treatment-table").appTable({
            source: "<?php echo_uri('pontorh/tratamento/list_data'); ?>",
            filterParams: $.extend({datatable: true}, {
                date_from: $("#pontorh-treatment-date-from").val(),
                date_to: $("#pontorh-treatment-date-to").val(),
                team_member_id: $("#pontorh-treatment-team-member").val(),
                status: $("#pontorh-treatment-status").val(),
                pending_type: $("#pontorh-treatment-pending-type").val()
            }),
            order: [[1, "desc"]],
            columns: [
                {title: "<?php echo app_lang('pontorh_employee'); ?>"},
                {title: "<?php echo app_lang('pontorh_work_date'); ?>"},
                {title: "<?php echo app_lang('pontorh_project'); ?>"},
                {title: "<?php echo app_lang('pontorh_quantity_of_records'); ?>"},
                {title: "<?php echo app_lang('pontorh_status'); ?>"},
                {title: "<?php echo app_lang('pontorh_type'); ?>"},
                {title: "<?php echo app_lang('updated_at'); ?>"},
                {title: "<i data-feather='menu' class='icon-16'></i>", "class": "text-center option w140"}
            ]
        });
    });
</script>
