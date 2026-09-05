<?php
$vehicle_filter_options = [['id' => '', 'text' => 'Veículo']];
foreach ($vehicleOptions as $id => $text) {
    if ($id !== '') {
        $vehicle_filter_options[] = ['id' => (string)$id, 'text' => $text];
    }
}
?>
<div id="page-content" class="page-wrapper clearfix">
    <div class="card grid-button">
        <div class="page-title clearfix">
            <h1><?php echo app_lang('frota_maintenances'); ?></h1>
            <div class="title-button-group">
                <?php
                echo modal_anchor(
                    get_uri('frota/manutencoes/modal_form'),
                    "<i data-feather='plus-circle' class='icon-16'></i> " . app_lang('frota_new_maintenance'),
                    ['class' => 'btn btn-default', 'title' => app_lang('frota_new_maintenance')]
                );
                ?>
            </div>
        </div>
        <div class="table-responsive">
            <table id="frota-maintenances-table" class="display" cellspacing="0" width="100%"></table>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function () {
        $("#frota-maintenances-table").appTable({
            source: '<?php echo_uri('frota/manutencoes/list_data'); ?>',
            order: [[0, 'desc']],
            filterDropdown: [
                {name: 'vehicle_id', class: 'w250', options: <?php echo json_encode($vehicle_filter_options); ?>},
                {name: 'type', class: 'w180', options: [
                    {id: '', text: 'Tipo'},
                    {id: 'preventive', text: 'Preventiva'},
                    {id: 'corrective', text: 'Corretiva'}
                ]},
                {name: 'status', class: 'w180', options: [
                    {id: '', text: '<?php echo app_lang('status'); ?>'},
                    {id: 'scheduled', text: 'Agendada'},
                    {id: 'in_progress', text: 'Em andamento'},
                    {id: 'completed', text: 'Concluída'},
                    {id: 'cancelled', text: 'Cancelada'}
                ]}
            ],
            columns: [
                {title: 'Data', class: 'all w100'},
                {title: 'Veículo', class: 'all'},
                {title: 'Tipo', class: 'w110'},
                {title: 'Descrição', class: 'all'},
                {title: 'Fornecedor', class: 'w180'},
                {title: 'Custo', class: 'w100 text-right'},
                {title: '<?php echo app_lang('status'); ?>', class: 'w120'},
                {title: '<i data-feather="menu" class="icon-16"></i>', class: 'text-center option w100'}
            ],
            printColumns: [0, 1, 2, 3, 4, 5, 6],
            xlsColumns: [0, 1, 2, 3, 4, 5, 6]
        });
    });
</script>
