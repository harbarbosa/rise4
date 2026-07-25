<?php
$type_dropdown = $type_dropdown ?? array();
$category_dropdown = $category_dropdown ?? array();

$type_filters_dropdown = array();
foreach ($type_dropdown as $key => $label) {
    $type_filters_dropdown[] = array('id' => $key, 'text' => $label);
}

$category_filters_dropdown = array();
foreach ($category_dropdown as $key => $label) {
    $category_filters_dropdown[] = array('id' => $key, 'text' => $label);
}
?>

<div id="page-content" class="page-wrapper clearfix grid-button">
    <div class="card">
        <div class="page-title clearfix">
            <h1><?php echo app_lang('licitaia_keywords'); ?></h1>
            <div class="title-button-group">
                <?php if ($can_manage ?? false) { ?>
                    <?php echo modal_anchor(get_uri('licitaia/keywords/modal_form'), "<i data-feather='plus-circle' class='icon-16'></i> " . app_lang('licitaia_new_keyword'), array('class' => 'btn btn-primary', 'title' => app_lang('licitaia_new_keyword'))); ?>
                <?php } ?>
            </div>
        </div>

        <div class="table-responsive">
            <table id="licitaia-keywords-table" class="display" cellspacing="0" width="100%"></table>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function () {
        var filterDropdown = [
            {name: "keyword_type", class: "w180", options: <?php echo json_encode($type_filters_dropdown); ?>},
            {name: "category", class: "w200", options: <?php echo json_encode($category_filters_dropdown); ?>}
        ];

        $("#licitaia-keywords-table").appTable({
            source: "<?php echo_uri('licitaia/keywords/list_data'); ?>",
            order: [[0, "asc"]],
            filterDropdown: filterDropdown,
            filterParams: {datatable: true},
            columns: [
                {title: "<?php echo app_lang('licitaia_keyword'); ?>"},
                {title: "<?php echo app_lang('licitaia_category'); ?>"},
                {title: "<?php echo app_lang('licitaia_keyword_type'); ?>"},
                {title: "<?php echo app_lang('licitaia_weight'); ?>", class: "text-center"},
                {title: "<?php echo app_lang('status'); ?>"},
                {title: "<i data-feather='menu' class='icon-16'></i>", "class": "text-center option w120"}
            ]
        });

        $(document).on("click", ".js-toggle-keyword-status", function (e) {
            e.preventDefault();
            var $el = $(this);
            $.ajax({
                url: $el.data("action-url"),
                type: "post",
                dataType: "json",
                data: {
                    id: $el.data("id"),
                    active: $el.data("active")
                },
                success: function (response) {
                    if (response && response.success) {
                        $("#licitaia-keywords-table").appTable({reload: true});
                    }
                }
            });
        });
    });
</script>
