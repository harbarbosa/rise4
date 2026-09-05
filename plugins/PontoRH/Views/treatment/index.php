<?php
$summary = $summary ?? array();
$filters = $filters ?? array();
$team_members_dropdown = $team_members_dropdown ?? array();
$status_dropdown = $status_dropdown ?? array();
$pending_type_dropdown = $pending_type_dropdown ?? array();
?>

<style>
    .pontorh-treatment-index .pontorh-metric {border-right:1px solid #eef1f4;padding:16px 20px;min-height:76px;}
    .pontorh-treatment-index .pontorh-metric:last-child {border-right:0;}
    .pontorh-treatment-index .pontorh-metric-label {font-size:12px;color:#8a8f98;margin-bottom:4px;}
    .pontorh-treatment-index .pontorh-metric-value {font-size:23px;font-weight:600;color:#4e5d6c;}
    .pontorh-treatment-index .filters-row .form-label {font-size:12px;color:#69727d;margin-bottom:5px;}
    .pontorh-treatment-index #pontorh-treatment-table td {vertical-align:middle;}
    .pontorh-treatment-index #pontorh-treatment-table td:nth-child(1),
    .pontorh-treatment-index #pontorh-treatment-table td:nth-child(2),
    .pontorh-treatment-index #pontorh-treatment-table td:nth-child(5),
    .pontorh-treatment-index #pontorh-treatment-table td:nth-child(8) {white-space:nowrap;}
</style>

<div id="page-content" class="page-wrapper clearfix pontorh-treatment-index">
    <div class="card">
        <div class="page-title clearfix">
            <div>
                <h1>Tratamento de Ponto</h1>
                <div class="text-muted">Confira apenas os dias que precisam de atenção antes do fechamento.</div>
            </div>
            <div class="title-button-group">
                <?php echo modal_anchor(get_uri('pontorh/tratamento/modal_form'), "<i data-feather='plus' class='icon-16'></i> Adicionar marcação", array('class' => 'btn btn-primary', 'title' => 'Adicionar marcação')); ?>
            </div>
        </div>

        <div class="card-body border-bottom">
            <form method="get" action="<?php echo get_uri('pontorh/tratamento'); ?>" class="general-form">
                <div class="row filters-row align-items-end">
                    <div class="col-lg-2 col-md-4 col-sm-6">
                        <label class="form-label">Data inicial</label>
                        <?php echo form_input(array('name' => 'date_from', 'id' => 'pontorh-treatment-date-from', 'class' => 'form-control datepicker', 'autocomplete' => 'off', 'value' => $filters['date_from'] ?? get_my_local_time('Y-m-01'))); ?>
                    </div>
                    <div class="col-lg-2 col-md-4 col-sm-6">
                        <label class="form-label">Data final</label>
                        <?php echo form_input(array('name' => 'date_to', 'id' => 'pontorh-treatment-date-to', 'class' => 'form-control datepicker', 'autocomplete' => 'off', 'value' => $filters['date_to'] ?? get_my_local_time('Y-m-t'))); ?>
                    </div>
                    <div class="col-lg-3 col-md-4 col-sm-6">
                        <label class="form-label">Funcionário</label>
                        <?php echo form_dropdown('team_member_id', $team_members_dropdown, $filters['team_member_id'] ?? '', 'class="form-control select2" id="pontorh-treatment-team-member"'); ?>
                    </div>
                    <div class="col-lg-2 col-md-4 col-sm-6">
                        <label class="form-label">Situação</label>
                        <?php echo form_dropdown('status', $status_dropdown, $filters['status'] ?? '', 'class="form-control select2" id="pontorh-treatment-status"'); ?>
                    </div>
                    <div class="col-lg-3 col-md-8 col-sm-6">
                        <label class="form-label">Ocorrência</label>
                        <?php echo form_dropdown('pending_type', $pending_type_dropdown, $filters['pending_type'] ?? '', 'class="form-control select2" id="pontorh-treatment-pending-type"'); ?>
                    </div>
                    <div class="col-12 mt-3">
                        <button type="submit" class="btn btn-primary"><i data-feather="filter" class="icon-16"></i> Filtrar</button>
                        <a href="<?php echo get_uri('pontorh/tratamento'); ?>" class="btn btn-default">Limpar</a>
                    </div>
                </div>
            </form>
        </div>

        <div class="row g-0 border-bottom">
            <div class="col-md-3 pontorh-metric">
                <div class="pontorh-metric-label">Jornadas incompletas</div>
                <div class="pontorh-metric-value"><?php echo (int) get_array_value($summary, 'incomplete_days', 0); ?></div>
            </div>
            <div class="col-md-3 pontorh-metric">
                <div class="pontorh-metric-label">Marcações inconsistentes</div>
                <div class="pontorh-metric-value"><?php echo (int) get_array_value($summary, 'inconsistent_days', 0); ?></div>
            </div>
            <div class="col-md-3 pontorh-metric">
                <div class="pontorh-metric-label">Fora do local</div>
                <div class="pontorh-metric-value"><?php echo (int) get_array_value($summary, 'outside_area', 0); ?></div>
            </div>
            <div class="col-md-3 pontorh-metric">
                <div class="pontorh-metric-label">Aguardando justificativa</div>
                <div class="pontorh-metric-value"><?php echo (int) get_array_value($summary, 'awaiting_justification', 0); ?></div>
            </div>
        </div>

        <div class="table-responsive">
            <table id="pontorh-treatment-table" class="display" cellspacing="0" width="100%"></table>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function () {
        $(".page-wrapper .select2").select2();
        setDatePicker("#pontorh-treatment-date-from", {});
        setDatePicker("#pontorh-treatment-date-to", {});

        $("#pontorh-treatment-table").appTable({
            source: "<?php echo_uri('pontorh/tratamento/list_data'); ?>",
            filterParams: $.extend({datatable: true}, {
                date_from: $("#pontorh-treatment-date-from").val(),
                date_to: $("#pontorh-treatment-date-to").val(),
                team_member_id: $("#pontorh-treatment-team-member").val(),
                status: $("#pontorh-treatment-status").val(),
                pending_type: $("#pontorh-treatment-pending-type").val()
            }),
            order: [[1, "desc"]],
            columns: [
                {title: "Funcionário"},
                {title: "Data"},
                {title: "Projeto"},
                {title: "Marcações", "class": "text-center w90"},
                {title: "Situação"},
                {title: "Ocorrência"},
                {title: "Última atualização"},
                {title: "<i data-feather='menu' class='icon-16'></i>", "class": "text-center option w90"}
            ]
        });
    });
</script>