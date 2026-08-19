<div id="page-content" class="page-wrapper clearfix">
    <div class="card">
        <div class="page-title clearfix">
            <h1><?php echo app_lang('laudostecnicos_categories_title'); ?></h1>
            <div class="title-button-group">
                <?php if (!empty($can_manage_categories)) { ?>
                    <?php echo modal_anchor(get_uri('laudostecnicos/categorias/modal_form'), "<i data-feather='plus-circle' class='icon-16'></i> " . app_lang('add_item'), array('class' => 'btn btn-primary', 'title' => app_lang('laudostecnicos_categories_title'))); ?>
                <?php } ?>
            </div>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table id="laudostecnicos-categories-table" class="display" cellspacing="0" width="100%"></table>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(function () {
        $("#laudostecnicos-categories-table").appTable({
            source: '<?php echo_uri("laudostecnicos/categorias/list_data") ?>',
            columns: [
                {title: "Nome", "class": "all"},
                {title: "Codigo", "class": "desktop"},
                {title: "Descricao", "class": "desktop"},
                {title: "Cor", "class": "desktop"},
                {title: "Icone", "class": "desktop"},
                {title: "Ordem", "class": "text-center w100"},
                {title: "Status", "class": "text-center w100"},
                {title: "<i data-feather='menu' class='icon-16'></i>", "class": "text-center option w100"}
            ],
            order: [[5, 'asc']]
        });
    });
</script>
