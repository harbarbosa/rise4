<?php
$comparison_rows = get_array_value($comparison, 'rows') ?: array();
$from_version = get_array_value($comparison, 'from');
$to_version = get_array_value($comparison, 'to');
?>

<div class="card mb20">
    <div class="card-body">
        <h5 class="mb15"><?php echo app_lang('fotovoltaico_proposal_compare_versions'); ?></h5>
        <?php if (!$proposal_id || !count($versions)) { ?>
            <div class="text-off"><?php echo app_lang('no_records_found'); ?></div>
        <?php } else { ?>
            <?php echo form_open(get_uri('fotovoltaico/proposals/view/' . $proposal_id), array('method' => 'get', 'class' => 'general-form')); ?>
                <div class="row">
                    <div class="col-md-5">
                        <div class="form-group mb15">
                            <label class="form-label"><?php echo app_lang('fotovoltaico_compare'); ?></label>
                            <?php
                            $version_options = array('' => '-');
                            foreach ($versions as $version) {
                                $version_options[$version->version_number] = app_lang('fotovoltaico_version') . ' ' . $version->version_number;
                            }
                            echo form_dropdown('compare_from', $version_options, $selected_compare_from, 'class="form-select"');
                            ?>
                        </div>
                    </div>
                    <div class="col-md-5">
                        <div class="form-group mb15">
                            <label class="form-label">&nbsp;</label>
                            <?php echo form_dropdown('compare_to', $version_options, $selected_compare_to, 'class="form-select"'); ?>
                        </div>
                    </div>
                    <div class="col-md-2 d-flex align-items-end mb15">
                        <button type="submit" class="btn btn-default w100p"><?php echo app_lang('fotovoltaico_compare'); ?></button>
                    </div>
                </div>
            <?php echo form_close(); ?>

            <?php if (count($comparison_rows)) { ?>
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th><?php echo app_lang('fotovoltaico_field'); ?></th>
                                <th><?php echo $from_version ? app_lang('fotovoltaico_before') . ' ' . (int) $from_version->version_number : app_lang('fotovoltaico_before'); ?></th>
                                <th><?php echo $to_version ? app_lang('fotovoltaico_after') . ' ' . (int) $to_version->version_number : app_lang('fotovoltaico_after'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($comparison_rows as $row) { ?>
                                <tr class="<?php echo $row['changed'] ? 'table-warning' : ''; ?>">
                                    <td><?php echo esc($row['label']); ?></td>
                                    <td><?php echo esc($row['left']); ?></td>
                                    <td><?php echo esc($row['right']); ?></td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            <?php } else { ?>
                <div class="text-off"><?php echo app_lang('no_records_found'); ?></div>
            <?php } ?>
        <?php } ?>
    </div>
</div>
