<div id="page-content" class="page-wrapper clearfix">
    <div class="card">
        <div class="page-title clearfix">
            <h1>Checklists</h1>
            <div class="title-button-group">
                <?php echo modal_anchor(get_uri("laudo_technical/checklist_edit"), "<i data-feather='plus' class='icon-16'></i> Novo Checklist", array("class" => "btn btn-primary", "title" => "Novo Checklist")); ?>
            </div>
        </div>

        <div class="card-header">
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Tipo de Laudo</label>
                        <?php echo form_dropdown('laudo_type_id', $types_dropdown, '', "class='form-control' id='filter_type'"); ?>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Status</label>
                        <?php echo form_dropdown('status', $status_list, '', "class='form-control' id='filter_status'"); ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="table-responsive">
            <table id="checklists-table" class="display" width="100%"></table>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    var table = $("#checklists-table").appTable({
        source: '<?php echo_uri("laudo_technical/checklists_list_data") ?>',
        columns: [
            { title: '<?php echo app_lang("id"); ?>', "class": "w50" },
            { title: '<?php echo app_lang("laudos_name"); ?>' },
            { title: '<?php echo app_lang("laudos_code"); ?>' },
            { title: '<?php echo app_lang("laudos_type"); ?>' },
            { title: 'Itens' },
            { title: '<?php echo app_lang("status"); ?>' },
            { title: '<?php echo app_lang("created_at"); ?>' },
            { title: '<?php echo app_lang("actions"); ?>', "class": "w150" }
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