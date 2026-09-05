<style>
    .pontorh-adjustments-index .filter-label {font-size:12px;color:#69727d;margin-bottom:5px;}
    .pontorh-adjustments-index #pontorh-adjustments-table td {vertical-align:middle;}
    .pontorh-adjustments-index #pontorh-adjustments-table td:nth-child(1),
    .pontorh-adjustments-index #pontorh-adjustments-table td:nth-child(2),
    .pontorh-adjustments-index #pontorh-adjustments-table td:nth-child(3),
    .pontorh-adjustments-index #pontorh-adjustments-table td:nth-child(6),
    .pontorh-adjustments-index #pontorh-adjustments-table td:nth-child(7) {white-space:nowrap;}
</style>

<div id="page-content" class="page-wrapper clearfix pontorh-adjustments-index">
    <div class="card">
        <div class="page-title clearfix">
            <div>
                <h1>Ajustes de Ponto</h1>
                <div class="text-muted">Solicitações de correção de marcações e respectivas aprovações.</div>
            </div>
            <div class="title-button-group">
                <?php if ($can_request) { ?>
                    <?php echo modal_anchor(get_uri('pontorh/ajustes/modal_form'), "<i data-feather='plus' class='icon-16'></i> Solicitar ajuste", array('class' => 'btn btn-primary', 'title' => 'Solicitar ajuste', 'data-modal-lg' => '1')); ?>
                <?php } ?>
            </div>
        </div>

        <div class="card-body border-bottom">
            <div class="row align-items-end">
                <div class="col-lg-3 col-md-6">
                    <label class="filter-label">Funcionário</label>
                    <?php echo form_dropdown('team_member_id', $team_members_dropdown, '', 'class="form-control select2 w100p" id="pontorh-adjustment-filter-member"'); ?>
                </div>
                <div class="col-lg-2 col-md-6">
                    <label class="filter-label">Data inicial</label>
                    <?php echo form_input(array('id' => 'pontorh-adjustment-date-from','class' => 'form-control datepicker','autocomplete' => 'off')); ?>
                </div>
                <div class="col-lg-2 col-md-6">
                    <label class="filter-label">Data final</label>
                    <?php echo form_input(array('id' => 'pontorh-adjustment-date-to','class' => 'form-control datepicker','autocomplete' => 'off')); ?>
                </div>
                <div class="col-lg-2 col-md-6">
                    <label class="filter-label">Tipo</label>
                    <?php echo form_dropdown('adjustment_type', $adjustment_type_dropdown, '', 'class="form-control select2 w100p" id="pontorh-adjustment-filter-type"'); ?>
                </div>
                <div class="col-lg-3 col-md-6">
                    <label class="filter-label">Situação</label>
                    <?php echo form_dropdown('status', $status_dropdown, '', 'class="form-control select2 w100p" id="pontorh-adjustment-filter-status"'); ?>
                </div>
                <div class="col-12 mt-3">
                    <button type="button" id="pontorh-adjustment-filter-btn" class="btn btn-primary"><i data-feather="filter" class="icon-16"></i> Filtrar</button>
                    <button type="button" id="pontorh-adjustment-clear-btn" class="btn btn-default">Limpar</button>
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table id="pontorh-adjustments-table" class="display" cellspacing="0" width="100%"></table>
        </div>
    </div>
</div>

<script type="text/javascript">
    function pontorhAdjustmentFilters() {
        return {
            team_member_id: $("#pontorh-adjustment-filter-member").val(),
            date_from: $("#pontorh-adjustment-date-from").val(),
            date_to: $("#pontorh-adjustment-date-to").val(),
            adjustment_type: $("#pontorh-adjustment-filter-type").val(),
            status: $("#pontorh-adjustment-filter-status").val()
        };
    }

    function reloadPontorhAdjustmentsTable() {
        if (window.InstanceCollection && window.InstanceCollection["pontorh-adjustments-table"]) {
            window.InstanceCollection["pontorh-adjustments-table"].filterParams = $.extend({datatable: true}, pontorhAdjustmentFilters());
        }
        $("#pontorh-adjustments-table").appTable({reload: true});
    }

    $(document).ready(function () {
        $(".page-wrapper .select2").select2();
        setDatePicker("#pontorh-adjustment-date-from", {});
        setDatePicker("#pontorh-adjustment-date-to", {});

        $("#pontorh-adjustments-table").appTable({
            source: "<?php echo_uri('pontorh/ajustes/list_data'); ?>",
            filterParams: $.extend({datatable: true}, pontorhAdjustmentFilters()),
            order: [[1, "desc"]],
            columns: [
                {title: "Funcionário"},
                {title: "Data", "class": "w100"},
                {title: "Hora", "class": "w90"},
                {title: "Tipo"},
                {title: "Justificativa"},
                {title: "Situação"},
                {title: "<i data-feather='menu' class='icon-16'></i>", "class": "text-center option w100"}
            ]
        });

        $("#pontorh-adjustment-filter-btn").on("click", reloadPontorhAdjustmentsTable);
        $("#pontorh-adjustment-clear-btn").on("click", function () {
            $("#pontorh-adjustment-filter-member, #pontorh-adjustment-filter-type, #pontorh-adjustment-filter-status").val("").trigger("change");
            $("#pontorh-adjustment-date-from, #pontorh-adjustment-date-to").val("");
            reloadPontorhAdjustmentsTable();
        });
    });
</script>