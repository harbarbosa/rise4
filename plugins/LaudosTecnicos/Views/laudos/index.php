<div id="page-content" class="page-wrapper clearfix">
    <div class="card">
        <div class="page-title clearfix">
            <h1><?php echo app_lang('laudos_list'); ?></h1>
            <div class="title-button-group">
                <?php if ($can_create) { ?>
                    <?php echo modal_anchor(get_uri('laudos_tecnicos/modal_form'), '<i data-feather="plus" class="icon-16"></i> ' . app_lang('laudos_add'), array('class' => 'btn btn-primary', 'title' => app_lang('laudos_add'))); ?>
                <?php } ?>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="laudos-table" class="display" width="100%"></table>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function() {
        $("#laudos-table").appTable({
            source: '<?php echo_uri('laudos_tecnicos/list_data') ?>',
            columns: [
                {title: '<?php echo app_lang('laudos_id'); ?>', "class": "w50"},
                {title: '<?php echo app_lang('laudos_title'); ?>'},
                {title: '<?php echo app_lang('laudos_type'); ?>'},
                {title: '<?php echo app_lang('laudos_category'); ?>'},
                {title: '<?php echo app_lang('client'); ?>'},
                {title: '<?php echo app_lang('laudos_status'); ?>', "class": "w100"},
                {title: '<?php echo app_lang('laudos_created_at'); ?>', "class": "w120"},
                {title: '', "class": "w100"}
            ],
            order: [[6, 'desc']]
        });
    });
</script>