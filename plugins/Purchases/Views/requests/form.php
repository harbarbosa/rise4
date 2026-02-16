<?php
$info = $request_info;
$is_edit = !empty($info->id);
$items_dropdown_list = $items_dropdown_list ?? array('' => '-');
$items_options_html = '';
foreach ($items_dropdown_list as $key => $value) {
    $items_options_html .= "<option value='" . esc($key) . "'>" . esc($value) . "</option>";
}
?>

<?php echo form_open(get_uri('purchases_requests/save'), array('id' => 'purchases-request-form', 'class' => 'general-form', 'role' => 'form')); ?>
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
                <?php if ($is_edit) { ?>
                <div class="col-md-4 mb15">
                    <label for="status" class="form-label"><?php echo app_lang('status'); ?></label>
                    <?php
                    $status_keys = $status_keys ?? array();
                    $status_options = array();
                    foreach ($status_keys as $status_key) {
                        $status_options[$status_key] = app_lang('purchases_status_' . $status_key);
                    }
                    echo form_dropdown('status', $status_options, $info->status, "class='select2' id='status'");
                    ?>
                </div>
                <?php } ?>
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
                    <button type="button" id="add-item-row" class="btn btn-default">
                        <i data-feather='plus-circle' class='icon-16'></i> <?php echo app_lang('purchases_add_item'); ?>
                    </button>
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
                });
            }\r\n        });\r\n\r\n        window.purchasesRemoveItemRow = function (button) {
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



