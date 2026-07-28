<div id="page-content" class="page-wrapper clearfix">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="page-title clearfix">
                    <h1>Inspeções</h1>
                    <div class="title-button-group">
                        <?php echo modal_anchor(get_uri("laudo_inspections/form"), "<i data-feather='plus' class='icon-16'></i> Nova Inspeção", array("class" => "btn btn-primary", "title" => "Nova Inspeção")); ?>
                        <a href="<?php echo get_uri('laudo_inspections/calendar'); ?>" class="btn btn-default">
                            <i data-feather="calendar" class="icon-16"></i> Agenda
                        </a>
                    </div>
                </div>

                <div class="card-header">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Status</label>
                                <?php echo form_dropdown('status', $status_list, '', "class='form-control' id='filter_status'"); ?>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Data Início</label>
                                <input type="date" id="filter_start_date" class="form-control" />
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Data Fim</label>
                                <input type="date" id="filter_end_date" class="form-control" />
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table id="inspections-table" class="display" width="100%"></table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    var table = $("#inspections-table").appTable({
        source: '<?php echo_uri("laudo_inspections/list_data") ?>',
        columns: [
            { title: '<?php echo app_lang("id"); ?>', "class": "w50" },
            { title: '<?php echo app_lang("laudos_code"); ?>' },
            { title: '<?php echo app_lang("laudos_title"); ?>' },
            { title: '<?php echo app_lang("laudos_client"); ?>' },
            { title: '<?php echo app_lang("laudos_inspection_date"); ?>' },
            { title: '<?php echo app_lang("time"); ?>' },
            { title: '<?php echo app_lang("laudos_technician"); ?>' },
            { title: '<?php echo app_lang("status"); ?>' },
            { title: '<?php echo app_lang("actions"); ?>', "class": "w100" }
        ],
        order: [[4, 'desc'], [5, 'asc']],
        filterParams: {
            status: function() { return $('#filter_status').val(); },
            start_date: function() { return $('#filter_start_date').val(); },
            end_date: function() { return $('#filter_end_date').val(); }
        }
    });
    
    $('#filter_status, #filter_start_date, #filter_end_date').change(function() {
        table.reload({
            status: $('#filter_status').val(),
            start_date: $('#filter_start_date').val(),
            end_date: $('#filter_end_date').val()
        });
    });
});
</script>