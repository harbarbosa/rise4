<div id="page-content" class="page-wrapper clearfix">
    <div class="card">
        <div class="page-title clearfix">
            <h1><?php echo app_lang('organizador_categories'); ?></h1>
            <div class="title-button-group">
                <?php echo anchor(get_uri('organizador/settings'), "<i data-feather='arrow-left' class='icon-16'></i> " . app_lang('organizador_settings'), array('class' => 'btn btn-default')); ?>
                <?php echo modal_anchor(get_uri('organizador/categories/modal_form'), "<i data-feather='plus-circle' class='icon-16'></i> " . app_lang('add'), array('class' => 'btn btn-primary', 'title' => app_lang('organizador_categories'))); ?>
            </div>
        </div>
        <div class="card-body">
            <table id="organizador-categories-table" class="display no-thead b-b-only no-hover" cellspacing="0" width="100%"></table>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function () {
        $("#organizador-categories-table").appTable({
            source: '<?php echo_uri("organizador/categories/list_data") ?>',
            order: [[2, "asc"]],
            columns: [
                {title: "<?php echo app_lang('title'); ?>"},
                {title: "<?php echo app_lang('color'); ?>"},
                {title: "<?php echo app_lang('sort'); ?>"},
                {title: "<?php echo app_lang('options'); ?>", className: "text-center option w100"}
            ]
        });
    });
</script>
