<div class="mb20">
    <h5 class="mb10"><?php echo app_lang('fotovoltaico_wizard_step_review'); ?></h5>
    <p class="text-off mb0">Revise os dados antes de concluir a proposta.</p>
</div>

<div class="row g-3">
    <div class="col-md-6">
        <div class="card bg-light border-0">
            <div class="card-body">
                <strong><?php echo app_lang('fotovoltaico_proposal_client'); ?></strong>
                <div class="mt5"><?php echo esc(get_array_value($summary, 'crm_reference') ?: '-'); ?></div>
                <div class="mt10">
                    <strong><?php echo app_lang('fotovoltaico_proposal_consumer_unit'); ?>:</strong>
                    <div><?php echo esc(get_array_value($summary, 'consumer_unit') ?: '-'); ?></div>
                </div>
                <div class="mt10">
                    <strong><?php echo app_lang('fotovoltaico_proposal_distributor'); ?>:</strong>
                    <div><?php echo esc(get_array_value($summary, 'distributor') ?: '-'); ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card bg-light border-0">
            <div class="card-body">
                <strong><?php echo app_lang('fotovoltaico_wizard_summary'); ?></strong>
                <div class="mt10">
                    <strong><?php echo app_lang('fotovoltaico_proposal_consumption_avg'); ?>:</strong>
                    <div><?php echo number_format((float) (get_array_value($summary, 'consumption_avg') ?: 0), 2, ',', '.'); ?></div>
                </div>
                <div class="mt10">
                    <strong><?php echo app_lang('total'); ?>:</strong>
                    <div><?php echo to_currency((float) (get_array_value($summary, 'total') ?: 0), get_setting('currency_symbol')); ?></div>
                </div>
                <div class="mt10">
                    <strong><?php echo app_lang('status'); ?>:</strong>
                    <div><?php echo fotovoltaico_proposal_status_badge(get_array_value($summary, 'status') ?: 'draft'); ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12">
        <label for="review_notes" class="form-label"><?php echo app_lang('notes'); ?></label>
        <?php echo form_textarea(array('id' => 'review_notes', 'name' => 'review_notes', 'value' => get_array_value($wizard_data, 'review_notes'), 'class' => 'form-control', 'rows' => 4)); ?>
    </div>
</div>

<div class="d-flex justify-content-between align-items-center mt20">
    <div>
        <a href="<?php echo get_uri('fotovoltaico/proposal_wizard/step/' . $proposal_id . '/' . $previous_step); ?>" class="btn btn-default">
            <i data-feather="arrow-left" class="icon-16"></i> <?php echo app_lang('fotovoltaico_wizard_previous'); ?>
        </a>
    </div>
    <button type="submit" class="btn btn-success">
        <i data-feather="check-circle" class="icon-16"></i> <?php echo app_lang('fotovoltaico_wizard_finish'); ?>
    </button>
</div>
