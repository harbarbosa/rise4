<div class="page-content clearfix">
    <div class="row">
        <div class="col-md-12">
            <div class="page-title clearfix">
                <h1><?php echo app_lang('fv_tariffs'); ?></h1>
                <div class="title-button-group">
                    <?php echo modal_anchor(get_uri('fotovoltaico/tariffs_modal_form'), "<i data-feather='plus-circle' class='icon-16'></i> " . app_lang('add'), array('class' => 'btn btn-default', 'title' => app_lang('add'), 'data-post-utility_id' => $utility_id)); ?>
                </div>
            </div>

            <div class="card">
                <div class="table-responsive">
                    <table id="fv-tariffs-table" class="display" cellspacing="0" width="100%"></table>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function () {
        $("#fv-tariffs-table").appTable({
            source: '<?php echo_uri("fotovoltaico/tariffs_list_data/" . $utility_id); ?>',
            columns: [
                {title: '<?php echo app_lang("group_type"); ?>'},
                {title: '<?php echo app_lang("modality"); ?>'},
                {title: '<?php echo app_lang("fv_tariff_te"); ?>'},
                {title: '<?php echo app_lang("fv_tariff_tusd"); ?>'},
                {title: '<?php echo app_lang("fv_tariff_flags"); ?>'},
                {title: '<?php echo app_lang("fv_tariff_total"); ?>'},
                {title: '<?php echo app_lang("valid_from"); ?>'},
                {title: '<?php echo app_lang("valid_to"); ?>'},
                {title: '<?php echo app_lang("actions"); ?>', "class": "text-center option w150"}
            ]
        });
    });
</script>
