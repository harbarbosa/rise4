<div class="p20">
    <h4><?php echo app_lang('update'); ?></h4>
    <?php if (isset($result['success']) && $result['success']) { ?>
        <div class="alert alert-success">
            <?php echo app_lang('record_saved'); ?>
        </div>
    <?php } else { ?>
        <div class="alert alert-danger">
            <?php echo app_lang('error_occurred'); ?>
        </div>
    <?php } ?>

    <?php if (!empty($result['errors'])) { ?>
        <ul class="mt15">
            <?php foreach ($result['errors'] as $error) { ?>
                <li><?php echo esc($error); ?></li>
            <?php } ?>
        </ul>
    <?php } ?>
</div>
