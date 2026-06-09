<div id="page-content" class="page-wrapper clearfix">
    <div class="card">
        <div class="page-title clearfix">
            <h1><?php echo app_lang('pontorh_adjustments'); ?></h1>
            <div class="title-button-group">
                <?php if ($can_request) { ?>
                    <?php echo modal_anchor(get_uri('pontorh/ajustes/modal_form'), "<i data-feather='plus-circle' class='icon-16'></i> " . app_lang('pontorh_adjustment_request'), array('class' => 'btn btn-primary', 'title' => app_lang('pontorh_adjustment_request'), 'data-modal-lg' => '1')); ?>
                <?php } ?>
            </div>
        </div>

        <div class="card-body border-bottom">
            <div class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label"><?php echo app_lang('pontorh_employee'); ?></label>
                    <?php echo form_dropdown('team_member_id', $team_members_dropdown, '', 'class="form-control select2" id="pontorh-adjustment-filter-member"'); ?>
                </div>
                <div class="col-md-3">
                    <label class="form-label"><?php echo app_lang('pontorh_work_date'); ?></label>
                    <div class="input-daterange input-group">
                        <?php echo form_input(array(
                            'id' => 'pontorh-adjustment-date-from',
                            'class' => 'form-control datepicker',
                            'autocomplete' => 'off',
                            'placeholder' => app_lang('from_date'),
                        )); ?>
                        <span class="input-group-text">-</span>
                        <?php echo form_input(array(
                            'id' => 'pontorh-adjustment-date-to',
                            'class' => 'form-control datepicker',
                            'autocomplete' => 'off',
                            'placeholder' => app_lang('to_date'),
                        )); ?>
                    </div>
                </div>
                <div class="col-md-2">
                    <label class="form-label"><?php echo app_lang('pontorh_type'); ?></label>
                    <?php echo form_dropdown('adjustment_type', $adjustment_type_dropdown, '', 'class="form-control select2" id="pontorh-adjustment-filter-type"'); ?>
                </div>
                <div class="col-md-2">
                    <label class="form-label"><?php echo app_lang('pontorh_status'); ?></label>
                    <?php echo form_dropdown('status', $status_dropdown, '', 'class="form-control select2" id="pontorh-adjustment-filter-status"'); ?>
                </div>
                <div class="col-md-2">
                    <button type="button" id="pontorh-adjustment-filter-btn" class="btn btn-primary btn-sm me-2"><?php echo app_lang('filter'); ?></button>
                    <button type="button" id="pontorh-adjustment-clear-btn" class="btn btn-default btn-sm"><?php echo app_lang('clear'); ?></button>
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table id="pontorh-adjustments-table" class="display" cellspacing="0" width="100%"></table>
        </div>
    </div>
</div>

<script type="text/javascript">
    function pontorhAdjustmentFilters() {
        return {
            team_member_id: $("#pontorh-adjustment-filter-member").val(),
            date_from: $("#pontorh-adjustment-date-from").val(),
            date_to: $("#pontorh-adjustment-date-to").val(),
            adjustment_type: $("#pontorh-adjustment-filter-type").val(),
            status: $("#pontorh-adjustment-filter-status").val()
        };
    }

    function reloadPontorhAdjustmentsTable() {
        if (window.InstanceCollection && window.InstanceCollection["pontorh-adjustments-table"]) {
            window.InstanceCollection["pontorh-adjustments-table"].filterParams = $.extend({datatable: true}, pontorhAdjustmentFilters());
        }
        $("#pontorh-adjustments-table").appTable({reload: true});
    }

    $(document).ready(function () {
        $("#pontorh-adjustments-table").appTable({
            source: "<?php echo_uri('pontorh/ajustes/list_data'); ?>",
            filterParams: $.extend({datatable: true}, pontorhAdjustmentFilters()),
            order: [[1, "desc"]],
            columns: [
                {title: "<?php echo app_lang('pontorh_employee'); ?>"},
                {title: "<?php echo app_lang('pontorh_work_date'); ?>"},
                {title: "<?php echo app_lang('pontorh_adjustment_time'); ?>"},
                {title: "<?php echo app_lang('pontorh_type'); ?>"},
                {title: "<?php echo app_lang('pontorh_adjustment_justification'); ?>"},
                {title: "<?php echo app_lang('pontorh_status'); ?>"},
                {title: "<i data-feather='menu' class='icon-16'></i>", "class": "text-center option w120"}
            ]
        });

        $("#pontorh-adjustment-filter-btn").on("click", function () {
            reloadPontorhAdjustmentsTable();
        });

        $("#pontorh-adjustment-clear-btn").on("click", function () {
            $("#pontorh-adjustment-filter-member, #pontorh-adjustment-filter-type, #pontorh-adjustment-filter-status").val("").trigger("change");
            $("#pontorh-adjustment-date-from, #pontorh-adjustment-date-to").val("");
            reloadPontorhAdjustmentsTable();
        });
    });
</script>
