<div id="page-content" class="page-wrapper clearfix">
    <div class="card">
        <div class="page-title clearfix">
            <h1><?php echo app_lang('laudos_status_title'); ?></h1>
            <div class="title-button-group">
                <?php echo modal_anchor(get_uri("laudos_tecnicos/status_modal_form"), "<i data-feather='plus' class='icon-16'></i> " . app_lang('laudos_status_add'), array("class" => "btn btn-primary", "title" => app_lang('laudos_status_add'))); ?>
            </div>
        </div>
        
        <div class="table-responsive">
            <table id="status-table" class="display" width="100%"></table>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $("#status-table").appTable({
        source: '<?php echo_uri("laudos_tecnicos/status_list_data") ?>',
        columns: [
            { title: '<?php echo app_lang("id"); ?>' },
            { title: '<?php echo app_lang("laudos_code"); ?>' },
            { title: '<?php echo app_lang("laudos_name"); ?>' },
            { title: '<?php echo app_lang("laudos_initial"); ?>' },
            { title: '<?php echo app_lang("laudos_final"); ?>' },
            { title: '<?php echo app_lang("laudos_cancel"); ?>' },
            { title: '<?php echo app_lang("laudos_sort_order"); ?>' },
            { title: '<?php echo app_lang("status"); ?>' },
            { title: '<?php echo app_lang("actions"); ?>' }
        ],
        order: [[6, 'asc']]
    });
});
</script>