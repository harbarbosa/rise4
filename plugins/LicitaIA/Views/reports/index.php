<div id="page-content" class="page-wrapper clearfix">
    <div class="card">
        <div class="page-title clearfix">
            <h1><?php echo app_lang('licitaia_reports'); ?></h1>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table id="licitaia-reports-table" class="display" cellspacing="0" width="100%"></table>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function () {
        $("#licitaia-reports-table").appTable({
            source: "<?php echo_uri('licitaia/reports/list_data'); ?>",
            columns: [
                {title: "<?php echo app_lang('licitaia_opportunity'); ?>"},
                {title: "<?php echo app_lang('licitaia_report_type'); ?>"},
                {title: "<?php echo app_lang('licitaia_report_title'); ?>"},
                {title: "<?php echo app_lang('date'); ?>"},
                {title: "<?php echo app_lang('created_by'); ?>"},
                {title: "<i data-feather='menu' class='icon-16'></i>", "class": "text-center option w100"}
            ]
        });
    });
</script>
