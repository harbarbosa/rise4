<div id="page-content" class="page-wrapper clearfix">
    <div class="card">
        <div class="page-title clearfix">
            <h1><?php echo app_lang('ged_submissions'); ?></h1>
            <div class="title-button-group">
                <?php if (!empty($can_create)) { ?>
                    <?php echo modal_anchor(get_uri('ged/submissions/modal_form'), "<i data-feather='plus-circle' class='icon-16'></i> Novo envio", array('class' => 'btn btn-default', 'title' => 'Novo envio')); ?>
                <?php } ?>
            </div>
        </div>

        <div class="card-body border-bottom">
            <div class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Documento</label>
                    <?php echo form_dropdown('filter_document_id', $documents_dropdown, '', 'class="form-control select2" id="filter-document-id"'); ?>
                </div>
                <div class="col-md-2">
                    <button type="button" id="ged-submissions-filter-btn" class="btn btn-primary btn-sm me-2">Filtrar</button>
                    <button type="button" id="ged-submissions-clear-btn" class="btn btn-default btn-sm">Limpar</button>
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table id="ged-submissions-table" class="display" cellspacing="0" width="100%"></table>
        </div>
    </div>
</div>

<script type="text/javascript">
    function getGedSubmissionFilters() {
        return {
            document_id: $("#filter-document-id").val()
        };
    }

    function reloadGedSubmissionsTable() {
        var tableId = "ged-submissions-table";
        if (window.InstanceCollection && window.InstanceCollection[tableId]) {
            window.InstanceCollection[tableId].filterParams = $.extend({datatable: true}, getGedSubmissionFilters());
        }
        $("#ged-submissions-table").appTable({reload: true});
    }

    $(document).ready(function () {
        $(".page-wrapper .select2").select2();

        $("#ged-submissions-table").appTable({
            source: "<?php echo_uri('ged/submissions/list_data'); ?>",
            filterParams: $.extend({datatable: true}, getGedSubmissionFilters()),
            order: [[3, "desc"]],
            columns: [
                {title: "<?php echo app_lang('ged_field_document'); ?>", "class": "all"},
                {title: "<?php echo app_lang('ged_field_document_type'); ?>"},
                {title: "<?php echo app_lang('ged_field_employee'); ?> / <?php echo app_lang('ged_field_owner_type'); ?>"},
                {title: "Enviado em"},
                {title: "<?php echo app_lang('ged_field_status'); ?>"},
                {title: "Irregular"},
                {title: "<i data-feather='menu' class='icon-16'></i>", "class": "text-center option w120"}
            ],
            printColumns: [0, 1, 2, 3, 4, 5, 6],
            xlsColumns: [0, 1, 2, 3, 4, 5, 6]
        });

        $("#ged-submissions-filter-btn").on("click", function () {
            reloadGedSubmissionsTable();
        });

        $("#ged-submissions-clear-btn").on("click", function () {
            $("#filter-document-id").val("");
            reloadGedSubmissionsTable();
        });
    });
</script>
