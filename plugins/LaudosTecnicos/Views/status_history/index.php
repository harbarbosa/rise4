<div id="page-content" class="page-wrapper clearfix">
    <div class="card">
        <div class="page-title clearfix">
            <h1><?php echo app_lang('laudostecnicos_status_history_title'); ?></h1>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table id="laudostecnicos-status-history-table" class="display" cellspacing="0" width="100%"></table>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(function () {
        $("#laudostecnicos-status-history-table").appTable({
            source: '<?php echo_uri("laudostecnicos/historico-status/list_data") ?>',
            columns: [
                {title: "#", "class": "text-center w100"},
                {title: "<?php echo app_lang('title'); ?>", "class": "all"},
                {title: "De", "class": "desktop"},
                {title: "Para", "class": "desktop"},
                {title: "Usuario", "class": "desktop"},
                {title: "Origem", "class": "desktop"},
                {title: "IP", "class": "desktop"},
                {title: "<?php echo app_lang('date'); ?>", "class": "desktop"},
                {title: "Comentario", "class": "desktop"}
            ],
            order: [[7, 'desc']]
        });
    });
</script>
