<?php
$model_info = $model_info ?? (object) array(
    'id' => 0,
    'title' => '',
    'employee_id' => 0,
    'project_id' => 0,
    'client_id' => 0,
    'destination' => '',
    'start_date' => '',
    'end_date' => '',
    'purpose' => '',
    'notes' => '',
    'status' => 'draft',
    'total_amount' => 0,
);

$destination_value = trim((string) ($model_info->destination ?? ''));

$can_edit_trip = $can_edit_trip ?? true;
?>

<?php echo form_open(get_uri('travelrefunds/trips/save'), array('id' => 'travelrefunds-trip-modal-form', 'class' => 'general-form', 'role' => 'form')); ?>
<div class="modal-body clearfix">
    <div class="container-fluid">
        <input type="hidden" name="id" value="<?php echo esc($model_info->id); ?>" />

        <div class="form-group">
            <div class="row">
                <label for="title" class="col-md-3"><?php echo 'Titulo da viagem'; ?> *</label>
                <div class="col-md-9">
                    <?php
                    $title_attributes = array(
                        'id' => 'title',
                        'name' => 'title',
                        'value' => $model_info->title,
                        'class' => 'form-control',
                        'placeholder' => 'Titulo da viagem',
                        'autofocus' => true,
                        'required' => true,
                    );
                    if (!$can_edit_trip) {
                        $title_attributes['readonly'] = 'readonly';
                    }
                    echo form_input($title_attributes);
                    ?>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="employee_id" class="col-md-3"><?php echo 'Funcionario responsavel'; ?> *</label>
                <div class="col-md-9">
                    <?php echo form_dropdown('employee_id', $responsible_employee_dropdown ?? array(), $model_info->employee_id, 'class="form-control select2 w100p" id="employee_id"' . ($can_edit_trip ? '' : ' disabled')); ?>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="project_id" class="col-md-3"><?php echo 'Projeto'; ?></label>
                <div class="col-md-9">
                    <?php echo form_dropdown('project_id', $project_dropdown, $model_info->project_id, 'class="form-control select2 w100p" id="project_id"' . ($can_edit_trip ? '' : ' disabled')); ?>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="client_id" class="col-md-3"><?php echo 'Cliente'; ?></label>
                <div class="col-md-9">
                    <?php echo form_dropdown('client_id', $client_dropdown, $model_info->client_id, 'class="form-control select2 w100p" id="client_id"' . ($can_edit_trip ? '' : ' disabled')); ?>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="traveler_ids" class="col-md-3"><?php echo 'Funcionarios'; ?></label>
                <div class="col-md-9">
                    <?php echo form_dropdown('traveler_ids[]', $travelers_dropdown, $selected_traveler_ids ?? array(), 'class="form-control select2 w100p" id="traveler_ids" multiple="multiple"' . ($can_edit_trip ? '' : ' disabled')); ?>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="total_amount" class="col-md-3"><?php echo 'Valor'; ?></label>
                <div class="col-md-9">
                    <?php
                    $amount_attributes = array(
                        'id' => 'total_amount',
                        'name' => 'total_amount',
                        'value' => $model_info->total_amount !== null && $model_info->total_amount !== '' ? to_decimal_format((float) $model_info->total_amount) : '',
                        'class' => 'form-control money js-currency-field',
                        'placeholder' => '0,00',
                        'autocomplete' => 'off',
                    );
                    if (!$can_edit_trip) {
                        $amount_attributes['readonly'] = 'readonly';
                    }
                    echo form_input($amount_attributes);
                    ?>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="destination" class="col-md-3"><?php echo 'Destino'; ?></label>
                <div class="col-md-9">
                    <?php
                    $destination_attributes = array(
                        'id' => 'destination',
                        'name' => 'destination',
                        'value' => $destination_value,
                        'class' => 'form-control select2 w100p',
                        'placeholder' => 'Digite para buscar uma cidade',
                        'autocomplete' => 'off',
                    );
                    if (!$can_edit_trip) {
                        $destination_attributes['readonly'] = 'readonly';
                    }
                    echo form_input($destination_attributes);
                    ?>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="start_date" class="col-md-3"><?php echo 'Data inicial'; ?></label>
                <div class="col-md-9">
                    <?php
                    $start_date_attributes = array(
                        'id' => 'start_date',
                        'name' => 'start_date',
                        'value' => $model_info->start_date,
                        'class' => 'form-control',
                        'type' => 'date',
                    );
                    if (!$can_edit_trip) {
                        $start_date_attributes['readonly'] = 'readonly';
                    }
                    echo form_input($start_date_attributes);
                    ?>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="end_date" class="col-md-3"><?php echo 'Data final'; ?></label>
                <div class="col-md-9">
                    <?php
                    $end_date_attributes = array(
                        'id' => 'end_date',
                        'name' => 'end_date',
                        'value' => $model_info->end_date,
                        'class' => 'form-control',
                        'type' => 'date',
                    );
                    if (!$can_edit_trip) {
                        $end_date_attributes['readonly'] = 'readonly';
                    }
                    echo form_input($end_date_attributes);
                    ?>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="purpose" class="col-md-3"><?php echo 'Objetivo da viagem'; ?></label>
                <div class="col-md-9">
                    <?php
                    $purpose_attributes = array(
                        'id' => 'purpose',
                        'name' => 'purpose',
                        'value' => $model_info->purpose,
                        'class' => 'form-control',
                        'rows' => 3,
                        'placeholder' => 'Objetivo da viagem',
                    );
                    if (!$can_edit_trip) {
                        $purpose_attributes['readonly'] = 'readonly';
                    }
                    echo form_textarea($purpose_attributes);
                    ?>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="notes" class="col-md-3"><?php echo 'Observacoes'; ?></label>
                <div class="col-md-9">
                    <?php
                    $notes_attributes = array(
                        'id' => 'notes',
                        'name' => 'notes',
                        'value' => $model_info->notes,
                        'class' => 'form-control',
                        'rows' => 3,
                        'placeholder' => 'Observacoes',
                    );
                    if (!$can_edit_trip) {
                        $notes_attributes['readonly'] = 'readonly';
                    }
                    echo form_textarea($notes_attributes);
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal-footer">
    <button type="button" class="btn btn-default btn-sm" data-bs-dismiss="modal"><span data-feather="x" class="icon-16"></span> <?php echo app_lang('close'); ?></button>
    <?php if ($can_edit_trip) { ?>
        <button type="submit" class="btn btn-primary btn-sm"><span data-feather="check-circle" class="icon-16"></span> <?php echo app_lang('save'); ?></button>
    <?php } ?>
</div>
<?php echo form_close(); ?>

<script type="text/javascript">
    $(document).ready(function () {
        $("#travelrefunds-trip-modal-form .select2").not("#destination").select2();

        $("#destination").select2({
            showSearchBox: true,
            minimumInputLength: 1,
            placeholder: "Digite para buscar uma cidade",
            ajax: {
                url: "<?php echo get_uri('travelrefunds/cities'); ?>",
                type: "GET",
                dataType: "json",
                quietMillis: 250,
                data: function (term, page) {
                    return {
                        q: term,
                        page: page,
                        limit: 25
                    };
                },
                results: function (data) {
                    return {
                        results: data.results || []
                    };
                }
            }
        });

        var destinationValue = $("#destination").val();
        if (destinationValue) {
            $("#destination").select2("data", {
                id: destinationValue,
                text: destinationValue
            });
        }

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

        $("#travelrefunds-trip-modal-form .js-currency-field").each(function () {
            formatCurrencyField($(this));
        });

        $(document).on("blur", "#travelrefunds-trip-modal-form .js-currency-field", function () {
            formatCurrencyField($(this));
        });

        $(document).on("input", "#travelrefunds-trip-modal-form .js-currency-field", function () {
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

            var el = this;
            if (el.setSelectionRange) {
                var length = $field.val().length;
                el.setSelectionRange(length, length);
            }
        });

        window.travelrefundsTripModalForm = $("#travelrefunds-trip-modal-form").appForm({
            closeModalOnSuccess: false,
            onSuccess: function (result) {
                if (result && result.redirect) {
                    window.location = result.redirect;
                    return;
                }

                if (result && result.success) {
                    location.reload();
                }
            }
        });

        setTimeout(function () {
            $("#title").trigger("focus");
        }, 200);
    });
</script>
