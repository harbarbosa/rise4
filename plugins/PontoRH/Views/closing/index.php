<?php
$rows = $rows ?? array();
$pending_count = (int) ($pending_count ?? 0);
$closed_count = count(array_filter($rows, static function ($row) { return ($row['status'] ?? '') === 'closed'; }));
?>
<style>
    .pontorh-closing .metric-strip {display:flex;flex-wrap:wrap;border-bottom:1px solid #eef1f4;}
    .pontorh-closing .metric {flex:1;min-width:180px;padding:16px 20px;border-right:1px solid #eef1f4;}
    .pontorh-closing .metric:last-child {border-right:0;}
    .pontorh-closing .metric-label {font-size:12px;color:#8a8f98;margin-bottom:4px;}
    .pontorh-closing .metric-value {font-size:23px;font-weight:600;color:#4e5d6c;}
    .pontorh-closing table td,.pontorh-closing table th {vertical-align:middle;white-space:nowrap;}
</style>
<div id="page-content" class="page-wrapper clearfix pontorh-closing">
    <div class="card">
        <div class="page-title clearfix">
            <div>
                <h1>Fechamento do Ponto</h1>
                <div class="text-muted">Conferência final do período antes de bloquear alterações.</div>
            </div>
            <div class="title-button-group">
                <a href="<?php echo get_uri('pontorh/tratamento'); ?>" class="btn btn-default"><i data-feather="alert-circle" class="icon-16"></i> Tratar pendências</a>
            </div>
        </div>
        <div class="card-body border-bottom">
            <form method="get" action="<?php echo get_uri('pontorh/fechamento'); ?>">
                <div class="row align-items-end">
                    <div class="col-md-3"><label class="form-label">Mês</label><?php echo form_dropdown('month', $month_dropdown, $month, 'class="form-control select2"'); ?></div>
                    <div class="col-md-2"><label class="form-label">Ano</label><?php echo form_dropdown('year', $year_dropdown, $year, 'class="form-control select2"'); ?></div>
                    <div class="col-md-7"><button class="btn btn-primary" type="submit"><i data-feather="refresh-cw" class="icon-16"></i> Atualizar</button></div>
                </div>
            </form>
        </div>
        <div class="metric-strip">
            <div class="metric"><div class="metric-label">Funcionários</div><div class="metric-value"><?php echo count($rows); ?></div></div>
            <div class="metric"><div class="metric-label">Pendências abertas</div><div class="metric-value <?php echo $pending_count ? 'text-danger' : 'text-success'; ?>"><?php echo $pending_count; ?></div></div>
            <div class="metric"><div class="metric-label">Períodos fechados</div><div class="metric-value"><?php echo $closed_count; ?>/<?php echo count($rows); ?></div></div>
        </div>
        <div class="card-body">
            <?php if ($pending_count) { ?><div class="alert alert-warning"><strong>Fechamento bloqueado.</strong> Resolva as pendências deste período antes de fechar.</div><?php } ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead><tr><th>Funcionário</th><th>Previsto</th><th>Trabalhado</th><th>Horas extras</th><th>Faltas</th><th>Atrasos</th><th>Saldo</th><th>Situação</th></tr></thead>
                    <tbody>
                    <?php foreach ($rows as $row) {
                        $member = model('App\\Models\\Users_model')->get_one((int) $row['team_member_id']);
                        $name = trim(($member->first_name ?? '') . ' ' . ($member->last_name ?? ''));
                        $balance = (int) ($row['worked_minutes'] ?? 0) - (int) ($row['expected_minutes'] ?? 0);
                    ?>
                        <tr>
                            <td><?php echo esc($name ?: ('#' . $row['team_member_id'])); ?></td>
                            <td><?php echo pontorh_minutes_to_hours_label($row['expected_minutes'] ?? 0); ?></td>
                            <td><?php echo pontorh_minutes_to_hours_label($row['worked_minutes'] ?? 0); ?></td>
                            <td><?php echo pontorh_minutes_to_hours_label($row['overtime_minutes'] ?? 0); ?></td>
                            <td><?php echo pontorh_minutes_to_hours_label($row['absence_minutes'] ?? 0); ?></td>
                            <td><?php echo pontorh_minutes_to_hours_label($row['late_minutes'] ?? 0); ?></td>
                            <td class="<?php echo $balance < 0 ? 'text-danger' : 'text-success'; ?>"><strong><?php echo pontorh_minutes_to_hours_label($balance); ?></strong></td>
                            <td><?php echo ($row['status'] ?? '') === 'closed' ? '<span class="badge bg-success">Fechado</span>' : '<span class="badge bg-warning text-dark">Aberto</span>'; ?></td>
                        </tr>
                    <?php } ?>
                    <?php if (!$rows) { ?><tr><td colspan="8" class="text-center text-muted p20">Nenhum funcionário disponível.</td></tr><?php } ?>
                    </tbody>
                </table>
            </div>
            <div class="d-flex gap-2 justify-content-end mt-3">
                <?php if ($can_reopen && $closed_count) { ?><button id="pontorh-reopen-period" class="btn btn-default"><i data-feather="unlock" class="icon-16"></i> Reabrir período</button><?php } ?>
                <?php if (!$pending_count && $closed_count < count($rows)) { ?><button id="pontorh-close-period" class="btn btn-primary"><i data-feather="lock" class="icon-16"></i> Fechar período</button><?php } ?>
            </div>
        </div>
    </div>
</div>
<script>
$(document).ready(function () {
    $('.pontorh-closing .select2').select2();
    function submitPeriod(url) {
        appLoader.show();
        $.post(url, {month: <?php echo (int) $month; ?>, year: <?php echo (int) $year; ?>}, function (response) {
            appLoader.hide();
            if (response.success) { appAlert.success(response.message, {duration: 4000}); setTimeout(function(){ location.reload(); }, 700); }
            else { appAlert.error(response.message); }
        }, 'json').fail(function(){ appLoader.hide(); appAlert.error('Não foi possível processar o fechamento.'); });
    }
    $('#pontorh-close-period').on('click', function(){ if (confirm('Após o fechamento, as marcações deste mês ficarão bloqueadas até uma reabertura. Continuar?')) submitPeriod('<?php echo get_uri('pontorh/fechamento/fechar'); ?>'); });
    $('#pontorh-reopen-period').on('click', function(){ if (confirm('Reabrir este período e permitir alterações novamente?')) submitPeriod('<?php echo get_uri('pontorh/fechamento/reabrir'); ?>'); });
});
</script>