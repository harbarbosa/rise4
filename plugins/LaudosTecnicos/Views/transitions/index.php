<div id="page-content" class="page-wrapper clearfix">
    <div class="card">
        <div class="page-title clearfix">
            <h1><?php echo app_lang('laudostecnicos_transitions_title'); ?></h1>
            <div class="title-button-group">
                <?php if (!empty($can_manage_transitions)) { ?>
                    <?php echo modal_anchor(get_uri('laudostecnicos/transitions/modal_form'), "<i data-feather='plus-circle' class='icon-16'></i> " . app_lang('add_item'), array('class' => 'btn btn-primary', 'title' => app_lang('laudostecnicos_transitions_title'))); ?>
                <?php } ?>
            </div>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table id="laudostecnicos-transitions-table" class="display" cellspacing="0" width="100%"></table>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(function () {
        $("#laudostecnicos-transitions-table").appTable({
            source: '<?php echo_uri("laudostecnicos/transitions/list_data") ?>',
            columns: [
                {title: "De", "class": "all"},
                {title: "Para", "class": "all"},
                {title: "Comentario", "class": "text-center w120"},
                {title: "Notificacao", "class": "text-center w120"},
                {title: "Tarefa", "class": "text-center w100"},
                {title: "Status", "class": "text-center w100"},
                {title: "<i data-feather='menu' class='icon-16'></i>", "class": "text-center option w100"}
            ],
            order: [[0, 'asc']]
        });
    });
</script>
