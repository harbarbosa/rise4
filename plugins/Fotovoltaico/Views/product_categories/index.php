<div id="page-content" class="page-wrapper clearfix">
    <div class="card">
        <div class="page-title clearfix">
            <h4 class="float-start mb-0"><?php echo app_lang('fotovoltaico_product_categories'); ?></h4>
            <div class="title-button-group float-end">
                <?php if ($can_manage_products) { ?>
                    <?php echo modal_anchor(get_uri("fotovoltaico/product_categories/modal_form"), "<i data-feather='plus-circle' class='icon-16'></i> " . app_lang('fotovoltaico_add_category'), array("class" => "btn btn-default", "title" => app_lang('fotovoltaico_add_category'))); ?>
                <?php } ?>
            </div>
        </div>
        <div class="table-responsive">
            <table id="product-categories-table" class="display" cellspacing="0" width="100%"></table>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function () {
        $("#product-categories-table").appTable({
            source: '<?php echo_uri("fotovoltaico/product_categories/list_data") ?>',
            columns: [
                {title: "<?php echo app_lang('title') ?>"},
                {title: "<?php echo app_lang('fotovoltaico_slug') ?>"},
                {title: "<?php echo app_lang('fotovoltaico_status') ?>", "class": "text-center w100"},
                {title: "<i data-feather='menu' class='icon-16'></i>", "class": "text-center option w100"}
            ]
        });
    });
</script>
