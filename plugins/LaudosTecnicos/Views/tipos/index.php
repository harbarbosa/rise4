<div id="page-content" class="page-wrapper clearfix">
    <div class="card">
        <div class="page-title clearfix">
            <h1><?php echo app_lang('laudos_types_title'); ?></h1>
            <div class="title-button-group">
                <?php echo modal_anchor(get_uri('laudos_tecnicos/tipo_modal_form'), '<i data-feather="plus" class="icon-16"></i> ' . app_lang('laudos_type_add'), array('class' => 'btn btn-primary', 'title' => app_lang('laudos_type_add'))); ?>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="laudos-types-table" class="display" width="100%"></table>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function() {
        $("#laudos-types-table").appTable({
            source: '<?php echo_uri('laudos_tecnicos/tipos_list_data') ?>',
            columns: [
                {title: 'ID', "class": "w50"},
                {title: '<?php echo app_lang('laudos_type_name'); ?>'},
                {title: '<?php echo app_lang('laudos_type_prefix'); ?>', "class": "w80"},
                {title: '<?php echo app_lang('laudos_type_require_inspection'); ?>', "class": "w80"},
                {title: '<?php echo app_lang('laudos_type_require_approval'); ?>', "class": "w80"},
                {title: '<?php echo app_lang('laudos_type_validity_days'); ?>', "class": "w80"},
                {title: '', "class": "w80"}
            ]
        });
    });
</script>