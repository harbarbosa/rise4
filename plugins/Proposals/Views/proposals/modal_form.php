<?php
$proposal_info = $proposal_info ?? (object) array();
$clients_dropdown = $clients_dropdown ?? array();
$status_options = $status_options ?? array();
$commission_types = $commission_types ?? array();
$is_edit = isset($proposal_info->id) && $proposal_info->id;
?>

<?php echo form_open(get_uri("propostas/save"), array("id" => "proposal-form", "class" => "general-form", "role" => "form")); ?>
<div class="modal-body clearfix">
    <div class="container-fluid">
        <input type="hidden" name="id" value="<?php echo esc($proposal_info->id ?? 0); ?>" />

        <div class="form-group">
            <div class="row">
                <label for="title" class="col-md-3"><?php echo app_lang('proposals_title'); ?></label>
                <div class="col-md-9">
                    <input type="text" id="title" name="title" class="form-control" value="<?php echo esc($proposal_info->title ?? ''); ?>" required />
                </div>
            </div>
        </div>

        <?php if ($clients_dropdown && count($clients_dropdown)) { ?>
            <div class="form-group">
                <div class="row">
                    <label for="client_id" class="col-md-3"><?php echo app_lang('client'); ?></label>
                    <div class="col-md-9">
                        <?php echo form_dropdown("client_id", $clients_dropdown, $proposal_info->client_id ?? "", "class='select2' id='client_id'"); ?>
                    </div>
                </div>
            </div>
            <input type="hidden" name="client_name" value="" />
        <?php } else { ?>
            <div class="form-group">
                <div class="row">
                    <label for="client_name" class="col-md-3"><?php echo app_lang('proposals_client_name'); ?></label>
                    <div class="col-md-9">
                        <input type="text" id="client_name" name="client_name" class="form-control" value="<?php echo esc($proposal_info->client_name ?? ''); ?>" />
                    </div>
                </div>
            </div>
        <?php } ?>

        <div class="form-group">
            <div class="row">
                <label for="validity_days" class="col-md-3"><?php echo app_lang('proposals_validity_days'); ?></label>
                <div class="col-md-3">
                    <input type="number" id="validity_days" name="validity_days" class="form-control" value="<?php echo esc($proposal_info->validity_days ?? ''); ?>" />
                </div>
                <label for="status" class="col-md-3"><?php echo app_lang('status'); ?></label>
                <div class="col-md-3">
                    <?php
                    $status_dropdown = array();
                    foreach ($status_options as $status_option) {
                        $status_dropdown[$status_option['id']] = $status_option['text'];
                    }
                    echo form_dropdown("status", $status_dropdown, $proposal_info->status ?? "draft", "class='select2' id='status'");
                    ?>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="description" class="col-md-3"><?php echo app_lang('proposals_description'); ?></label>
                <div class="col-md-9">
                    <textarea id="description" name="description" class="form-control" rows="3"><?php echo esc($proposal_info->description ?? ''); ?></textarea>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="payment_terms" class="col-md-3"><?php echo app_lang('proposals_payment_terms'); ?></label>
                <div class="col-md-9">
                    <textarea id="payment_terms" name="payment_terms" class="form-control" rows="3"><?php echo esc($proposal_info->payment_terms ?? ''); ?></textarea>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="observations" class="col-md-3"><?php echo app_lang('proposals_observations'); ?></label>
                <div class="col-md-9">
                    <textarea id="observations" name="observations" class="form-control" rows="3"><?php echo esc($proposal_info->observations ?? ''); ?></textarea>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="commission_type" class="col-md-3"><?php echo app_lang('proposals_commission_type'); ?></label>
                <div class="col-md-3">
                    <?php echo form_dropdown("commission_type", $commission_types, $proposal_info->commission_type ?? "percent", "class='select2' id='commission_type'"); ?>
                </div>
                <label for="commission_value" class="col-md-3"><?php echo app_lang('proposals_commission_value'); ?></label>
                <div class="col-md-3">
                    <input type="text" id="commission_value" name="commission_value" class="form-control" value="<?php echo esc(number_format((float)($proposal_info->commission_value ?? 0), 2, ",", ".")); ?>" />
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label class="col-md-3"><?php echo app_lang('taxes'); ?></label>
                <div class="col-md-9">
                    <div class="row">
                        <div class="col-md-6">
                            <label for="tax_product_percent" class="small text-off"><?php echo app_lang('proposals_tax_product_percent'); ?></label>
                            <input type="text" id="tax_product_percent" name="tax_product_percent" class="form-control" value="<?php echo esc(number_format((float)($proposal_info->tax_product_percent ?? 0), 2, ",", ".")); ?>" />
                        </div>
                        <div class="col-md-6">
                            <label for="tax_service_percent" class="small text-off"><?php echo app_lang('proposals_tax_service_percent'); ?></label>
                            <input type="text" id="tax_service_percent" name="tax_service_percent" class="form-control" value="<?php echo esc(number_format((float)($proposal_info->tax_service_percent ?? 0), 2, ",", ".")); ?>" />
                        </div>
                    </div>
                    <div class="form-check mt10">
                        <input type="checkbox" class="form-check-input" id="tax_service_only" name="tax_service_only" value="1" <?php echo !empty($proposal_info->tax_service_only) ? "checked" : ""; ?> />
                        <label class="form-check-label" for="tax_service_only"><?php echo app_lang('proposals_tax_service_only'); ?></label>
                    </div>
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
                location.reload();
            }
        });
    });
</script>
