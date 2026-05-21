<?php
$form_action = $step === 'review' ? get_uri('fotovoltaico/proposal_wizard/finish') : get_uri('fotovoltaico/proposal_wizard/save_step');
$wizard_step_view = 'Fotovoltaico\\Views\\proposal_wizard\\steps\\' . $step;
$summary_items = array(
    array('label' => app_lang('fotovoltaico_proposal_code'), 'value' => $summary['proposal_code'] ?? '-'),
    array('label' => app_lang('fotovoltaico_proposal_current_version'), 'value' => (int) ($summary['current_version'] ?? 0)),
    array('label' => app_lang('fotovoltaico_proposal_crm'), 'value' => $summary['crm_reference'] ?? '-'),
    array('label' => app_lang('fotovoltaico_proposal_consumer_unit'), 'value' => $summary['consumer_unit'] ?? '-'),
    array('label' => app_lang('fotovoltaico_proposal_distributor'), 'value' => $summary['distributor'] ?? '-'),
    array('label' => app_lang('fotovoltaico_proposal_consumption_avg'), 'value' => number_format((float) ($summary['consumption_avg'] ?? 0), 2, ',', '.')),
    array('label' => app_lang('total'), 'value' => to_currency((float) ($summary['total'] ?? 0), get_setting('currency_symbol'))),
);
?>
<div id="page-content" class="page-wrapper clearfix">
    <div class="page-title clearfix">
        <h4 class="float-start mb-0"><?php echo app_lang('fotovoltaico_wizard'); ?></h4>
        <div class="title-button-group float-end">
            <?php echo anchor(get_uri('fotovoltaico/proposals'), "<i data-feather='arrow-left' class='icon-16'></i> " . app_lang('back_to_list'), array('class' => 'btn btn-default')); ?>
        </div>
    </div>

    <div class="card mb20">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <div class="small text-off"><?php echo esc($step_title); ?></div>
                    <div class="fw-bold"><?php echo app_lang('fotovoltaico_wizard_progress'); ?></div>
                </div>
                <div class="text-end">
                    <div class="display-6 lh-1 mb-0"><?php echo (int) $progress; ?>%</div>
                    <div class="small text-off"><?php echo app_lang('fotovoltaico_wizard_resume_draft'); ?></div>
                </div>
            </div>
            <div class="progress mt15" style="height: 8px;">
                <div class="progress-bar" role="progressbar" style="width: <?php echo (int) $progress; ?>%;" aria-valuenow="<?php echo (int) $progress; ?>" aria-valuemin="0" aria-valuemax="100"></div>
            </div>

            <ul class="nav nav-pills mt20 flex-wrap gap-2">
                <?php foreach ($steps as $index => $wizard_step) { ?>
                    <?php
                    $is_active = $wizard_step === $step;
                    $is_available = $index <= (int) $step_index;
                    $step_url = get_uri('fotovoltaico/proposal_wizard/step/' . $proposal_id . '/' . $wizard_step);
                    ?>
                    <li class="nav-item">
                        <?php if ($is_available) { ?>
                            <a class="nav-link <?php echo $is_active ? 'active' : ''; ?>" href="<?php echo $step_url; ?>">
                                <span class="badge <?php echo $is_active ? 'bg-light text-dark' : 'bg-secondary'; ?> me-1"><?php echo $index + 1; ?></span>
                                <?php echo esc(get_array_value($step_labels, $wizard_step)); ?>
                            </a>
                        <?php } else { ?>
                            <span class="nav-link disabled">
                                <span class="badge bg-secondary me-1"><?php echo $index + 1; ?></span>
                                <?php echo esc(get_array_value($step_labels, $wizard_step)); ?>
                            </span>
                        <?php } ?>
                    </li>
                <?php } ?>
            </ul>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-8">
            <?php echo form_open($form_action, array('id' => 'proposal-wizard-form', 'class' => 'general-form', 'role' => 'form')); ?>
                <input type="hidden" name="proposal_id" value="<?php echo (int) $proposal_id; ?>" />
                <input type="hidden" name="step" value="<?php echo esc($step); ?>" />

                <div class="card mb20">
                    <div class="card-body">
                        <?php echo view($wizard_step_view, $step_view_data); ?>
                    </div>
                </div>
            <?php echo form_close(); ?>
        </div>

        <div class="col-xl-4">
            <div class="card mb20">
                <div class="card-body">
                    <h5 class="mb15"><?php echo app_lang('fotovoltaico_wizard_summary'); ?></h5>
                    <?php foreach ($summary_items as $item) { ?>
                        <div class="mb10">
                            <strong><?php echo esc($item['label']); ?>:</strong>
                            <div><?php echo is_numeric($item['value']) ? esc($item['value']) : esc((string) $item['value']); ?></div>
                        </div>
                    <?php } ?>
                </div>
            </div>

            <?php if (!empty($current_tariff) && isset($current_tariff->id)) { ?>
                <div class="card mb20">
                    <div class="card-body">
                        <h5 class="mb15"><?php echo app_lang('fotovoltaico_tariffs'); ?></h5>
                        <div class="mb10">
                            <strong><?php echo app_lang('fotovoltaico_distributor_name'); ?>:</strong>
                            <div><?php echo esc($current_tariff->distributor_title ?? '-'); ?></div>
                        </div>
                        <div class="mb10">
                            <strong><?php echo app_lang('fotovoltaico_tariff_modality'); ?>:</strong>
                            <div><?php echo esc($current_tariff->modality ?? '-'); ?></div>
                        </div>
                        <div class="mb10">
                            <strong><?php echo app_lang('fotovoltaico_tariff_subgroup'); ?>:</strong>
                            <div><?php echo esc($current_tariff->subgroup ?? '-'); ?></div>
                        </div>
                        <div class="mb10">
                            <strong><?php echo app_lang('fotovoltaico_valid_from'); ?>:</strong>
                            <div><?php echo esc($current_tariff->valid_from ?? '-'); ?></div>
                        </div>
                        <div class="mb0">
                            <strong><?php echo app_lang('fotovoltaico_valid_to'); ?>:</strong>
                            <div><?php echo esc($current_tariff->valid_to ?: '-'); ?></div>
                        </div>
                    </div>
                </div>
            <?php } ?>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(function () {
        $("#proposal-wizard-form .select2").select2();
        $("#proposal-wizard-form").appForm({
            onSuccess: function (result) {
                if (result && result.redirect_url) {
                    window.location = result.redirect_url;
                    return false;
                }
            }
        });
    });
</script>
