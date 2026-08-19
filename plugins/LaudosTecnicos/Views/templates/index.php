<?php
$types_dropdown = is_array($types_dropdown ?? null) ? $types_dropdown : array();
$categories_dropdown = is_array($categories_dropdown ?? null) ? $categories_dropdown : array();
?>

<div id="page-content" class="page-wrapper clearfix">
    <div class="card">
        <div class="page-title clearfix">
            <h1><?php echo app_lang('laudostecnicos_templates_title'); ?></h1>
            <div class="title-button-group">
                <?php if (!empty($can_manage_templates)) { ?>
                    <?php echo modal_anchor(get_uri('laudostecnicos/templates/modal_form'), "<i data-feather='plus-circle' class='icon-16'></i> Novo template", array('class' => 'btn btn-primary', 'title' => 'Novo template')); ?>
                <?php } ?>
            </div>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table id="laudostecnicos-templates-table" class="display" cellspacing="0" width="100%"></table>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(function () {
        var $table = $("#laudostecnicos-templates-table");

        $table.appTable({
            source: '<?php echo_uri("laudostecnicos/templates/list_data") ?>',
            filterDropdown: [
                {name: "status", class: "w140", options: [{"id":"", "text":"Todos"},{"id":"draft", "text":"Rascunho"},{"id":"published", "text":"Publicado"},{"id":"archived", "text":"Arquivado"},{"id":"inactive", "text":"Inativo"}]},
                {name: "type_id", class: "w240", options: <?php echo json_encode(array_map(function($id, $text) { return array('id' => $id, 'text' => $text); }, array_keys($types_dropdown), array_values($types_dropdown)), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>},
                {name: "category_id", class: "w240", options: <?php echo json_encode(array_map(function($id, $text) { return array('id' => $id, 'text' => $text); }, array_keys($categories_dropdown), array_values($categories_dropdown)), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>}
            ],
            columns: [
                {title: "Nome", "class": "all"},
                {title: "Codigo", "class": "desktop"},
                {title: "Chave", "class": "desktop"},
                {title: "Tipo", "class": "desktop"},
                {title: "Categoria", "class": "desktop"},
                {title: "Versao", "class": "text-center w90"},
                {title: "Status", "class": "text-center w100"},
                {title: "Padrao", "class": "text-center w90"},
                {title: "Ativo", "class": "text-center w90"},
                {title: "Publicado em", "class": "desktop"},
                {title: "<i data-feather='menu' class='icon-16'></i>", "class": "text-center option w230"}
            ],
            order: [[4, 'asc'], [5, 'desc']]
        });
    });
</script>
