<?php
$item = $item ?? (object) array();
$default_markup_percent = isset($default_markup_percent) ? (float)$default_markup_percent : 0;
$markup_value = isset($item->id) ? (float)($item->markup ?? 0) : $default_markup_percent;
?>

<?php echo form_open(get_uri("propostas/products_save"), array("id" => "proposal-product-form", "class" => "general-form", "role" => "form")); ?>
<div class="modal-body clearfix">
    <div class="container-fluid">
        <input type="hidden" name="id" value="<?php echo esc($item->id ?? 0); ?>" />

        <div class="form-group">
            <div class="row">
                <label for="title" class="col-md-3"><?php echo app_lang('title'); ?></label>
                <div class="col-md-9">
                    <?php
                    echo form_input(array(
                        "id" => "title",
                        "name" => "title",
                        "value" => $item->title ?? "",
                        "class" => "form-control",
                        "placeholder" => app_lang('title'),
                        "data-rule-required" => true,
                        "data-msg-required" => app_lang("field_required")
                    ));
                    ?>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="ca_code" class="col-md-3"><?php echo app_lang('proposals_ca_code'); ?></label>
                <div class="col-md-9">
                    <?php
                    echo form_input(array(
                        "id" => "ca_code",
                        "name" => "ca_code",
                        "value" => $item->ca_code ?? "",
                        "class" => "form-control",
                        "placeholder" => app_lang('proposals_ca_code')
                    ));
                    ?>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="unit_type" class="col-md-3"><?php echo app_lang('proposals_unit'); ?></label>
                <div class="col-md-9">
                    <?php
                    echo form_input(array(
                        "id" => "unit_type",
                        "name" => "unit_type",
                        "value" => $item->unit_type ?? "",
                        "class" => "form-control",
                        "placeholder" => app_lang('proposals_unit')
                    ));
                    ?>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="markup" class="col-md-3"><?php echo app_lang('proposals_markup'); ?></label>
                <div class="col-md-9">
                    <?php
                    echo form_input(array(
                        "id" => "markup",
                        "name" => "markup",
                        "value" => number_format($markup_value, 2, ",", "."),
                        "class" => "form-control js-decimal",
                        "placeholder" => app_lang('proposals_markup'),
                        "inputmode" => "decimal"
                    ));
                    ?>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="cost" class="col-md-3"><?php echo app_lang('proposals_cost'); ?></label>
                <div class="col-md-9">
                    <?php
                    echo form_input(array(
                        "id" => "cost",
                        "name" => "cost",
                        "value" => number_format((float)($item->cost ?? $item->rate ?? 0), 2, ",", "."),
                        "class" => "form-control js-decimal",
                        "placeholder" => app_lang('proposals_cost'),
                        "inputmode" => "decimal"
                    ));
                    ?>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="sale" class="col-md-3"><?php echo app_lang('proposals_sale'); ?></label>
                <div class="col-md-9">
                    <?php
                    echo form_input(array(
                        "id" => "sale",
                        "name" => "sale",
                        "value" => number_format((float)($item->sale ?? 0), 2, ",", "."),
                        "class" => "form-control js-decimal",
                        "placeholder" => app_lang('proposals_sale'),
                        "inputmode" => "decimal"
                    ));
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-default" data-bs-dismiss="modal"><span data-feather="x" class="icon-16"></span> <?php echo app_lang('cancel'); ?></button>
    <button type="submit" class="btn btn-primary"><span data-feather="check-circle" class="icon-16"></span> <?php echo app_lang('save'); ?></button>
</div>
<?php echo form_close(); ?>

<script type="text/javascript">
    $(document).ready(function () {
        var $cost = $("#cost");
        var $sale = $("#sale");
        var $markup = $("#markup");

        function parseDecimal(value) {
            var text = (value || "").toString().trim();
            if (!text) {
                return 0;
            }
            text = text.replace(/[^\d,.\-]/g, "");
            var lastComma = text.lastIndexOf(",");
            var lastDot = text.lastIndexOf(".");
            if (lastComma !== -1 && lastDot !== -1) {
                if (lastComma > lastDot) {
                    text = text.replace(/\./g, "");
                    text = text.replace(",", ".");
                } else {
                    text = text.replace(/,/g, "");
                }
            } else if (lastComma !== -1) {
                text = text.replace(/\./g, "");
                text = text.replace(",", ".");
            } else {
                text = text.replace(/,/g, "");
            }
            var num = parseFloat(text);
            return isNaN(num) ? 0 : num;
        }

        function formatNumber2(value) {
            var num = isNaN(value) ? 0 : Number(value);
            return num.toFixed(2).replace(".", ",");
        }

        function recalc(from) {
            var cost = parseDecimal($cost.val());
            var sale = parseDecimal($sale.val());
            var markup = parseDecimal($markup.val());

            if (from === "markup") {
                if (cost > 0 && markup > 0) {
                    sale = cost * (1 + (markup / 100));
                    $sale.val(formatNumber2(sale));
                }
                return;
            }

            if (from === "sale") {
                if (cost > 0 && sale > 0) {
                    markup = ((sale / cost) - 1) * 100;
                    $markup.val(formatNumber2(markup));
                }
                return;
            }

            if (from === "cost") {
                if (sale > 0 && cost > 0) {
                    markup = ((sale / cost) - 1) * 100;
                    $markup.val(formatNumber2(markup));
                } else if (markup > 0 && cost > 0) {
                    sale = cost * (1 + (markup / 100));
                    $sale.val(formatNumber2(sale));
                }
            }
        }

        $(".js-decimal").on("input", function () {
            var cleaned = $(this).val().replace(/[^\d,.\-]/g, "");
            $(this).val(cleaned);
        });

        $cost.on("change blur", function () {
            $(this).val(formatNumber2(parseDecimal($(this).val())));
            recalc("cost");
        });

        $sale.on("change blur", function () {
            $(this).val(formatNumber2(parseDecimal($(this).val())));
            recalc("sale");
        });

        $markup.on("change blur", function () {
            $(this).val(formatNumber2(parseDecimal($(this).val())));
            recalc("markup");
        });

        $("#proposal-product-form").appForm({
            onSuccess: function (result) {
                if (result && result.success) {
                    $("#products-table").appTable({newData: result.data, dataId: result.id});
                }
            }
        });
    });
</script>
