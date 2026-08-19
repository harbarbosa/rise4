<?php
$risk_cards = is_array($risk_cards ?? null) ? $risk_cards : array();
$nc_statuses = is_array($nc_statuses ?? null) ? $nc_statuses : array();
$classification_options = is_array($classification_options ?? null) ? $classification_options : array();
$plan_statuses = is_array($plan_statuses ?? null) ? $plan_statuses : array();
?>

<div id="page-content" class="page-wrapper clearfix">
    <div class="page-title clearfix">
        <h1><?php echo app_lang('laudostecnicos_nonconformities_title'); ?></h1>
        <div class="title-button-group">
            <?php if (!empty($can_manage_nonconformities)) { ?>
                <?php echo modal_anchor(get_uri('laudostecnicos/nao-conformidades/modal_form'), "<i data-feather='plus-circle' class='icon-16'></i> " . app_lang('add_item'), array('class' => 'btn btn-primary', 'title' => app_lang('laudostecnicos_nonconformities_title'))); ?>
            <?php } ?>
        </div>
    </div>

    <div class="row g-3 mb20">
        <?php foreach ($risk_cards as $card) { ?>
            <div class="col-md-4 col-lg-3">
                <div class="rounded p-3 <?php echo esc($card['class']); ?>">
                    <div class="small opacity-75"><?php echo esc($card['title']); ?></div>
                    <div class="fs-3 fw-bold"><?php echo esc($card['value']); ?></div>
                </div>
            </div>
        <?php } ?>
    </div>

    <ul class="nav nav-tabs scrollable-tabs rounded mb20" role="tablist">
        <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#nc-tab">Nao conformidades</a></li>
        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#risk-tab">Matriz de risco</a></li>
        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#plans-tab">Planos de acao</a></li>
    </ul>

    <div class="tab-content">
        <div class="tab-pane fade show active" id="nc-tab">
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="laudostecnicos-nc-table" class="display" cellspacing="0" width="100%"></table>
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="risk-tab">
            <div class="card">
                <div class="card-body">
                    <div class="mb15">
                        <?php if (!empty($can_manage_risk_matrix)) { ?>
                            <?php echo modal_anchor(get_uri('laudostecnicos/nao-conformidades/matrix_modal_form'), "<i data-feather='plus-circle' class='icon-16'></i> " . app_lang('add_item'), array('class' => 'btn btn-primary', 'title' => app_lang('laudostecnicos_risk_matrix'))); ?>
                        <?php } ?>
                    </div>
                    <div class="table-responsive">
                        <table id="laudostecnicos-risk-table" class="display" cellspacing="0" width="100%"></table>
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="plans-tab">
            <div class="card">
                <div class="card-body">
                    <div class="mb15">
                        <?php if (!empty($can_manage_action_plans)) { ?>
                            <?php echo modal_anchor(get_uri('laudostecnicos/nao-conformidades/plan_modal_form'), "<i data-feather='plus-circle' class='icon-16'></i> " . app_lang('add_item'), array('class' => 'btn btn-primary', 'title' => app_lang('laudostecnicos_action_plans'))); ?>
                        <?php } ?>
                    </div>
                    <div class="table-responsive">
                        <table id="laudostecnicos-plans-table" class="display" cellspacing="0" width="100%"></table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(function () {
        $("#laudostecnicos-nc-table").appTable({
            source: '<?php echo_uri("laudostecnicos/nao-conformidades/list_data") ?>',
            columns: [
                {title: "Codigo", "class": "all"},
                {title: "Titulo", "class": "desktop"},
                {title: "Cliente", "class": "desktop"},
                {title: "Laudo", "class": "desktop"},
                {title: "Inspecao", "class": "desktop"},
                {title: "Classificacao", "class": "text-center"},
                {title: "Risco", "class": "text-center"},
                {title: "Status", "class": "text-center"},
                {title: "Prazo", "class": "desktop"},
                {title: "Responsavel", "class": "desktop"},
                {title: "<i data-feather='menu' class='icon-16'></i>", "class": "text-center option w180"}
            ],
            order: [[0, 'desc']]
        });

        $("#laudostecnicos-risk-table").appTable({
            source: '<?php echo_uri("laudostecnicos/nao-conformidades/matrix_list_data") ?>',
            columns: [
                {title: "Nome", "class": "all"},
                {title: "Probabilidade", "class": "text-center"},
                {title: "Impacto", "class": "text-center"},
                {title: "Resultado", "class": "text-center"},
                {title: "Classificacao", "class": "text-center"},
                {title: "Cor", "class": "text-center"},
                {title: "Prazo", "class": "text-center"},
                {title: "Padrao", "class": "text-center"},
                {title: "Ativo", "class": "text-center"},
                {title: "<i data-feather='menu' class='icon-16'></i>", "class": "text-center option w180"}
            ],
            order: [[1, 'asc'], [2, 'asc']]
        });

        $("#laudostecnicos-plans-table").appTable({
            source: '<?php echo_uri("laudostecnicos/nao-conformidades/plans_list_data") ?>',
            columns: [
                {title: "Codigo", "class": "all"},
                {title: "NC", "class": "desktop"},
                {title: "Titulo NC", "class": "desktop"},
                {title: "Acao", "class": "desktop"},
                {title: "Responsavel", "class": "desktop"},
                {title: "Prazo", "class": "desktop"},
                {title: "Status", "class": "text-center"},
                {title: "Tarefa", "class": "desktop"},
                {title: "<i data-feather='menu' class='icon-16'></i>", "class": "text-center option w180"}
            ],
            order: [[5, 'asc']]
        });
    });
</script>
