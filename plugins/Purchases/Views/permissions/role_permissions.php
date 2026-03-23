<?php
$purchases_view = get_array_value($permissions, "purchases_view");
$purchases_manage = get_array_value($permissions, "purchases_manage");
$purchases_approve = get_array_value($permissions, "purchases_approve");
$purchases_financial_approve = get_array_value($permissions, "purchases_financial_approve");
$purchases_financial_limit = get_array_value($permissions, "purchases_financial_limit");
$purchases_financial_limit = $purchases_financial_limit !== "" && $purchases_financial_limit !== null
    ? number_format((float)$purchases_financial_limit, 2, get_setting("decimal_separator"), get_setting("thousand_separator"))
    : "";
?>

<li>
    <span data-feather="key" class="icon-14 ml-20"></span>
    <h5><?php echo app_lang("purchases_permissions"); ?></h5>
    <div>
        <?php
        echo form_checkbox("purchases_view", "1", $purchases_view ? true : false, "id='purchases_view' class='form-check-input'");
        ?>
        <label for="purchases_view"><?php echo app_lang("purchases_view_permission"); ?></label>
    </div>
    <div>
        <?php
        echo form_checkbox("purchases_manage", "1", $purchases_manage ? true : false, "id='purchases_manage' class='form-check-input'");
        ?>
        <label for="purchases_manage"><?php echo app_lang("purchases_manage_permission"); ?></label>
    </div>
    <div>
        <?php
        echo form_checkbox("purchases_approve", "1", $purchases_approve ? true : false, "id='purchases_approve' class='form-check-input'");
        ?>
        <label for="purchases_approve"><?php echo app_lang("purchases_approve_permission"); ?></label>
    </div>
    <div>
        <?php
        echo form_checkbox("purchases_financial_approve", "1", $purchases_financial_approve ? true : false, "id='purchases_financial_approve' class='form-check-input'");
        ?>
        <label for="purchases_financial_approve"><?php echo app_lang("purchases_financial_approve_permission"); ?></label>
    </div>
    <div id="purchases-financial-limit-wrapper" class="mt5" style="<?php echo $purchases_financial_approve ? '' : 'display:none;'; ?>">
        <label for="purchases_financial_limit" class="d-block mb5"><?php echo app_lang("purchases_financial_limit"); ?></label>
        <input type="text" id="purchases_financial_limit" name="purchases_financial_limit" value="<?php echo esc($purchases_financial_limit); ?>" class="form-control w200 js-currency-field" />
    </div>
</li>

<script type="text/javascript">
    $(document).ready(function () {
        var $checkbox = $("#purchases_financial_approve");
        var $wrapper = $("#purchases-financial-limit-wrapper");
        var $input = $("#purchases_financial_limit");

        var formatCurrencyField = function ($field) {
            if (typeof toCurrency !== "function" || typeof unformatCurrency !== "function") {
                return;
            }

            var value = $field.val();
            if (value === "") {
                return;
            }

            var numeric = unformatCurrency(value);
            if (isNaN(numeric)) {
                return;
            }

            $field.val(toCurrency(numeric));
        };

        $checkbox.on("change", function () {
            if (this.checked) {
                $wrapper.show();
                formatCurrencyField($input);
            } else {
                $wrapper.hide();
                $input.val("");
            }
        });

        $input.on("input", function () {
            var $field = $(this);
            if ($field.data("formatting")) {
                return;
            }
            $field.data("formatting", true);
            if (typeof toCurrency === "function") {
                var raw = $field.val();
                var digits = raw.replace(/\D/g, "");
                if (!digits.length) {
                    $field.val("");
                } else {
                    var numeric = parseInt(digits, 10) / 100;
                    $field.val(toCurrency(numeric));
                }
            }
            $field.data("formatting", false);

            var el = this;
            if (el.setSelectionRange) {
                var len = $field.val().length;
                el.setSelectionRange(len, len);
            }
        });

        formatCurrencyField($input);
    });
</script>
