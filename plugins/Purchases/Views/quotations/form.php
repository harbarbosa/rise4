<?php
$info = $quotation_info;
$items_dropdown_list = $items_dropdown_list ?? array('' => '-');
$suppliers_dropdown = $suppliers_dropdown ?? array();
$schema_ready = isset($schema_ready) ? (bool) $schema_ready : true;
$schema_warning = $schema_warning ?? '';
?>

<?php echo form_open(get_uri('purchases_quotations/save'), array('id' => 'purchases-quotation-form', 'class' => 'general-form')); ?>

<div id="page-content" class="page-wrapper clearfix">
    <div class="card">
        <div class="page-title clearfix">
            <h1><?php echo app_lang('purchases_add_standalone_quotation'); ?></h1>
            <div class="title-button-group">
                <?php echo anchor(get_uri('purchases_quotations'), app_lang('back_to_list'), array('class' => 'btn btn-default')); ?>
            </div>
        </div>
        <div class="card-body">
            <?php if (!$schema_ready && $schema_warning) { ?>
                <div class="alert alert-warning"><?php echo esc($schema_warning); ?></div>
            <?php } ?>
            <div class="row">
                <div class="col-md-4 mb15">
                    <label class="form-label"><?php echo app_lang('purchases_quotation_code'); ?></label>
                    <input type="text" class="form-control" value="<?php echo esc($info->quotation_code ? $info->quotation_code : '-'); ?>" readonly />
                </div>
                <div class="col-md-8 mb15">
                    <label for="title" class="form-label"><?php echo app_lang('purchases_quotation_title'); ?></label>
                    <input type="text" id="title" name="title" class="form-control" value="<?php echo esc($info->title); ?>" <?php echo $schema_ready ? '' : 'disabled'; ?> />
                </div>
            </div>

            <div class="row">
                <div class="col-md-12 mb15">
                    <label for="supplier_ids" class="form-label"><?php echo app_lang('purchases_suppliers'); ?></label>
                    <select name="supplier_ids[]" id="supplier_ids" class="form-control select2" multiple data-placeholder="<?php echo app_lang('purchases_suppliers'); ?>" <?php echo $schema_ready ? '' : 'disabled'; ?>>
                        <?php foreach ($suppliers_dropdown as $supplier_id => $supplier_name) { ?>
                            <option value="<?php echo esc($supplier_id); ?>"><?php echo esc($supplier_name); ?></option>
                        <?php } ?>
                    </select>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12 mb15">
                    <label for="note" class="form-label"><?php echo app_lang('purchases_note'); ?></label>
                    <textarea id="note" name="note" class="form-control" rows="2" <?php echo $schema_ready ? '' : 'disabled'; ?>><?php echo esc($info->note); ?></textarea>
                </div>
            </div>

            <div class="mt15">
                <div class="d-flex justify-content-between align-items-center mb10">
                    <h4 class="mb0"><?php echo app_lang('purchases_items'); ?></h4>
                    <button type="button" id="add-item-row" class="btn btn-default" <?php echo $schema_ready ? '' : 'disabled'; ?>>
                        <i data-feather='plus-circle' class='icon-16'></i> <?php echo app_lang('purchases_add_item'); ?>
                    </button>
                </div>
                <div class="table-responsive">
                    <table class="table" id="quotation-items-table">
                        <thead>
                            <tr>
                                <th style="width: 18%;"><?php echo app_lang('purchases_material'); ?></th>
                                <th style="width: 24%;"><?php echo app_lang('purchases_item_description'); ?></th>
                                <th style="width: 10%;"><?php echo app_lang('purchases_qty'); ?></th>
                                <th style="width: 10%;"><?php echo app_lang('purchases_unit'); ?></th>
                                <th style="width: 15%;"><?php echo app_lang('purchases_desired_date'); ?></th>
                                <th><?php echo app_lang('purchases_note'); ?></th>
                                <th style="width: 56px;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><?php echo form_dropdown("item_id[]", $items_dropdown_list, "", "class='form-control quotation-item-select' " . ($schema_ready ? '' : 'disabled')); ?></td>
                                <td><input type="text" name="description[]" class="form-control" <?php echo $schema_ready ? '' : 'disabled'; ?> /></td>
                                <td><input type="text" name="quantity[]" class="form-control text-right" value="1" <?php echo $schema_ready ? '' : 'disabled'; ?> /></td>
                                <td><input type="text" name="unit[]" class="form-control" value="UN" <?php echo $schema_ready ? '' : 'disabled'; ?> /></td>
                                <td><input type="date" name="desired_date[]" class="form-control" <?php echo $schema_ready ? '' : 'disabled'; ?> /></td>
                                <td><input type="text" name="item_note[]" class="form-control" <?php echo $schema_ready ? '' : 'disabled'; ?> /></td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-default btn-sm remove-item" <?php echo $schema_ready ? '' : 'disabled'; ?>><i data-feather='x' class='icon-16'></i></button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt15">
                <button type="submit" class="btn btn-primary" <?php echo $schema_ready ? '' : 'disabled'; ?>><i data-feather='check-circle' class='icon-16'></i> <?php echo app_lang('save'); ?></button>
            </div>
        </div>
    </div>
</div>

<?php echo form_close(); ?>

<script type="text/javascript">
    $(document).ready(function () {
        <?php if (!$schema_ready) { ?>
        return;
        <?php } ?>
        var optionsHtml = <?php echo json_encode(implode('', array_map(function ($value, $label) {
            return "<option value='" . esc($value) . "'>" . esc($label) . "</option>";
        }, array_keys($items_dropdown_list), $items_dropdown_list))); ?>;

        function initItemSelect($el) {
            $el.select2({width: "100%"});
        }

        function addRow() {
            var rowHtml = "<tr>" +
                "<td><select name='item_id[]' class='form-control quotation-item-select'>" + optionsHtml + "</select></td>" +
                "<td><input type='text' name='description[]' class='form-control' /></td>" +
                "<td><input type='text' name='quantity[]' class='form-control text-right' value='1' /></td>" +
                "<td><input type='text' name='unit[]' class='form-control' value='UN' /></td>" +
                "<td><input type='date' name='desired_date[]' class='form-control' /></td>" +
                "<td><input type='text' name='item_note[]' class='form-control' /></td>" +
                "<td class='text-center'><button type='button' class='btn btn-default btn-sm remove-item'><i data-feather='x' class='icon-16'></i></button></td>" +
                "</tr>";
            var $row = $(rowHtml);
            $("#quotation-items-table tbody").append($row);
            initItemSelect($row.find(".quotation-item-select"));
            feather.replace();
        }

        initItemSelect($(".quotation-item-select"));
        $("#supplier_ids").select2({width: "100%"});

        $("#add-item-row").on("click", function () {
            addRow();
        });

        $(document).on("click", ".remove-item", function () {
            if ($("#quotation-items-table tbody tr").length === 1) {
                $("#quotation-items-table tbody tr").find("input").val("");
                $("#quotation-items-table tbody tr").find("select").val("").trigger("change");
                return;
            }
            $(this).closest("tr").remove();
        });

        $("#purchases-quotation-form").appForm({
            onSuccess: function (result) {
                if (result.redirect) {
                    window.location = result.redirect;
                }
            }
        });
    });
</script>
