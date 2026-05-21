<div id="page-content" class="page-wrapper clearfix">
    <div class="page-title clearfix">
        <h4 class="float-start mb-0">
            <?php echo $is_new ? app_lang('fotovoltaico_add_proposal') : esc($proposal->title ?: $proposal->proposal_code); ?>
        </h4>
        <div class="title-button-group float-end">
            <?php echo anchor(get_uri('fotovoltaico/proposals'), "<i data-feather='arrow-left' class='icon-16'></i> " . app_lang('back_to_list'), array('class' => 'btn btn-default')); ?>
            <?php if (!$is_new && ($can_manage_proposals || $can_create_proposals) && in_array($proposal->status ?: 'draft', array('draft', 'in_progress'), true)) { ?>
                <?php
                $edit_step = trim((string) ($proposal->wizard_step ?: ''));
                if ($edit_step === '') {
                    $edit_step = 'client';
                }
                ?>
                <?php echo anchor(get_uri('fotovoltaico/proposal_wizard/step/' . (int) $proposal->id . '/' . $edit_step), "<i data-feather='edit' class='icon-16'></i> " . app_lang('edit'), array('class' => 'btn btn-default')); ?>
            <?php } ?>
            <?php if (!$is_new && $can_generate_pdf) { ?>
                <?php echo form_open(get_uri('fotovoltaico/proposals/generate_pdf/' . (int) $proposal->id . '/' . (int) $current_version_id), array('class' => 'd-inline', 'target' => '_blank')); ?>
                    <input type="hidden" name="mode" value="download" />
                    <button type="submit" class="btn btn-default">
                        <i data-feather='file-text' class='icon-16'></i> <?php echo app_lang('download_pdf'); ?>
                    </button>
                <?php echo form_close(); ?>
            <?php } ?>
            <?php if (!$is_new && ($can_create_proposals || $can_manage_proposals)) { ?>
                <?php echo form_open(get_uri('fotovoltaico/proposals/duplicate_version'), array('class' => 'd-inline')); ?>
                    <input type="hidden" name="id" value="<?php echo (int) $proposal->id; ?>" />
                    <button type="submit" class="btn btn-default">
                        <i data-feather='copy' class='icon-16'></i> <?php echo app_lang('fotovoltaico_proposal_duplicate_version'); ?>
                    </button>
                <?php echo form_close(); ?>
            <?php } ?>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-8">
            <?php echo form_open(get_uri('fotovoltaico/proposals/save'), array('id' => 'proposal-form', 'class' => 'general-form', 'role' => 'form')); ?>
                <input type="hidden" name="id" value="<?php echo (int) $proposal->id; ?>" />
                <input type="hidden" name="currency" value="<?php echo esc($proposal->currency ?: get_setting('default_currency')); ?>" />

                <div class="card mb20">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-8">
                                <div class="form-group mb15">
                                    <label for="title" class="form-label"><?php echo app_lang('fotovoltaico_proposal_title'); ?></label>
                                    <?php echo form_input(array(
                                        'id' => 'title',
                                        'name' => 'title',
                                        'value' => $proposal->title,
                                        'class' => 'form-control',
                                        'placeholder' => app_lang('fotovoltaico_proposal_title'),
                                        'disabled' => !$can_manage_proposals && !$can_create_proposals ? true : false
                                    )); ?>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group mb15">
                                    <label for="status" class="form-label"><?php echo app_lang('status'); ?></label>
                                    <?php echo form_dropdown('status', $status_options, $proposal->status ?: 'draft', 'class="form-select" ' . (($can_manage_proposals || $can_approve_proposals || $can_create_proposals) ? '' : 'disabled')); ?>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group mb15">
                                    <label for="client_id" class="form-label"><?php echo app_lang('fotovoltaico_proposal_client'); ?></label>
                                    <?php echo form_dropdown('client_id', $client_options, $proposal->client_id ?: '', 'class="form-select" ' . (($proposal->contact_id || $proposal->lead_id) ? 'disabled' : '')); ?>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group mb15">
                                    <label for="lead_id" class="form-label"><?php echo app_lang('fotovoltaico_proposal_lead'); ?></label>
                                    <?php echo form_dropdown('lead_id', $lead_options, $proposal->lead_id ?: '', 'class="form-select" ' . (($proposal->contact_id || $proposal->client_id) ? 'disabled' : '')); ?>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group mb15">
                                    <label for="contact_id" class="form-label"><?php echo app_lang('fotovoltaico_proposal_contact'); ?></label>
                                    <?php echo form_dropdown('contact_id', $contact_options, $proposal->contact_id ?: '', 'class="form-select" ' . (($proposal->lead_id || $proposal->client_id) ? 'disabled' : '')); ?>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-5">
                                <div class="form-group mb15">
                                    <label for="consumer_unit" class="form-label"><?php echo app_lang('fotovoltaico_proposal_consumer_unit'); ?></label>
                                    <?php echo form_input(array(
                                        'id' => 'consumer_unit',
                                        'name' => 'consumer_unit',
                                        'value' => $proposal->consumer_unit,
                                        'class' => 'form-control',
                                        'placeholder' => app_lang('fotovoltaico_proposal_consumer_unit'),
                                        'disabled' => !$can_manage_proposals && !$can_create_proposals ? true : false
                                    )); ?>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group mb15">
                                    <label for="distributor_id" class="form-label"><?php echo app_lang('fotovoltaico_proposal_distributor'); ?></label>
                                    <?php echo form_dropdown('distributor_id', $distributor_options, $proposal->distributor_id ?: '', 'class="form-select" ' . (!$can_manage_proposals && !$can_create_proposals ? 'disabled' : '')); ?>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group mb15">
                                    <label for="consumption_avg" class="form-label"><?php echo app_lang('fotovoltaico_proposal_consumption_avg'); ?></label>
                                    <?php echo form_input(array(
                                        'id' => 'consumption_avg',
                                        'name' => 'consumption_avg',
                                        'value' => $proposal->consumption_avg,
                                        'class' => 'form-control text-end',
                                        'placeholder' => app_lang('fotovoltaico_proposal_consumption_avg'),
                                        'disabled' => !$can_manage_proposals && !$can_create_proposals ? true : false
                                    )); ?>
                                </div>
                            </div>
                        </div>

                        <div class="form-group mb15">
                            <label for="notes" class="form-label"><?php echo app_lang('notes'); ?></label>
                            <?php echo form_textarea(array(
                                'id' => 'notes',
                                'name' => 'notes',
                                'value' => $proposal->notes,
                                'class' => 'form-control',
                                'rows' => 4,
                                'disabled' => !$can_manage_proposals && !$can_create_proposals ? true : false
                            )); ?>
                        </div>

                        <div class="form-group mb0">
                            <button type="submit" class="btn btn-primary" <?php echo (!$can_manage_proposals && !$can_create_proposals) ? 'disabled' : ''; ?>>
                                <i data-feather='save' class='icon-16'></i> <?php echo app_lang('save'); ?>
                            </button>
                        </div>
                    </div>
                </div>
            <?php echo form_close(); ?>

            <div class="card mb20">
                <div class="card-body">
                    <ul class="nav nav-tabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" data-bs-toggle="tab" href="#proposal-timeline" role="tab"><?php echo app_lang('fotovoltaico_proposal_timeline'); ?></a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#proposal-compare" role="tab"><?php echo app_lang('fotovoltaico_proposal_compare_versions'); ?></a>
                        </li>
                    </ul>
                    <div class="tab-content pt20">
                        <div class="tab-pane fade show active" id="proposal-timeline" role="tabpanel">
                            <?php echo view('Fotovoltaico\\Views\\proposals\\timeline', array(
                                'proposal' => $proposal,
                                'versions' => $versions,
                                'proposal_id' => $proposal_id,
                            )); ?>
                        </div>
                        <div class="tab-pane fade" id="proposal-compare" role="tabpanel">
                            <?php echo view('Fotovoltaico\\Views\\proposals\\compare', array(
                                'proposal' => $proposal,
                                'versions' => $versions,
                                'comparison' => $comparison,
                                'proposal_id' => $proposal_id,
                                'selected_compare_from' => $selected_compare_from,
                                'selected_compare_to' => $selected_compare_to,
                            )); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card mb20">
                <div class="card-body">
                    <h5 class="mb15"><?php echo app_lang('fotovoltaico_proposal_details'); ?></h5>
                    <div class="mb10">
                        <strong><?php echo app_lang('fotovoltaico_proposal_code'); ?>:</strong>
                        <div><?php echo esc($proposal->proposal_code ?: '-'); ?></div>
                    </div>
                    <div class="mb10">
                        <strong><?php echo app_lang('fotovoltaico_proposal_current_version'); ?>:</strong>
                        <div><?php echo (int) ($proposal->current_version ?: 1); ?></div>
                    </div>
                    <div class="mb10">
                        <strong><?php echo app_lang('status'); ?>:</strong>
                        <div><?php echo fotovoltaico_proposal_status_badge($proposal->status ?: 'draft'); ?></div>
                    </div>
                    <div class="mb10">
                        <strong><?php echo app_lang('fotovoltaico_proposal_crm'); ?>:</strong>
                        <div><?php echo esc($summary['crm_reference'] ?? '-'); ?></div>
                    </div>
                    <div class="mb10">
                        <strong><?php echo app_lang('fotovoltaico_proposal_distributor'); ?>:</strong>
                        <div><?php echo esc($summary['distributor'] ?? '-'); ?></div>
                    </div>
                    <div class="mb10">
                        <strong><?php echo app_lang('fotovoltaico_proposal_consumer_unit'); ?>:</strong>
                        <div><?php echo esc($summary['consumer_unit'] ?? '-'); ?></div>
                    </div>
                    <div class="mb10">
                        <strong><?php echo app_lang('fotovoltaico_proposal_consumption_avg'); ?>:</strong>
                        <div><?php echo number_format((float) ($summary['consumption_avg'] ?? 0), 2, ',', '.'); ?></div>
                    </div>
                    <div class="mb10">
                        <strong><?php echo app_lang('total'); ?>:</strong>
                        <div><?php echo to_currency((float) ($summary['total'] ?? 0), get_setting('currency_symbol')); ?></div>
                    </div>
                </div>
            </div>

            <div class="card mb20">
                <div class="card-body">
                    <h5 class="mb15"><?php echo app_lang('fotovoltaico_proposal_change_status'); ?></h5>
                    <?php echo form_open(get_uri('fotovoltaico/proposals/change_status'), array('class' => 'general-form', 'role' => 'form')); ?>
                        <input type="hidden" name="id" value="<?php echo (int) $proposal->id; ?>" />
                        <div class="form-group mb15">
                            <?php echo form_dropdown('status', $status_options, $proposal->status ?: 'draft', 'class="form-select" ' . (!$can_manage_proposals && !$can_approve_proposals ? 'disabled' : '')); ?>
                        </div>
                        <button type="submit" class="btn btn-default w100p" <?php echo (!$can_manage_proposals && !$can_approve_proposals) ? 'disabled' : ''; ?>>
                            <i data-feather='shuffle' class='icon-16'></i> <?php echo app_lang('save'); ?>
                        </button>
                    <?php echo form_close(); ?>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <h5 class="mb15"><?php echo app_lang('fotovoltaico_proposal_versions'); ?></h5>
                    <?php if (count($versions)) { ?>
                        <div class="list-group">
                            <?php foreach ($versions as $version) { ?>
                                <div class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong><?php echo app_lang('fotovoltaico_version') . ' ' . (int) $version->version_number; ?></strong>
                                            <div class="small text-off"><?php echo esc($version->created_by_name ?: '-'); ?></div>
                                        </div>
                                        <div><?php echo fotovoltaico_proposal_status_badge($version->status ?: 'draft'); ?></div>
                                    </div>
                                    <div class="small mt10">
                                        <?php echo format_to_relative_time($version->created_at); ?>
                                    </div>
                                    <div class="small mt10">
                                        <?php echo to_currency((float) $version->total, get_setting('currency_symbol')); ?>
                                    </div>
                                    <div class="mt10">
                                        <a href="<?php echo get_uri('fotovoltaico/proposals/view/' . $proposal_id . '?compare_from=' . ((int) $version->version_number > 1 ? ((int) $version->version_number - 1) : 1) . '&compare_to=' . (int) $version->version_number); ?>" class="btn btn-default btn-sm">
                                            <?php echo app_lang('fotovoltaico_proposal_compare_versions'); ?>
                                        </a>
                                    </div>
                                </div>
                            <?php } ?>
                        </div>
                    <?php } else { ?>
                        <div class="text-off"><?php echo app_lang('no_records_found'); ?></div>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if ($is_new && ($can_manage_proposals || $can_create_proposals)) { ?>
<script type="text/javascript">
    $(function () {
        $("#title").focus();
    });
</script>
<?php } ?>
