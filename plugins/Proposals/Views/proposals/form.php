<?php
$proposal_info = $proposal_info ?? (object) array();
$clients_dropdown = $clients_dropdown ?? array();
$status_options = $status_options ?? array();
$commission_types = $commission_types ?? array();
$is_edit = isset($proposal_info->id) && $proposal_info->id;
?>

<div id="page-content" class="page-wrapper clearfix">
    <div class="card">
        <div class="page-title clearfix">
            <h1><?php echo $is_edit ? app_lang('proposals_edit') : app_lang('proposals_add'); ?></h1>
        </div>
        <div class="card-body">
            <?php echo form_open(get_uri("propostas/save"), array("id" => "proposal-form", "class" => "general-form", "role" => "form")); ?>
            <input type="hidden" name="id" value="<?php echo esc($proposal_info->id ?? 0); ?>" />

            <div class="form-group">
                <label for="title"><?php echo app_lang('proposals_title'); ?></label>
                <input type="text" id="title" name="title" class="form-control" value="<?php echo esc($proposal_info->title ?? ''); ?>" required />
            </div>

            <?php if ($clients_dropdown && count($clients_dropdown)) { ?>
                <div class="form-group">
                    <label for="client_id"><?php echo app_lang('client'); ?></label>
                    <?php echo form_dropdown("client_id", $clients_dropdown, $proposal_info->client_id ?? "", "class='select2' id='client_id'"); ?>
                </div>
                <input type="hidden" name="client_name" value="" />
            <?php } else { ?>
                <div class="form-group">
                    <label for="client_name"><?php echo app_lang('proposals_client_name'); ?></label>
                    <input type="text" id="client_name" name="client_name" class="form-control" value="<?php echo esc($proposal_info->client_name ?? ''); ?>" />
                </div>
            <?php } ?>

            <div class="form-group">
                <label for="validity_days"><?php echo app_lang('proposals_validity_days'); ?></label>
                <input type="number" id="validity_days" name="validity_days" class="form-control" value="<?php echo esc($proposal_info->validity_days ?? ''); ?>" />
            </div>

            <div class="form-group">
                <label for="description"><?php echo app_lang('proposals_description'); ?></label>
                <textarea id="description" name="description" class="form-control" rows="3"><?php echo esc($proposal_info->description ?? ''); ?></textarea>
            </div>

            <div class="form-group">
                <label for="payment_terms"><?php echo app_lang('proposals_payment_terms'); ?></label>
                <textarea id="payment_terms" name="payment_terms" class="form-control" rows="3"><?php echo esc($proposal_info->payment_terms ?? ''); ?></textarea>
            </div>

            <div class="form-group">
                <label for="observations"><?php echo app_lang('proposals_observations'); ?></label>
                <textarea id="observations" name="observations" class="form-control" rows="3"><?php echo esc($proposal_info->observations ?? ''); ?></textarea>
            </div>

            <div class="form-group">
                <label for="status"><?php echo app_lang('status'); ?></label>
                <?php
                $status_dropdown = array();
                foreach ($status_options as $status_option) {
                    $status_dropdown[$status_option['id']] = $status_option['text'];
                }
                echo form_dropdown("status", $status_dropdown, $proposal_info->status ?? "draft", "class='select2' id='status'");
                ?>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="commission_type"><?php echo app_lang('proposals_commission_type'); ?></label>
                        <?php echo form_dropdown("commission_type", $commission_types, $proposal_info->commission_type ?? "percent", "class='select2' id='commission_type'"); ?>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="commission_value"><?php echo app_lang('proposals_commission_value'); ?></label>
                        <input type="text" id="commission_value" name="commission_value" class="form-control" value="<?php echo esc(number_format((float)($proposal_info->commission_value ?? 0), 2, ",", ".")); ?>" />
                    </div>
                </div>
            </div>

            <div class="mt20">
                <h4 class="mb10">Taxas</h4>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="tax_product_percent">Imposto Produto (%)</label>
                            <input type="text" id="tax_product_percent" name="tax_product_percent" class="form-control" value="<?php echo esc(number_format((float)($proposal_info->tax_product_percent ?? 0), 2, ",", ".")); ?>" />
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="tax_service_percent">Imposto Servico (%)</label>
                            <input type="text" id="tax_service_percent" name="tax_service_percent" class="form-control" value="<?php echo esc(number_format((float)($proposal_info->tax_service_percent ?? 0), 2, ",", ".")); ?>" />
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="tax_service_only" name="tax_service_only" value="1" <?php echo !empty($proposal_info->tax_service_only) ? "checked" : ""; ?> />
                        <label class="form-check-label" for="tax_service_only">Faturar tudo como servico (usar imposto de servico)</label>
                    </div>
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

        $("#proposal-form").appForm({
            onSuccess: function (result) {
                if (result && result.redirect_to) {
                    window.location = result.redirect_to;
                    return;
                }
                if (result && result.message) {
                    appAlert.success(result.message, {duration: 10000});
                }
            }
        });
    });
</script>
