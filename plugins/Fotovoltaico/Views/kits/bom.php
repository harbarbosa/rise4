<div class="card shadow-none border">
    <div class="card-body">
        <div class="page-title clearfix">
            <h5 class="float-start mb-0"><?php echo app_lang('fotovoltaico_bom'); ?></h5>
        </div>

        <?php if ($can_manage_kits) { ?>
            <?php echo form_open(get_uri("fotovoltaico/kits/add_item"), array("id" => "kit-item-form", "class" => "general-form", "role" => "form")); ?>
            <input type="hidden" name="kit_id" value="<?php echo (int) $kit->id; ?>" />
            <div class="row g-3 mb20">
                <div class="col-md-4">
                    <label class="form-label" for="product_id"><?php echo app_lang('fotovoltaico_product_name'); ?></label>
                    <?php echo form_dropdown("product_id", $product_options, "", "class='select2 form-control' id='product_id' data-rule-required='true' data-msg-required='" . app_lang("field_required") . "'"); ?>
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="quantity"><?php echo app_lang('quantity'); ?></label>
                    <?php echo form_input(array("id" => "quantity", "name" => "quantity", "value" => "1", "class" => "form-control", "data-rule-required" => true, "data-msg-required" => app_lang("field_required"))); ?>
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="unit_price"><?php echo app_lang('fotovoltaico_product_sale_price'); ?></label>
                    <?php echo form_input(array("id" => "unit_price", "name" => "unit_price", "value" => "", "class" => "form-control")); ?>
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="unit_cost"><?php echo app_lang('fotovoltaico_product_cost_price'); ?></label>
                    <?php echo form_input(array("id" => "unit_cost", "name" => "unit_cost", "value" => "", "class" => "form-control")); ?>
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="sort"><?php echo app_lang('sort'); ?></label>
                    <?php echo form_input(array("id" => "sort", "name" => "sort", "value" => "0", "class" => "form-control")); ?>
                </div>
                <div class="col-md-12">
                    <label class="form-label" for="notes"><?php echo app_lang('fotovoltaico_notes'); ?></label>
                    <?php echo form_textarea(array("id" => "notes", "name" => "notes", "value" => "", "class" => "form-control", "rows" => 2)); ?>
                </div>
            </div>
            <div class="mb20">
                <button type="submit" class="btn btn-primary"><span data-feather="plus-circle" class="icon-16"></span> <?php echo app_lang('add'); ?></button>
            </div>
            <?php echo form_close(); ?>
        <?php } ?>

        <div class="table-responsive">
            <table id="kit-items-table" class="display" cellspacing="0" width="100%"></table>
        </div>
    </div>
</div>

<script type="text/javascript">
    window.reloadKitBom = function () {
        location.reload();
    };

    $(document).ready(function () {
        var productLookup = <?php echo $product_lookup_json ?: '{}'; ?>;

        $("#kit-items-table").appTable({
            source: '<?php echo_uri("fotovoltaico/kits/items_list_data/" . (int) $kit->id) ?>',
            columns: [
                {title: "<?php echo app_lang('fotovoltaico_product_name') ?>"},
                {title: "<?php echo app_lang('fotovoltaico_product_type') ?>"},
                {title: "<?php echo app_lang('quantity') ?>", "class": "text-right w80"},
                {title: "<?php echo app_lang('fotovoltaico_product_sale_price') ?>", "class": "text-right w100"},
                {title: "<?php echo app_lang('fotovoltaico_product_cost_price') ?>", "class": "text-right w100"},
                {title: "<?php echo app_lang('fotovoltaico_kit_total_price') ?>", "class": "text-right w100"},
                {title: "<?php echo app_lang('fotovoltaico_kit_total_cost') ?>", "class": "text-right w100"},
                {title: "<?php echo app_lang('fotovoltaico_notes') ?>"},
                {title: "<i data-feather='menu' class='icon-16'></i>", "class": "text-center option w100"}
            ]
        });

        $("#kit-item-form").appForm({
            onSuccess: function (result) {
                if (result && result.success) {
                    location.reload();
                }
            }
        });

        $("#kit-item-form .select2").select2().on("change", function () {
            var data = productLookup[$(this).val()];
            if (data) {
                $("#unit_price").val(data.sale_price);
                $("#unit_cost").val(data.cost_price);
            }
        });

        $("#product_id").trigger("change");
    });
</script>
