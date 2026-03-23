<div id="page-content" class="page-wrapper clearfix">
    <div class="row">
        <div class="col-sm-3 col-lg-2">
            <?php
            $tab_view['active_tab'] = "projectanalizer";
            echo view("settings/tabs", $tab_view);
            ?>
        </div>

        <div class="col-sm-9 col-lg-10">
            <div class="card">
                <div class="card-header">
                    <h4><?php echo app_lang("pa_error_logs"); ?></h4>
                    <div class="title-button-group">
                        <?php echo anchor(get_uri("projectanalizer_settings"), app_lang("back"), array("class" => "btn btn-default")); ?>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <label><?php echo app_lang("pa_log_files"); ?></label>
                            <div class="list-group">
                                <?php if (!empty($files)) { ?>
                                    <?php foreach ($files as $file) { ?>
                                        <?php
                                        $is_active = $selected === $file["name"];
                                        $url = get_uri("projectanalizer_settings/logs?file=" . urlencode($file["name"]));
                                        ?>
                                        <a href="<?php echo $url; ?>" class="list-group-item list-group-item-action<?php echo $is_active ? " active" : ""; ?>">
                                            <?php echo esc($file["name"]); ?>
                                            <div class="text-muted small"><?php echo number_format($file["size"] / 1024, 1, ",", "."); ?> KB</div>
                                        </a>
                                    <?php } ?>
                                <?php } else { ?>
                                    <div class="text-muted"><?php echo app_lang("pa_no_logs"); ?></div>
                                <?php } ?>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <label><?php echo app_lang("pa_log_content"); ?></label>
                            <div class="p-3" style="background:#0b1021; color:#dfe7ff; height:520px; overflow:auto; font-family: monospace; font-size: 12px;">
                                <?php
                                $raw = (string)$content;
                                $lines = preg_split("/\r\n|\n|\r/", $raw);
                                $entries = [];
                                $current = [];
                                foreach ($lines as $line) {
                                    if (preg_match('/^(CRITICAL|ERROR|WARNING|NOTICE|INFO|DEBUG)\s+-\s+\\d{4}-\\d{2}-\\d{2}/', $line)) {
                                        if (!empty($current)) {
                                            $entries[] = implode("\n", $current);
                                            $current = [];
                                        }
                                    }
                                    $current[] = $line;
                                }
                                if (!empty($current)) {
                                    $entries[] = implode("\n", $current);
                                }
                                if (empty($entries)) {
                                    $entries = [$raw];
                                }
                                ?>
                                <?php foreach ($entries as $entry) { ?>
                                    <div style="padding:10px; margin-bottom:10px; border:1px solid #1f2a44; border-radius:6px; background:#0f172a;">
                                        <div style="white-space:pre-wrap;"><?php echo esc(trim($entry)); ?></div>
                                    </div>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
