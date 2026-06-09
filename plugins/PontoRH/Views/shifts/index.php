<div id="page-content" class="page-wrapper clearfix">
    <div class="card">
        <div class="page-title clearfix">
            <h1><?php echo app_lang('pontorh_shifts'); ?></h1>
            <div class="title-button-group">
                <?php if ($can_manage) { ?>
                    <?php echo modal_anchor(get_uri('pontorh/jornadas/modal_form'), "<i data-feather='plus-circle' class='icon-16'></i> " . app_lang('pontorh_shifts'), array('class' => 'btn btn-primary', 'title' => app_lang('pontorh_shifts'), 'data-modal-lg' => '1')); ?>
                <?php } ?>
            </div>
        </div>

        <div class="card-body border-bottom">
            <div class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label"><?php echo app_lang('pontorh_schedule_team_member'); ?></label>
                    <?php echo form_dropdown('team_member_id', $team_members_dropdown, '', 'class="form-control select2" id="pontorh-filter-team-member"'); ?>
                </div>
                <div class="col-md-3">
                    <label class="form-label"><?php echo app_lang('pontorh_schedule_type'); ?></label>
                    <?php echo form_dropdown('schedule_type', $schedule_type_dropdown, '', 'class="form-control select2" id="pontorh-filter-type"'); ?>
                </div>
                <div class="col-md-3">
                    <label class="form-label"><?php echo app_lang('status'); ?></label>
                    <?php echo form_dropdown('active', array('' => '-', '1' => app_lang('active'), '0' => app_lang('inactive')), '', 'class="form-control select2" id="pontorh-filter-status"'); ?>
                </div>
                <div class="col-md-3">
                    <button type="button" id="pontorh-filter-btn" class="btn btn-primary btn-sm me-2"><?php echo app_lang('filter'); ?></button>
                    <button type="button" id="pontorh-clear-btn" class="btn btn-default btn-sm"><?php echo app_lang('clear'); ?></button>
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table id="pontorh-shifts-table" class="display" cellspacing="0" width="100%"></table>
        </div>
    </div>
</div>

<script type="text/javascript">
    function pontorhShiftsFilters() {
        return {
            team_member_id: $("#pontorh-filter-team-member").val(),
            schedule_type: $("#pontorh-filter-type").val(),
            active: $("#pontorh-filter-status").val()
        };
    }

    function reloadPontorhShiftsTable() {
        if (window.InstanceCollection && window.InstanceCollection["pontorh-shifts-table"]) {
            window.InstanceCollection["pontorh-shifts-table"].filterParams = $.extend({datatable: true}, pontorhShiftsFilters());
        }
        $("#pontorh-shifts-table").appTable({reload: true});
    }

    $(document).ready(function () {
        $("#pontorh-shifts-table").appTable({
            source: "<?php echo_uri('pontorh/jornadas/list_data'); ?>",
            filterParams: $.extend({datatable: true}, pontorhShiftsFilters()),
            order: [[0, "asc"]],
            columns: [
                {title: "<?php echo app_lang('name'); ?>"},
                {title: "<?php echo app_lang('pontorh_schedule_team_members'); ?>"},
                {title: "<?php echo app_lang('pontorh_schedule_type'); ?>"},
                {title: "<?php echo app_lang('pontorh_check_in'); ?>"},
                {title: "<?php echo app_lang('pontorh_check_out'); ?>"},
                {title: "<?php echo app_lang('pontorh_break_minutes'); ?>"},
                {title: "<?php echo app_lang('pontorh_tolerance_minutes'); ?>"},
                {title: "<?php echo app_lang('pontorh_extra_tolerance_minutes'); ?>"},
                {title: "<?php echo app_lang('pontorh_bank_hours'); ?>"},
                {title: "<?php echo app_lang('status'); ?>"},
                {title: "<i data-feather='menu' class='icon-16'></i>", "class": "text-center option w120"}
            ]
        });

        $("#pontorh-filter-btn").on("click", function () {
            reloadPontorhShiftsTable();
        });

        $("#pontorh-clear-btn").on("click", function () {
            $("#pontorh-filter-team-member, #pontorh-filter-type, #pontorh-filter-status").val("").trigger("change");
            reloadPontorhShiftsTable();
        });
    });
</script>
