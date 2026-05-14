<div id="page-content" class="page-wrapper clearfix">
    <div class="card">
        <div class="page-title clearfix">
            <h1><?php echo app_lang('ged_documents'); ?></h1>
            <div class="title-button-group">
                <?php if (!empty($can_create)) { ?>
                    <?php echo modal_anchor(get_uri('ged/documents/modal_form'), "<i data-feather='plus-circle' class='icon-16'></i> Novo documento", array('class' => 'btn btn-default', 'title' => 'Novo documento')); ?>
                <?php } ?>
            </div>
        </div>

        <div class="card-body border-bottom">
            <div class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label"><?php echo app_lang('ged_field_document_type'); ?></label>
                    <?php echo form_dropdown('filter_document_type_id', $document_types_dropdown, '', 'class="form-control select2" id="filter-document-type-id"'); ?>
                </div>
                <div class="col-md-2">
                    <label class="form-label"><?php echo app_lang('ged_field_owner_type'); ?></label>
                    <?php echo form_dropdown('filter_owner_type', $owner_types_dropdown, '', 'class="form-control select2" id="filter-owner-type"'); ?>
                </div>
                <div class="col-md-2">
                    <label class="form-label"><?php echo app_lang('ged_field_employee'); ?></label>
                    <?php echo form_dropdown('filter_employee_id', $employees_dropdown, '', 'class="form-control select2" id="filter-employee-id"'); ?>
                </div>
                <div class="col-md-2">
                    <label class="form-label"><?php echo app_lang('ged_field_supplier'); ?></label>
                    <?php echo form_dropdown('filter_supplier_id', $suppliers_dropdown, '', 'class="form-control select2" id="filter-supplier-id"'); ?>
                </div>
                <div class="col-md-2">
                    <label class="form-label"><?php echo app_lang('ged_field_status'); ?></label>
                    <?php echo form_dropdown('filter_status', $status_dropdown, '', 'class="form-control select2" id="filter-status"'); ?>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Vencimento</label>
                    <?php echo form_dropdown('filter_expiration_scope', $expiration_scope_dropdown, '', 'class="form-control select2" id="filter-expiration-scope"'); ?>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Venc. inicio</label>
                    <input type="date" id="filter-expiration-start" class="form-control" />
                </div>
                <div class="col-md-2">
                    <label class="form-label">Venc. fim</label>
                    <input type="date" id="filter-expiration-end" class="form-control" />
                </div>
                <div class="col-md-3">
                    <button type="button" id="ged-documents-filter-btn" class="btn btn-primary btn-sm me-2">Filtrar</button>
                    <button type="button" id="ged-documents-clear-btn" class="btn btn-default btn-sm">Limpar</button>
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table id="ged-documents-table" class="display" cellspacing="0" width="100%"></table>
        </div>
    </div>
</div>

<script type="text/javascript">
    function applyGedDocumentFiltersFromQuery() {
        var params = new URLSearchParams(window.location.search);
        var map = {
            document_type_id: "#filter-document-type-id",
            owner_type: "#filter-owner-type",
            employee_id: "#filter-employee-id",
            supplier_id: "#filter-supplier-id",
            status: "#filter-status",
            expiration_scope: "#filter-expiration-scope",
            expiration_start: "#filter-expiration-start",
            expiration_end: "#filter-expiration-end"
        };

        Object.keys(map).forEach(function (key) {
            if (params.has(key)) {
                $(map[key]).val(params.get(key));
            }
        });
    }

    function getGedDocumentFilters() {
        return {
            document_type_id: $("#filter-document-type-id").val(),
            owner_type: $("#filter-owner-type").val(),
            employee_id: $("#filter-employee-id").val(),
            supplier_id: $("#filter-supplier-id").val(),
            status: $("#filter-status").val(),
            expiration_scope: $("#filter-expiration-scope").val(),
            expiration_start: $("#filter-expiration-start").val(),
            expiration_end: $("#filter-expiration-end").val()
        };
    }

    function reloadGedDocumentsTable() {
        var tableId = "ged-documents-table";
        if (window.InstanceCollection && window.InstanceCollection[tableId]) {
            window.InstanceCollection[tableId].filterParams = $.extend({datatable: true}, getGedDocumentFilters());
        }
        $("#ged-documents-table").appTable({reload: true});
    }

    $(document).ready(function () {
        applyGedDocumentFiltersFromQuery();
        $(".page-wrapper .select2").select2();

        $("#ged-documents-table").appTable({
            source: "<?php echo_uri('ged/documents/list_data'); ?>",
            filterParams: $.extend({datatable: true}, getGedDocumentFilters()),
            order: [[3, "asc"]],
            columns: [
                {title: "<?php echo app_lang('ged_field_title'); ?>", "class": "all"},
                {title: "<?php echo app_lang('ged_field_document_type'); ?>"},
                {title: "<?php echo app_lang('ged_field_owner_type'); ?>"},
                {title: "<?php echo app_lang('ged_field_issue_date'); ?>"},
                {title: "<?php echo app_lang('ged_field_expiration_date'); ?>"},
                {title: "<?php echo app_lang('ged_field_status'); ?>"},
                {title: "<?php echo app_lang('ged_field_file'); ?>", "class": "text-center"},
                {title: "<i data-feather='menu' class='icon-16'></i>", "class": "text-center option w120"}
            ],
            printColumns: [0, 1, 2, 3, 4, 5, 6],
            xlsColumns: [0, 1, 2, 3, 4, 5, 6]
        });

        $("#ged-documents-filter-btn").on("click", function () {
            reloadGedDocumentsTable();
        });

        $("#ged-documents-clear-btn").on("click", function () {
            $("#filter-document-type-id, #filter-owner-type, #filter-employee-id, #filter-supplier-id, #filter-status, #filter-expiration-scope").val("");
            $("#filter-expiration-start, #filter-expiration-end").val("");
            reloadGedDocumentsTable();
        });
    });
</script>
