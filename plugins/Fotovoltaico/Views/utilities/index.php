<div class="page-content clearfix">
    <div class="row">
        <div class="col-md-12">
            <div class="page-title clearfix">
                <h1><?php echo app_lang('fv_utilities_tariffs'); ?></h1>
                <div class="title-button-group">
                    <?php echo modal_anchor(get_uri('fotovoltaico/utilities_modal_form'), "<i data-feather='plus-circle' class='icon-16'></i> " . app_lang('add'), array('class' => 'btn btn-default', 'title' => app_lang('add'))); ?>
                </div>
            </div>

            <div class="card">
                <div class="table-responsive">
                    <table id="fv-utilities-table" class="display" cellspacing="0" width="100%"></table>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function () {
        $("#fv-utilities-table").appTable({
            source: '<?php echo_uri("fotovoltaico/utilities_list_data"); ?>',
            columns: [
                {title: '<?php echo app_lang("title"); ?>'},
                {title: '<?php echo app_lang("state"); ?>'},
                {title: '<?php echo app_lang("code"); ?>'},
                {title: '<?php echo app_lang("actions"); ?>', "class": "text-center option w180"}
            ]
        });
    });
</script>
