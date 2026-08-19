<?php
$context_type = trim((string) ($context_type ?? ''));
$context_id = (int) ($context_id ?? 0);
$context_label = trim((string) ($context_label ?? ''));
$can_create_laudos = !empty($can_create_laudos);
$can_edit_laudos = !empty($can_edit_laudos);
$can_delete_drafts = !empty($can_delete_drafts);
$clients_dropdown = is_array($clients_dropdown ?? null) ? $clients_dropdown : array();
$projects_dropdown = is_array($projects_dropdown ?? null) ? $projects_dropdown : array();
$contacts_dropdown = is_array($contacts_dropdown ?? null) ? $contacts_dropdown : array();
$team_members_dropdown = is_array($team_members_dropdown ?? null) ? $team_members_dropdown : array();
$types_dropdown = is_array($types_dropdown ?? null) ? $types_dropdown : array();
$categories_dropdown = is_array($categories_dropdown ?? null) ? $categories_dropdown : array();
$statuses_dropdown = is_array($statuses_dropdown ?? null) ? $statuses_dropdown : array();
$priority_dropdown = is_array($priority_dropdown ?? null) ? $priority_dropdown : array();
$units_dropdown = is_array($units_dropdown ?? null) ? $units_dropdown : array();

$dropdown_to_json = function (array $options) {
    $items = array();
    foreach ($options as $key => $value) {
        if (is_array($value) && isset($value['id']) && isset($value['text'])) {
            $items[] = $value;
            continue;
        }

        $items[] = array('id' => $key, 'text' => $value);
    }

    return json_encode($items);
};

$source_url = 'laudostecnicos/laudos/list_data';
if ($context_type && $context_id) {
    $source_url .= '/' . $context_type . '/' . $context_id;
}

$add_button = '';
if ($can_create_laudos) {
    $add_attrs = array('class' => 'btn btn-primary', 'title' => 'Novo laudo');
    if ($context_type === 'client' && $context_id) {
        $add_attrs['data-post-client_id'] = $context_id;
    }
    if ($context_type === 'project' && $context_id) {
        $add_attrs['data-post-project_id'] = $context_id;
    }
    $add_button = modal_anchor(get_uri('laudostecnicos/laudos/modal_form'), "<i data-feather='plus-circle' class='icon-16'></i> Novo laudo", $add_attrs);
}

if ($context_label === '') {
    $context_label = $context_type === 'client' ? 'Cliente' : ($context_type === 'project' ? 'Projeto' : '');
}
?>

<div class="card border-top-0 rounded-top-0 xs-no-bottom-margin">
<?php if ($context_type && $context_id) { ?>
        <div class="card-body pb-0">
            <div class="alert alert-info mb20">
                <strong><?php echo esc($context_type === 'client' ? 'Cliente' : ($context_type === 'project' ? 'Projeto' : ucfirst($context_type))); ?>:</strong> <?php echo esc($context_label ?: '-'); ?>
            </div>
        </div>
    <?php } ?>
    <div class="card-body pt-0">
        <div class="table-responsive">
            <table id="laudostecnicos-laudos-table" class="display xs-hide-dtr-control no-title" cellspacing="0" width="100%"></table>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function () {
        var filterDropdown = [
            {name: "client_id", class: "w200", options: <?php echo $dropdown_to_json($clients_dropdown); ?>},
            {name: "contact_id", class: "w200", options: <?php echo $dropdown_to_json($contacts_dropdown); ?>},
            {name: "project_id", class: "w200", options: <?php echo $dropdown_to_json($projects_dropdown); ?>},
            {name: "type_id", class: "w200", options: <?php echo $dropdown_to_json($types_dropdown); ?>},
            {name: "category_id", class: "w200", options: <?php echo $dropdown_to_json($categories_dropdown); ?>},
            {name: "responsible_id", class: "w200", options: <?php echo $dropdown_to_json($team_members_dropdown); ?>},
            {name: "reviewer_id", class: "w200", options: <?php echo $dropdown_to_json($team_members_dropdown); ?>},
            {name: "approver_id", class: "w200", options: <?php echo $dropdown_to_json($team_members_dropdown); ?>},
            {name: "status", class: "w180", options: <?php echo $dropdown_to_json($statuses_dropdown); ?>},
            {name: "priority", class: "w150", options: <?php echo $dropdown_to_json($priority_dropdown); ?>},
            {name: "unit_name", class: "w200", options: <?php echo $dropdown_to_json($units_dropdown); ?>}
        ];

        var rangeDatepicker = [
            {startDate: {name: "request_start_date", value: ""}, endDate: {name: "request_end_date", value: ""}, showClearButton: true, label: "Data da solicitação", ranges: ['this_month', 'last_month', 'this_year', 'last_year', 'last_30_days', 'last_7_days']},
            {startDate: {name: "validity_start_date", value: ""}, endDate: {name: "validity_end_date", value: ""}, showClearButton: true, label: "Validade", ranges: ['this_month', 'last_month', 'this_year', 'last_year', 'last_30_days', 'last_7_days']}
        ];

        $("#laudostecnicos-laudos-table").appTable({
            source: "<?php echo_uri($source_url); ?>",
            smartFilterIdentity: "laudostecnicos_laudos_list",
            filterDropdown: filterDropdown,
            rangeDatepicker: rangeDatepicker,
            selectionHandler: {
                batchDeleteUrl: "<?php echo get_uri('laudostecnicos/laudos/delete'); ?>"
            },
            columns: [
                {title: "Número", "class": "all", order_by: "number"},
                {title: "Revisão", "class": "text-center w80", order_by: "revision"},
                {title: "Título", "class": "all", order_by: "title"},
                {title: "Cliente", "class": "desktop", order_by: "client_name"},
                {title: "Projeto", "class": "desktop", order_by: "project_name"},
                {title: "Tipo", "class": "desktop", order_by: "type_name"},
                {title: "Categoria", "class": "desktop", order_by: "category_name"},
                {title: "Responsável", "class": "desktop", order_by: "technical_responsible_name"},
                {title: "Data da solicitação", "class": "text-center w120", order_by: "request_date"},
                {title: "Data da inspeção", "class": "text-center w120", order_by: "inspection_date"},
                {title: "Data de emissão", "class": "text-center w120", order_by: "issue_date"},
                {title: "Validade", "class": "text-center w120", order_by: "validity_date"},
                {title: "Status", "class": "text-center w150", order_by: "status"},
                {title: "Prioridade", "class": "text-center w100", order_by: "priority"},
                {title: "<i data-feather='menu' class='icon-16'></i>", "class": "text-center option w120"}
            ],
            printColumns: [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13],
            xlsColumns: [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13]
        });
    });
</script>
