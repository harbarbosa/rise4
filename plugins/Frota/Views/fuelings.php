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
            <h1><?php echo app_lang('frota_fuelings'); ?></h1>
            <div class="title-button-group">
                <?php
                echo modal_anchor(
                    get_uri('frota/abastecimentos/modal_form'),
                    "<i data-feather='plus-circle' class='icon-16'></i> " . app_lang('frota_new_fueling'),
                    ['class' => 'btn btn-default', 'title' => app_lang('frota_new_fueling')]
                );
                ?>
            </div>
        </div>
        <div class="table-responsive">
            <table id="frota-fuelings-table" class="display" cellspacing="0" width="100%"></table>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function () {
        $("#frota-fuelings-table").appTable({
            source: '<?php echo_uri('frota/abastecimentos/list_data'); ?>',
            order: [[0, 'desc']],
            filterDropdown: [
                {
                    name: 'vehicle_id',
                    class: 'w250',
                    options: <?php echo json_encode($vehicle_filter_options); ?>
                }
            ],
            rangeDatepicker: [
                {
                    startDate: {name: 'date_from', value: ''},
                    endDate: {name: 'date_to', value: ''},
                    showClearButton: true,
                    label: 'Período'
                }
            ],
            columns: [
                {title: 'Data', class: 'all w150'},
                {title: 'Veículo', class: 'all'},
                {title: 'KM', class: 'w100 text-right'},
                {title: 'Litros', class: 'w100 text-right'},
                {title: 'Preço/L', class: 'w100 text-right'},
                {title: 'Total', class: 'w100 text-right'},
                {title: 'Posto', class: 'w200'},
                {title: '<i data-feather="menu" class="icon-16"></i>', class: 'text-center option w100'}
            ],
            printColumns: [0, 1, 2, 3, 4, 5, 6],
            xlsColumns: [0, 1, 2, 3, 4, 5, 6]
        });
    });
</script>
