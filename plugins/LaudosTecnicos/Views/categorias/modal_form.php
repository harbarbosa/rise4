<?php
$id = get_array_value($model_info, 'id');
$name = get_array_value($model_info, 'name');
$code = get_array_value($model_info, 'code');
$description = get_array_value($model_info, 'description');
$color = get_array_value($model_info, 'color') ?: '#3788d8';
$icon = get_array_value($model_info, 'icon');
$sort_order = get_array_value($model_info, 'sort_order') ?: 0;
$status = get_array_value($model_info, 'status') ?? 1;
?>

<form id="categoria-form" method="POST" class="dialog-form">
    <input type="hidden" name="id" value="<?php echo $id; ?>" />
    
    <div class="form-group">
        <label for="name"><?php echo app_lang('laudos_name'); ?> *</label>
        <?php
        echo form_input(array(
            'id' => 'name',
            'name' => 'name',
            'value' => $name,
            'class' => 'form-control',
            'required' => true,
            'placeholder' => app_lang('laudos_category_name_placeholder')
        ));
        ?>
    </div>
    
    <div class="form-group">
        <label for="code"><?php echo app_lang('laudos_code'); ?> *</label>
        <?php
        echo form_input(array(
            'id' => 'code',
            'name' => 'code',
            'value' => $code,
            'class' => 'form-control',
            'required' => true,
            'placeholder' => 'Ex: ELETRICA, SPDA, CFTV'
        ));
        ?>
    </div>
    
    <div class="form-group">
        <label for="description"><?php echo app_lang('description'); ?></label>
        <?php
        echo form_textarea(array(
            'id' => 'description',
            'name' => 'description',
            'value' => $description,
            'class' => 'form-control',
            'rows' => 3,
            'placeholder' => app_lang('laudos_category_description_placeholder')
        ));
        ?>
    </div>
    
    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label for="color"><?php echo app_lang('laudos_color'); ?></label>
                <div class="input-group color-picker">
                    <?php
                    echo form_input(array(
                        'id' => 'color',
                        'name' => 'color',
                        'value' => $color,
                        'class' => 'form-control',
                        'type' => 'color'
                    ));
                    ?>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label for="icon"><?php echo app_lang('laudos_icon'); ?></label>
                <?php
                echo form_input(array(
                    'id' => 'icon',
                    'name' => 'icon',
                    'value' => $icon,
                    'class' => 'form-control',
                    'placeholder' => 'Ex: zap, cpu, lock'
                ));
                ?>
                <small class="help-block"><?php echo app_lang('laudos_icon_help'); ?></small>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label for="sort_order"><?php echo app_lang('laudos_sort_order'); ?></label>
                <?php
                echo form_input(array(
                    'id' => 'sort_order',
                    'name' => 'sort_order',
                    'value' => $sort_order,
                    'class' => 'form-control',
                    'type' => 'number',
                    'min' => 0
                ));
                ?>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group mt-4">
                <div class="form-check">
                    <?php echo form_checkbox('status', '1', $status ? true : false, "id='status' class='form-check-input'"); ?>
                    <label for="status"><?php echo app_lang('active'); ?></label>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
$(document).ready(function() {
    $('#categoria-form').appForm({
        onSuccess: function(response) {
            $('#categorias-table').appTable({reload: true});
        }
    });
});
</script>