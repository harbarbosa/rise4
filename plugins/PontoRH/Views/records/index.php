<?php
$default_date_from = get_my_local_time('Y-m-01');
$default_date_to = get_my_local_time('Y-m-t');
?>

<style>
    .pontorh-records-index .filter-label {font-size:12px;color:#69727d;margin-bottom:5px;}
    .pontorh-records-index #pontorh-records-table td {vertical-align:middle;}
    .pontorh-records-index #pontorh-records-table td:nth-child(1),
    .pontorh-records-index #pontorh-records-table td:nth-child(2),
    .pontorh-records-index #pontorh-records-table td:nth-child(3),
    .pontorh-records-index #pontorh-records-table td:nth-child(7),
    .pontorh-records-index #pontorh-records-table td:nth-child(8) {white-space:nowrap;}
</style>

<div id="page-content" class="page-wrapper clearfix pontorh-records-index">
    <div class="card">
        <div class="page-title clearfix">
            <div>
                <h1>Registros de Ponto</h1>
                <div class="text-muted">Consulte as marcações registradas pelos funcionários.</div>
            </div>
            <div class="title-button-group">
                <?php if ($can_manage) { ?>
                    <?php echo modal_anchor(get_uri('pontorh/registros/modal_form'), "<i data-feather='plus' class='icon-16'></i> Nova marcação", array('class' => 'btn btn-primary', 'title' => 'Nova marcação')); ?>
                <?php } ?>
            </div>
        </div>

        <div class="card-body border-bottom">
            <div class="row align-items-end">
                <div class="col-lg-3 col-md-6">
                    <label class="filter-label">Funcionário</label>
                    <select name="team_member_id" id="pontorh-filter-team-member" class="form-control select2 w100p">
                        <option value=""></option>
                        <?php foreach ($team_members_dropdown as $value => $label) { ?>
                            <option value="<?php echo esc($value); ?>"><?php echo esc($label); ?></option>
                        <?php } ?>
                    </select>
                </div>
                <div class="col-lg-2 col-md-6">
                    <label class="filter-label">Data inicial</label>
                    <?php echo form_input(array('id' => 'pontorh-filter-date-from','type' => 'text','class' => 'form-control datepicker','autocomplete' => 'off','value' => $default_date_from)); ?>
                </div>
                <div class="col-lg-2 col-md-6">
                    <label class="filter-label">Data final</label>
                    <?php echo form_input(array('id' => 'pontorh-filter-date-to','type' => 'text','class' => 'form-control datepicker','autocomplete' => 'off','value' => $default_date_to)); ?>
                </div>
                <div class="col-lg-2 col-md-6">
                    <label class="filter-label">Marcação</label>
                    <select name="punch_type" id="pontorh-filter-type" class="form-control select2 w100p">
                        <option value=""></option>
                        <?php foreach ($punch_type_dropdown as $value => $label) { ?><option value="<?php echo esc($value); ?>"><?php echo esc($label); ?></option><?php } ?>
                    </select>
                </div>
                <div class="col-lg-3 col-md-6">
                    <label class="filter-label">Situação</label>
                    <select name="status" id="pontorh-filter-status" class="form-control select2 w100p">
                        <option value=""></option>
                        <?php foreach ($status_dropdown as $value => $label) { ?><option value="<?php echo esc($value); ?>"><?php echo esc($label); ?></option><?php } ?>
                    </select>
                </div>
                <div class="col-12 mt-3">
                    <button type="button" id="pontorh-filter-btn" class="btn btn-primary"><i data-feather="filter" class="icon-16"></i> Filtrar</button>
                    <button type="button" id="pontorh-clear-btn" class="btn btn-default">Limpar</button>
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table id="pontorh-records-table" class="display" cellspacing="0" width="100%"></table>
        </div>
    </div>
</div>

<script type="text/javascript">
    function pontorhRecordsFilters() {
        return {
            team_member_id: $("#pontorh-filter-team-member").val(),
            date_from: $("#pontorh-filter-date-from").val(),
            date_to: $("#pontorh-filter-date-to").val(),
            status: $("#pontorh-filter-status").val(),
            punch_type: $("#pontorh-filter-type").val()
        };
    }

    function reloadPontorhRecordsTable() {
        if (window.InstanceCollection && window.InstanceCollection["pontorh-records-table"]) {
            window.InstanceCollection["pontorh-records-table"].filterParams = $.extend({datatable: true}, pontorhRecordsFilters());
        }
        $("#pontorh-records-table").appTable({reload: true});
    }

    $(document).ready(function () {
        $(".page-wrapper .select2").select2();
        setDatePicker("#pontorh-filter-date-from, #pontorh-filter-date-to");
        $("#pontorh-records-table").appTable({
            source: "<?php echo_uri('pontorh/registros/list_data'); ?>",
            filterParams: $.extend({datatable: true}, pontorhRecordsFilters()),
            order: [[1, "desc"]],
            columns: [
                {title: "Funcionário"},
                {title: "Data", "class": "w100"},
                {title: "Hora", "class": "w90"},
                {title: "Marcação"},
                {title: "Local"},
                {title: "Origem"},
                {title: "Situação"},
                {title: "<i data-feather='menu' class='icon-16'></i>", "class": "text-center option w100"}
            ]
        });
        $("#pontorh-filter-btn").on("click", reloadPontorhRecordsTable);
        $("#pontorh-clear-btn").on("click", function () {
            $("#pontorh-filter-team-member, #pontorh-filter-status, #pontorh-filter-type").val("").trigger("change");
            $("#pontorh-filter-date-from, #pontorh-filter-date-to").val("");
            reloadPontorhRecordsTable();
        });
    });
</script>