<?php
$power_rating = is_numeric($model_info->power_rating ?? null) ? (float) $model_info->power_rating : 0;
$efficiency = is_numeric($model_info->efficiency ?? null) ? (float) $model_info->efficiency : 0;
$cost_price = is_numeric($model_info->cost_price ?? null) ? (float) $model_info->cost_price : 0;
$sale_price = is_numeric($model_info->sale_price ?? null) ? (float) $model_info->sale_price : 0;
$tax_rate = is_numeric($model_info->tax_rate ?? null) ? (float) $model_info->tax_rate : 0;
?>
<?php echo form_open(get_uri("fotovoltaico/products/save"), array("id" => "product-form", "class" => "general-form", "role" => "form")); ?>
<div class="modal-body clearfix">
    <div class="container-fluid">
        <input type="hidden" name="id" value="<?php echo $model_info->id; ?>" />

        <div class="form-group">
            <div class="row">
                <label for="title" class="col-md-3"><?php echo app_lang('fotovoltaico_product_name'); ?></label>
                <div class="col-md-9">
                    <?php
                    echo form_input(array(
                        "id" => "title",
                        "name" => "title",
                        "value" => $model_info->title,
                        "class" => "form-control",
                        "autofocus" => true,
                        "data-rule-required" => true,
                        "data-msg-required" => app_lang("field_required"),
                    ));
                    ?>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="category_id" class="col-md-3"><?php echo app_lang('fotovoltaico_product_category'); ?></label>
                <div class="col-md-9">
                    <?php echo form_dropdown("category_id", $categories_dropdown, $model_info->category_id, "class='select2 form-control' id='category_id' data-rule-required='true' data-msg-required='" . app_lang("field_required") . "'"); ?>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="product_type" class="col-md-3"><?php echo app_lang('fotovoltaico_product_type'); ?></label>
                <div class="col-md-9">
                    <?php echo form_dropdown("product_type", $product_types_dropdown, $model_info->product_type, "class='select2 form-control' id='product_type' data-rule-required='true' data-msg-required='" . app_lang("field_required") . "'"); ?>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="distributor_id" class="col-md-3"><?php echo app_lang('fotovoltaico_product_distributor'); ?></label>
                <div class="col-md-9">
                    <?php echo form_dropdown("distributor_id", $distributors_dropdown, $model_info->distributor_id, "class='select2 form-control' id='distributor_id'"); ?>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="sku" class="col-md-3"><?php echo app_lang('fotovoltaico_product_sku'); ?></label>
                <div class="col-md-9">
                    <?php echo form_input(array("id" => "sku", "name" => "sku", "value" => $model_info->sku, "class" => "form-control")); ?>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="brand" class="col-md-3"><?php echo app_lang('fotovoltaico_product_brand'); ?></label>
                <div class="col-md-9">
                    <?php echo form_input(array("id" => "brand", "name" => "brand", "value" => $model_info->brand, "class" => "form-control")); ?>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="model" class="col-md-3"><?php echo app_lang('fotovoltaico_product_model'); ?></label>
                <div class="col-md-9">
                    <?php echo form_input(array("id" => "model", "name" => "model", "value" => $model_info->model, "class" => "form-control")); ?>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="unit" class="col-md-3"><?php echo app_lang('fotovoltaico_product_unit'); ?></label>
                <div class="col-md-9">
                    <?php echo form_input(array("id" => "unit", "name" => "unit", "value" => $model_info->unit ? $model_info->unit : 'un', "class" => "form-control")); ?>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="warranty" class="col-md-3"><?php echo app_lang('fotovoltaico_warranty'); ?></label>
                <div class="col-md-9">
                    <?php echo form_input(array("id" => "warranty", "name" => "warranty", "value" => $model_info->warranty, "class" => "form-control")); ?>
                </div>
            </div>
        </div>

        <div class="form-group js-specs-field">
            <div class="row">
                <label for="power_rating" class="col-md-3"><?php echo app_lang('fotovoltaico_power_rating'); ?></label>
                <div class="col-md-9">
                    <?php echo form_input(array("id" => "power_rating", "name" => "power_rating", "value" => to_decimal_format($power_rating), "class" => "form-control")); ?>
                </div>
            </div>
        </div>

        <div class="form-group js-specs-field">
            <div class="row">
                <label for="efficiency" class="col-md-3"><?php echo app_lang('fotovoltaico_efficiency'); ?></label>
                <div class="col-md-9">
                    <?php echo form_input(array("id" => "efficiency", "name" => "efficiency", "value" => to_decimal_format($efficiency), "class" => "form-control")); ?>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="cost_price" class="col-md-3"><?php echo app_lang('fotovoltaico_product_cost_price'); ?></label>
                <div class="col-md-9">
                    <?php echo form_input(array("id" => "cost_price", "name" => "cost_price", "value" => to_decimal_format($cost_price), "class" => "form-control")); ?>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="sale_price" class="col-md-3"><?php echo app_lang('fotovoltaico_product_sale_price'); ?></label>
                <div class="col-md-9">
                    <?php echo form_input(array("id" => "sale_price", "name" => "sale_price", "value" => to_decimal_format($sale_price), "class" => "form-control")); ?>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="voltage" class="col-md-3"><?php echo app_lang('fotovoltaico_voltage'); ?></label>
                <div class="col-md-9">
                    <?php echo form_input(array("id" => "voltage", "name" => "voltage", "value" => $model_info->voltage, "class" => "form-control")); ?>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="tax_rate" class="col-md-3"><?php echo app_lang('fotovoltaico_tax_rate'); ?></label>
                <div class="col-md-9">
                    <?php echo form_input(array("id" => "tax_rate", "name" => "tax_rate", "value" => to_decimal_format($tax_rate), "class" => "form-control")); ?>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="description" class="col-md-3"><?php echo app_lang('fotovoltaico_product_description'); ?></label>
                <div class="col-md-9">
                    <?php echo form_textarea(array("id" => "description", "name" => "description", "value" => $model_info->description, "class" => "form-control", "rows" => 3)); ?>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="technical_specs_json" class="col-md-3"><?php echo app_lang('fotovoltaico_technical_specs'); ?></label>
                <div class="col-md-9">
                    <?php echo form_textarea(array("id" => "technical_specs_json", "name" => "technical_specs_json", "value" => $technical_specs_json, "class" => "form-control", "rows" => 6, "placeholder" => '{"inverter":{"mppt":2}}')); ?>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="active" class="col-md-3"><?php echo app_lang('fotovoltaico_product_active'); ?></label>
                <div class="col-md-9">
                    <?php echo form_checkbox("active", "1", $model_info->active ? true : false, "id='active' class='form-check-input'"); ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal-footer">
    <button type="button" class="btn btn-default" data-bs-dismiss="modal"><span data-feather="x" class="icon-16"></span> <?php echo app_lang('close'); ?></button>
    <button type="submit" class="btn btn-primary"><span data-feather="check-circle" class="icon-16"></span> <?php echo app_lang('save'); ?></button>
</div>
<?php echo form_close(); ?>

<script type="text/javascript">
    $(document).ready(function () {
        $("#product-form").appForm({
            onSuccess: function (result) {
                if (result && result.success) {
                    $("#products-table").appTable({newData: result.data, dataId: result.id});
                }
            }
        });

        $("#product-form .select2").select2();

        var toggleSpecs = function () {
            var value = $("#product_type").val();
            var show = value === "modulo" || value === "inversor";
            $(".js-specs-field").toggle(show);
        };

        $("#product_type").on("change", toggleSpecs);
        toggleSpecs();
    });
</script>
