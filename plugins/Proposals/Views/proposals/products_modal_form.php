<?php
$item = $item ?? (object) array();
$default_markup_percent = isset($default_markup_percent) ? (float)$default_markup_percent : 0;
$markup_value = isset($item->id) ? (float)($item->markup ?? 0) : $default_markup_percent;
$product_type = $product_type ?? 'simple';
$components = $components ?? array();
$available_items = $available_items ?? array();

function proposal_component_options($available_items, $selected = 0)
{
    $html = "<option value=''>- Selecione -</option>";
    foreach ($available_items as $available_item) {
        $is_selected = ((int)$available_item->id === (int)$selected) ? ' selected' : '';
        $unit = $available_item->unit_type ? ' (' . esc($available_item->unit_type) . ')' : '';
        $html .= "<option value='" . (int)$available_item->id . "'{$is_selected}>" . esc($available_item->title) . $unit . "</option>";
    }
    return $html;
}
?>

<?php echo form_open(get_uri("propostas/products_save"), array("id" => "proposal-product-form", "class" => "general-form", "role" => "form")); ?>
<div class="modal-body clearfix">
    <div class="container-fluid">
        <input type="hidden" name="id" value="<?php echo esc($item->id ?? 0); ?>" />

        <div class="form-group">
            <div class="row">
                <label for="title" class="col-md-3"><?php echo app_lang('title'); ?></label>
                <div class="col-md-9">
                    <?php echo form_input(array("id" => "title", "name" => "title", "value" => $item->title ?? "", "class" => "form-control", "placeholder" => app_lang('title'), "data-rule-required" => true, "data-msg-required" => app_lang("field_required"))); ?>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="product_type" class="col-md-3">Tipo do produto</label>
                <div class="col-md-9">
                    <?php echo form_dropdown('product_type', array('simple' => 'Produto simples', 'kit' => 'Kit / Produto composto'), $product_type, "class='select2 form-control' id='product_type'"); ?>
                    <small class="text-muted">No kit, a proposta mostra um único produto, mas o sistema calcula internamente os materiais da composição.</small>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="ca_code" class="col-md-3"><?php echo app_lang('proposals_ca_code'); ?></label>
                <div class="col-md-9">
                    <?php echo form_input(array("id" => "ca_code", "name" => "ca_code", "value" => $item->ca_code ?? "", "class" => "form-control", "placeholder" => app_lang('proposals_ca_code'))); ?>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="unit_type" class="col-md-3"><?php echo app_lang('proposals_unit'); ?></label>
                <div class="col-md-9">
                    <?php echo form_input(array("id" => "unit_type", "name" => "unit_type", "value" => $item->unit_type ?? "", "class" => "form-control", "placeholder" => app_lang('proposals_unit'))); ?>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="markup" class="col-md-3"><?php echo app_lang('proposals_markup'); ?></label>
                <div class="col-md-9">
                    <?php echo form_input(array("id" => "markup", "name" => "markup", "value" => number_format($markup_value, 2, ",", "."), "class" => "form-control js-decimal", "placeholder" => app_lang('proposals_markup'), "inputmode" => "decimal")); ?>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="cost" class="col-md-3"><?php echo app_lang('proposals_cost'); ?></label>
                <div class="col-md-9">
                    <?php echo form_input(array("id" => "cost", "name" => "cost", "value" => number_format((float)($item->cost ?? $item->rate ?? 0), 2, ",", "."), "class" => "form-control js-decimal", "placeholder" => app_lang('proposals_cost'), "inputmode" => "decimal")); ?>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="sale" class="col-md-3"><?php echo app_lang('proposals_sale'); ?></label>
                <div class="col-md-9">
                    <?php echo form_input(array("id" => "sale", "name" => "sale", "value" => number_format((float)($item->sale ?? 0), 2, ",", "."), "class" => "form-control js-decimal", "placeholder" => app_lang('proposals_sale'), "inputmode" => "decimal")); ?>
                </div>
            </div>
        </div>

        <div id="composition-panel" class="mt-3" style="display:none;">
            <div class="card border">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <strong>Composição do kit</strong>
                        <div class="text-muted small">Informe quanto de cada produto é consumido para 1 unidade deste kit.</div>
                    </div>
                    <button type="button" class="btn btn-default btn-sm" id="add-component"><i data-feather="plus" class="icon-16"></i> Adicionar componente</button>
                </div>
                <div class="card-body p-2">
                    <div class="table-responsive">
                        <table class="table table-sm mb-0" id="components-table">
                            <thead>
                                <tr>
                                    <th style="min-width:220px;">Produto componente</th>
                                    <th style="width:125px;">Qtd. por unidade</th>
                                    <th style="width:120px;">Unid. referência</th>
                                    <th style="width:100px;">Perda %</th>
                                    <th style="width:150px;">Arredondamento</th>
                                    <th style="width:45px;"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($components as $component) { ?>
                                    <tr class="component-row">
                                        <td><select name="component_item_id[]" class="form-control component-select"><?php echo proposal_component_options($available_items, $component->component_item_id); ?></select></td>
                                        <td><input type="text" name="component_qty[]" class="form-control js-component-decimal" inputmode="decimal" value="<?php echo number_format((float)$component->qty_per_unit, 6, ',', ''); ?>"></td>
                                        <td><input type="text" name="component_reference_unit[]" class="form-control" value="<?php echo esc($component->reference_unit ?: $component->component_unit); ?>" placeholder="m, un, barra..."></td>
                                        <td><input type="text" name="component_loss_percent[]" class="form-control js-component-decimal" inputmode="decimal" value="<?php echo number_format((float)$component->loss_percent, 2, ',', ''); ?>"></td>
                                        <td><select name="component_round_up[]" class="form-control"><option value="0" <?php echo !$component->round_up ? 'selected' : ''; ?>>Não</option><option value="1" <?php echo $component->round_up ? 'selected' : ''; ?>>Para cima</option></select></td>
                                        <td><button type="button" class="btn btn-default btn-sm remove-component" title="Remover"><i data-feather="x" class="icon-16"></i></button></td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="text-muted small mt-2">Exemplo: 0,3333 barra por metro. Em 100 m = 33,33 barras; com “Para cima”, o cálculo interno será 34 barras. A perda é aplicada antes do arredondamento.</div>
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

<script type="text/template" id="component-row-template">
<tr class="component-row">
    <td><select name="component_item_id[]" class="form-control component-select"><?php echo proposal_component_options($available_items, 0); ?></select></td>
    <td><input type="text" name="component_qty[]" class="form-control js-component-decimal" inputmode="decimal" value="1,000000"></td>
    <td><input type="text" name="component_reference_unit[]" class="form-control" placeholder="m, un, barra..."></td>
    <td><input type="text" name="component_loss_percent[]" class="form-control js-component-decimal" inputmode="decimal" value="0,00"></td>
    <td><select name="component_round_up[]" class="form-control"><option value="0" selected>Não</option><option value="1">Para cima</option></select></td>
    <td><button type="button" class="btn btn-default btn-sm remove-component" title="Remover"><i data-feather="x" class="icon-16"></i></button></td>
</tr>
</script>

<script type="text/javascript">
    $(document).ready(function () {
        var $cost = $("#cost"), $sale = $("#sale"), $markup = $("#markup"), $type = $("#product_type");

        function parseDecimal(value) {
            var text = (value || "").toString().trim();
            if (!text) return 0;
            text = text.replace(/[^\d,.\-]/g, "");
            var lastComma = text.lastIndexOf(","), lastDot = text.lastIndexOf(".");
            if (lastComma !== -1 && lastDot !== -1) {
                if (lastComma > lastDot) { text = text.replace(/\./g, "").replace(",", "."); }
                else { text = text.replace(/,/g, ""); }
            } else if (lastComma !== -1) { text = text.replace(/\./g, "").replace(",", "."); }
            else { text = text.replace(/,/g, ""); }
            var num = parseFloat(text); return isNaN(num) ? 0 : num;
        }

        function formatNumber2(value) { return Number(isNaN(value) ? 0 : value).toFixed(2).replace(".", ","); }
        function recalc(from) {
            var cost = parseDecimal($cost.val()), sale = parseDecimal($sale.val()), markup = parseDecimal($markup.val());
            if (from === "markup" && cost > 0) { $sale.val(formatNumber2(cost * (1 + markup / 100))); }
            else if (from === "sale" && cost > 0 && sale > 0) { $markup.val(formatNumber2(((sale / cost) - 1) * 100)); }
            else if (from === "cost") {
                if (sale > 0 && cost > 0) $markup.val(formatNumber2(((sale / cost) - 1) * 100));
                else if (markup > 0 && cost > 0) $sale.val(formatNumber2(cost * (1 + markup / 100)));
            }
        }

        function initSelects(context) {
            $(context).find('.component-select').each(function () {
                if (!$(this).hasClass('select2-hidden-accessible')) $(this).select2({width: '100%'});
            });
        }

        function toggleComposition() {
            var isKit = $type.val() === 'kit';
            $('#composition-panel').toggle(isKit);
            if (isKit && $('#components-table tbody .component-row').length === 0) $('#add-component').trigger('click');
        }

        $('.js-decimal, .js-component-decimal').on('input', function () { $(this).val($(this).val().replace(/[^\d,.\-]/g, '')); });
        $cost.on('change blur', function () { $(this).val(formatNumber2(parseDecimal($(this).val()))); recalc('cost'); });
        $sale.on('change blur', function () { $(this).val(formatNumber2(parseDecimal($(this).val()))); recalc('sale'); });
        $markup.on('change blur', function () { $(this).val(formatNumber2(parseDecimal($(this).val()))); recalc('markup'); });

        $type.select2({width: '100%'}).on('change', toggleComposition);
        initSelects(document);
        toggleComposition();

        $('#add-component').on('click', function () {
            var $row = $($('#component-row-template').html());
            $('#components-table tbody').append($row);
            initSelects($row);
            feather.replace();
        });

        $(document).on('click', '.remove-component', function () { $(this).closest('tr').remove(); });
        $(document).on('input', '.js-component-decimal', function () { $(this).val($(this).val().replace(/[^\d,.\-]/g, '')); });

        $('#proposal-product-form').appForm({
            onSuccess: function (result) {
                if (result && result.success) $('#products-table').appTable({newData: result.data, dataId: result.id});
            }
        });
    });
</script>
