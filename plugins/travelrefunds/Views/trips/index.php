<div id="page-content" class="page-wrapper clearfix">
    <div class="card">
        <div class="page-title clearfix">
            <h1>Minhas Viagens</h1>
            <div class="title-button-group">
                <?php echo anchor(get_uri('travelrefunds'), '<i data-feather="grid" class="icon-16"></i> Dashboard', array('class' => 'btn btn-default')); ?>
                <?php echo anchor(get_uri('travelrefunds/reports'), '<i data-feather="bar-chart-2" class="icon-16"></i> Relatorios', array('class' => 'btn btn-default')); ?>
                <?php echo anchor(get_uri('travelrefunds/approvals'), '<i data-feather="check-circle" class="icon-16"></i> Aprovacoes', array('class' => 'btn btn-default')); ?>
                <?php if (!empty($can_create)) { ?>
                    <?php echo modal_anchor(get_uri('travelrefunds/trips/modal_form'), '<i data-feather="plus-circle" class="icon-16"></i> Nova Viagem', array('class' => 'btn btn-primary', 'title' => 'Nova Viagem')); ?>
                <?php } ?>
            </div>
        </div>
    </div>

    <div class="card border-top-0 rounded-top-0">
        <div class="table-responsive">
            <table id="travelrefunds-trips-table" class="display" cellspacing="0" width="100%"></table>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function () {
        $("#travelrefunds-trips-table").appTable({
            source: "<?php echo_uri('travelrefunds/trips/list_data'); ?>",
            columns: [
                {title: "Titulo", "class": "all"},
                {title: "Destino"},
                {title: "Projeto"},
                {title: "Periodo"},
                {title: "Valor total", "class": "text-end"},
                {title: "Status"},
                {title: "<i data-feather='menu' class='icon-16'></i>", "class": "text-center option w140"}
            ]
        });
    });
</script>
