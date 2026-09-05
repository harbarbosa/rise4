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
            <h1><?php echo app_lang('frota_issues'); ?></h1>
            <div class="title-button-group">
                <?php
                echo modal_anchor(
                    get_uri('frota/ocorrencias/modal_form'),
                    "<i data-feather='plus-circle' class='icon-16'></i> " . app_lang('frota_register_issue'),
                    ['class' => 'btn btn-default', 'title' => app_lang('frota_register_issue')]
                );
                ?>
            </div>
        </div>
        <div class="table-responsive">
            <table id="frota-issues-table" class="display" cellspacing="0" width="100%"></table>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function () {
        $("#frota-issues-table").appTable({
            source: '<?php echo_uri('frota/ocorrencias/list_data'); ?>',
            order: [[0, 'desc']],
            filterDropdown: [
                {name: 'vehicle_id', class: 'w250', options: <?php echo json_encode($vehicle_filter_options); ?>},
                {name: 'severity', class: 'w160', options: [
                    {id: '', text: 'Gravidade'},
                    {id: 'low', text: 'Baixa'},
                    {id: 'medium', text: 'Média'},
                    {id: 'high', text: 'Alta'},
                    {id: 'critical', text: 'Crítica'}
                ]},
                {name: 'status', class: 'w160', options: [
                    {id: '', text: '<?php echo app_lang('status'); ?>'},
                    {id: 'open', text: 'Aberta'},
                    {id: 'in_progress', text: 'Em andamento'},
                    {id: 'resolved', text: 'Resolvida'}
                ]}
            ],
            columns: [
                {title: 'Data', class: 'all w150'},
                {title: 'Veículo', class: 'all'},
                {title: 'Ocorrência', class: 'all'},
                {title: 'Gravidade', class: 'w100'},
                {title: 'KM', class: 'w100 text-right'},
                {title: '<?php echo app_lang('status'); ?>', class: 'w120'},
                {title: '<i data-feather="menu" class="icon-16"></i>', class: 'text-center option w100'}
            ],
            printColumns: [0, 1, 2, 3, 4, 5],
            xlsColumns: [0, 1, 2, 3, 4, 5]
        });
    });
</script>
