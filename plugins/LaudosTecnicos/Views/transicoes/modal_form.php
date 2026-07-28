<?php
// Array vazio para novo registro
$model_info = isset($model_info) ? $model_info : array();
?>

<form id="transicao-form" method="POST" class="dialog-form">
    <div class="form-group">
        <label for="from_status_id"><?php echo app_lang('laudos_from_status'); ?> *</label>
        <select name="from_status_id" id="from_status_id" class="form-control" required>
            <option value=""><?php echo app_lang('laudos_select_status'); ?></option>
            <?php foreach ($status_list as $status): ?>
            <option value="<?php echo $status->id; ?>"><?php echo $status->name; ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    
    <div class="form-group">
        <label for="to_status_id"><?php echo app_lang('laudos_to_status'); ?> *</label>
        <select name="to_status_id" id="to_status_id" class="form-control" required>
            <option value=""><?php echo app_lang('laudos_select_status'); ?></option>
            <?php foreach ($status_list as $status): ?>
            <option value="<?php echo $status->id; ?>"><?php echo $status->name; ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    
    <div class="form-group">
        <label for="sort_order"><?php echo app_lang('laudos_sort_order'); ?></label>
        <?php
        echo form_input(array(
            'id' => 'sort_order',
            'name' => 'sort_order',
            'value' => 0,
            'class' => 'form-control',
            'type' => 'number',
            'min' => 0
        ));
        ?>
    </div>
    
    <div class="row">
        <div class="col-md-6">
            <div class="form-check">
                <?php echo form_checkbox('require_comment', '1', false, "id='require_comment' class='form-check-input'"); ?>
                <label for="require_comment"><?php echo app_lang('laudos_require_comment'); ?></label>
            </div>
            <div class="form-check">
                <?php echo form_checkbox('notify', '1', false, "id='notify' class='form-check-input'"); ?>
                <label for="notify"><?php echo app_lang('laudos_notify'); ?></label>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-check">
                <?php echo form_checkbox('create_task', '1', false, "id='create_task' class='form-check-input'"); ?>
                <label for="create_task"><?php echo app_lang('laudos_create_task'); ?></label>
            </div>
            <div class="form-check">
                <?php echo form_checkbox('active', '1', true, "id='active' class='form-check-input'"); ?>
                <label for="active"><?php echo app_lang('active'); ?></label>
            </div>
        </div>
    </div>
</form>

<script>
$(document).ready(function() {
    $('#transicao-form').appForm({
        onSuccess: function(response) {
            $('#transicoes-table').appTable({reload: true});
        }
    });
    
    $('#from_status_id, #to_status_id').change(function() {
        if ($('#from_status_id').val() === $('#to_status_id').val() && $('#from_status_id').val() !== '') {
            appAlert.error('<?php echo app_lang("laudos_same_status_error"); ?>');
            $(this).val('').change();
        }
    });
});
</script>