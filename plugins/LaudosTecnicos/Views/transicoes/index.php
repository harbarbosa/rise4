<div id="page-content" class="page-wrapper clearfix">
    <div class="card">
        <div class="page-title clearfix">
            <h1><?php echo app_lang('laudos_transitions_title'); ?></h1>
            <div class="title-button-group">
                <?php echo modal_anchor(get_uri("laudos_tecnicos/transicao_modal_form"), "<i data-feather='plus' class='icon-16'></i> " . app_lang('laudos_transition_add'), array("class" => "btn btn-primary", "title" => app_lang('laudos_transition_add'))); ?>
            </div>
        </div>
        
        <div class="card-header">
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="from_status_filter"><?php echo app_lang('laudos_from_status'); ?></label>
                        <select id="from_status_filter" class="form-control">
                            <option value=""><?php echo app_lang('laudos_all_status'); ?></option>
                            <?php foreach ($status_list as $status): ?>
                            <option value="<?php echo $status->id; ?>"><?php echo $status->name; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="table-responsive">
            <table id="transicoes-table" class="display" width="100%"></table>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    var table = $("#transicoes-table").appTable({
        source: '<?php echo_uri("laudos_tecnicos/transicoes_list_data") ?>',
        columns: [
            { title: '<?php echo app_lang("id"); ?>' },
            { title: '<?php echo app_lang("laudos_from_status"); ?>' },
            { title: '' },
            { title: '<?php echo app_lang("laudos_to_status"); ?>' },
            { title: '<?php echo app_lang("laudos_sort_order"); ?>' },
            { title: '<?php echo app_lang("status"); ?>' },
            { title: '<?php echo app_lang("laudos_require_comment"); ?>' },
            { title: '<?php echo app_lang("laudos_notify"); ?>' },
            { title: '<?php echo app_lang("actions"); ?>' }
        ],
        order: [[4, 'asc']]
    });
    
    $('#from_status_filter').change(function() {
        table.reload({
            from_status_id: $(this).val()
        });
    });
});
</script>