<div id="page-content" class="page-wrapper clearfix">
    <div class="card">
        <div class="page-title clearfix">
            <h1><?php echo app_lang('purchases_reports'); ?></h1>
        </div>
        <div class="card-body">
            <div class="mt15">
                <h4 class="mb10"><?php echo app_lang('purchases_report_by_period'); ?></h4>
                <div class="table-responsive">
                    <table id="purchases-report-period-table" class="display" cellspacing="0" width="100%"></table>
                </div>
            </div>

            <div class="mt25">
                <h4 class="mb10"><?php echo app_lang('purchases_report_open_overdue'); ?></h4>
                <div class="table-responsive">
                    <table id="purchases-report-open-table" class="display" cellspacing="0" width="100%"></table>
                </div>
            </div>

            <div class="mt25">
                <h4 class="mb10"><?php echo app_lang('purchases_report_top_items'); ?></h4>
                <div class="table-responsive">
                    <table id="purchases-report-items-table" class="display" cellspacing="0" width="100%"></table>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function () {
        $("#purchases-report-period-table").appTable({
            source: '<?php echo_uri("purchases_reports/purchases_by_period") ?>',
            filterDropdown: [
                {name: "project_id", class: "w200", options: <?php echo $projects_dropdown; ?>},
                {name: "supplier_id", class: "w200", options: <?php echo $suppliers_dropdown; ?>}
            ],
            rangeDatepicker: [{startDate: {name: "start_date"}, endDate: {name: "end_date"}, showClearButton: true}],
            columns: [
                {title: "<?php echo app_lang('project'); ?>", "class": "all"},
                {title: "<?php echo app_lang('purchases_supplier'); ?>"},
                {title: "<?php echo app_lang('purchases_orders_count'); ?>", "class": "text-right"},
                {title: "<?php echo app_lang('total'); ?>", "class": "text-right"}
            ],
            printColumns: [0, 1, 2, 3],
            xlsColumns: [0, 1, 2, 3]
        });

        $("#purchases-report-open-table").appTable({
            source: '<?php echo_uri("purchases_reports/open_overdue") ?>',
            filterDropdown: [
                {name: "supplier_id", class: "w200", options: <?php echo $suppliers_dropdown; ?>}
            ],
            columns: [
                {title: "<?php echo app_lang('purchases_po_code'); ?>", "class": "all"},
                {title: "<?php echo app_lang('purchases_supplier'); ?>"},
                {title: "<?php echo app_lang('project'); ?>"},
                {title: "<?php echo app_lang('status'); ?>"},
                {title: "<?php echo app_lang('purchases_expected_delivery_date'); ?>"},
                {title: "<?php echo app_lang('total'); ?>", "class": "text-right"},
                {title: "<?php echo app_lang('purchases_overdue'); ?>", "class": "text-center w100"}
            ],
            printColumns: [0, 1, 2, 3, 4, 5, 6],
            xlsColumns: [0, 1, 2, 3, 4, 5, 6]
        });

        $("#purchases-report-items-table").appTable({
            source: '<?php echo_uri("purchases_reports/top_items") ?>',
            rangeDatepicker: [{startDate: {name: "start_date"}, endDate: {name: "end_date"}, showClearButton: true}],
            columns: [
                {title: "<?php echo app_lang('purchases_item_description'); ?>", "class": "all"},
                {title: "<?php echo app_lang('purchases_unit'); ?>"},
                {title: "<?php echo app_lang('purchases_qty'); ?>", "class": "text-right"},
                {title: "<?php echo app_lang('total'); ?>", "class": "text-right"}
            ],
            printColumns: [0, 1, 2, 3],
            xlsColumns: [0, 1, 2, 3]
        });
    });
</script>
