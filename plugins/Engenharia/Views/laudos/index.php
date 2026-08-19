<div id="page-content" class="page-wrapper clearfix">
    <div class="card">
        <div class="page-title clearfix">
            <h1><?php echo app_lang('engenharia_laudos'); ?></h1>
        <div class="title-button-group"><?php if ($can_create) { echo modal_anchor(get_uri('engenharia/laudos/modal_form'), "<i data-feather='plus-circle' class='icon-16'></i> " . app_lang('engenharia_add_laudo'), array('class' => 'btn btn-primary', 'title' => app_lang('engenharia_add_laudo'))); } ?></div>
        </div>
        <?php if ($can_create) { ?><div class="d-md-none mb15"><?php echo modal_anchor(get_uri('engenharia/laudos/modal_form'), "<i data-feather='plus-circle' class='icon-16'></i> " . app_lang('engenharia_add_laudo'), array('class' => 'btn btn-primary w-100', 'title' => app_lang('engenharia_add_laudo'))); ?></div><?php } ?>
        <div class="table-responsive"><table id="engenharia-laudos-table" class="display" cellspacing="0" width="100%"></table></div>
    </div>
</div>
<script type="text/javascript">
$(document).ready(function () { $("#engenharia-laudos-table").appTable({
    source: '<?php echo_uri('engenharia/laudos/list_data'); ?>', serverSide: true,
    filterDropdown: [
        {name: "type_id", class: "w200", options: <?php echo json_encode(engenharia_app_table_options($types_dropdown)); ?>},
        {name: "client_id", class: "w200", options: <?php echo json_encode(engenharia_app_table_options($clients_dropdown)); ?>},
        {name: "technical_responsible_id", class: "w200", options: <?php echo json_encode(engenharia_app_table_options($team_members_dropdown)); ?>},
        {name: "inspection_technician_id", class: "w200", options: <?php echo json_encode(engenharia_app_table_options($team_members_dropdown)); ?>},
        {name: "status", class: "w200", options: <?php echo json_encode(engenharia_app_table_options($status_dropdown)); ?>}
    ],
    rangeDatepicker: [{startDate: {name: "inspection_date_from", value: ""}, endDate: {name: "inspection_date_to", value: ""}, showClearButton: true, label: "<?php echo app_lang('engenharia_inspection_period'); ?>"}],
    columns: [
        {title: "<?php echo app_lang('engenharia_laudo_number'); ?>", class: "all", order_by: "number"}, {title: "<?php echo app_lang('title'); ?>", class: "all", order_by: "title"},
        {title: "<?php echo app_lang('engenharia_type'); ?>", order_by: "type_name"}, {title: "<?php echo app_lang('client'); ?>", order_by: "client_name"},
        {title: "<?php echo app_lang('engenharia_responsible'); ?>"}, {title: "<?php echo app_lang('engenharia_inspection_date'); ?>", order_by: "inspection_date"},
        {title: "<?php echo app_lang('status'); ?>", order_by: "status"}, {title: "<?php echo app_lang('engenharia_last_update'); ?>", order_by: "updated_at"},
        {title: "<i data-feather='menu' class='icon-16'></i>", class: "text-center option w100"}
    ]
}); });
</script>
