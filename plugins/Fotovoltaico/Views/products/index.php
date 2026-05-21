<div id="page-content" class="page-wrapper clearfix">
    <div class="card">
        <div class="page-title clearfix">
            <h4 class="float-start mb-0"><?php echo app_lang('fotovoltaico_products'); ?></h4>
            <div class="title-button-group float-end">
                <?php if ($can_manage_products) { ?>
                    <?php echo anchor(get_uri('fotovoltaico/product_categories'), "<i data-feather='layers' class='icon-16'></i> " . app_lang('fotovoltaico_product_categories'), array('class' => 'btn btn-default')); ?>
                    <?php echo modal_anchor(get_uri('fotovoltaico/products/modal_form'), "<i data-feather='plus-circle' class='icon-16'></i> " . app_lang('fotovoltaico_add_product'), array('class' => 'btn btn-default', 'title' => app_lang('fotovoltaico_add_product'))); ?>
                <?php } ?>
            </div>
        </div>

        <div class="table-responsive">
            <table id="products-table" class="display" cellspacing="0" width="100%"></table>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function () {
        $("#products-table").appTable({
            source: '<?php echo_uri("fotovoltaico/products/list_data") ?>',
            order: [[0, 'asc']],
            filterDropdown: [
                {name: "category_id", class: "w200", options: <?php echo $categories_dropdown; ?>},
                {name: "product_type", class: "w200", options: <?php echo $product_types_dropdown; ?>},
                {name: "active", class: "w150", options: <?php echo $status_dropdown; ?>}
            ],
            columns: [
                {title: "<?php echo app_lang('fotovoltaico_product_name') ?>", "class": "w20p"},
                {title: "<?php echo app_lang('fotovoltaico_product_category') ?>", "class": "w120"},
                {title: "<?php echo app_lang('fotovoltaico_product_type') ?>", "class": "w120"},
                {title: "<?php echo app_lang('fotovoltaico_product_sku') ?>", "class": "w120"},
                {title: "<?php echo app_lang('fotovoltaico_product_brand') ?>", "class": "w120"},
                {title: "<?php echo app_lang('fotovoltaico_product_model') ?>", "class": "w120"},
                {title: "<?php echo app_lang('fotovoltaico_product_cost_price') ?>", "class": "text-right w100"},
                {title: "<?php echo app_lang('fotovoltaico_product_sale_price') ?>", "class": "text-right w100"},
                {title: "<?php echo app_lang('fotovoltaico_product_active') ?>", "class": "text-center w100"},
                {title: "<i data-feather='menu' class='icon-16'></i>", "class": "text-center option w100"}
            ]
        });
    });
</script>
