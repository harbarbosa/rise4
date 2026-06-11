<?php
$model_info = $model_info ?? (object) array(
    'id' => 0,
    'trip_id' => 0,
    'employee_id' => 0,
    'project_id' => 0,
    'category_id' => 0,
    'expense_date' => get_my_local_time('Y-m-d'),
    'amount' => 0,
    'status' => 'pending',
    'payment_method' => '',
    'description' => '',
    'notes' => '',
    'vendor' => '',
    'receipt_number' => '',
    'receipt_file' => '',
    'has_invoice' => 0,
);
$can_edit = $can_edit ?? true;
$selected_trip_id = isset($model_info->trip_id) ? $model_info->trip_id : '';
$selected_project_id = isset($selected_project_id) ? $selected_project_id : (isset($model_info->project_id) ? $model_info->project_id : '');
$selected_project_id = $selected_project_id ?: ((!empty($selected_trip_id) && !empty($trip_project_map[$selected_trip_id])) ? $trip_project_map[$selected_trip_id] : '');
$selected_employee_id = isset($model_info->employee_id) ? $model_info->employee_id : '';
$selected_category_id = isset($model_info->category_id) ? $model_info->category_id : '';
$selected_amount = isset($model_info->amount) ? $model_info->amount : '';
$selected_status = isset($model_info->status) ? $model_info->status : 'pending';
$selected_payment_method = isset($model_info->payment_method) ? $model_info->payment_method : '';
$selected_vendor = isset($model_info->vendor) ? $model_info->vendor : '';
$selected_receipt_number = isset($model_info->receipt_number) ? $model_info->receipt_number : '';
$selected_attachment_id = isset($model_info->attachment_id) ? $model_info->attachment_id : '';
$selected_attachment_files = isset($selected_attachment_files) ? $selected_attachment_files : '';
$selected_receipt_file = isset($model_info->receipt_file) ? $model_info->receipt_file : '';
$selected_description = isset($model_info->description) ? $model_info->description : '';
$selected_notes = isset($model_info->notes) ? $model_info->notes : '';
?>

<?php echo form_open(get_uri('travelrefunds/reimbursements/save'), array('id' => 'travelrefunds-reimbursement-modal-form', 'class' => 'general-form', 'role' => 'form')); ?>
<div id="travelrefunds-reimbursement-dropzone" class="post-dropzone">
<div class="modal-body clearfix">
    <div class="container-fluid">
        <input type="hidden" name="id" value="<?php echo esc($model_info->id); ?>" />

        <div class="form-group">
            <div class="row">
                <label for="trip_id" class="col-md-3">Viagem</label>
                <div class="col-md-9">
                    <?php
                    $trips_dropdown = array('' => '-');
                    foreach (($trips ?? array()) as $trip) {
                        $trips_dropdown[$trip->id] = $trip->title;
                    }
                    echo form_dropdown('trip_id', $trips_dropdown, $selected_trip_id, 'class="form-control select2 w100p" id="trip_id"' . ($can_edit ? '' : ' disabled'));
                    ?>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="project_id" class="col-md-3">Projeto</label>
                <div class="col-md-9">
                    <?php
                    $projects_dropdown = $projects_dropdown ?? array('' => '-');
                    echo form_dropdown('project_id', $projects_dropdown, $selected_project_id, 'class="form-control select2 w100p" id="project_id"' . ($can_edit ? '' : ' disabled'));
                    ?>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="employee_id" class="col-md-3">Funcionario</label>
                <div class="col-md-9">
                    <?php
                    $users_dropdown = array('' => '-');
                    foreach (($users ?? array()) as $user) {
                        $users_dropdown[$user->id] = trim($user->first_name . ' ' . $user->last_name);
                    }
                    echo form_dropdown('employee_id', $users_dropdown, $selected_employee_id, 'class="form-control select2 w100p" id="employee_id"' . ($can_edit ? '' : ' disabled'));
                    ?>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="category_id" class="col-md-3">Categoria</label>
                <div class="col-md-9">
                    <?php
                    $categories_dropdown = array('' => '-');
                    foreach (($categories ?? array()) as $category) {
                        $categories_dropdown[$category->id] = $category->name ?: $category->title;
                    }
                    echo form_dropdown('category_id', $categories_dropdown, $selected_category_id, 'class="form-control select2 w100p" id="category_id"' . ($can_edit ? '' : ' disabled'));
                    ?>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="expense_date" class="col-md-3">Data da despesa</label>
                <div class="col-md-9">
                    <input type="date" name="expense_date" id="expense_date" class="form-control" value="<?php echo esc(isset($model_info->expense_date) ? $model_info->expense_date : get_my_local_time('Y-m-d')); ?>" <?php echo $can_edit ? '' : 'readonly'; ?> />
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="amount" class="col-md-3">Valor</label>
                <div class="col-md-9">
                    <input type="text" name="amount" id="amount" class="form-control money js-currency-field" value="<?php echo esc($selected_amount !== null && $selected_amount !== '' ? to_decimal_format((float) $selected_amount) : ''); ?>" autocomplete="off" placeholder="0,00" <?php echo $can_edit ? '' : 'readonly'; ?> />
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="payment_method" class="col-md-3">Forma de pagamento</label>
                <div class="col-md-9">
                    <?php echo form_dropdown('payment_method', array('' => '-') + array_combine($payment_methods ?? array(), $payment_methods ?? array()), $selected_payment_method, 'class="form-control select2 w100p" id="payment_method"' . ($can_edit ? '' : ' disabled')); ?>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="status" class="col-md-3">Status</label>
                <div class="col-md-9">
                    <?php
                    $status_dropdown = array('' => '-');
                    foreach (($status_options ?? array()) as $status) {
                        $status_dropdown[$status] = travelrefunds_status_label($status);
                    }
                    echo form_dropdown('status', $status_dropdown, $selected_status, 'class="form-control select2 w100p" id="status"' . ($can_edit ? '' : ' disabled'));
                    ?>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="vendor" class="col-md-3">Fornecedor</label>
                <div class="col-md-9">
                    <input type="text" name="vendor" id="vendor" class="form-control" value="<?php echo esc($selected_vendor); ?>" <?php echo $can_edit ? '' : 'readonly'; ?> />
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="receipt_number" class="col-md-3">Numero do comprovante</label>
                <div class="col-md-9">
                    <input type="text" name="receipt_number" id="receipt_number" class="form-control" value="<?php echo esc($selected_receipt_number); ?>" <?php echo $can_edit ? '' : 'readonly'; ?> />
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label class="col-md-3">Upload de comprovante/NF</label>
                <div class="col-md-9">
                    <input type="hidden" name="attachment_id" id="attachment_id" value="<?php echo esc($selected_attachment_id); ?>" />
                    <div class="mb10 d-flex align-items-center justify-content-between">
                        <div class="strong">Upload de comprovante/NF</div>
                        <?php echo view("includes/upload_button", array("single_file" => true, "upload_button_text" => "Anexar comprovante")); ?>
                    </div>
                    <?php if ($selected_attachment_files) { ?>
                        <div class="mb10">
                            <?php echo view("includes/file_list", array("files" => $selected_attachment_files)); ?>
                        </div>
                    <?php } else if (!empty($selected_attachment_id)) { ?>
                        <div class="text-off mt10">
                            <a href="<?php echo get_uri('file_manager/view_file/' . $selected_attachment_id); ?>" target="_blank">Ver anexo existente</a>
                        </div>
                    <?php } else if (!empty($selected_receipt_file)) { ?>
                        <div class="text-off mt10">
                            <a href="<?php echo base_url('files/travelrefunds/reimbursements/' . rawurlencode($selected_receipt_file)); ?>" target="_blank">Ver anexo existente</a>
                        </div>
                    <?php } ?>
                    <?php echo view("includes/dropzone_preview"); ?>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="description" class="col-md-3">Descricao</label>
                <div class="col-md-9">
                    <textarea name="description" id="description" class="form-control" rows="3" <?php echo $can_edit ? '' : 'readonly'; ?>><?php echo esc($selected_description); ?></textarea>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="notes" class="col-md-3">Observacoes</label>
                <div class="col-md-9">
                    <textarea name="notes" id="notes" class="form-control" rows="3" <?php echo $can_edit ? '' : 'readonly'; ?>><?php echo esc($selected_notes); ?></textarea>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal-footer">
    <button type="button" class="btn btn-default btn-sm cancel-upload" data-bs-dismiss="modal"><span data-feather="x" class="icon-16"></span> <?php echo app_lang('close'); ?></button>
    <?php if ($can_edit) { ?>
        <button type="submit" class="btn btn-primary btn-sm"><span data-feather="check-circle" class="icon-16"></span> <?php echo app_lang('save'); ?></button>
    <?php } ?>
</div>
</div>
<?php echo form_close(); ?>

<script type="text/javascript">
    $(document).ready(function () {
        $("#travelrefunds-reimbursement-modal-form .select2").select2();
        $("#travelrefunds-reimbursement-modal-form").appForm({
            onSuccess: function () {
                location.reload();
            }
        });

        var tripProjectMap = <?php echo json_encode($trip_project_map ?? array()); ?>;
        $("#trip_id").on("change", function () {
            var selectedTripId = $(this).val();
            if (selectedTripId && tripProjectMap[selectedTripId]) {
                $("#project_id").val(tripProjectMap[selectedTripId]).trigger("change");
            }
        });

        var formatCurrencyField = function ($field) {
            if (typeof toCurrency !== "function" || typeof unformatCurrency !== "function") {
                return;
            }

            var value = $field.val();
            if (value === "") {
                return;
            }

            var numeric = unformatCurrency(value);
            if (isNaN(numeric)) {
                return;
            }

            $field.val(toCurrency(numeric));
        };

        $("#travelrefunds-reimbursement-modal-form .js-currency-field").each(function () {
            formatCurrencyField($(this));
        });

        $(document).on("blur", "#travelrefunds-reimbursement-modal-form .js-currency-field", function () {
            formatCurrencyField($(this));
        });

        $(document).on("input", "#travelrefunds-reimbursement-modal-form .js-currency-field", function () {
            var $field = $(this);
            if ($field.data("formatting")) {
                return;
            }
            $field.data("formatting", true);

            if (typeof toCurrency === "function") {
                var raw = $field.val();
                var digits = raw.replace(/\D/g, "");
                if (!digits.length) {
                    $field.val("");
                } else {
                    var numeric = parseInt(digits, 10) / 100;
                    $field.val(toCurrency(numeric));
                }
            }

            $field.data("formatting", false);
        });
    });
</script>
