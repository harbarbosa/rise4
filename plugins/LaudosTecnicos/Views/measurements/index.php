<div id="page-content" class="page-wrapper clearfix">
    <div class="card">
        <div class="page-title clearfix">
            <h1><?php echo app_lang('laudostecnicos_measurements_title'); ?></h1>
            <div class="title-button-group">
                <?php if (!empty($can_manage_measurements)) { ?>
                    <?php echo modal_anchor(get_uri('laudostecnicos/medicoes/modal_form'), "<i data-feather='plus-circle' class='icon-16'></i> " . app_lang('add_item'), array('class' => 'btn btn-primary', 'title' => app_lang('laudostecnicos_measurements_title'))); ?>
                <?php } ?>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="laudostecnicos-measurements-table" class="display" cellspacing="0" width="100%"></table>
            </div>
        </div>
    </div>
</div>
<script>
$(function () {
    $("#laudostecnicos-measurements-table").appTable({
        source: '<?php echo_uri("laudostecnicos/medicoes/list_data") ?>',
        columns: [
            {title: "Tipo", "class": "all"},
            {title: "Valor", "class": "desktop"},
            {title: "Unidade", "class": "desktop"},
            {title: "Resultado", "class": "desktop"},
            {title: "Local", "class": "desktop"},
            {title: "Equipamento", "class": "desktop"},
            {title: "Data", "class": "desktop"},
            {title: "<i data-feather='menu' class='icon-16'></i>", "class": "text-center option w100"}
        ]
    });
});
</script>
