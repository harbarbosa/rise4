<?php
// $permissions vem do hook
$fv_manage = get_array_value($permissions ?? array(), 'fv_products_manage') === '1';
?>

<div class="form-group">
    <label class="col-md-4"><?php echo app_lang('fv_products'); ?></label>
    <div class="col-md-8">
        <div class="checkbox">
            <label>
                <input type="checkbox" name="fv_products_manage" value="1" <?php echo $fv_manage ? "checked" : ""; ?> />
                <?php echo app_lang('can_manage'); ?>
            </label>
        </div>
    </div>
</div>
