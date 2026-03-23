<div id="page-content" class="page-wrapper clearfix">
    <div class="card">
        <div class="page-title clearfix">
            <h1><?php echo app_lang('purchases_purchase_orders'); ?></h1>
        </div>
        <div class="table-responsive">
            <table id="purchases-orders-table" class="display" cellspacing="0" width="100%"></table>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function () {
        $("#purchases-orders-table").appTable({
            source: '<?php echo_uri("purchases_orders/list_data") ?>',
            filterDropdown: [
                {name: "status", class: "w150", options: <?php echo $statuses_dropdown; ?>},
                {name: "supplier_id", class: "w200", options: <?php echo $suppliers_dropdown; ?>}
            ],
            order: [[0, "desc"]],
            columns: [
                {title: "<?php echo app_lang('purchases_po_code'); ?>", "class": "all"},
                {title: "<?php echo app_lang('purchases_request_code'); ?>"},
                {title: "<?php echo app_lang('purchases_supplier'); ?>"},
                {title: "<?php echo app_lang('project'); ?>"},
                {title: "<?php echo app_lang('status'); ?>"},
                {title: "<?php echo app_lang('date'); ?>"},
                {title: "<?php echo app_lang('total'); ?>", "class": "text-right"},
                {title: "<i data-feather='menu' class='icon-16'></i>", "class": "text-center option w100"}
            ],
            printColumns: [0, 1, 2, 3, 4, 5, 6],
            xlsColumns: [0, 1, 2, 3, 4, 5, 6]
        });
    });
</script>
