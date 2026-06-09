<?php
$adjustment = $adjustment ?? (object) array();
?>

<div id="page-content" class="page-wrapper clearfix">
    <div class="card">
        <div class="page-title clearfix">
            <h1><?php echo app_lang('pontorh_adjustment_details'); ?></h1>
            <div class="title-button-group">
                <a href="<?php echo get_uri('pontorh/ajustes'); ?>" class="btn btn-default"><?php echo app_lang('back'); ?></a>
            </div>
        </div>

        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="text-muted"><?php echo app_lang('pontorh_employee'); ?></div>
                            <div class="font-18 fw-bold"><?php echo esc($adjustment->team_member_name ?: '-'); ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="text-muted"><?php echo app_lang('pontorh_work_date'); ?></div>
                            <div class="font-18 fw-bold"><?php echo esc($adjustment->adjustment_date ?: '-'); ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="text-muted"><?php echo app_lang('pontorh_adjustment_time'); ?></div>
                                <div class="font-18 fw-bold"><?php echo esc($adjustment->adjustment_time ? pontorh_extract_time($adjustment->adjustment_time) : '-'); ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="text-muted"><?php echo app_lang('pontorh_type'); ?></div>
                            <div class="font-18 fw-bold"><?php echo esc(pontorh_adjustment_type_label($adjustment->adjustment_type ?? '')); ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="text-muted"><?php echo app_lang('pontorh_status'); ?></div>
                            <div class="font-18 fw-bold"><span class="badge bg-secondary"><?php echo esc(pontorh_adjustment_status_label($adjustment->status ?? '')); ?></span></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="text-muted"><?php echo app_lang('created_by'); ?></div>
                            <div class="font-18 fw-bold"><?php echo esc($adjustment->creator_name ?: '-'); ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="text-muted"><?php echo app_lang('pontorh_adjustment_justification'); ?></div>
                            <div class="mt-2"><?php echo nl2br(esc($adjustment->reason ?: '-')); ?></div>
                        </div>
                    </div>
                </div>
                <?php if (!empty($adjustment->reviewed_by) || !empty($adjustment->reviewed_at)) { ?>
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-body">
                                <div class="text-muted"><?php echo app_lang('pontorh_adjustment_review'); ?></div>
                                <div class="font-18 fw-bold"><?php echo esc($adjustment->reviewer_name ?: '-'); ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-body">
                                <div class="text-muted"><?php echo app_lang('updated_at'); ?></div>
                                <div class="font-18 fw-bold"><?php echo esc($adjustment->reviewed_at ? format_to_datetime($adjustment->reviewed_at) : '-'); ?></div>
                            </div>
                        </div>
                    </div>
                <?php } ?>
            </div>
        </div>
    </div>
</div>
