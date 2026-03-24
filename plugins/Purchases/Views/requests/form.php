<?php
$info = $request_info;
$is_edit = !empty($info->id);
$items_dropdown_list = $items_dropdown_list ?? array('' => '-');
$items_options_html = '';
foreach ($items_dropdown_list as $key => $value) {
    $items_options_html .= "<option value='" . esc($key) . "'>" . esc($value) . "</option>";
}
?>

<?php echo form_open_multipart(get_uri('purchases_requests/save'), array('id' => 'purchases-request-form', 'class' => 'general-form', 'role' => 'form')); ?>
<input type="hidden" name="id" value="<?php echo esc($info->id); ?>" />

<div id="page-content" class="page-wrapper clearfix">
    <div class="card">
        <div class="page-title clearfix">
            <h1><?php echo $is_edit ? app_lang('purchases_edit_request') : app_lang('purchases_add_request'); ?></h1>
            <div class="title-button-group">
                <?php echo anchor(get_uri('purchases_requests'), app_lang('back_to_list'), array('class' => 'btn btn-default')); ?>
            </div>
        </div>
        <div class="card-body">
            <?php if (session()->getFlashdata('error')) { ?>
                <div class="alert alert-danger"><?php echo esc(session()->getFlashdata('error')); ?></div>
            <?php } ?>
            <div class="row">
                <div class="col-md-3 mb15">
                    <label for="request_code" class="form-label"><?php echo app_lang('purchases_request_code'); ?></label>
                    <input type="text" id="request_code" class="form-control" value="<?php echo esc($info->request_code ? $info->request_code : '-'); ?>" readonly />
                </div>
                <div class="col-md-3 mb15">
                    <label for="project_id" class="form-label"><?php echo app_lang('project'); ?></label>
                    <?php echo form_dropdown('project_id', $projects_dropdown, $info->project_id, "class='select2' id='project_id'"); ?>
                </div>
                <div class="col-md-3 mb15">
                    <label for="os_id" class="form-label"><?php echo app_lang('purchases_os'); ?></label>
                    <?php echo form_dropdown('os_id', $os_dropdown, $info->os_id, "class='select2' id='os_id'"); ?>
                </div>
            </div>
            <div class="row">
                <div class="col-md-4 mb15">
                    <label for="cost_center" class="form-label"><?php echo app_lang('purchases_cost_center'); ?></label>
                    <input type="text" id="cost_center" name="cost_center" value="<?php echo esc($info->cost_center); ?>" class="form-control" />
                </div>
                <div class="col-md-4 mb15">
                    <label for="priority" class="form-label"><?php echo app_lang('purchases_priority'); ?></label>
                    <?php
                    $priority_options = array(
                        'low' => app_lang('purchases_priority_low'),
                        'medium' => app_lang('purchases_priority_medium'),
                        'high' => app_lang('purchases_priority_high')
                    );
                    echo form_dropdown('priority', $priority_options, $info->priority ? $info->priority : 'medium', "class='select2' id='priority'");
                    ?>
                </div>
                <div class="col-md-4 mb15">
                    <label for="is_internal" class="form-label"><?php echo app_lang('purchases_internal'); ?></label>
                    <div>
                        <?php echo form_checkbox("is_internal", "1", $info->is_internal ? true : false, "id='is_internal' class='form-check-input'"); ?>
                        <span class="ms-1"><?php echo app_lang('purchases_internal_hint'); ?></span>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12 mb15">
                    <label for="note" class="form-label"><?php echo app_lang('purchases_note'); ?></label>
                    <textarea id="note" name="note_header" class="form-control" rows="2"><?php echo esc($info->note); ?></textarea>
                </div>
            </div>

            <div class="mt15">
                <div class="d-flex justify-content-between align-items-center mb10">
                    <h4 class="mb0"><?php echo app_lang('purchases_items'); ?></h4>
                    <div class="d-flex gap-2">
                        <?php echo anchor(get_uri('purchases_requests/download_items_template'), "<i data-feather='download' class='icon-16'></i> " . app_lang('purchases_download_import_template'), array('class' => 'btn btn-default')); ?>
                        <button type="button" id="add-item-row" class="btn btn-default">
                            <i data-feather='plus-circle' class='icon-16'></i> <?php echo app_lang('purchases_add_item'); ?>
                        </button>
                    </div>
                </div>
                <div class="row mb15">
                    <div class="col-md-6">
                        <label for="items_import_file" class="form-label"><?php echo app_lang('purchases_import_items_excel'); ?></label>
                        <input type="file" id="items_import_file" name="items_import_file" class="form-control" accept=".csv,text/csv,application/vnd.ms-excel" />
                        <small class="text-muted"><?php echo app_lang('purchases_import_items_excel_help'); ?></small>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table" id="purchases-items-table">
                        <thead>
                            <tr>
                                <th style="width: 18%;"><?php echo app_lang('purchases_material'); ?></th>
                                <th style="width: 22%;"><?php echo app_lang('purchases_item_description'); ?></th>
                                <th style="width: 10%;"><?php echo app_lang('purchases_qty'); ?></th>
                                <th style="width: 10%;"><?php echo app_lang('purchases_unit'); ?></th>
                                <th style="width: 15%;"><?php echo app_lang('purchases_desired_date'); ?></th>
                                <th><?php echo app_lang('purchases_note'); ?></th>
                                <th style="width: 50px;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($request_items)) { ?>
                                <?php foreach ($request_items as $item) { ?>
                                    <tr>
                                        <td>
                                            <?php echo form_dropdown("item_id[]", $items_dropdown_list, $item->item_id, "class='form-control item-select'"); ?>
                                        </td>
                                        <td>
                                            <input type="text" name="description[]" class="form-control" value="<?php echo esc($item->description); ?>" />
                                        </td>
                                        <td>
                                            <input type="text" name="quantity[]" class="form-control text-right" value="<?php echo esc(to_decimal_format($item->quantity)); ?>" />
                                        </td>
                                        <td>
                                            <input type="text" name="unit[]" class="form-control" value="<?php echo esc($item->unit ? $item->unit : $item->item_unit); ?>" />
                                        </td>
                                        <td>
                                            <input type="date" name="desired_date[]" class="form-control" value="<?php echo esc($item->desired_date); ?>" required />
                                        </td>
                                        <td>
                                            <input type="text" name="note[]" class="form-control" value="<?php echo esc($item->note); ?>" />
                                        </td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-default btn-sm remove-item" onclick="if (window.purchasesRemoveItemRow) { window.purchasesRemoveItemRow(this); } return false;"><i data-feather='x' class='icon-16'></i></button>
                                        </td>
                                    </tr>
                                <?php } ?>
                            <?php } else { ?>
                                <tr>
                                    <td>
                                        <?php echo form_dropdown("item_id[]", $items_dropdown_list, "", "class='form-control item-select'"); ?>
                                    </td>
                                    <td>
                                        <input type="text" name="description[]" class="form-control" />
                                    </td>
                                    <td>
                                        <input type="text" name="quantity[]" class="form-control text-right" value="1" />
                                    </td>
                                    <td>
                                        <input type="text" name="unit[]" class="form-control" value="UN" />
                                    </td>
                                    <td>
                                        <input type="date" name="desired_date[]" class="form-control" required />
                                    </td>
                                    <td>
                                        <input type="text" name="note[]" class="form-control" />
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-default btn-sm remove-item" onclick="if (window.purchasesRemoveItemRow) { window.purchasesRemoveItemRow(this); } return false;"><i data-feather='x' class='icon-16'></i></button>
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt15">
                <button type="submit" class="btn btn-primary"><i data-feather='check-circle' class='icon-16'></i> <?php echo app_lang('save'); ?></button>
            </div>
        </div>
    </div>
</div>

<?php echo form_close(); ?>

<script type="text/javascript">
    (function () {
        window.purchasesInitItemSelect = function ($select) {
            if (!$select || !$select.length) {
                return;
            }
            $select.select2({
                width: '100%',
                
                placeholder: <?php echo json_encode(app_lang('purchases_material')); ?>,
                allowClear: true,
                language: {
                    noResults: function () {
                        return <?php echo json_encode(app_lang('purchases_material_not_found')); ?>;
                    }
                }
            });

            var updateDescriptionFromSelect = function ($select) {
                var $row = $select.closest('tr');
                var $desc = $row.find("input[name='description[]']");
                var selectedData = $select.select2("data");
                var selectedText = selectedData && selectedData.length ? selectedData[0].text : $select.find("option:selected").text();
                if (selectedText && selectedText !== "-" && selectedText !== "+") {
                    $desc.val(selectedText);
                }
            };

            $select.on('change', function () {
                var itemId = $(this).val();
                var $row = $(this).closest('tr');
                var $desc = $row.find("input[name='description[]']");
                updateDescriptionFromSelect($(this));
                if (!itemId || itemId === '+') {
                    return;
                }
                $.ajax({
                    url: '<?php echo_uri("purchases_requests/get_item_info_suggestion") ?>',
                    type: 'POST',
                    dataType: 'json',
                    data: {item_id: itemId},
                    success: function (result) {
                        if (result && result.success && result.item_info) {
                            var info = result.item_info;
                            var $unit = $row.find("input[name='unit[]']");
                            $desc.val(info.title || '');
                            if (!$unit.val()) {
                                $unit.val(info.unit_type || 'UN');
                            }
                        }
                    }
                });
            });

            $select.on('select2:select', function (e) {
                var $row = $(this).closest('tr');
                var $desc = $row.find("input[name='description[]']");
                var selectedText = e && e.params && e.params.data ? e.params.data.text : '';
                if (selectedText && selectedText !== "-" && selectedText !== "+") {
                    $desc.val(selectedText);
                } else {
                    updateDescriptionFromSelect($(this));
                }
            });
        };

        window.purchasesAddItemRow = function () {
            var $tbody = $("#purchases-items-table tbody");
            var itemsOptionsHtml = <?php echo json_encode($items_options_html); ?>;
            var rowHtml = "<tr>" +
                "<td><select name='item_id[]' class='form-control item-select'>" + itemsOptionsHtml + "</select></td>" +
                "<td><input type='text' name='description[]' class='form-control' /></td>" +
                "<td><input type='text' name='quantity[]' class='form-control text-right' value='1' /></td>" +
                "<td><input type='text' name='unit[]' class='form-control' value='UN' /></td>" +
                "<td><input type='date' name='desired_date[]' class='form-control' required /></td>" +
                "<td><input type='text' name='note[]' class='form-control' /></td>" +
                "<td class='text-center'><button type='button' class='btn btn-default btn-sm remove-item' onclick=\"if (window.purchasesRemoveItemRow) { window.purchasesRemoveItemRow(this); } return false;\"><i data-feather='x' class='icon-16'></i></button></td>" +
                "</tr>";
            var $row = $(rowHtml);
            $tbody.append($row);
            window.purchasesInitItemSelect($row.find('.item-select'));
            if (typeof feather !== "undefined") {
                feather.replace();
            }
        };

        $(document).ready(function () {
            $(".select2").not(".item-select").select2();
            $(".item-select").each(function () { window.purchasesInitItemSelect($(this)); });

            window.purchasesClearSelect = function ($el) {
                if (!$el || !$el.length) {
                    return;
                }
                $el.val(null).trigger("change");
                $el.val("").trigger("change");
            };

            window.purchasesUpdateRequestContext = function () {
                var isInternal = $("#is_internal").is(":checked");
                var projectId = $("#project_id").val();
                var osId = $("#os_id").val();

                if (isInternal) {
                    window.purchasesClearSelect($("#project_id"));
                    window.purchasesClearSelect($("#os_id"));
                    $("#project_id").prop("disabled", true);
                    $("#os_id").prop("disabled", true);
                    return;
                }

                $("#project_id").prop("disabled", false);
                $("#os_id").prop("disabled", false);

                if (projectId) {
                    window.purchasesClearSelect($("#os_id"));
                } else if (osId) {
                    window.purchasesClearSelect($("#project_id"));
                }
            };

            $("#is_internal").on("change", function () {
                window.purchasesUpdateRequestContext();
            });

            $("#project_id").on("change", function () {
                if ($(this).val()) {
                    $("#is_internal").prop("checked", false);
                    window.purchasesClearSelect($("#os_id"));
                }
            });

            $("#os_id").on("change", function () {
                if ($(this).val()) {
                    $("#is_internal").prop("checked", false);
                    window.purchasesClearSelect($("#project_id"));
                }
            });

            window.purchasesUpdateRequestContext();

            $(document).on('click', '#add-item-row', function () {
                window.purchasesAddItemRow();
            });

            $(document).on('click', '.remove-item', function () {
                window.purchasesRemoveItemRow(this);
            });
        });

        window.purchasesRemoveItemRow = function (button) {
            var $row = $(button).closest('tr');
            if ($('#purchases-items-table tbody tr').length > 1) {
                $row.remove();
                return;
            }

            $row.find("input[type='text'], input[type='date'], textarea").val('');
            $row.find("select").val('').trigger('change');
            $row.find("input[name='quantity[]']").val('1');
            $row.find("input[name='unit[]']").val('UN');
        };
    })();
</script>
