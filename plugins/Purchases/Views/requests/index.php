<div id="page-content" class="page-wrapper clearfix">
    <div class="card">
        <div class="page-title clearfix">
            <h1><?php echo app_lang('purchases_requests'); ?></h1>
            <div class="title-button-group">
                <?php echo anchor(get_uri('purchases_requests/request_form'), "<i data-feather='plus-circle' class='icon-16'></i> " . app_lang('purchases_add_request'), array("class" => "btn btn-default")); ?>
            </div>
        </div>
        <div class="table-responsive">
            <table id="purchases-requests-table" class="display" cellspacing="0" width="100%"></table>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function () {
        $("#purchases-requests-table").appTable({
            source: '<?php echo_uri("purchases_requests/list_data") ?>',
            filterDropdown: [
                {name: "status", class: "w150", options: <?php echo $statuses_dropdown; ?>},
                {name: "project_id", class: "w200", options: <?php echo $projects_dropdown; ?>}
            ],
            rangeDatepicker: [{startDate: {name: "start_date"}, endDate: {name: "end_date"}, showClearButton: true}],
            order: [[0, "desc"]],
            columns: [
                {title: "<?php echo app_lang('purchases_request_code'); ?>", "class": "all"},
                {title: "<?php echo app_lang('project'); ?>"},
                {title: "<?php echo app_lang('purchases_priority'); ?>"},
                {title: "<?php echo app_lang('status'); ?>"},
                {title: "<?php echo app_lang('purchases_requested_by'); ?>"},
                {title: "<?php echo app_lang('created_date'); ?>"},
                {title: "<i data-feather='menu' class='icon-16'></i>", "class": "text-center option w100"}
            ],
            printColumns: [0, 1, 2, 3, 4, 5],
            xlsColumns: [0, 1, 2, 3, 4, 5]
        });
    });
</script>
