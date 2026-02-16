<?php
$settings = $settings ?? (object) array();
$taxes = $taxes ?? array();
$commission_types = $commission_types ?? array();

$tax_product = array("percent" => 0, "active" => 1);
$tax_service = array("percent" => 0, "active" => 1);
foreach ($taxes as $tax) {
    $name = strtolower(trim((string)($tax["name"] ?? "")));
    if ($name === "imposto produto") {
        $tax_product["percent"] = $tax["percent"] ?? 0;
        $tax_product["active"] = !empty($tax["active"]) ? 1 : 0;
    } elseif ($name === "imposto servico") {
        $tax_service["percent"] = $tax["percent"] ?? 0;
        $tax_service["active"] = !empty($tax["active"]) ? 1 : 0;
    }
}
?>

<div id="page-content" class="page-wrapper clearfix">
    <div class="card">
        <div class="page-title clearfix">
            <h1><?php echo app_lang('proposals_settings'); ?></h1>
        </div>
        <div class="card-body">
            <?php echo form_open(get_uri("propostas/save_settings"), array("id" => "proposals-settings-form", "class" => "general-form", "role" => "form")); ?>
            <div class="form-group">
                <label for="default_commission_type"><?php echo app_lang('proposals_commission_type'); ?></label>
                <?php echo form_dropdown("default_commission_type", $commission_types, $settings->default_commission_type ?? "percent", "class='select2' id='default_commission_type'"); ?>
            </div>
            <div class="form-group">
                <label for="default_commission_value"><?php echo app_lang('proposals_commission_value'); ?></label>
                <input type="text" id="default_commission_value" name="default_commission_value" class="form-control" value="<?php echo esc(number_format((float)($settings->default_commission_value ?? 0), 2, ",", ".")); ?>" />
            </div>
            <div class="form-group">
                <label for="default_markup_percent"><?php echo app_lang('proposals_markup_percent'); ?></label>
                <input type="text" id="default_markup_percent" name="default_markup_percent" class="form-control" value="<?php echo esc(number_format((float)($settings->default_markup_percent ?? 0), 2, ",", ".")); ?>" />
            </div>

            <div class="mt20">
                <div class="d-flex justify-content-between align-items-center mb10">
                    <h4 class="mb0">Taxas</h4>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered" id="proposals-taxes-table">
                        <thead>
                            <tr>
                                <th><?php echo app_lang('proposals_tax_name'); ?></th>
                                <th class="w120"><?php echo app_lang('proposals_tax_percent'); ?></th>
                                <th class="w100"><?php echo app_lang('proposals_tax_active'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    Imposto Produto
                                    <input type="hidden" name="tax_name[]" value="Imposto Produto" />
                                </td>
                                <td><input type="text" name="tax_percent[]" class="form-control text-right" value="<?php echo esc($tax_product['percent']); ?>" /></td>
                                <td class="text-center">
                                    <input type="checkbox" name="tax_active[]" value="1" <?php echo $tax_product['active'] ? 'checked' : ''; ?> />
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    Imposto Servico
                                    <input type="hidden" name="tax_name[]" value="Imposto Servico" />
                                </td>
                                <td><input type="text" name="tax_percent[]" class="form-control text-right" value="<?php echo esc($tax_service['percent']); ?>" /></td>
                                <td class="text-center">
                                    <input type="checkbox" name="tax_active[]" value="1" <?php echo $tax_service['active'] ? 'checked' : ''; ?> />
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="form-group">
                <button type="submit" class="btn btn-primary"><?php echo app_lang('save'); ?></button>
                <?php echo anchor(get_uri('propostas'), app_lang('cancel'), array('class' => 'btn btn-default')); ?>
            </div>
            <?php echo form_close(); ?>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function () {
        $(".select2").select2();

        $("#proposals-settings-form").appForm({
            onSuccess: function (result) {
                if (result && result.message) {
                    appAlert.success(result.message, {duration: 10000});
                }
            }
        });

        // Fixed tax rows (Imposto Produto/Servico) - no add/remove actions.
    });
</script>
