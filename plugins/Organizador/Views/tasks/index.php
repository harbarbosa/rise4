<?php
load_js(array(
    "assets/js/app_helper.js"
));

$quick_filters_dropdown = array();
$quick_filters_dropdown[] = array(
    "id" => "",
    "text" => "- " . app_lang("quick_filters") . " -",
);
foreach ($filters_dropdown as $key => $label) {
    $quick_filters_dropdown[] = array(
        "id" => $key,
        "text" => $label,
    );
}
$initial_filter_params = array(
    "datatable" => true,
);
if (!empty($selected_quick_filter)) {
    $initial_filter_params["quick_filter"] = $selected_quick_filter;
}
if (!empty($selected_status)) {
    $initial_filter_params["status"] = $selected_status;
}
if (!empty($selected_priority)) {
    $initial_filter_params["priority"] = $selected_priority;
}
if (!empty($selected_category_id)) {
    $initial_filter_params["category_id"] = $selected_category_id;
}

$status_filters_dropdown = array();
$status_filters_dropdown[] = array(
    "id" => "",
    "text" => "- " . app_lang("status") . " -",
);
foreach ($statuses_dropdown as $key => $label) {
    $status_filters_dropdown[] = array(
        "id" => $key,
        "text" => $label,
    );
}

$priority_filters_dropdown = array();
$priority_filters_dropdown[] = array(
    "id" => "",
    "text" => "- " . app_lang("priority") . " -",
);
foreach ($priorities_dropdown as $key => $label) {
    $priority_filters_dropdown[] = array(
        "id" => $key,
        "text" => $label,
    );
}

$category_filters_dropdown = array();
$category_filters_dropdown[] = array(
    "id" => "",
    "text" => "- " . app_lang("category") . " -",
);
foreach ($categories_dropdown as $key => $label) {
    $category_filters_dropdown[] = array(
        "id" => $key,
        "text" => $label,
    );
}
?>

<div id="page-content" class="page-wrapper clearfix grid-button">
    <div class="card">
        <div class="page-title clearfix">
            <h1><?php echo app_lang('organizador_tasks'); ?></h1>
            <div class="title-button-group">
                <?php if (\Organizador\Plugin::canAddTasks($login_user)) { ?>
                    <?php echo modal_anchor(get_uri('organizador/tasks/modal_form'), "<i data-feather='plus-circle' class='icon-16'></i> " . app_lang('organizador_new_task'), array('class' => 'btn btn-primary', 'title' => app_lang('organizador_new_task'))); ?>
                <?php } ?>
            </div>
        </div>

        <div class="table-responsive">
            <table id="organizador-table" class="display" cellspacing="0" width="100%"></table>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function () {
        window.organizadorRefreshAfterSave = function () {
            $("#organizador-table").appTable({reload: true});
        };

        var filterDropdown = [
            {name: "quick_filter", class: "w200", options: <?php echo json_encode($quick_filters_dropdown); ?>},
            {name: "status", class: "w150", options: <?php echo json_encode($status_filters_dropdown); ?>},
            {name: "priority", class: "w150", options: <?php echo json_encode($priority_filters_dropdown); ?>},
            {name: "category_id", class: "w200", options: <?php echo json_encode($category_filters_dropdown); ?>}
        ];

        $("#organizador-table").appTable({
            source: '<?php echo_uri("organizador/tasks/list_data") ?>',
            order: [[4, "asc"]],
            showSearchBox: true,
            filterDropdown: filterDropdown,
            filterParams: <?php echo json_encode($initial_filter_params); ?>,
            columns: [
                {title: "<?php echo app_lang('title'); ?>"},
                {title: "<?php echo app_lang('organizador_priority'); ?>"},
                {title: "<?php echo app_lang('organizador_status'); ?>"},
                {title: "<?php echo app_lang('organizador_categories'); ?>"},
                {title: "<?php echo app_lang('organizador_due_date'); ?>"},
                {title: "<?php echo app_lang('organizador_task_assigned_to'); ?>"},
                {title: "<?php echo app_lang('options'); ?>", className: "text-center option w100"}
            ],
            printColumns: [0, 1, 2, 3, 4, 5]
        });
    });
</script>
