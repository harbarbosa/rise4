<div class="p20">
    <h4><?php echo app_lang('fv_client_tab'); ?></h4>
    <?php if (!empty($rows)) { ?>
        <ul class="list-group">
            <?php foreach ($rows as $row) { ?>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <span><?php echo esc($row->title); ?></span>
                    <span class="badge bg-secondary"><?php echo esc($row->status); ?></span>
                </li>
            <?php } ?>
        </ul>
    <?php } else { ?>
        <div class="text-muted"><?php echo app_lang('no_record_found'); ?></div>
    <?php } ?>
</div>
