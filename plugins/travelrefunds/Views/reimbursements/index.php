<div id="page-content" class="page-wrapper clearfix">
    <div class="card mb15">
        <div class="page-title clearfix d-flex justify-content-between align-items-center">
            <div>
                <h1>Reembolsos</h1>
                <div class="text-off">Listagem dos reembolsos lançados no sistema.</div>
            </div>
            <div class="title-button-group">
                <?php echo anchor(get_uri('travelrefunds'), '<i data-feather="grid" class="icon-16"></i> Dashboard', array('class' => 'btn btn-default')); ?>
                <?php echo anchor(get_uri('travelrefunds/trips'), '<i data-feather="map-pin" class="icon-16"></i> Minhas Viagens', array('class' => 'btn btn-default')); ?>
                <?php echo anchor(get_uri('travelrefunds/approvals'), '<i data-feather="check-circle" class="icon-16"></i> Aprovacoes', array('class' => 'btn btn-default')); ?>
                <?php if (!empty($can_create)) { ?>
                    <?php echo modal_anchor(get_uri('travelrefunds/reimbursements/modal_form'), '<i data-feather="plus-circle" class="icon-16"></i> Novo Reembolso', array('class' => 'btn btn-primary', 'title' => 'Novo Reembolso')); ?>
                <?php } ?>
            </div>
        </div>
        <div class="card-body border-bottom">
            <p class="text-muted mb-0">Use esta tela para consultar e cadastrar reembolsos vinculados as viagens.</p>
        </div>
        <div class="table-responsive">
            <table id="travelrefunds-reimbursements-table" class="display" cellspacing="0" width="100%"></table>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function () {
        $("#travelrefunds-reimbursements-table").appTable({
            source: "<?php echo_uri('travelrefunds/reimbursements/list_data'); ?>",
            columns: [
                {title: "Descricao", class: "all"},
                {title: "Viagem"},
                {title: "Projeto"},
                {title: "Funcionario"},
                {title: "Categoria"},
                {title: "Valor", class: "text-end"},
                {title: "Status"},
                {title: '<i data-feather="menu" class="icon-16"></i>', class: "text-center option w100", visible: true}
            ]
        });
    });
</script>
