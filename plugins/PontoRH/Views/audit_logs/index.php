<div id="page-content" class="page-wrapper clearfix">
    <div class="card">
        <div class="page-title clearfix">
            <h1><?php echo app_lang('pontorh_audit_logs'); ?></h1>
        </div>

        <div class="card-body border-bottom">
            <div class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label"><?php echo app_lang('pontorh_employee'); ?></label>
                    <?php echo form_dropdown('team_member_id', $team_members_dropdown ?? array('' => '-'), '', 'class="form-control select2" id="pontorh-audit-member"'); ?>
                </div>
                <div class="col-md-2">
                    <label class="form-label"><?php echo app_lang('pontorh_action'); ?></label>
                    <?php echo form_dropdown('action', $action_dropdown, '', 'class="form-control select2" id="pontorh-audit-action"'); ?>
                </div>
                <div class="col-md-2">
                    <label class="form-label"><?php echo app_lang('pontorh_entity_type'); ?></label>
                    <?php echo form_input(array(
                        'id' => 'pontorh-audit-entity',
                        'class' => 'form-control',
                    )); ?>
                </div>
                <div class="col-md-2">
                    <label class="form-label"><?php echo app_lang('pontorh_status'); ?></label>
                    <?php echo form_dropdown('status', $status_dropdown, '', 'class="form-control select2" id="pontorh-audit-status"'); ?>
                </div>
                <div class="col-md-3">
                    <label class="form-label"><?php echo app_lang('pontorh_work_date'); ?></label>
                    <div class="input-daterange input-group">
                        <?php echo form_input(array(
                            'id' => 'pontorh-audit-date-from',
                            'class' => 'form-control datepicker',
                            'autocomplete' => 'off',
                            'placeholder' => app_lang('from_date'),
                        )); ?>
                        <span class="input-group-text">-</span>
                        <?php echo form_input(array(
                            'id' => 'pontorh-audit-date-to',
                            'class' => 'form-control datepicker',
                            'autocomplete' => 'off',
                            'placeholder' => app_lang('to_date'),
                        )); ?>
                    </div>
                </div>
                <div class="col-md-12">
                    <button type="button" id="pontorh-audit-filter-btn" class="btn btn-primary btn-sm me-2"><?php echo app_lang('filter'); ?></button>
                    <button type="button" id="pontorh-audit-clear-btn" class="btn btn-default btn-sm"><?php echo app_lang('clear'); ?></button>
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table id="pontorh-audit-table" class="display" cellspacing="0" width="100%"></table>
        </div>
    </div>
</div>

<script type="text/javascript">
    function pontorhAuditFilters() {
        return {
            team_member_id: $("#pontorh-audit-member").val(),
            action: $("#pontorh-audit-action").val(),
            entity_type: $("#pontorh-audit-entity").val(),
            status: $("#pontorh-audit-status").val(),
            date_from: $("#pontorh-audit-date-from").val(),
            date_to: $("#pontorh-audit-date-to").val()
        };
    }

    $(document).ready(function () {
        $("#pontorh-audit-table").appTable({
            source: "<?php echo_uri('pontorh/auditoria/list_data'); ?>",
            filterParams: $.extend({datatable: true}, pontorhAuditFilters()),
            order: [[0, "desc"]],
            columns: [
                {title: "<?php echo app_lang('date'); ?>"},
                {title: "<?php echo app_lang('pontorh_employee'); ?>"},
                {title: "<?php echo app_lang('creator'); ?>"},
                {title: "<?php echo app_lang('pontorh_entity_type'); ?>"},
                {title: "<?php echo app_lang('pontorh_action'); ?>"},
                {title: "<?php echo app_lang('description'); ?>"},
                {title: "<?php echo app_lang('source'); ?>"},
                {title: "<?php echo app_lang('status'); ?>"},
                {title: "<i data-feather='menu' class='icon-16'></i>", "class": "text-center option w100"}
            ]
        });

        $("#pontorh-audit-filter-btn").on("click", function () {
            $("#pontorh-audit-table").appTable({reload: true});
        });

        $("#pontorh-audit-clear-btn").on("click", function () {
            $("#pontorh-audit-member, #pontorh-audit-action, #pontorh-audit-status").val("").trigger("change");
            $("#pontorh-audit-entity, #pontorh-audit-date-from, #pontorh-audit-date-to").val("");
            $("#pontorh-audit-table").appTable({reload: true});
        });
    });
</script>
