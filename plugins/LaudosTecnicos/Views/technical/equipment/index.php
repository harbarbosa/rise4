<div id="page-content" class="page-wrapper clearfix">
    <div class="card">
        <div class="page-title clearfix">
            <h1>Equipamentos de Medição</h1>
            <div class="title-button-group">
                <?php echo modal_anchor(get_uri("laudo_technical/equipment_form"), "<i data-feather='plus' class='icon-16'></i> Novo Equipamento", array("class" => "btn btn-primary", "title" => "Novo Equipamento")); ?>
            </div>
        </div>

        <div class="table-responsive">
            <table id="equipment-table" class="display" width="100%"></table>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $("#equipment-table").appTable({
        source: '<?php echo_uri("laudo_technical/equipment_list_data") ?>',
        columns: [
            { title: '<?php echo app_lang("id"); ?>', "class": "w50" },
            { title: '<?php echo app_lang("laudos_name"); ?>' },
            { title: '<?php echo app_lang("laudos_equipment_type"); ?>' },
            { title: '<?php echo app_lang("laudos_serial_number"); ?>' },
            { title: '<?php echo app_lang("laudos_patrimony"); ?>' },
            { title: '<?php echo app_lang("laudos_next_calibration"); ?>' },
            { title: 'Calibração' },
            { title: '<?php echo app_lang("status"); ?>' },
            { title: '<?php echo app_lang("actions"); ?>', "class": "w80" }
        ],
        order: [[1, 'asc']]
    });
});
</script>