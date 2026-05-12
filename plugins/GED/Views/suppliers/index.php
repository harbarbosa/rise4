<div id="page-content" class="page-wrapper clearfix">
    <div class="card">
        <div class="page-title clearfix">
            <h1><?php echo app_lang('ged_suppliers'); ?></h1>
            <div class="title-button-group">
                <?php if (!empty($can_create)) { ?>
                    <?php echo modal_anchor(get_uri('ged/suppliers/modal_form'), "<i data-feather='plus-circle' class='icon-16'></i> Novo fornecedor/portal", array('class' => 'btn btn-default', 'title' => 'Novo fornecedor/portal')); ?>
                <?php } ?>
            </div>
        </div>

        <div class="table-responsive">
            <table id="ged-suppliers-table" class="display" cellspacing="0" width="100%"></table>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function () {
        $("#ged-suppliers-table").appTable({
            source: "<?php echo_uri('ged/suppliers/list_data'); ?>",
            order: [[0, "asc"]],
            columns: [
                {title: "Nome", "class": "all"},
                {title: "Portal"},
                {title: "Contato"},
                {title: "E-mail"},
                {title: "Telefone"},
                {title: "Observacoes"},
                {title: "Status"},
                {title: "<i data-feather='menu' class='icon-16'></i>", "class": "text-center option w100"}
            ],
            printColumns: [0, 1, 2, 3, 4, 5, 6],
            xlsColumns: [0, 1, 2, 3, 4, 5, 6]
        });
    });
</script>
