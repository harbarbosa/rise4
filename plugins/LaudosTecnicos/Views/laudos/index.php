<div id="page-content" class="page-wrapper clearfix">
    <div class="card">
        <div class="page-title clearfix">
            <h1><?php echo app_lang('laudos_list'); ?></h1>
            <div class="title-button-group">
                <?php echo modal_anchor(get_uri("laudos_tecnicos/modal_form"), "<i data-feather='plus' class='icon-16'></i> " . app_lang('laudos_add'), array("class" => "btn btn-primary", "title" => app_lang('laudos_add'))); ?>
                <a href="<?php echo get_uri('laudos_tecnicos/export'); ?>" class="btn btn-default">
                    <i data-feather="download" class="icon-16"></i> <?php echo app_lang('export'); ?>
                </a>
            </div>
        </div>

        <div class="card-header">
            <div class="row">
                <div class="col-md-2">
                    <div class="form-group">
                        <label><?php echo app_lang('laudos_type'); ?></label>
                        <?php echo form_dropdown('laudo_type_id', $types_dropdown, '', "class='form-control' id='filter_type'"); ?>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label><?php echo app_lang('laudos_category'); ?></label>
                        <?php echo form_dropdown('category_id', $categories_dropdown, '', "class='form-control' id='filter_category'"); ?>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label><?php echo app_lang('laudos_status'); ?></label>
                        <?php echo form_dropdown('status', $status_list, '', "class='form-control' id='filter_status'"); ?>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label><?php echo app_lang('priority'); ?></label>
                        <select name="priority" id="filter_priority" class="form-control">
                            <option value=""><?php echo app_lang('laudos_all'); ?></option>
                            <option value="low"><?php echo app_lang('laudos_priority_low'); ?></option>
                            <option value="normal"><?php echo app_lang('laudos_priority_normal'); ?></option>
                            <option value="high"><?php echo app_lang('laudos_priority_high'); ?></option>
                            <option value="urgent"><?php echo app_lang('laudos_priority_urgent'); ?></option>
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label><?php echo app_lang('start_date'); ?></label>
                        <?php echo form_input(array('type' => 'date', 'name' => 'start_date', 'id' => 'filter_start_date', 'class' => 'form-control')); ?>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label><?php echo app_lang('end_date'); ?></label>
                        <?php echo form_input(array('type' => 'date', 'name' => 'end_date', 'id' => 'filter_end_date', 'class' => 'form-control')); ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="table-responsive">
            <table id="laudos-table" class="display" width="100%"></table>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    var table = $("#laudos-table").appTable({
        source: '<?php echo_uri("laudos_tecnicos/list_data") ?>',
        columns: [
            { title: '<?php echo app_lang("id"); ?>', "class": "w50" },
            { title: '<?php echo app_lang("laudos_number"); ?>' },
            { title: '<?php echo app_lang("laudos_revision"); ?>' },
            { title: '<?php echo app_lang("laudos_title"); ?>' },
            { title: '<?php echo app_lang("laudos_client"); ?>' },
            { title: '<?php echo app_lang("project"); ?>' },
            { title: '<?php echo app_lang("laudos_type"); ?>' },
            { title: '<?php echo app_lang("laudos_category"); ?>' },
            { title: '<?php echo app_lang("laudos_technician"); ?>' },
            { title: '<?php echo app_lang("laudos_request_date"); ?>' },
            { title: '<?php echo app_lang("laudos_inspection_date"); ?>' },
            { title: '<?php echo app_lang("laudos_issue_date"); ?>' },
            { title: '<?php echo app_lang("laudos_valid_until"); ?>' },
            { title: '<?php echo app_lang("laudos_status"); ?>' },
            { title: '<?php echo app_lang("priority"); ?>' },
            { title: '<?php echo app_lang("actions"); ?>', "class": "w150" }
        ],
        order: [[0, 'desc']],
        filterParams: {
            laudo_type_id: function() { return $('#filter_type').val(); },
            category_id: function() { return $('#filter_category').val(); },
            status: function() { return $('#filter_status').val(); },
            priority: function() { return $('#filter_priority').val(); },
            start_date: function() { return $('#filter_start_date').val(); },
            end_date: function() { return $('#filter_end_date').val(); }
        }
    });
    
    // Aplicar filtros
    $('#filter_type, #filter_category, #filter_status, #filter_priority, #filter_start_date, #filter_end_date').change(function() {
        table.reload({
            laudo_type_id: $('#filter_type').val(),
            category_id: $('#filter_category').val(),
            status: $('#filter_status').val(),
            priority: $('#filter_priority').val(),
            start_date: $('#filter_start_date').val(),
            end_date: $('#filter_end_date').val()
        });
    });
    
    // Limpar filtros
    $('.filter-clear').click(function() {
        $('#filter_type, #filter_category, #filter_status, #filter_priority, #filter_start_date, #filter_end_date').val('');
        table.reload({});
    });
});
</script>