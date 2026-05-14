<?php
$model_info = $model_info ?? (object) array(
    'id' => 0,
    'document_id' => 0,
    'submitted_at' => '',
    'notes' => '',
    'owner_type' => '',
    'employee_id' => 0,
    'document_ids' => array()
);

$is_edit_mode = !empty($is_edit_mode);
$current_owner_type = $model_info->owner_type ?? '';
$selected_employee_id = (int) ($model_info->employee_id ?? 0);
$selected_document_ids = is_array($model_info->document_ids ?? null) ? $model_info->document_ids : array((int) ($model_info->document_id ?? 0));
?>

    <?php echo form_open(get_uri('ged/submissions/save'), array('id' => 'ged-submission-form', 'class' => 'general-form', 'role' => 'form')); ?>
<div class="modal-body clearfix">
    <input type="hidden" name="id" value="<?php echo esc($model_info->id); ?>" />

    <div class="form-group">
        <label for="owner_type" class="form-label">1. O documento e da empresa ou do funcionario? *</label>
        <?php echo form_dropdown('owner_type', $owner_types_dropdown, $current_owner_type, 'class="form-control select2 js_app_dropdown" id="owner_type" required'); ?>
    </div>

    <div class="form-group hide" id="employee-section">
        <label for="employee_id" class="form-label">2. Selecione o funcionario *</label>
        <?php echo form_dropdown('employee_id', !empty($employees_dropdown) ? $employees_dropdown : array(), $selected_employee_id, 'class="form-control select2 js_app_dropdown" id="employee_id" size="8"'); ?>
    </div>

    <div class="form-group hide" id="documents-section">
        <label for="document_ids" class="form-label">3. Selecione os documentos validos *</label>
        <?php echo form_dropdown('document_ids[]', !empty($available_documents_dropdown) ? $available_documents_dropdown : array(), $selected_document_ids, 'class="form-control select2 js_app_dropdown" id="document_ids" multiple size="8"'); ?>
    </div>

    <div class="form-group">
        <label for="submitted_at" class="form-label">Enviado em</label>
        <input type="datetime-local" id="submitted_at" name="submitted_at" value="<?php echo esc($model_info->submitted_at ? str_replace(' ', 'T', substr($model_info->submitted_at, 0, 16)) : ''); ?>" class="form-control" />
    </div>

    <div class="form-group">
        <label for="notes" class="form-label">Observacoes</label>
        <textarea id="notes" name="notes" class="form-control" rows="4"><?php echo esc($model_info->notes); ?></textarea>
    </div>
</div>

<div class="modal-footer">
    <button type="button" class="btn btn-default btn-sm" data-bs-dismiss="modal"><span data-feather="x" class="icon-16"></span> <?php echo app_lang('close'); ?></button>
    <button type="submit" class="btn btn-primary btn-sm"><span data-feather="check-circle" class="icon-16"></span> <?php echo app_lang('save'); ?></button>
</div>
<?php echo form_close(); ?>

<script type="text/javascript">
    var gedSubmissionInitialDocumentIds = <?php echo json_encode(array_values(array_map('intval', $selected_document_ids))); ?>;
    var gedSubmissionInitialEmployeeId = <?php echo json_encode($selected_employee_id); ?>;

    function loadGedSubmissionDocuments(ownerType, ownerId, selectedValues) {
        if (!ownerType) {
            $("#document_ids").val(null).trigger("change");
            return;
        }

        var $documentsSelect = $("#document_ids");
        if ($documentsSelect.hasClass("select2-hidden-accessible")) {
            $documentsSelect.appDropdown("destroy");
        }

        appLoader.show({
            container: "#documents-section",
            zIndex: 1
        });

        appAjaxRequest({
            url: "<?php echo get_uri('ged/submissions/get_owner_documents_suggestion'); ?>/" + ownerType + "/" + (ownerId || 0),
            dataType: "json",
            success: function (result) {
                $documentsSelect.empty();
                $.each(result, function (index, item) {
                    $documentsSelect.append($("<option></option>").attr("value", item.id).text(item.text));
                });

                $documentsSelect.show().appDropdown();
                if (selectedValues && selectedValues.length) {
                    $documentsSelect.val(selectedValues.map(String)).trigger("change");
                } else {
                    $documentsSelect.val(null).trigger("change");
                }

                appLoader.hide();
            },
            error: function () {
                appLoader.hide();
            }
        });
    }

    function loadGedSubmissionEmployees(selectedEmployeeId, selectedDocumentIds) {
        var $employeeSelect = $("#employee_id");
        if ($employeeSelect.hasClass("select2-hidden-accessible")) {
            $employeeSelect.appDropdown("destroy");
        }

        appLoader.show({
            container: "#employee-section",
            zIndex: 1
        });

        appAjaxRequest({
            url: "<?php echo get_uri('ged/submissions/get_employees_suggestion'); ?>",
            dataType: "json",
            success: function (result) {
                $employeeSelect.empty();
                $.each(result, function (index, item) {
                    $employeeSelect.append($("<option></option>").attr("value", item.id).text(item.text));
                });

                $employeeSelect.show().appDropdown();
                if (selectedEmployeeId) {
                    $employeeSelect.val(String(selectedEmployeeId));
                    if (selectedDocumentIds && selectedDocumentIds.length) {
                        loadGedSubmissionDocuments("employee", selectedEmployeeId, selectedDocumentIds);
                    }
                }

                appLoader.hide();
            },
            error: function () {
                appLoader.hide();
            }
        });
    }

    function toggleGedOwnerFields(initialLoad) {
        var ownerType = $("#owner_type").val();
        if (initialLoad && !ownerType) {
            ownerType = <?php echo json_encode($current_owner_type); ?>;
            $("#owner_type").val(ownerType);
        }

        if (!initialLoad) {
            $("#employee_id").val("").trigger("change");
            $("#document_ids").val(null).trigger("change");
        }

        if (ownerType === "employee") {
            $("#employee-section").removeClass("hide").show();
            $("#documents-section").addClass("hide").hide();
            if (!initialLoad) {
                loadGedSubmissionEmployees("", []);
            }
            return;
        }

        if (ownerType === "company") {
            $("#employee-section").addClass("hide").hide();
            $("#documents-section").removeClass("hide").show();
            if (!initialLoad) {
                loadGedSubmissionDocuments("company", 0, []);
            }
            return;
        }

        $("#employee-section").addClass("hide").hide();
        $("#documents-section").addClass("hide").hide();
    }

    $(document).ready(function () {
        $("#owner_type").appDropdown();

        $("#ged-submission-form").appForm({
            onSuccess: function () {
                $("#ged-submissions-table").appTable({reload: true});
            }
        });

        $("#owner_type").on("change", function () {
            toggleGedOwnerFields(false);
        });
        $("#employee_id").on("change", function () {
            var employeeId = $(this).val();
            if (employeeId) {
                $("#documents-section").removeClass("hide").show();
                loadGedSubmissionDocuments("employee", employeeId, []);
            } else {
                $("#document_ids").val(null).trigger("change");
                $("#documents-section").addClass("hide").hide();
            }
        });

        $("#documents-section").hide();
        $("#employee-section").hide();
        toggleGedOwnerFields(true);
    });
</script>
