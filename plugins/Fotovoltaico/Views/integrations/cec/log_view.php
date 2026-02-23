<div class="page-content clearfix">
    <div class="row">
        <div class="col-md-12">
            <div class="page-title clearfix">
                <h1><?php echo app_lang('fv_log_details'); ?></h1>
                <div class="title-button-group">
                    <a href="<?php echo get_uri('fotovoltaico/integrations/cec/logs'); ?>" class="btn btn-default">
                        <i data-feather="arrow-left"></i> <?php echo app_lang('back'); ?>
                    </a>
                </div>
            </div>

            <div class="card p20">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong><?php echo app_lang('fv_log_status'); ?>:</strong> <?php echo $row->status; ?></p>
                        <p><strong><?php echo app_lang('fv_log_started'); ?>:</strong> <?php echo $row->started_at; ?></p>
                        <p><strong><?php echo app_lang('fv_log_finished'); ?>:</strong> <?php echo $row->finished_at; ?></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong><?php echo app_lang('fv_log_run_id'); ?>:</strong> <?php echo $row->run_id; ?></p>
                        <p><strong><?php echo app_lang('fv_log_error'); ?>:</strong> <?php echo esc($row->error_message); ?></p>
                    </div>
                </div>

                <hr />

                <h4><?php echo app_lang('fv_log_summary'); ?></h4>
                <pre style="white-space: pre-wrap;"><?php echo esc(json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); ?></pre>
            </div>
        </div>
    </div>
</div>
