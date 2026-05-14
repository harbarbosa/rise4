<?php
$model_info = $model_info ?? (object) array(
    'id' => 0,
    'title' => '',
    'document_type_id' => 0,
    'owner_type' => 'company',
    'owner_id' => 0,
    'employee_id' => 0,
    'supplier_id' => 0,
    'issue_date' => '',
    'expiration_date' => '',
    'notes' => '',
    'file_path' => '',
    'original_filename' => '',
);

$current_owner_type = $model_info->owner_type ?: 'company';
?>

<?php echo form_open_multipart(get_uri('ged/documents/save'), array('id' => 'ged-document-form', 'class' => 'general-form', 'role' => 'form')); ?>
<div class="modal-body clearfix">
    <input type="hidden" name="id" value="<?php echo esc($model_info->id); ?>" />

    <div class="form-group">
        <label for="title" class="form-label"><?php echo app_lang('ged_field_title'); ?> *</label>
        <input type="text" id="title" name="title" value="<?php echo esc($model_info->title); ?>" class="form-control" required />
    </div>

    <div class="form-group">
        <label for="document_type_id" class="form-label"><?php echo app_lang('ged_field_document_type'); ?> *</label>
        <?php echo form_dropdown('document_type_id', $document_types_dropdown, $model_info->document_type_id, 'class="form-control select2" id="document_type_id" required'); ?>
    </div>

    <div class="form-group">
        <label for="owner_type" class="form-label"><?php echo app_lang('ged_field_owner_type'); ?> *</label>
        <?php echo form_dropdown('owner_type', $owner_types_dropdown, $current_owner_type, 'class="form-control select2" id="owner_type" required'); ?>
    </div>

    <div class="form-group owner-field owner-employee-field <?php echo $current_owner_type === 'employee' ? '' : 'hide'; ?>">
        <label for="employee_id" class="form-label"><?php echo app_lang('ged_field_employee'); ?></label>
        <?php echo form_dropdown('employee_id', $employees_dropdown, $model_info->employee_id, 'class="form-control select2" id="employee_id"'); ?>
    </div>

    <div class="form-group owner-field owner-supplier-field <?php echo $current_owner_type === 'supplier' ? '' : 'hide'; ?>">
        <label for="supplier_id" class="form-label"><?php echo app_lang('ged_field_supplier'); ?></label>
        <?php echo form_dropdown('supplier_id', $suppliers_dropdown, $model_info->supplier_id, 'class="form-control select2" id="supplier_id"'); ?>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label for="issue_date" class="form-label"><?php echo app_lang('ged_field_issue_date'); ?></label>
                <input type="date" id="issue_date" name="issue_date" value="<?php echo esc($model_info->issue_date); ?>" class="form-control" />
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label for="expiration_date" class="form-label"><?php echo app_lang('ged_field_expiration_date'); ?></label>
                <input type="date" id="expiration_date" name="expiration_date" value="<?php echo esc($model_info->expiration_date); ?>" class="form-control" />
            </div>
        </div>
    </div>

    <div class="form-group">
        <label for="document_file" class="form-label"><?php echo app_lang('ged_field_file'); ?></label>
        <input type="file" id="document_file" name="document_file" class="form-control" />
        <small class="text-muted">Formatos e tamanho seguem a configuracao do GED.</small>
        <?php if (!empty($model_info->original_filename)) { ?>
            <div class="mt10">
                <span class="badge bg-light text-dark"><?php echo esc($model_info->original_filename); ?></span>
            </div>
        <?php } ?>
    </div>

    <div class="form-group">
        <label for="notes" class="form-label"><?php echo app_lang('ged_field_notes'); ?></label>
        <textarea id="notes" name="notes" class="form-control" rows="4"><?php echo esc($model_info->notes); ?></textarea>
    </div>
</div>

<div class="modal-footer">
    <button type="button" class="btn btn-default btn-sm" data-bs-dismiss="modal"><span data-feather="x" class="icon-16"></span> <?php echo app_lang('close'); ?></button>
    <button type="submit" class="btn btn-primary btn-sm"><span data-feather="check-circle" class="icon-16"></span> <?php echo app_lang('save'); ?></button>
</div>
<?php echo form_close(); ?>

<script type="text/javascript">
    function toggleGedOwnerFields() {
        var ownerType = $("#owner_type").val();
        $(".owner-field").addClass("hide");
        $(".owner-employee-field, .owner-supplier-field").find("select").prop("disabled", true);

        if (ownerType === "employee") {
            $(".owner-employee-field").removeClass("hide");
            $("#employee_id").prop("disabled", false);
        } else if (ownerType === "supplier") {
            $(".owner-supplier-field").removeClass("hide");
            $("#supplier_id").prop("disabled", false);
        }
    }

    $(document).ready(function () {
        toggleGedOwnerFields();
        $("#owner_type").on("change", toggleGedOwnerFields);
        $("#ged-document-form .select2").select2();

        $("#ged-document-form").appForm({
            onSuccess: function (result) {
                $("#ged-documents-table").appTable({newData: result.data, dataId: result.id});
            }
        });
    });
</script>
