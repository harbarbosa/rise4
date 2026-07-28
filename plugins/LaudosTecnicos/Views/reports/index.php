<div id="page-content" class="page-wrapper clearfix">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="page-title clearfix">
                    <h1>Relatórios</h1>
                </div>
                
                <div class="card-header">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Data Início</label>
                                <input type="date" id="start_date" class="form-control" value="<?php echo date('Y-m-01'); ?>" />
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Data Fim</label>
                                <input type="date" id="end_date" class="form-control" value="<?php echo date('Y-m-t'); ?>" />
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Relatório</label>
                                <select id="report_type" class="form-control">
                                    <option value="laudos_period">Laudos por Período</option>
                                    <option value="laudos_client">Laudos por Cliente</option>
                                    <option value="laudos_status">Laudos por Status</option>
                                    <option value="laudos_type">Laudos por Tipo</option>
                                    <option value="laudos_overdue">Laudos Vencidos</option>
                                    <option value="nonconformities">Não Conformidades</option>
                                    <option value="action_plans">Planos de Ação</option>
                                    <option value="inspections_unproductive">Inspeções Improdutivas</option>
                                    <option value="equipment_calibration">Equipamentos sem Calibração</option>
                                    <option value="productivity">Produtividade</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>&nbsp;</label>
                                <button class="btn btn-primary w-100" onclick="runReport()">
                                    <i data-feather="play" class="icon-16"></i> Gerar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card-body">
                    <div id="report-results"></div>
                </div>
                
                <div class="card-footer">
                    <button class="btn btn-success" onclick="exportReport('csv')">
                        <i data-feather="file" class="icon-16"></i> Exportar CSV
                    </button>
                    <button class="btn btn-danger" onclick="exportReport('pdf')">
                        <i data-feather="file-text" class="icon-16"></i> Exportar PDF
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function runReport() {
    var type = $('#report_type').val();
    var start = $('#start_date').val();
    var end = $('#end_date').val();
    
    $.ajax({
        url: '<?php echo get_uri("laudo_reports/run/"); ?>' + type,
        type: 'GET',
        data: { start_date: start, end_date: end },
        success: function(response) {
            if (response.success) {
                renderReport(response.data);
            }
        }
    });
}

function renderReport(data) {
    if (!data || (Array.isArray(data) && data.length === 0)) {
        $('#report-results').html('<p class="text-muted">Nenhum dado encontrado</p>');
        return;
    }
    
    var html = '<table class="table table-bordered"><thead><tr>';
    
    if (typeof data === 'object' && !Array.isArray(data)) {
        // Dados único (ex: productivity)
        for (var key in data) {
            html += '<th>' + key + '</th>';
        }
        html += '</tr></thead><tbody><tr>';
        for (var key in data) {
            html += '<td>' + data[key] + '</td>';
        }
        html += '</tr></tbody>';
    } else {
        // Array de dados
        var keys = Object.keys(data[0]);
        for (var i = 0; i < keys.length; i++) {
            html += '<th>' + keys[i] + '</th>';
        }
        html += '</tr></thead><tbody>';
        for (var i = 0; i < data.length; i++) {
            html += '<tr>';
            for (var j = 0; j < keys.length; j++) {
                html += '<td>' + data[i][keys[j]] + '</td>';
            }
            html += '</tr>';
        }
        html += '</tbody>';
    }
    
    html += '</table>';
    $('#report-results').html(html);
}

function exportReport(format) {
    var type = $('#report_type').val();
    var start = $('#start_date').val();
    var end = $('#end_date').val();
    
    window.open('<?php echo get_uri("laudo_reports/export/"); ?>' + type + '/' + format + '?start_date=' + start + '&end_date=' + end);
}
</script>