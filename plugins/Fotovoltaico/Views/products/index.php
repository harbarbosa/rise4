<div class="page-content clearfix">
    <div class="row">
        <div class="col-md-12">
            <div class="page-title clearfix">
                <h1><?php echo app_lang('fv_products'); ?></h1>
                <div class="title-button-group">
                    <?php echo modal_anchor(get_uri('fotovoltaico/products/import_modal'), "<i data-feather='upload' class='icon-16'></i> " . app_lang('fv_import_csv'), array('class' => 'btn btn-default', 'title' => app_lang('fv_import_csv'))); ?>
                    <?php echo modal_anchor(get_uri('fotovoltaico/products_modal_form'), "<i data-feather='plus-circle' class='icon-16'></i> " . app_lang('add'), array('class' => 'btn btn-default', 'title' => app_lang('add'))); ?>
                </div>
            </div>

            <div class="card">
                <div class="table-responsive">
                    <table id="fv-products-table" class="display" cellspacing="0" width="100%"></table>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function () {
        var filterDropdowns = [
            {name: "type", class: "w200", options: <?php echo json_encode($types_dropdown); ?>},
            {name: "brand", class: "w200", options: <?php echo json_encode($brands_dropdown); ?>},
            {name: "is_active", class: "w150", options: {"": "-", "1": "<?php echo app_lang('yes'); ?>", "0": "<?php echo app_lang('no'); ?>"}}
        ];

        $("#fv-products-table").appTable({
            source: '<?php echo_uri("fotovoltaico/products_list_data"); ?>',
            filterDropdown: filterDropdowns,
            columns: [
                {title: '<?php echo app_lang("active"); ?>'},
                {title: '<?php echo app_lang("type"); ?>'},
                {title: '<?php echo app_lang("brand"); ?>'},
                {title: '<?php echo app_lang("model"); ?>'},
                {title: '<?php echo app_lang("power_w"); ?>'},
                {title: '<?php echo app_lang("cost"); ?>'},
                {title: '<?php echo app_lang("price"); ?>'},
                {title: '<?php echo app_lang("warranty"); ?>'},
                {title: '<?php echo app_lang("actions"); ?>', "class": "text-center option w200"}
            ]
        });

        $("body").on("click", ".js-toggle-active", function () {
            var $btn = $(this);
            $.ajax({
                url: "<?php echo get_uri('fotovoltaico/products_toggle_active'); ?>",
                type: "POST",
                dataType: "json",
                data: {id: $btn.data("id"), is_active: $btn.data("active")}
            }).done(function () {
                $("#fv-products-table").appTable({newData: null});
            });
        });
    });
</script>
