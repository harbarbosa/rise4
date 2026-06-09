<?php
$default_date_from = date('Y-m-01');
$default_date_to = date('Y-m-t');
?>

<div id="page-content" class="page-wrapper clearfix">
    <div class="card">
        <div class="page-title clearfix">
            <h1><?php echo app_lang('pontorh_records'); ?></h1>
            <div class="title-button-group">
                <?php if ($can_manage) { ?>
                    <?php echo modal_anchor(get_uri('pontorh/registros/modal_form'), "<i data-feather='plus-circle' class='icon-16'></i> " . app_lang('pontorh_records'), array('class' => 'btn btn-primary', 'title' => app_lang('pontorh_records'))); ?>
                <?php } ?>
            </div>
        </div>

        <div class="card-body border-bottom">
            <div class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label"><?php echo app_lang('pontorh_employee'); ?></label>
                    <select name="team_member_id" id="pontorh-filter-team-member" class="form-control select2" style="width:100%;">
                        <option value=""></option>
                        <?php foreach ($team_members_dropdown as $value => $label) { ?>
                            <option value="<?php echo esc($value); ?>"><?php echo esc($label); ?></option>
                        <?php } ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label"><?php echo app_lang('start_date'); ?></label>
                    <div class="input-group">
                        <?php echo form_input(array(
                            'id' => 'pontorh-filter-date-from',
                            'type' => 'text',
                            'class' => 'form-control datepicker',
                            'autocomplete' => 'off',
                            'placeholder' => app_lang('from_date'),
                            'value' => $default_date_from,
                        )); ?>
                        <span class="input-group-text"><i data-feather="calendar" class="icon-14"></i></span>
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label"> <?php echo app_lang('end_date'); ?></label>
                    <div class="input-group">
                        <?php echo form_input(array(
                            'id' => 'pontorh-filter-date-to',
                            'type' => 'text',
                            'class' => 'form-control datepicker',
                            'autocomplete' => 'off',
                            'placeholder' => app_lang('to_date'),
                            'value' => $default_date_to,
                        )); ?>
                        <span class="input-group-text"><i data-feather="calendar" class="icon-14"></i></span>
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label"><?php echo app_lang('pontorh_status'); ?></label>
                    <select name="status" id="pontorh-filter-status" class="form-control select2" style="width:100%;">
                        <option value=""></option>
                        <?php foreach ($status_dropdown as $value => $label) { ?>
                            <option value="<?php echo esc($value); ?>"><?php echo esc($label); ?></option>
                        <?php } ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label"><?php echo app_lang('pontorh_type'); ?></label>
                    <select name="punch_type" id="pontorh-filter-type" class="form-control select2" style="width:100%;">
                        <option value=""></option>
                        <?php foreach ($punch_type_dropdown as $value => $label) { ?>
                            <option value="<?php echo esc($value); ?>"><?php echo esc($label); ?></option>
                        <?php } ?>
                    </select>
                </div>
                <div class="col-md-12">
                    <button type="button" id="pontorh-filter-btn" class="btn btn-primary btn-sm me-2"><?php echo app_lang('filter'); ?></button>
                    <button type="button" id="pontorh-clear-btn" class="btn btn-default btn-sm">Limpar</button>
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table id="pontorh-records-table" class="display" cellspacing="0" width="100%"></table>
        </div>
    </div>
</div>

<script type="text/javascript">
    function pontorhRecordsFilters() {
        return {
            team_member_id: $("#pontorh-filter-team-member").val(),
            date_from: $("#pontorh-filter-date-from").val(),
            date_to: $("#pontorh-filter-date-to").val(),
            status: $("#pontorh-filter-status").val(),
            punch_type: $("#pontorh-filter-type").val()
        };
    }

    function reloadPontorhRecordsTable() {
        if (window.InstanceCollection && window.InstanceCollection["pontorh-records-table"]) {
            window.InstanceCollection["pontorh-records-table"].filterParams = $.extend({datatable: true}, pontorhRecordsFilters());
        }
        $("#pontorh-records-table").appTable({reload: true});
    }

    $(document).ready(function () {
        $(".page-wrapper .select2").select2();
        setDatePicker("#pontorh-filter-date-from, #pontorh-filter-date-to");

        $("#pontorh-records-table").appTable({
            source: "<?php echo_uri('pontorh/registros/list_data'); ?>",
            filterParams: $.extend({datatable: true}, pontorhRecordsFilters()),
            order: [[1, "desc"]],
            columns: [
                {title: "<?php echo app_lang('pontorh_employee'); ?>"},
                {title: "<?php echo app_lang('date'); ?>"},
                {title: "<?php echo app_lang('time'); ?>"},
                {title: "<?php echo app_lang('pontorh_type'); ?>"},
                {title: "<?php echo app_lang('pontorh_location'); ?>"},
                {title: "<?php echo app_lang('pontorh_source'); ?>"},
                {title: "<?php echo app_lang('pontorh_status'); ?>"},
                {title: "<i data-feather='menu' class='icon-16'></i>", "class": "text-center option w140"}
            ]
        });

        $("#pontorh-filter-btn").on("click", function () {
            reloadPontorhRecordsTable();
        });

        $("#pontorh-clear-btn").on("click", function () {
            $("#pontorh-filter-team-member, #pontorh-filter-status, #pontorh-filter-type").val("").trigger("change");
            $("#pontorh-filter-date-from, #pontorh-filter-date-to").val("");
            reloadPontorhRecordsTable();
        });
    });
</script>
