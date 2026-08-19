<?php
$types_dropdown = is_array($types_dropdown ?? null) ? $types_dropdown : array();
$categories_dropdown = is_array($categories_dropdown ?? null) ? $categories_dropdown : array();
$status_options = is_array($status_options ?? null) ? $status_options : array();

$types_filter = array(array('id' => '', 'text' => 'Todos'));
foreach ($types_dropdown as $id => $text) {
    $type_label = is_scalar($text) ? (string) $text : implode(' ', array_filter(array_map('strval', (array) $text)));
    $types_filter[] = array('id' => (string) $id, 'text' => $type_label);
}

$categories_filter = array(array('id' => '', 'text' => 'Todas'));
foreach ($categories_dropdown as $id => $text) {
    $category_label = is_scalar($text) ? (string) $text : implode(' ', array_filter(array_map('strval', (array) $text)));
    $categories_filter[] = array('id' => (string) $id, 'text' => $category_label);
}

$status_filter = array(array('id' => '', 'text' => 'Todos'));
foreach ($status_options as $id => $text) {
    $status_label = is_scalar($text) ? (string) $text : implode(' ', array_filter(array_map('strval', (array) $text)));
    $status_filter[] = array('id' => (string) $id, 'text' => $status_label);
}
?>
<div id="page-content" class="page-wrapper clearfix">
    <div class="card">
        <div class="page-title clearfix">
            <h1><?php echo app_lang('laudostecnicos_checklists_title'); ?></h1>
            <div class="title-button-group">
                <?php if (!empty($can_manage_checklists)) { ?>
                    <?php echo modal_anchor(get_uri('laudostecnicos/checklists/modal_form'), "<i data-feather='plus-circle' class='icon-16'></i> " . app_lang('add_item'), array('class' => 'btn btn-primary', 'title' => app_lang('laudostecnicos_checklists_title'))); ?>
                <?php } ?>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="laudostecnicos-checklists-table" class="display" cellspacing="0" width="100%"></table>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(function () {
        $("#laudostecnicos-checklists-table").appTable({
            source: '<?php echo_uri("laudostecnicos/checklists/list_data") ?>',
            filterDropdown: [
                {name: "status", class: "w180", options: <?php echo json_encode($status_filter, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>},
                {name: "type_id", class: "w220", options: <?php echo json_encode($types_filter, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>},
                {name: "category_id", class: "w220", options: <?php echo json_encode($categories_filter, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>}
            ],
            columns: [
                {title: "Nome", "class": "all"},
                {title: "Codigo", "class": "desktop"},
                {title: "Categoria", "class": "desktop"},
                {title: "Tipo", "class": "desktop"},
                {title: "Versao", "class": "text-center w90"},
                {title: "Status", "class": "text-center w120"},
                {title: "Padrao", "class": "text-center w90"},
                {title: "Ativo", "class": "text-center w90"},
                {title: "Publicado em", "class": "desktop"},
                {title: "<i data-feather='menu' class='icon-16'></i>", "class": "text-center option w260"}
            ],
            order: [[4, 'desc'], [0, 'asc']]
        });
    });
</script>
