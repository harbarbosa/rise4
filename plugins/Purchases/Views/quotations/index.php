<?php
$schema_ready = isset($schema_ready) ? (bool) $schema_ready : true;
$schema_warning = $schema_warning ?? '';
?>

<div id="page-content" class="page-wrapper clearfix">
    <div class="card">
        <div class="page-title clearfix">
            <h1><?php echo app_lang('purchases_quotations'); ?></h1>
            <div class="title-button-group">
                <?php if ($schema_ready) { ?>
                    <?php echo anchor(get_uri('purchases_quotations/create'), "<i data-feather='plus-circle' class='icon-16'></i> " . app_lang('purchases_add_standalone_quotation'), array("class" => "btn btn-default")); ?>
                <?php } ?>
            </div>
        </div>
        <?php if (!$schema_ready && $schema_warning) { ?>
            <div class="card-body pb0">
                <div class="alert alert-warning mb0"><?php echo esc($schema_warning); ?></div>
            </div>
        <?php } ?>
        <div class="table-responsive">
            <table id="purchases-quotations-table" class="display" cellspacing="0" width="100%"></table>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function () {
        <?php if (!$schema_ready) { ?>
        return;
        <?php } ?>
        $("#purchases-quotations-table").appTable({
            source: '<?php echo_uri("purchases_quotations/list_data") ?>',
            order: [[0, "desc"]],
            columns: [
                {title: "<?php echo app_lang('purchases_quotation_code'); ?>", "class": "all"},
                {title: "<?php echo app_lang('purchases_quotation_title'); ?>"},
                {title: "<?php echo app_lang('status'); ?>"},
                {title: "<?php echo app_lang('purchases_winner'); ?>"},
                {title: "<?php echo app_lang('created_date'); ?>"},
                {title: "<i data-feather='menu' class='icon-16'></i>", "class": "text-center option w100"}
            ],
            printColumns: [0, 1, 2, 3, 4],
            xlsColumns: [0, 1, 2, 3, 4]
        });
    });
</script>
