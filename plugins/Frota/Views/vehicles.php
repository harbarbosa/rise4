<div id="page-content" class="page-wrapper clearfix">
    <div class="card grid-button">
        <div class="page-title clearfix">
            <h1><?php echo app_lang('frota_vehicles'); ?></h1>
            <div class="title-button-group">
                <?php
                echo modal_anchor(
                    get_uri('frota/veiculos/modal_form'),
                    "<i data-feather='plus-circle' class='icon-16'></i> " . app_lang('frota_new_vehicle'),
                    ['class' => 'btn btn-default', 'title' => app_lang('frota_new_vehicle')]
                );
                ?>
            </div>
        </div>
        <div class="table-responsive">
            <table id="frota-vehicles-table" class="display" cellspacing="0" width="100%"></table>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function () {
        $("#frota-vehicles-table").appTable({
            source: '<?php echo_uri('frota/veiculos/list_data'); ?>',
            order: [[0, 'asc']],
            filterDropdown: [
                {
                    name: 'status',
                    class: 'w200',
                    options: [
                        {id: '', text: '<?php echo app_lang('status'); ?>'},
                        {id: 'active', text: 'Ativo'},
                        {id: 'maintenance', text: 'Em manutenção'},
                        {id: 'inactive', text: 'Inativo'}
                    ]
                }
            ],
            columns: [
                {title: 'Placa', class: 'all w100'},
                {title: 'Prefixo', class: 'w100'},
                {title: 'Veículo', class: 'all'},
                {title: 'Ano', class: 'w75'},
                {title: 'KM atual', class: 'w100 text-right'},
                {title: 'Próxima revisão', class: 'w150'},
                {title: '<?php echo app_lang('status'); ?>', class: 'w120'},
                {title: '<i data-feather="menu" class="icon-16"></i>', class: 'text-center option w100'}
            ],
            printColumns: [0, 1, 2, 3, 4, 5, 6],
            xlsColumns: [0, 1, 2, 3, 4, 5, 6]
        });
    });
</script>
