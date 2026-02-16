<div id="page-content" class="page-wrapper clearfix">
    <div class="card">
        <div class="page-title clearfix">
            <h1><?php echo app_lang('purchases_approvals'); ?></h1>
        </div>
        <div class="table-responsive">
            <table id="purchases-approvals-table" class="display" cellspacing="0" width="100%"></table>
        </div>
    </div>
</div>

<?php
$mine_only_options = array(
    array('id' => '', 'text' => '- ' . app_lang('purchases_approvals_filter') . ' -'),
    array('id' => '1', 'text' => app_lang('purchases_my_pending_approvals'))
);
?>

<script type="text/javascript">
    $(document).ready(function () {
        $("#purchases-approvals-table").appTable({
            source: '<?php echo_uri("purchases_requests/approvals_list_data") ?>',
            filterDropdown: [
                {name: "mine_only", class: "w200", options: <?php echo json_encode($mine_only_options); ?>}
            ],
            order: [[0, "desc"]],
            columns: [
                {title: "<?php echo app_lang('purchases_request_code'); ?>", "class": "all"},
                {title: "<?php echo app_lang('purchases_project_or_cost_center'); ?>"},
                {title: "<?php echo app_lang('purchases_requested_by'); ?>"},
                {title: "<?php echo app_lang('total'); ?>", "class": "text-right"},
                {title: "<?php echo app_lang('purchases_status'); ?>"},
                {title: "<?php echo app_lang('purchases_approval_role'); ?>"},
                {title: "<i data-feather='menu' class='icon-16'></i>", "class": "text-center option w100"}
            ],
            printColumns: [0, 1, 2, 3, 4, 5],
            xlsColumns: [0, 1, 2, 3, 4, 5]
        });
    });
</script>
