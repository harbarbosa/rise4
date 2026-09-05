<?php
$case = $case ?? (object) array();
$records = $records ?? array();
$history = $history ?? array();
$diagnostics = $diagnostics ?? array();
$classification = $classification ?? array();
$final = $final ?? array();
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
if (($case->status ?? '') === 'closed' || ($case->status ?? '') === 'complete' || ($case->status ?? '') === 'treated_manual') {
    $status_class = 'bg-success';
} elseif (in_array(($case->status ?? ''), array('incomplete', 'inconsistent', 'outside_area', 'no_photo'), true)) {
    $status_class = 'bg-warning text-dark';
}
?>

<style>
    .pontorh-detail .page-title {display:flex;align-items:center;justify-content:space-between;gap:20px;}
    .pontorh-detail .page-title h1 {margin-bottom:2px;}
    .pontorh-detail .summary-strip {display:flex;flex-wrap:wrap;border-bottom:1px solid #eef1f4;background:#fff;}
    .pontorh-detail .summary-item {min-width:150px;flex:1;padding:16px 18px;border-right:1px solid #eef1f4;}
    .pontorh-detail .summary-item:last-child {border-right:0;}
    .pontorh-detail .summary-label {font-size:12px;color:#8a8f98;margin-bottom:5px;}
    .pontorh-detail .summary-value {font-size:16px;font-weight:600;color:#4e5d6c;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
    .pontorh-detail .timeline-table th,.pontorh-detail .timeline-table td {vertical-align:middle;white-space:nowrap;}
    .pontorh-detail .timeline-table td.type-cell {white-space:normal;min-width:150px;}
    .pontorh-detail .timeline-table td.source-cell {min-width:100px;}
    .pontorh-detail .timeline-table td.location-cell {max-width:220px;overflow:hidden;text-overflow:ellipsis;}
    .pontorh-detail .issue-box {border-left:3px solid #f1b44c;background:#fff9ed;padding:14px 16px;border-radius:3px;}
    .pontorh-detail .issue-box ul {margin:8px 0 0 18px;padding:0;}
    .pontorh-detail .section-title {font-size:16px;font-weight:600;margin:0;}
    .pontorh-detail .compact-card {border:1px solid #eef1f4;border-radius:4px;box-shadow:none;}
    .pontorh-detail .action-icon {margin:0 3px;}
    .pontorh-detail .badge {font-size:12px;font-weight:500;}
    @media (max-width: 767px) {
        .pontorh-detail .page-title {align-items:flex-start;flex-direction:column;}
        .pontorh-detail .summary-item {min-width:50%;}
    }
</style>

<div id="page-content" class="page-wrapper clearfix pontorh-detail">
    <div class="card">
        <div class="page-title clearfix">
            <div>
                <h1>Tratamento de Ponto</h1>
                <div class="text-muted"><?php echo esc($employee_name); ?> · <?php echo esc($work_date); ?> · <?php echo (int) $record_count; ?> <?php echo $record_count === 1 ? 'marcação' : 'marcações'; ?></div>
            </div>
            <div class="title-button-group">
                <?php if ($can_write) { ?>
                    <?php echo modal_anchor(get_uri('pontorh/tratamento/modal_form/' . (int) ($case->id ?? 0)), "<i data-feather='plus' class='icon-16'></i> Adicionar marcação", array('class' => 'btn btn-primary', 'title' => 'Adicionar marcação', 'data-modal-lg' => '1')); ?>
                <?php } ?>
                <a href="<?php echo get_uri('pontorh/tratamento'); ?>" class="btn btn-default"><i data-feather="arrow-left" class="icon-16"></i> Voltar</a>
            </div>
        </div>

        <div class="summary-strip">
            <div class="summary-item">
                <div class="summary-label">Funcionário</div>
                <div class="summary-value" title="<?php echo esc($employee_name); ?>"><?php echo esc($employee_name); ?></div>
            </div>
            <div class="summary-item">
                <div class="summary-label">Data</div>
                <div class="summary-value"><?php echo esc($work_date); ?></div>
            </div>
            <div class="summary-item">
                <div class="summary-label">Situação</div>
                <div class="summary-value"><span class="badge <?php echo $status_class; ?>"><?php echo esc($status_label); ?></span></div>
            </div>
            <div class="summary-item">
                <div class="summary-label">Ocorrência</div>
                <div class="summary-value" title="<?php echo esc($pending_type_label); ?>"><?php echo esc($pending_type_label ?: '-'); ?></div>
            </div>
            <div class="summary-item">
                <div class="summary-label">Trabalhado</div>
                <div class="summary-value"><?php echo esc(pontorh_minutes_to_hours_label($minutes_worked)); ?></div>
            </div>
            <div class="summary-item">
                <div class="summary-label">Saldo</div>
                <div class="summary-value"><?php echo esc(pontorh_minutes_to_hours_label($bank_minutes)); ?></div>
            </div>
        </div>

        <div class="card-body">
            <?php if (!empty($friendly_diagnostics)) { ?>
                <div class="issue-box mb-4">
                    <div class="d-flex align-items-center"><i data-feather="alert-triangle" class="icon-16 me-2"></i><strong>O que precisa ser conferido</strong></div>
                    <ul>
                        <?php foreach ($friendly_diagnostics as $line) { ?><li><?php echo esc($line); ?></li><?php } ?>
                    </ul>
                </div>
            <?php } ?>

            <div class="compact-card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="section-title">Marcações do dia</h4>
                    <span class="text-muted small"><?php echo (int) $record_count; ?> <?php echo $record_count === 1 ? 'registro' : 'registros'; ?></span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0 timeline-table">
                        <thead>
                            <tr>
                                <th>Hora</th>
                                <th>Marcação</th>
                                <th>Origem</th>
                                <th>Situação</th>
                                <th>Local</th>
                                <?php if ($can_write) { ?><th class="text-center">Ações</th><?php } ?>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (!empty($records)) { ?>
                            <?php foreach ($records as $record) {
                                $type = (string) ($record->punch_type ?? '');
                                $source = strtolower((string) ($record->source ?? ''));
                                $row_status = strtolower((string) ($record->status ?? ''));
                            ?>
                                <tr>
                                    <td><strong><?php echo esc($record->punch_time ? pontorh_extract_time($record->punch_time) : '-'); ?></strong></td>
                                    <td class="type-cell"><?php echo esc($punch_labels[$type] ?? pontorh_punch_type_label($type)); ?></td>
                                    <td class="source-cell"><?php echo esc($source_labels[$source] ?? ($record->source ?: '-')); ?></td>
                                    <td><?php echo esc($status_labels[$row_status] ?? ($record->status ?: '-')); ?></td>
                                    <td class="location-cell" title="<?php echo esc($record->location_name ?? ''); ?>"><?php echo esc($record->location_name ?: '-'); ?></td>
                                    <?php if ($can_write) { ?>
                                        <td class="text-center text-nowrap">
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

            <div class="row">
                <div class="col-lg-7">
                    <div class="compact-card mb-4">
                        <div class="card-header"><h4 class="section-title">Tratamento</h4></div>
                        <div class="card-body">
                            <?php echo form_open(get_uri('pontorh/tratamento/action'), array('id' => 'pontorh-treatment-action-form', 'class' => 'general-form')); ?>
                            <input type="hidden" name="case_id" value="<?php echo (int) ($case->id ?? 0); ?>" />
                            <div class="row">
                                <div class="col-md-5">
                                    <div class="form-group">
                                        <label>Ação</label>
                                        <select name="action_type" class="form-control select2 w100p" required>
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
                                </div>
                                <div class="col-md-7">
                                    <div class="form-group">
                                        <label>Justificativa</label>
                                        <textarea name="justification" class="form-control" rows="3" placeholder="Informe o motivo do tratamento realizado"></textarea>
                                    </div>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary"><i data-feather="check" class="icon-16"></i> Salvar tratamento</button>
                            <?php echo form_close(); ?>
                        </div>
                    </div>
                </div>
                <div class="col-lg-5">
                    <div class="compact-card mb-4">
                        <div class="card-header"><h4 class="section-title">Histórico</h4></div>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead><tr><th>Data</th><th>Ação</th><th>Responsável</th></tr></thead>
                                <tbody>
                                <?php if (!empty($history)) { ?>
                                    <?php foreach ($history as $item) { ?>
                                        <tr>
                                            <td class="text-nowrap"><?php echo !empty($item->created_at) ? format_to_datetime($item->created_at) : '-'; ?></td>
                                            <td><?php echo esc($history_action_labels[$item->action ?? ''] ?? ($item->action ?? '-')); ?></td>
                                            <td><?php echo esc($item->creator_name ?: '-'); ?></td>
                                        </tr>
                                    <?php } ?>
                                <?php } else { ?>
                                    <tr><td colspan="3" class="text-center text-muted">Nenhuma ação registrada.</td></tr>
                                <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function () {
        $("#pontorh-treatment-action-form .select2").select2();
        $("#pontorh-treatment-action-form").appForm({
            onSuccess: function () { location.reload(); }
        });
        if (typeof feather !== 'undefined') { feather.replace(); }
    });
</script>