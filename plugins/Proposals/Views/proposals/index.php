<div id="page-content" class="page-wrapper clearfix">
    <div class="card">
        <div class="page-title clearfix">
            <h1><?php echo app_lang('proposals_menu'); ?></h1>
            <div class="title-button-group">
                <?php if (isset($can_manage) && $can_manage) { ?>
                    <?php echo modal_anchor(get_uri('propostas/modal_form'), "<i data-feather='plus-circle' class='icon-16'></i> " . app_lang('proposals_add'), array("class" => "btn btn-default", "title" => app_lang('proposals_add'))); ?>
                <?php } ?>
            </div>
        </div>
        <div class="table-responsive">
            <table id="proposals-table" class="display" cellspacing="0" width="100%"></table>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function () {
        $("#proposals-table").appTable({
            source: '<?php echo_uri("propostas/list_data") ?>',
            filterDropdown: [
                {name: "status", class: "w150", options: <?php echo $statuses_dropdown; ?>}
            ],
            order: [[0, "desc"]],
            columns: [
                {title: "<?php echo app_lang('proposals_code'); ?>", "class": "all"},
                {title: "<?php echo app_lang('proposals_title'); ?>"},
                {title: "<?php echo app_lang('client'); ?>"},
                {title: "<?php echo app_lang('status'); ?>"},
                {title: "<?php echo app_lang('proposals_total'); ?>"},
                {title: "<?php echo app_lang('last_activity'); ?>"},
                {title: "<i data-feather='menu' class='icon-16'></i>", "class": "text-center option w100"}
            ],
            printColumns: [0, 1, 2, 3, 4, 5],
            xlsColumns: [0, 1, 2, 3, 4, 5]
        });
    });
</script>
