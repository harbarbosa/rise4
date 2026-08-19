<div id="page-content" class="page-wrapper clearfix">
    <div class="card">
        <div class="page-title clearfix">
            <h1><?php echo app_lang('laudostecnicos_measurement_types_title'); ?></h1>
            <div class="title-button-group">
                <?php if (!empty($can_manage_measurements)) { ?>
                    <?php echo modal_anchor(get_uri('laudostecnicos/tipos-medicao/modal_form'), "<i data-feather='plus-circle' class='icon-16'></i> " . app_lang('add_item'), array('class' => 'btn btn-primary', 'title' => app_lang('laudostecnicos_measurement_types_title'))); ?>
                <?php } ?>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="laudostecnicos-measurement-types-table" class="display" cellspacing="0" width="100%"></table>
            </div>
        </div>
    </div>
</div>
<script type="text/javascript">
    $(function () {
        $("#laudostecnicos-measurement-types-table").appTable({
            source: '<?php echo_uri("laudostecnicos/tipos-medicao/list_data") ?>',
            columns: [
                {title: "Nome", "class": "all"},
                {title: "Grandeza", "class": "desktop"},
                {title: "Unidade", "class": "desktop"},
                {title: "Min", "class": "desktop"},
                {title: "Max", "class": "desktop"},
                {title: "Referencia", "class": "desktop"},
                {title: "Tolerancia", "class": "desktop"},
                {title: "Casas", "class": "text-center w80"},
                {title: "Auto", "class": "text-center w80"},
                {title: "Status", "class": "text-center w80"},
                {title: "<i data-feather='menu' class='icon-16'></i>", "class": "text-center option w120"}
            ],
            order: [[0, 'asc']]
        });
    });
</script>
