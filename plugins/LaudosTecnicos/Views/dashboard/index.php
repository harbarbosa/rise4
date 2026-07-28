<div id="page-content" class="page-wrapper clearfix">
    <div class="card">
        <div class="page-title clearfix">
            <h1><?php echo app_lang('laudos_dashboard_title'); ?></h1>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3 mb15">
                    <div class="card card-body">
                        <div class="text-muted"><?php echo app_lang('laudos_total'); ?></div>
                        <div class="h4" id="total-laudos"><?php echo $counts['total']; ?></div>
                    </div>
                </div>
                <div class="col-md-3 mb15">
                    <div class="card card-body">
                        <div class="text-muted"><?php echo app_lang('laudos_drafts'); ?></div>
                        <div class="h4 text-secondary" id="drafts-laudos"><?php echo $counts['draft']; ?></div>
                    </div>
                </div>
                <div class="col-md-3 mb15">
                    <div class="card card-body">
                        <div class="text-muted"><?php echo app_lang('laudos_in_progress'); ?></div>
                        <div class="h4 text-primary" id="in-progress-laudos"><?php echo $counts['in_progress']; ?></div>
                    </div>
                </div>
                <div class="col-md-3 mb15">
                    <div class="card card-body">
                        <div class="text-muted"><?php echo app_lang('laudos_pending_review'); ?></div>
                        <div class="h4 text-warning" id="pending-laudos"><?php echo $counts['pending_review']; ?></div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-3 mb15">
                    <div class="card card-body">
                        <div class="text-muted"><?php echo app_lang('laudos_approved'); ?></div>
                        <div class="h4 text-info" id="approved-laudos"><?php echo $counts['approved']; ?></div>
                    </div>
                </div>
                <div class="col-md-3 mb15">
                    <div class="card card-body">
                        <div class="text-muted"><?php echo app_lang('laudos_issued'); ?></div>
                        <div class="h4 text-success" id="issued-laudos"><?php echo $counts['issued']; ?></div>
                    </div>
                </div>
                <div class="col-md-3 mb15">
                    <div class="card card-body">
                        <div class="text-muted"><?php echo app_lang('laudos_expired'); ?></div>
                        <div class="h4 text-danger" id="expired-laudos"><?php echo $counts['expired']; ?></div>
                    </div>
                </div>
                <div class="col-md-3 mb15">
                    <div class="card card-body">
                        <div class="text-muted"><?php echo app_lang('laudos_canceled'); ?></div>
                        <div class="h4 text-dark" id="canceled-laudos"><?php echo $counts['canceled']; ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>