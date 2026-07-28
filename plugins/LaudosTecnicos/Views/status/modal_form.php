<?php
$id = get_array_value($model_info, 'id');
$name = get_array_value($model_info, 'name');
$code = get_array_value($model_info, 'code');
$description = get_array_value($model_info, 'description');
$color = get_array_value($model_info, 'color') ?: '#3788d8';
$icon = get_array_value($model_info, 'icon');
$sort_order = get_array_value($model_info, 'sort_order') ?: 0;
$is_initial = get_array_value($model_info, 'is_initial') ?? 0;
$is_final = get_array_value($model_info, 'is_final') ?? 0;
$is_cancel = get_array_value($model_info, 'is_cancel') ?? 0;
$allow_edit = get_array_value($model_info, 'allow_edit') ?? 1;
$allow_delete = get_array_value($model_info, 'allow_delete') ?? 0;
$allow_issue = get_array_value($model_info, 'allow_issue') ?? 0;
$require_comment = get_array_value($model_info, 'require_comment') ?? 0;
$active = get_array_value($model_info, 'active') ?? 1;
?>

<form id="status-form" method="POST" class="dialog-form">
    <input type="hidden" name="id" value="<?php echo $id; ?>" />
    
    <div class="form-group">
        <label for="name"><?php echo app_lang('laudos_name'); ?> *</label>
        <?php
        echo form_input(array(
            'id' => 'name',
            'name' => 'name',
            'value' => $name,
            'class' => 'form-control',
            'required' => true
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
            'placeholder' => 'Ex: draft, requested, approved'
        ));
        ?>
        <small class="help-block"><?php echo app_lang('laudos_status_code_help'); ?></small>
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
        <div class="col-md-6">
            <div class="form-group">
                <label for="color"><?php echo app_lang('laudos_color'); ?></label>
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
        <div class="col-md-6">
            <div class="form-group">
                <label for="icon"><?php echo app_lang('laudos_icon'); ?></label>
                <?php
                echo form_input(array(
                    'id' => 'icon',
                    'name' => 'icon',
                    'value' => $icon,
                    'class' => 'form-control'
                ));
                ?>
            </div>
        </div>
    </div>
    
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
    
    <div class="row">
        <div class="col-md-6">
            <div class="form-check">
                <?php echo form_checkbox('is_initial', '1', $is_initial ? true : false, "id='is_initial' class='form-check-input'"); ?>
                <label for="is_initial"><?php echo app_lang('laudos_status_initial'); ?></label>
            </div>
            <div class="form-check">
                <?php echo form_checkbox('is_final', '1', $is_final ? true : false, "id='is_final' class='form-check-input'"); ?>
                <label for="is_final"><?php echo app_lang('laudos_status_final'); ?></label>
            </div>
            <div class="form-check">
                <?php echo form_checkbox('is_cancel', '1', $is_cancel ? true : false, "id='is_cancel' class='form-check-input'"); ?>
                <label for="is_cancel"><?php echo app_lang('laudos_status_cancel'); ?></label>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-check">
                <?php echo form_checkbox('allow_edit', '1', $allow_edit ? true : false, "id='allow_edit' class='form-check-input'"); ?>
                <label for="allow_edit"><?php echo app_lang('laudos_allow_edit'); ?></label>
            </div>
            <div class="form-check">
                <?php echo form_checkbox('allow_delete', '1', $allow_delete ? true : false, "id='allow_delete' class='form-check-input'"); ?>
                <label for="allow_delete"><?php echo app_lang('laudos_allow_delete'); ?></label>
            </div>
            <div class="form-check">
                <?php echo form_checkbox('allow_issue', '1', $allow_issue ? true : false, "id='allow_issue' class='form-check-input'"); ?>
                <label for="allow_issue"><?php echo app_lang('laudos_allow_issue'); ?></label>
            </div>
            <div class="form-check">
                <?php echo form_checkbox('require_comment', '1', $require_comment ? true : false, "id='require_comment' class='form-check-input'"); ?>
                <label for="require_comment"><?php echo app_lang('laudos_require_comment'); ?></label>
            </div>
        </div>
    </div>
    
    <div class="form-check mt-3">
        <?php echo form_checkbox('active', '1', $active ? true : false, "id='active' class='form-check-input'"); ?>
        <label for="active"><?php echo app_lang('active'); ?></label>
    </div>
</form>

<script>
$(document).ready(function() {
    $('#status-form').appForm({
        onSuccess: function(response) {
            $('#status-table').appTable({reload: true});
        }
    });
});
</script>