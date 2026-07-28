<div id="page-content" class="page-wrapper clearfix">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="page-title clearfix">
                    <h1>Não Conformidades</h1>
                    <div class="title-button-group">
                        <?php echo modal_anchor(get_uri("laudo_nonconformities/form"), "<i data-feather='plus' class='icon-16'></i> Nova NC", array("class" => "btn btn-primary", "title" => "Nova Não Conformidade")); ?>
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
                                <label>Classificação</label>
                                <?php echo form_dropdown('classification', $classification_list, '', "class='form-control' id='filter_classification'"); ?>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Nível Mín.</label>
                                <select class="form-control" id="filter_risk_level">
                                    <option value="">Todos</option>
                                    <option value="1">1+</option>
                                    <option value="4">4+ (Médio)</option>
                                    <option value="6">6+ (Alto)</option>
                                    <option value="9">9+ (Crítico)</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Buscar</label>
                                <input type="text" id="filter_search" class="form-control" placeholder="Código, título ou descrição..." />
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table id="nc-table" class="display" width="100%"></table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    var table = $("#nc-table").appTable({
        source: '<?php echo_uri("laudo_nonconformities/list_data") ?>',
        columns: [
            { title: '<?php echo app_lang("id"); ?>', "class": "w50" },
            { title: 'Código' },
            { title: 'Título' },
            { title: '<?php echo app_lang("laudos_client"); ?>' },
            { title: 'Classif.' },
            { title: 'Risco' },
            { title: 'Status' },
            { title: 'Data' },
            { title: '<?php echo app_lang("actions"); ?>', "class": "w100" }
        ],
        order: [[6, 'asc'], [7, 'desc']],
        filterParams: {
            status: function() { return $('#filter_status').val(); },
            classification: function() { return $('#filter_classification').val(); },
            risk_level: function() { return $('#filter_risk_level').val(); },
            search: function() { return $('#filter_search').val(); }
        }
    });
    
    $('#filter_status, #filter_classification, #filter_risk_level').change(function() {
        table.reload({
            status: $('#filter_status').val(),
            classification: $('#filter_classification').val(),
            risk_level: $('#filter_risk_level').val()
        });
    });
    
    $('#filter_search').keyup(function() {
        clearTimeout(window.nc_search_timeout);
        window.nc_search_timeout = setTimeout(function() {
            table.reload({ search: $('#filter_search').val() });
        }, 500);
    });
});
</script>