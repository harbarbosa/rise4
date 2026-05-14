<div id="page-content" class="page-wrapper clearfix">
    <div class="card">
        <div class="page-title clearfix">
            <h1><?php echo app_lang('ged_document_types'); ?></h1>
            <div class="title-button-group">
                <?php if (!empty($can_create)) { ?>
                    <?php echo modal_anchor(get_uri('ged/document_types/modal_form'), "<i data-feather='plus-circle' class='icon-16'></i> Novo tipo de documento", array('class' => 'btn btn-default', 'title' => 'Novo tipo de documento')); ?>
                <?php } ?>
            </div>
        </div>

        <div class="table-responsive">
            <table id="ged-document-types-table" class="display" cellspacing="0" width="100%"></table>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function () {
        $("#ged-document-types-table").appTable({
            source: "<?php echo_uri('ged/document_types/list_data'); ?>",
            order: [[0, "asc"]],
            columns: [
                {title: "<?php echo app_lang('ged_field_name'); ?>", "class": "all"},
                {title: "<?php echo app_lang('ged_field_description'); ?>"},
                {title: "<?php echo app_lang('ged_field_has_expiration'); ?>"},
                {title: "<?php echo app_lang('ged_field_status'); ?>"},
                {title: "<i data-feather='menu' class='icon-16'></i>", "class": "text-center option w100"}
            ],
            printColumns: [0, 1, 2, 3],
            xlsColumns: [0, 1, 2, 3]
        });
    });
</script>
