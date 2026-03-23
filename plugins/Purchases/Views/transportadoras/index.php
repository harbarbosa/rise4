<div id="page-content" class="page-wrapper clearfix">
    <div class="card">
        <div class="page-title clearfix">
            <h1><?php echo app_lang('purchases_transportadoras'); ?></h1>
            <div class="title-button-group">
                <?php echo modal_anchor(get_uri('purchases_transportadoras/modal_form'), "<i data-feather='plus-circle' class='icon-16'></i> " . app_lang('purchases_add_transportadora'), array("class" => "btn btn-default", "title" => app_lang('purchases_add_transportadora'))); ?>
            </div>
        </div>
        <div class="table-responsive">
            <table id="purchases-transportadoras-table" class="display" cellspacing="0" width="100%"></table>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function () {
        $("#purchases-transportadoras-table").appTable({
            source: '<?php echo_uri("purchases_transportadoras/list_data") ?>',
            order: [[0, "asc"]],
            columns: [
                {title: "<?php echo app_lang('purchases_transportadora_name'); ?>", "class": "all"},
                {title: "<?php echo app_lang('purchases_transportadora_email'); ?>"},
                {title: "<?php echo app_lang('purchases_transportadora_phone'); ?>"},
                {title: "<?php echo app_lang('purchases_transportadora_tax_id'); ?>"},
                {title: "<?php echo app_lang('purchases_transportadora_address'); ?>"},
                {title: "<i data-feather='menu' class='icon-16'></i>", "class": "text-center option w100"}
            ],
            printColumns: [0, 1, 2, 3, 4],
            xlsColumns: [0, 1, 2, 3, 4]
        });
    });
</script>
