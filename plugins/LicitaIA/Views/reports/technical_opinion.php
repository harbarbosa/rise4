<?php
$opportunity = $opportunity ?? null;
$latest_report = $latest_report ?? null;
?>

<div id="page-content" class="page-wrapper clearfix">
    <div class="card">
        <div class="page-title clearfix">
            <div>
                <h1 class="mb-1"><?php echo app_lang('licitaia_technical_opinion'); ?></h1>
                <div class="text-muted">
                    <?php echo esc($opportunity->title ?? '-'); ?>
                    <?php if (!empty($latest_report->generated_at)) { ?>
                        <span class="ms-2">| <?php echo esc($latest_report->generated_at); ?></span>
                    <?php } ?>
                </div>
            </div>
            <div class="title-button-group">
                <?php echo anchor(get_uri('licitaia/reports/download/' . (int) ($opportunity->id ?? 0)), "<i data-feather='download' class='icon-16'></i> " . app_lang('licitaia_download_pdf'), array('class' => 'btn btn-primary')); ?>
                <?php echo anchor(get_uri('licitaia/opportunities/view/' . (int) ($opportunity->id ?? 0)), "<i data-feather='arrow-left' class='icon-16'></i> " . app_lang('back'), array('class' => 'btn btn-default')); ?>
            </div>
        </div>

        <div class="card-body">
            <?php include PLUGINPATH . 'LicitaIA/Views/reports/technical_opinion_pdf.php'; ?>
        </div>
    </div>
</div>
