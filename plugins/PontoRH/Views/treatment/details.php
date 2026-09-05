<?php
$case = $case ?? (object) array();
$records = $records ?? array();
$history = $history ?? array();
$diagnostics = $diagnostics ?? array();
$can_write = !empty($can_write);

$status_label = pontorh_treatment_status_label($case->status ?? '');
$pending_type_label = pontorh_treatment_pending_type_label($case->pending_type ?? '');
$record_count = (int) ($case->record_count ?? count($records));
$minutes_worked = (int) ($case->minutes_worked ?? 0);
$bank_minutes = (int) ($case->bank_minutes ?? 0);
$employee_name = trim((string) ($case->team_member_name ?? '-'));
$work_date = !empty($case->work_date) ? format_to_date($case->work_date, false) : '-';

$history_action_labels = array(
    'reprocess' => 'Reprocessado',
    'request_justification' => 'Justificativa solicitada',
    'ignore_extra' => 'Marcação ignorada',
    'correct_classification' => 'Classificação corrigida',
    'approve_day' => 'Dia aprovado',
    'close_day' => 'Dia fechado',
    'forward_rh' => 'Encaminhado ao RH',
    'manual_mark_added' => 'Marcação manual adicionada',
    'edit' => 'Marcação alterada',
    'delete' => 'Marcação excluída',
);

$punch_labels = array(
    'in' => 'Entrada',
    'lunch_out' => 'Saída para intervalo',
    'lunch_return' => 'Retorno do intervalo',
    'out' => 'Saída',
);

$source_labels = array(
    'mobile_app' => 'App mobile',
    'manual' => 'Manual',
    'system' => 'Sistema',
    'adjustment' => 'Ajuste',
    'restapi' => 'API',
);

$status_labels = array(
    'pending' => 'Pendente',
    'approved' => 'Aprovado',
    'adjusted' => 'Ajustado',
    'outside_area' => 'Fora do local',
    'closed' => 'Fechado',
    'rejected' => 'Rejeitado',
);

$friendly_diagnostics = array();
foreach ((array) $diagnostics as $line) {
    $line = trim((string) $line);
    if ($line === '') {
        continue;
    }
    $line = str_replace(
        array('lunch_return', 'lunch_out', 'out', 'in'),
        array('retorno do intervalo', 'saída para intervalo', 'saída', 'entrada'),
        $line
    );
    $line = str_replace('Dia com menos de 4 marcações.', 'Foram encontradas menos marcações do que o esperado para a jornada.', $line);
    $line = str_replace('Sequência incompleta:', 'Marcações faltantes:', $line);
    $line = str_replace('Sequência fora do padrão esperado.', 'A ordem das marcações está diferente do padrão esperado.', $line);
    $line = str_replace('Existem marcações extras no dia.', 'Existem marcações adicionais que precisam ser conferidas.', $line);
    $friendly_diagnostics[] = $line;
}

$status_class = 'bg-secondary';
if (in_array(($case->status ?? ''), array('closed', 'complete', 'treated_manual'), true)) {
    $status_class = 'bg-success';
} elseif (in_array(($case->status ?? ''), array('incomplete', 'inconsistent', 'outside_area', 'no_photo'), true)) {
    $status_class = 'bg-warning text-dark';
}
?>

<style>
    .pontorh-detail-page {padding-bottom:24px;}
    .pontorh-detail-page .detail-header {display:flex;align-items:flex-start;justify-content:space-between;gap:24px;padding:22px 24px;border-bottom:1px solid #eef1f4;}
    .pontorh-detail-page .detail-title {min-width:0;flex:1;}
    .pontorh-detail-page .detail-title h1 {font-size:22px;line-height:1.3;margin:0 0 7px;font-weight:500;color:#4e5d6c;}
    .pontorh-detail-page .detail-meta {display:flex;flex-wrap:wrap;gap:8px 18px;color:#7b8794;font-size:13px;line-height:1.45;}
    .pontorh-detail-page .detail-meta strong {color:#4e5d6c;font-weight:600;}
    .pontorh-detail-page .detail-actions {display:flex;align-items:center;gap:8px;flex-shrink:0;}

    .pontorh-detail-page .summary-grid {display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:1px;background:#e9edf1;border-bottom:1px solid #e9edf1;}
    .pontorh-detail-page .summary-box {background:#fff;padding:17px 20px;min-width:0;min-height:88px;}
    .pontorh-detail-page .summary-label {font-size:12px;color:#8a949f;margin-bottom:7px;line-height:1.3;}
    .pontorh-detail-page .summary-value {font-size:15px;font-weight:600;color:#4e5d6c;line-height:1.45;overflow-wrap:anywhere;word-break:normal;white-space:normal;}
    .pontorh-detail-page .summary-value.employee {font-size:16px;}

    .pontorh-detail-page .detail-body {padding:22px 24px;}
    .pontorh-detail-page .notice-box {display:flex;gap:12px;padding:14px 16px;margin-bottom:20px;border:1px solid #f4d89b;background:#fffaf0;border-radius:5px;}
    .pontorh-detail-page .notice-box .notice-icon {color:#d69d20;flex-shrink:0;padding-top:2px;}
    .pontorh-detail-page .notice-title {font-weight:600;color:#5b6570;margin-bottom:5px;}
    .pontorh-detail-page .notice-box ul {margin:0;padding-left:18px;color:#66717d;}
    .pontorh-detail-page .notice-box li {margin:3px 0;line-height:1.45;}

    .pontorh-detail-page .section-card {border:1px solid #e8edf2;border-radius:5px;background:#fff;margin-bottom:20px;overflow:hidden;}
    .pontorh-detail-page .section-head {display:flex;align-items:center;justify-content:space-between;gap:12px;padding:14px 17px;border-bottom:1px solid #edf1f4;background:#fbfcfd;}
    .pontorh-detail-page .section-head h3 {font-size:15px;font-weight:600;color:#4e5d6c;margin:0;}
    .pontorh-detail-page .section-count {font-size:12px;color:#8a949f;white-space:nowrap;}
    .pontorh-detail-page .section-body {padding:17px;}

    .pontorh-detail-page .records-table {margin-bottom:0;}
    .pontorh-detail-page .records-table th {font-size:12px;color:#7b8794;font-weight:600;background:#fff;white-space:nowrap;border-top:0;}
    .pontorh-detail-page .records-table td {vertical-align:middle;color:#596672;line-height:1.4;}
    .pontorh-detail-page .records-table .time-cell {font-weight:600;color:#40505f;white-space:nowrap;}
    .pontorh-detail-page .records-table .type-cell {min-width:150px;}
    .pontorh-detail-page .records-table .location-cell {min-width:180px;white-space:normal;overflow-wrap:anywhere;}
    .pontorh-detail-page .record-actions {white-space:nowrap;text-align:right;}
    .pontorh-detail-page .record-actions .action-icon {display:inline-flex;align-items:center;justify-content:center;width:30px;height:30px;margin-left:3px;border-radius:4px;}

    .pontorh-detail-page .bottom-grid {display:grid;grid-template-columns:minmax(0,1.25fr) minmax(360px,.75fr);gap:20px;align-items:start;}
    .pontorh-detail-page .treatment-fields {display:grid;grid-template-columns:minmax(220px,.8fr) minmax(260px,1.2fr);gap:16px;align-items:start;}
    .pontorh-detail-page .form-group {margin-bottom:14px;}
    .pontorh-detail-page .form-group label {display:block;font-size:12px;font-weight:600;color:#687580;margin-bottom:6px;}
    .pontorh-detail-page textarea.form-control {min-height:92px;resize:vertical;}
    .pontorh-detail-page .save-row {display:flex;justify-content:flex-end;padding-top:2px;}

    .pontorh-detail-page .history-table {margin-bottom:0;}
    .pontorh-detail-page .history-table th {font-size:12px;color:#7b8794;font-weight:600;background:#fff;border-top:0;}
    .pontorh-detail-page .history-table td {vertical-align:top;color:#596672;line-height:1.4;}
    .pontorh-detail-page .history-table td:first-child {white-space:nowrap;}

    @media (max-width: 1199px) {
        .pontorh-detail-page .bottom-grid {grid-template-columns:1fr;}
    }
    @media (max-width: 991px) {
        .pontorh-detail-page .summary-grid {grid-template-columns:repeat(2,minmax(0,1fr));}
        .pontorh-detail-page .treatment-fields {grid-template-columns:1fr;gap:0;}
    }
    @media (max-width: 767px) {
        .pontorh-detail-page .detail-header {flex-direction:column;padding:18px;}
        .pontorh-detail-page .detail-actions {width:100%;flex-wrap:wrap;}
        .pontorh-detail-page .detail-actions .btn {flex:1;min-width:145px;}
        .pontorh-detail-page .summary-grid {grid-template-columns:1fr;}
        .pontorh-detail-page .detail-body {padding:16px;}
        .pontorh-detail-page .summary-box {min-height:auto;}
    }
</style>

<div id="page-content" class="page-wrapper clearfix pontorh-detail-page">
    <div class="card">
        <div class="detail-header">
            <div class="detail-title">
                <h1>Tratamento de Ponto</h1>
                <div class="detail-meta">
                    <span><strong><?php echo esc($employee_name); ?></strong></span>
                    <span><?php echo esc($work_date); ?></span>
                    <span><?php echo (int) $record_count; ?> <?php echo $record_count === 1 ? 'marcação' : 'marcações'; ?></span>
                </div>
            </div>
            <div class="detail-actions">
                <?php if ($can_write) { ?>
                    <?php echo modal_anchor(get_uri('pontorh/tratamento/modal_form/' . (int) ($case->id ?? 0)), "<i data-feather='plus' class='icon-16'></i> Adicionar marcação", array('class' => 'btn btn-primary', 'title' => 'Adicionar marcação', 'data-modal-lg' => '1')); ?>
                <?php } ?>
                <a href="<?php echo get_uri('pontorh/tratamento'); ?>" class="btn btn-default"><i data-feather="arrow-left" class="icon-16"></i> Voltar</a>
            </div>
        </div>

        <div class="summary-grid">
            <div class="summary-box">
                <div class="summary-label">Funcionário</div>
                <div class="summary-value employee"><?php echo esc($employee_name); ?></div>
            </div>
            <div class="summary-box">
                <div class="summary-label">Data</div>
                <div class="summary-value"><?php echo esc($work_date); ?></div>
            </div>
            <div class="summary-box">
                <div class="summary-label">Situação</div>
                <div class="summary-value"><span class="badge <?php echo $status_class; ?>"><?php echo esc($status_label ?: '-'); ?></span></div>
            </div>
            <div class="summary-box">
                <div class="summary-label">Ocorrência</div>
                <div class="summary-value"><?php echo esc($pending_type_label ?: '-'); ?></div>
            </div>
            <div class="summary-box">
                <div class="summary-label">Trabalhado</div>
                <div class="summary-value"><?php echo esc(pontorh_minutes_to_hours_label($minutes_worked)); ?></div>
            </div>
            <div class="summary-box">
                <div class="summary-label">Saldo</div>
                <div class="summary-value"><?php echo esc(pontorh_minutes_to_hours_label($bank_minutes)); ?></div>
            </div>
        </div>

        <div class="detail-body">
            <?php if (!empty($friendly_diagnostics)) { ?>
                <div class="notice-box">
                    <div class="notice-icon"><i data-feather="alert-triangle" class="icon-18"></i></div>
                    <div>
                        <div class="notice-title">O que precisa ser conferido</div>
                        <ul><?php foreach ($friendly_diagnostics as $line) { ?><li><?php echo esc($line); ?></li><?php } ?></ul>
                    </div>
                </div>
            <?php } ?>

            <div class="section-card">
                <div class="section-head">
                    <h3>Marcações do dia</h3>
                    <span class="section-count"><?php echo (int) $record_count; ?> <?php echo $record_count === 1 ? 'registro' : 'registros'; ?></span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover records-table">
                        <thead><tr><th>Hora</th><th>Marcação</th><th>Origem</th><th>Situação</th><th>Local</th><?php if ($can_write) { ?><th class="text-end">Ações</th><?php } ?></tr></thead>
                        <tbody>
                        <?php if (!empty($records)) { ?>
                            <?php foreach ($records as $record) {
                                $type = (string) ($record->punch_type ?? '');
                                $source = strtolower((string) ($record->source ?? ''));
                                $row_status = strtolower((string) ($record->status ?? ''));
                                $record_time = !empty($record->punch_time) ? pontorh_extract_time($record->punch_time) : '-';
                                $record_source = $source_labels[$source] ?? ((string) ($record->source ?? '-') ?: '-');
                                $record_status = $status_labels[$row_status] ?? ((string) ($record->status ?? '-') ?: '-');
                                $record_location = (string) ($record->location_name ?? '') ?: '-';
                            ?>
                                <tr>
                                    <td class="time-cell"><?php echo esc($record_time); ?></td>
                                    <td class="type-cell"><?php echo esc($punch_labels[$type] ?? pontorh_punch_type_label($type)); ?></td>
                                    <td><?php echo esc($record_source); ?></td>
                                    <td><?php echo esc($record_status); ?></td>
                                    <td class="location-cell"><?php echo esc($record_location); ?></td>
                                    <?php if ($can_write) { ?>
                                        <td class="record-actions">
                                            <?php echo modal_anchor(get_uri('pontorh/tratamento/record_modal/' . (int) ($case->id ?? 0) . '/' . (int) ($record->id ?? 0) . '/edit'), "<i data-feather='edit-2' class='icon-14'></i>", array('class' => 'action-icon', 'title' => 'Editar marcação', 'data-modal-lg' => '1')); ?>
                                            <?php echo modal_anchor(get_uri('pontorh/tratamento/record_modal/' . (int) ($case->id ?? 0) . '/' . (int) ($record->id ?? 0) . '/delete'), "<i data-feather='trash-2' class='icon-14'></i>", array('class' => 'action-icon text-danger', 'title' => 'Excluir marcação', 'data-modal-lg' => '1')); ?>
                                        </td>
                                    <?php } ?>
                                </tr>
                            <?php } ?>
                        <?php } else { ?>
                            <tr><td colspan="6" class="text-center text-muted p20">Nenhuma marcação encontrada.</td></tr>
                        <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="bottom-grid">
                <div class="section-card">
                    <div class="section-head"><h3>Tratamento</h3></div>
                    <div class="section-body">
                        <?php echo form_open(get_uri('pontorh/tratamento/action'), array('id' => 'pontorh-treatment-action-form', 'class' => 'general-form')); ?>
                        <input type="hidden" name="case_id" value="<?php echo (int) ($case->id ?? 0); ?>" />
                        <div class="treatment-fields">
                            <div class="form-group">
                                <label for="pontorh-action-type">Ação</label>
                                <select id="pontorh-action-type" name="action_type" class="form-control" required>
                                    <option value="">Selecione...</option>
                                    <option value="approve_day">Aprovar dia</option>
                                    <option value="reprocess">Reprocessar</option>
                                    <option value="request_justification">Solicitar justificativa</option>
                                    <option value="ignore_extra">Ignorar marcação extra</option>
                                    <option value="correct_classification">Confirmar correção</option>
                                    <option value="forward_rh">Encaminhar ao RH</option>
                                    <option value="close_day">Fechar dia</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="pontorh-treatment-justification">Justificativa</label>
                                <textarea id="pontorh-treatment-justification" name="justification" class="form-control" rows="4" placeholder="Informe o motivo do tratamento realizado"></textarea>
                            </div>
                        </div>
                        <div class="save-row"><button type="submit" class="btn btn-primary"><i data-feather="check" class="icon-16"></i> Salvar tratamento</button></div>
                        <?php echo form_close(); ?>
                    </div>
                </div>

                <div class="section-card">
                    <div class="section-head"><h3>Histórico</h3></div>
                    <div class="table-responsive">
                        <table class="table table-hover history-table">
                            <thead><tr><th>Data</th><th>Ação</th><th>Responsável</th></tr></thead>
                            <tbody>
                            <?php if (!empty($history)) { ?>
                                <?php foreach ($history as $item) { ?>
                                    <tr>
                                        <td><?php echo !empty($item->created_at) ? format_to_datetime($item->created_at) : '-'; ?></td>
                                        <td><?php echo esc($history_action_labels[$item->action ?? ''] ?? ($item->action ?? '-')); ?></td>
                                        <td><?php echo esc((string) ($item->creator_name ?? '') ?: '-'); ?></td>
                                    </tr>
                                <?php } ?>
                            <?php } else { ?>
                                <tr><td colspan="3" class="text-center text-muted p20">Nenhuma ação registrada.</td></tr>
                            <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function () {
        $("#pontorh-treatment-action-form").appForm({
            onSuccess: function () { location.reload(); }
        });
        if (typeof feather !== 'undefined') { feather.replace(); }
    });
</script>
