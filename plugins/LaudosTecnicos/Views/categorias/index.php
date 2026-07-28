<div id="page-content" class="page-wrapper clearfix">
    <div class="card">
        <div class="page-title clearfix">
            <h1><?php echo app_lang('laudos_categories_title'); ?></h1>
            <div class="title-button-group">
                <?php echo modal_anchor(get_uri("laudos_tecnicos/categoria_modal_form"), "<i data-feather='plus' class='icon-16'></i> " . app_lang('laudos_category_add'), array("class" => "btn btn-primary", "title" => app_lang('laudos_category_add'))); ?>
            </div>
        </div>
        
        <div class="table-responsive">
            <table id="categorias-table" class="display" width="100%"></table>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $("#categorias-table").appTable({
        source: '<?php echo_uri("laudos_tecnicos/categorias_list_data") ?>',
        columns: [
            { title: '<?php echo app_lang("id"); ?>' },
            { title: '<?php echo app_lang("laudos_code"); ?>' },
            { title: '<?php echo app_lang("laudos_name"); ?>' },
            { title: '<?php echo app_lang("description"); ?>' },
            { title: '<?php echo app_lang("status"); ?>' },
            { title: '<?php echo app_lang("laudos_sort_order"); ?>' },
            { title: '<?php echo app_lang("created_at"); ?>' },
            { title: '<?php echo app_lang("actions"); ?>' }
        ],
        order: [[5, 'asc'], [2, 'asc']],
        deleteConfirmation: false
    });
});
</script>