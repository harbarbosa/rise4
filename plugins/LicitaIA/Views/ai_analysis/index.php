<?php
$summary = $summary ?? array();
$recent_opportunities = $recent_opportunities ?? array();
?>

<div id="page-content" class="page-wrapper clearfix">
    <div class="card">
        <div class="page-title clearfix">
            <div>
                <h1><?php echo app_lang('licitaia_ai_status'); ?></h1>
                <div class="text-muted"><?php echo app_lang('licitaia_dashboard_intro'); ?></div>
            </div>
            <div class="title-button-group">
                <a href="<?php echo get_uri('licitaia/opportunities'); ?>" class="btn btn-default"><?php echo app_lang('licitaia_opportunities'); ?></a>
                <?php if ($can_manage_settings ?? false) { ?>
                    <a href="<?php echo get_uri('licitaia/settings'); ?>" class="btn btn-primary"><?php echo app_lang('licitaia_settings'); ?></a>
                <?php } ?>
            </div>
        </div>

        <div class="card-body">
            <div class="row g-3 mb-4">
                <div class="col-lg-3 col-md-6">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="text-muted"><?php echo app_lang('licitaia_total_opportunities'); ?></div>
                            <div class="font-26 fw-bold"><?php echo (int) get_array_value($summary, 'total', 0); ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="text-muted"><?php echo app_lang('licitaia_under_analysis'); ?></div>
                            <div class="font-26 fw-bold"><?php echo (int) get_array_value($summary, 'analyzing', 0); ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="text-muted"><?php echo app_lang('licitaia_participate'); ?></div>
                            <div class="font-26 fw-bold"><?php echo (int) get_array_value($summary, 'participate', 0); ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="text-muted"><?php echo app_lang('licitaia_sources'); ?></div>
                            <div class="font-26 fw-bold"><?php echo (int) get_array_value($summary, 'sources', 0); ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h4 class="mb-0"><?php echo app_lang('licitaia_ai_provider'); ?></h4>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-0"><?php echo app_lang('licitaia_empty_state'); ?> <?php echo app_lang('licitaia_ai_enabled'); ?></p>
                </div>
            </div>

            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0"><?php echo app_lang('licitaia_recent_opportunities'); ?></h4>
                    <a href="<?php echo get_uri('licitaia/opportunities'); ?>" class="btn btn-sm btn-default"><?php echo app_lang('view'); ?></a>
                </div>
                <div class="table-responsive">
                    <table class="table table-striped mb-0">
                        <thead>
                            <tr>
                                <th><?php echo app_lang('licitaia_opportunity'); ?></th>
                                <th><?php echo app_lang('licitaia_status'); ?></th>
                                <th><?php echo app_lang('licitaia_ai_status'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($recent_opportunities) { ?>
                                <?php foreach ($recent_opportunities as $opportunity) { ?>
                                    <tr>
                                        <td><?php echo esc($opportunity->title ?: '-'); ?></td>
                                        <td><?php echo esc(app_lang('licitaia_status_' . $opportunity->status)); ?></td>
                                        <td><?php echo esc(app_lang('licitaia_ai_' . $opportunity->ai_status)); ?></td>
                                    </tr>
                                <?php } ?>
                            <?php } else { ?>
                                <tr>
                                    <td colspan="3" class="text-center text-muted"><?php echo app_lang('licitaia_empty_state'); ?></td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
