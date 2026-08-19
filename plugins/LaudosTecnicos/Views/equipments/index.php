<div id="page-content" class="page-wrapper clearfix">
    <div class="card">
        <div class="page-title clearfix">
            <h1><?php echo app_lang('laudostecnicos_equipments_title'); ?></h1>
            <div class="title-button-group">
                <?php if (!empty($can_manage_equipments)) { ?>
                    <?php echo modal_anchor(get_uri('laudostecnicos/equipamentos/modal_form'), "<i data-feather='plus-circle' class='icon-16'></i> " . app_lang('add_item'), array('class' => 'btn btn-primary', 'title' => app_lang('laudostecnicos_equipments_title'))); ?>
                <?php } ?>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="laudostecnicos-equipments-table" class="display" cellspacing="0" width="100%"></table>
            </div>
        </div>
    </div>
</div>
<script>
$(function () {
    $("#laudostecnicos-equipments-table").appTable({
        source: '<?php echo_uri("laudostecnicos/equipamentos/list_data") ?>',
        columns: [
            {title: "Nome", "class": "all"},
            {title: "Tipo", "class": "desktop"},
            {title: "Fabricante", "class": "desktop"},
            {title: "Modelo", "class": "desktop"},
            {title: "Serie", "class": "desktop"},
            {title: "Patrimonio", "class": "desktop"},
            {title: "Ult. calibracao", "class": "desktop"},
            {title: "Prox. calibracao", "class": "desktop"},
            {title: "Status", "class": "text-center w100"},
            {title: "Calibracao", "class": "text-center w100"},
            {title: "<i data-feather='menu' class='icon-16'></i>", "class": "text-center option w120"}
        ]
    });
});
</script>
