<div class="page-content clearfix">
    <div class="row">
        <div class="col-md-12">
            <div class="page-title clearfix">
                <h1><?php echo app_lang('fv_regulatory_profiles'); ?></h1>
                <div class="title-button-group">
                    <?php echo modal_anchor(get_uri('fotovoltaico/regulatory_modal_form'), "<i data-feather='plus-circle' class='icon-16'></i> " . app_lang('add'), array('class' => 'btn btn-default', 'title' => app_lang('add'))); ?>
                </div>
            </div>

            <div class="card">
                <div class="table-responsive">
                    <table id="fv-regulatory-table" class="display" cellspacing="0" width="100%"></table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    $(function () {
        $("#fv-regulatory-table").appTable({
            source: "<?php echo_uri('fotovoltaico/regulatory_list_data'); ?>",
            columns: [
                {title: "ID"},
                {title: "<?php echo app_lang('name'); ?>"},
                {title: "<?php echo app_lang('description'); ?>"},
                {title: "<?php echo app_lang('active'); ?>"},
                {title: "<?php echo app_lang('actions'); ?>", "class": "text-center option w150"}
            ]
        });
    });
</script>
