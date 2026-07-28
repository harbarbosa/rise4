<?php
$id = get_array_value($model_info, 'id');
$name = get_array_value($model_info, 'name');
$code = get_array_value($model_info, 'code');
$description = get_array_value($model_info, 'description');
$category_id = get_array_value($model_info, 'category_id');
$prefix = get_array_value($model_info, 'prefix');
$validity_days = get_array_value($model_info, 'validity_days') ?: 365;
$require_technician = get_array_value($model_info, 'require_technician') ?? 1;
$require_review = get_array_value($model_info, 'require_review') ?? 1;
$require_approval = get_array_value($model_info, 'require_approval') ?? 1;
$require_signature = get_array_value($model_info, 'require_signature') ?? 0;
$require_inspection = get_array_value($model_info, 'require_inspection') ?? 1;
$require_equipment = get_array_value($model_info, 'require_equipment') ?? 0;
$allow_mobile = get_array_value($model_info, 'allow_mobile') ?? 1;
$status = get_array_value($model_info, 'status') ?? 1;
?>

<form id="tipo-form" method="POST" class="dialog-form">
    <input type="hidden" name="id" value="<?php echo $id; ?>" />
    
    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label for="name"><?php echo app_lang('laudos_name'); ?> *</label>
                <?php
                echo form_input(array(
                    'id' => 'name',
                    'name' => 'name',
                    'value' => $name,
                    'class' => 'form-control',
                    'required' => true,
                    'placeholder' => 'Ex: Laudo de SPDA'
                ));
                ?>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label for="code"><?php echo app_lang('laudos_code'); ?> *</label>
                <?php
                echo form_input(array(
                    'id' => 'code',
                    'name' => 'code',
                    'value' => $code,
                    'class' => 'form-control',
                    'required' => true,
                    'placeholder' => 'Ex: LAUDO_SPDA'
                ));
                ?>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label for="category_id"><?php echo app_lang('laudos_category'); ?></label>
                <?php
                echo form_dropdown('category_id', $categorias, $category_id, "class='form-control' id='category_id'");
                ?>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label for="prefix"><?php echo app_lang('laudos_type_prefix'); ?></label>
                <?php
                echo form_input(array(
                    'id' => 'prefix',
                    'name' => 'prefix',
                    'value' => $prefix,
                    'class' => 'form-control',
                    'placeholder' => 'Ex: SPDA'
                ));
                ?>
            </div>
        </div>
    </div>
    
    <div class="form-group">
        <label for="description"><?php echo app_lang('description'); ?></label>
        <?php
        echo form_textarea(array(
            'id' => 'description',
            'name' => 'description',
            'value' => $description,
            'class' => 'form-control',
            'rows' => 2
        ));
        ?>
    </div>
    
    <div class="row">
        <div class="col-md-4">
            <div class="form-group">
                <label for="validity_days"><?php echo app_lang('laudos_type_validity_days'); ?></label>
                <?php
                echo form_input(array(
                    'id' => 'validity_days',
                    'name' => 'validity_days',
                    'value' => $validity_days,
                    'class' => 'form-control',
                    'type' => 'number',
                    'min' => 1
                ));
                ?>
            </div>
        </div>
        <div class="col-md-8">
            <label><?php echo app_lang('laudos_requirements'); ?></label>
            <div class="row mt-2">
                <div class="col-md-4">
                    <div class="form-check">
                        <?php echo form_checkbox('require_technician', '1', $require_technician ? true : false, "id='require_technician' class='form-check-input'"); ?>
                        <label for="require_technician"><?php echo app_lang('laudos_type_require_technician'); ?></label>
                    </div>
                    <div class="form-check">
                        <?php echo form_checkbox('require_review', '1', $require_review ? true : false, "id='require_review' class='form-check-input'"); ?>
                        <label for="require_review"><?php echo app_lang('laudos_type_require_review'); ?></label>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-check">
                        <?php echo form_checkbox('require_approval', '1', $require_approval ? true : false, "id='require_approval' class='form-check-input'"); ?>
                        <label for="require_approval"><?php echo app_lang('laudos_type_require_approval'); ?></label>
                    </div>
                    <div class="form-check">
                        <?php echo form_checkbox('require_signature', '1', $require_signature ? true : false, "id='require_signature' class='form-check-input'"); ?>
                        <label for="require_signature"><?php echo app_lang('laudos_type_require_signature'); ?></label>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-check">
                        <?php echo form_checkbox('require_inspection', '1', $require_inspection ? true : false, "id='require_inspection' class='form-check-input'"); ?>
                        <label for="require_inspection"><?php echo app_lang('laudos_type_require_inspection'); ?></label>
                    </div>
                    <div class="form-check">
                        <?php echo form_checkbox('require_equipment', '1', $require_equipment ? true : false, "id='require_equipment' class='form-check-input'"); ?>
                        <label for="require_equipment"><?php echo app_lang('laudos_type_require_equipment'); ?></label>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mt-3">
        <div class="col-md-6">
            <div class="form-check">
                <?php echo form_checkbox('allow_mobile', '1', $allow_mobile ? true : false, "id='allow_mobile' class='form-check-input'"); ?>
                <label for="allow_mobile"><?php echo app_lang('laudos_type_allow_mobile'); ?></label>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-check">
                <?php echo form_checkbox('status', '1', $status ? true : false, "id='status' class='form-check-input'"); ?>
                <label for="status"><?php echo app_lang('active'); ?></label>
            </div>
        </div>
    </div>
</form>

<script>
$(document).ready(function() {
    $('#tipo-form').appForm({
        onSuccess: function(response) {
            $('#tipos-table').appTable({reload: true});
        }
    });
});
</script>