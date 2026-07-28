<div id="page-content" class="page-wrapper clearfix">
    <div class="card">
        <div class="page-title clearfix">
            <h1><?php echo app_lang('laudos_templates_title'); ?></h1>
            <div class="title-button-group">
                <?php echo modal_anchor(get_uri("laudos_templates/edit"), "<i data-feather='plus' class='icon-16'></i> " . app_lang('laudos_template_add'), array("class" => "btn btn-primary", "title" => app_lang('laudos_template_add'))); ?>
            </div>
        </div>

        <div class="card-header">
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label><?php echo app_lang('laudos_type'); ?></label>
                        <?php echo form_dropdown('laudo_type_id', $types_dropdown, '', "class='form-control' id='filter_type'"); ?>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label><?php echo app_lang('status'); ?></label>
                        <?php echo form_dropdown('status', $status_list, '', "class='form-control' id='filter_status'"); ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="table-responsive">
            <table id="templates-table" class="display" width="100%"></table>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    var table = $("#templates-table").appTable({
        source: '<?php echo_uri("laudos_templates/list_data") ?>',
        columns: [
            { title: '<?php echo app_lang("id"); ?>', "class": "w50" },
            { title: '<?php echo app_lang("laudos_name"); ?>' },
            { title: '<?php echo app_lang("laudos_code"); ?>' },
            { title: '<?php echo app_lang("laudos_type"); ?>' },
            { title: '<?php echo app_lang("default"); ?>' },
            { title: '<?php echo app_lang("status"); ?>' },
            { title: '<?php echo app_lang("created_at"); ?>' },
            { title: '<?php echo app_lang("actions"); ?>', "class": "w200" }
        ],
        order: [[1, 'asc']],
        filterParams: {
            laudo_type_id: function() { return $('#filter_type').val(); },
            status: function() { return $('#filter_status').val(); }
        }
    });
    
    $('#filter_type, #filter_status').change(function() {
        table.reload({
            laudo_type_id: $('#filter_type').val(),
            status: $('#filter_status').val()
        });
    });
});
</script>