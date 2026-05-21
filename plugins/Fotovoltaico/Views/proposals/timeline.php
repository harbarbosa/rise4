<?php if (count($versions)) { ?>
    <div class="timeline">
        <?php foreach ($versions as $version) { ?>
            <?php $version_result = json_decode($version->result_json, true); ?>
            <?php $version_summary = get_array_value($version_result, 'summary') ?: array(); ?>
            <div class="timeline-item mb20">
                <div class="timeline-icon">
                    <i data-feather="git-branch" class="icon-16"></i>
                </div>
                <div class="timeline-content card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h6 class="mb5"><?php echo app_lang('fotovoltaico_version') . ' ' . (int) $version->version_number; ?></h6>
                                <div class="small text-off"><?php echo format_to_relative_time($version->created_at); ?></div>
                            </div>
                            <div><?php echo fotovoltaico_proposal_status_badge($version->status ?: 'draft'); ?></div>
                        </div>
                        <div class="mt10">
                            <strong><?php echo app_lang('total'); ?>:</strong>
                            <?php echo to_currency((float) $version->total, get_setting('currency_symbol')); ?>
                        </div>
                        <div class="mt10">
                            <strong><?php echo app_lang('fotovoltaico_proposal_crm'); ?>:</strong>
                            <?php echo esc(get_array_value($version_summary, 'crm_reference') ?: '-'); ?>
                        </div>
                        <div class="mt10">
                            <strong><?php echo app_lang('fotovoltaico_proposal_current_version'); ?>:</strong>
                            <?php echo (int) ($version_summary['current_version'] ?? $version->version_number); ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php } ?>
    </div>
<?php } else { ?>
    <div class="text-off"><?php echo app_lang('no_records_found'); ?></div>
<?php } ?>
