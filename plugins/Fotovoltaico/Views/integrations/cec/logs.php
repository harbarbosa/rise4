<div class="page-content clearfix">
    <div class="row">
        <div class="col-md-12">
            <div class="page-title clearfix">
                <h1><?php echo app_lang('fv_integrations_logs'); ?></h1>
                <div class="title-button-group">
                    <a href="<?php echo get_uri('fotovoltaico/integrations/cec'); ?>" class="btn btn-default">
                        <i data-feather="arrow-left"></i> <?php echo app_lang('back'); ?>
                    </a>
                </div>
            </div>

            <div class="card p20">
                <div class="table-responsive">
                    <table class="display table" cellspacing="0" width="100%">
                        <thead>
                        <tr>
                            <th><?php echo app_lang('id'); ?></th>
                            <th><?php echo app_lang('fv_log_status'); ?></th>
                            <th><?php echo app_lang('fv_log_started'); ?></th>
                            <th><?php echo app_lang('fv_log_finished'); ?></th>
                            <th><?php echo app_lang('fv_log_actions'); ?></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($rows as $row) { ?>
                            <tr>
                                <td><?php echo $row->id; ?></td>
                                <td><?php echo $row->status; ?></td>
                                <td><?php echo $row->started_at; ?></td>
                                <td><?php echo $row->finished_at; ?></td>
                                <td>
                                    <a href="<?php echo get_uri('fotovoltaico/integrations/cec/log_view/' . $row->id); ?>" class="btn btn-default btn-sm">
                                        <i data-feather="eye"></i> <?php echo app_lang('view'); ?>
                                    </a>
                                </td>
                            </tr>
                        <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
