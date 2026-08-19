<div id="page-content" class="page-wrapper clearfix">
    <div class="card">
        <div class="page-title clearfix">
            <h1><?php echo app_lang('laudostecnicos_types_title'); ?></h1>
            <div class="title-button-group">
                <?php if (!empty($can_manage_types)) { ?>
                    <?php echo modal_anchor(get_uri('laudostecnicos/tipos/modal_form'), "<i data-feather='plus-circle' class='icon-16'></i> " . app_lang('add_item'), array('class' => 'btn btn-primary', 'title' => app_lang('laudostecnicos_types_title'))); ?>
                <?php } ?>
            </div>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table id="laudostecnicos-types-table" class="display" cellspacing="0" width="100%"></table>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(function () {
        $("#laudostecnicos-types-table").appTable({
            source: '<?php echo_uri("laudostecnicos/tipos/list_data") ?>',
            columns: [
                {title: "Nome", "class": "all"},
                {title: "Codigo", "class": "desktop"},
                {title: "Categoria", "class": "desktop"},
                {title: "Prefixo", "class": "desktop"},
                {title: "Validade", "class": "text-center w120"},
                {title: "Status", "class": "text-center w100"},
                {title: "Ordem", "class": "text-center w100"},
                {title: "<i data-feather='menu' class='icon-16'></i>", "class": "text-center option w100"}
            ],
            order: [[6, 'asc']]
        });
    });
</script>
