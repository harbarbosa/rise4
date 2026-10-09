<?php
$item = $item ?? (object) array();
$default_markup_percent = isset($default_markup_percent) ? (float)$default_markup_percent : 0;
$markup_value = isset($item->id) ? (float)($item->markup ?? 0) : $default_markup_percent;
$component_products = $component_products ?? array();
$composition_rows = $composition_rows ?? array();
$is_composite = !empty($is_composite);
?>
<style>
    #product-composition-wrapper .composition-product { min-width: 260px; }
    #product-composition-wrapper .composition-quantity { min-width: 110px; }
    #product-composition-wrapper td { vertical-align: middle; }
</style>

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
                <label class="col-md-3">Tipo do produto</label>
                <div class="col-md-9">
                    <label class="form-check-label">
                        <input type="checkbox" id="is_composite" name="is_composite" value="1" class="form-check-input" <?php echo $is_composite ? 'checked="checked"' : ''; ?> />
                        Produto composto
                    </label>
                    <div class="text-muted small mt5">O custo será calculado pela quantidade e pelo custo de cada componente.</div>
                </div>
            </div>
        </div>

        <div id="product-composition-wrapper" class="<?php echo $is_composite ? '' : 'd-none'; ?>">
            <div class="form-group">
                <div class="row">
                    <label class="col-md-3">Composição</label>
                    <div class="col-md-9">
                        <div class="table-responsive">
                            <table class="table table-bordered mb10">
                                <thead>
                                    <tr>
                                        <th>Produto componente</th>
                                        <th style="width:145px;">Quantidade por unidade</th>
                                        <th style="width:55px;"></th>
                                    </tr>
                                </thead>
                                <tbody id="composition-rows">
                                    <?php foreach ($composition_rows as $component) { ?>
                                        <?php
                                        $quantity_text = rtrim(rtrim(number_format((float)$component->quantity, 4, '.', ''), '0'), '.');
                                        ?>
                                        <tr class="composition-row">
                                            <td>
                                                <select name="component_item_id[]" class="form-control composition-product">
                                                    <option value="">- Selecione -</option>
                                                    <?php foreach ($component_products as $product) { ?>
                                                        <?php $product_cost = isset($product->cost) && is_numeric($product->cost) ? (float)$product->cost : (float)$product->rate; ?>
                                                        <option value="<?php echo (int)$product->id; ?>" data-cost="<?php echo esc($product_cost); ?>" <?php echo (int)$component->component_item_id === (int)$product->id ? 'selected="selected"' : ''; ?>>
                                                            <?php echo esc($product->title . ($product->unit_type ? ' (' . $product->unit_type . ')' : '')); ?>
                                                        </option>
                                                    <?php } ?>
                                                </select>
                                            </td>
                                            <td>
                                                <input type="text" name="component_quantity[]" value="<?php echo esc($quantity_text); ?>" class="form-control composition-quantity js-composition-decimal" inputmode="decimal" />
                                            </td>
                                            <td class="text-center">
                                                <button type="button" class="btn btn-default btn-sm js-remove-component" title="Remover">
                                                    <i data-feather="trash-2" class="icon-16"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                        <button type="button" id="add-composition-row" class="btn btn-default btn-sm">
                            <i data-feather="plus-circle" class="icon-16"></i> Adicionar componente
                        </button>
                        <div class="mt10">
                            <strong>Custo calculado:</strong> <span id="composition-total-cost"><?php echo to_currency((float)($item->cost ?? $item->rate ?? 0)); ?></span>
                        </div>
                    </div>
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
        var componentProducts = <?php echo json_encode(array_map(function ($product) {
            return array(
                'id' => (int)$product->id,
                'title' => $product->title . (!empty($product->unit_type) ? ' (' . $product->unit_type . ')' : ''),
                'cost' => isset($product->cost) && is_numeric($product->cost) ? (float)$product->cost : (float)$product->rate
            );
        }, $component_products)); ?>;

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

        function createCompositionRow() {
            var $row = $('<tr class="composition-row"></tr>');
            var $select = $('<select name="component_item_id[]" class="form-control composition-product"></select>');
            $select.append($('<option></option>').val('').text('- Selecione -'));
            $.each(componentProducts, function (_, product) {
                $select.append($('<option></option>')
                    .val(product.id)
                    .text(product.title)
                    .attr('data-cost', product.cost));
            });

            var $productCell = $('<td></td>').append($select);
            var $quantity = $('<input type="text" name="component_quantity[]" class="form-control composition-quantity js-composition-decimal" inputmode="decimal" value="1" />');
            var $quantityCell = $('<td></td>').append($quantity);
            var $remove = $('<button type="button" class="btn btn-default btn-sm js-remove-component" title="Remover"><i data-feather="trash-2" class="icon-16"></i></button>');
            var $actionCell = $('<td class="text-center"></td>').append($remove);

            $row.append($productCell, $quantityCell, $actionCell);
            $("#composition-rows").append($row);
            if (typeof feather !== "undefined") {
                feather.replace();
            }
        }

        function updateCompositeCost() {
            if (!$("#is_composite").is(":checked")) {
                $cost.prop("readonly", false);
                return;
            }

            var total = 0;
            $("#composition-rows .composition-row").each(function () {
                var $row = $(this);
                var cost = parseFloat($row.find(".composition-product option:selected").attr("data-cost")) || 0;
                var quantity = parseDecimal($row.find(".composition-quantity").val());
                total += cost * quantity;
            });

            $cost.prop("readonly", true).val(formatNumber2(total));
            $("#composition-total-cost").text(total.toLocaleString("pt-BR", {style: "currency", currency: "BRL"}));
            recalc("markup");
        }

        function toggleComposition() {
            var enabled = $("#is_composite").is(":checked");
            $("#product-composition-wrapper").toggleClass("d-none", !enabled);
            $cost.prop("readonly", enabled);
            if (enabled && !$("#composition-rows .composition-row").length) {
                createCompositionRow();
            }
            updateCompositeCost();
        }

        $("#is_composite").on("change", toggleComposition);
        $("#add-composition-row").on("click", function () {
            createCompositionRow();
        });
        $(document).on("click", ".js-remove-component", function () {
            $(this).closest(".composition-row").remove();
            updateCompositeCost();
        });
        $(document).on("change", ".composition-product", updateCompositeCost);
        $(document).on("input change blur", ".js-composition-decimal", function () {
            $(this).val($(this).val().replace(/[^\d,.\-]/g, ""));
            updateCompositeCost();
        });

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

        toggleComposition();

        $("#proposal-product-form").appForm({
            onSuccess: function (result) {
                if (result && result.success) {
                    $("#products-table").appTable({newData: result.data, dataId: result.id});
                }
            }
        });
    });
</script>
