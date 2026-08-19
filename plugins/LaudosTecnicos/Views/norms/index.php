<div id="page-content" class="page-wrapper clearfix">
    <div class="card">
        <div class="page-title clearfix">
            <h1><?php echo app_lang('laudostecnicos_norms_title'); ?></h1>
            <div class="title-button-group">
                <?php if (!empty($can_manage_norms)) { ?>
                    <?php echo modal_anchor(get_uri('laudostecnicos/normas/modal_form'), "<i data-feather='plus-circle' class='icon-16'></i> " . app_lang('add_item'), array('class' => 'btn btn-primary', 'title' => app_lang('laudostecnicos_norms_title'))); ?>
                <?php } ?>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="laudostecnicos-norms-table" class="display" cellspacing="0" width="100%"></table>
            </div>
        </div>
    </div>
</div>
<script>
$(function () {
    $("#laudostecnicos-norms-table").appTable({
        source: '<?php echo_uri("laudostecnicos/normas/list_data") ?>',
        columns: [
            {title: "Codigo", "class": "all"},
            {title: "Titulo", "class": "desktop"},
            {title: "Instituicao", "class": "desktop"},
            {title: "Categoria", "class": "desktop"},
            {title: "Edicao", "class": "desktop"},
            {title: "Ano", "class": "desktop"},
            {title: "Status", "class": "text-center w100"},
            {title: "<i data-feather='menu' class='icon-16'></i>", "class": "text-center option w120"}
        ]
    });
});
</script>
