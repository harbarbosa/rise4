<div class="card">
    <div class="page-title clearfix">
        <h1><?php echo app_lang('error_logs'); ?></h1>
    </div>

    <div class="p20">
        <form method="get" action="<?php echo get_uri('settings/error_logs'); ?>" class="row align-items-end mb20">
            <div class="col-md-4">
                <label for="error-log-file" class="form-label"><?php echo app_lang('log_file'); ?></label>
                <select id="error-log-file" name="file" class="form-control">
                    <?php foreach ($log_files as $log_file) { ?>
                        <option value="<?php echo esc($log_file['name']); ?>" <?php echo $selected_file === $log_file['name'] ? 'selected' : ''; ?>><?php echo esc($log_file['name']); ?></option>
                    <?php } ?>
                </select>
            </div>
            <div class="col-md-5">
                <label for="error-log-search" class="form-label"><?php echo app_lang('search'); ?></label>
                <input id="error-log-search" type="text" name="search" value="<?php echo esc($search); ?>" class="form-control" placeholder="ERROR, CRITICAL, proposal...">
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary w-100"><i data-feather="search" class="icon-16"></i> <?php echo app_lang('view'); ?></button>
            </div>
        </form>

        <?php if (!$log_files) { ?>
            <div class="alert alert-info mb0"><?php echo app_lang('no_error_logs'); ?></div>
        <?php } else if (!$selected_path) { ?>
            <div class="alert alert-warning mb0"><?php echo app_lang('log_file_not_found'); ?></div>
        <?php } else { ?>
            <div class="d-flex justify-content-between align-items-center mb10">
                <strong><?php echo esc($selected_file); ?></strong>
                <span class="text-muted"><?php echo count($log_content); ?> <?php echo app_lang('log_lines_displayed'); ?></span>
            </div>
            <pre class="border rounded p15 mb0" style="max-height: 620px; overflow: auto; white-space: pre-wrap; word-break: break-word;"><?php echo esc(implode("\n", $log_content)); ?></pre>
        <?php } ?>
    </div>
</div>
