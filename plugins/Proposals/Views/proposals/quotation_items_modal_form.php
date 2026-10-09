<?php
$proposal_id = (int)($proposal_id ?? 0);
$items = $items ?? array();
?>

<div class="modal-dialog modal-lg">
    <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title">Selecionar itens para cotação</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?php echo app_lang('close'); ?>"></button>
        </div>

        <?php echo form_open('propostas/send_memory_to_quotation', array('id' => 'proposal-quotation-items-form', 'class' => 'dialog-form')); ?>
        <input type="hidden" name="proposal_id" value="<?php echo $proposal_id; ?>">

        <div class="modal-body">
            <div class="d-flex justify-content-between align-items-center mb15">
                <span class="text-muted">Marque os materiais que deseja enviar para a cotação.</span>
                <label class="mb0">
                    <input type="checkbox" id="proposal-quotation-select-all" checked>
                    Selecionar todos
                </label>
            </div>

            <?php if (count($items)) { ?>
                <div class="table-responsive" style="max-height: 460px; overflow-y: auto;">
                    <table class="table table-hover table-bordered mb0">
                        <thead>
                            <tr>
                                <th class="text-center" style="width: 55px;"></th>
                                <th>Material</th>
                                <th class="text-end" style="width: 130px;"><?php echo app_lang('quantity'); ?></th>
                                <th style="width: 100px;"><?php echo app_lang('unit'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $item) { ?>
                                <tr>
                                    <td class="text-center">
                                        <input type="checkbox"
                                               class="proposal-quotation-item"
                                               name="selected_items[]"
                                               value="<?php echo esc($item['selection_key']); ?>"
                                               checked>
                                    </td>
                                    <td style="white-space: normal; word-break: break-word;"><?php echo esc($item['description']); ?></td>
                                    <td class="text-end"><?php echo rtrim(rtrim(number_format((float)$item['quantity'], 4, ',', '.'), '0'), ','); ?></td>
                                    <td><?php echo esc($item['unit'] ?: 'UN'); ?></td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            <?php } else { ?>
                <div class="alert alert-warning mb0"><?php echo app_lang('proposals_no_proposal_items'); ?></div>
            <?php } ?>
        </div>

        <div class="modal-footer">
            <button type="button" class="btn btn-default" data-bs-dismiss="modal"><?php echo app_lang('close'); ?></button>
            <button type="submit" class="btn btn-success" <?php echo count($items) ? '' : 'disabled'; ?>>
                <i data-feather="send" class="icon-16"></i> Enviar itens selecionados
            </button>
        </div>
        <?php echo form_close(); ?>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function () {
        var $form = $("#proposal-quotation-items-form");
        var $items = $form.find(".proposal-quotation-item");
        var $selectAll = $("#proposal-quotation-select-all");

        $selectAll.on("change", function () {
            $items.prop("checked", this.checked);
        });

        $items.on("change", function () {
            $selectAll.prop("checked", $items.length === $items.filter(":checked").length);
        });

        $form.on("submit", function (e) {
            e.preventDefault();

            if (!$items.filter(":checked").length) {
                appAlert.error("Selecione pelo menos um item para enviar à cotação.");
                return;
            }

            var $submit = $form.find('[type="submit"]');
            $submit.prop("disabled", true);

            appAjaxRequest({
                url: $form.attr("action"),
                type: "POST",
                dataType: "json",
                data: $form.serialize(),
                success: function (result) {
                    if (result && result.success && result.redirect) {
                        $("#app-modal").modal("hide");
                        window.location.href = result.redirect;
                        return;
                    }

                    appAlert.error((result && result.message) || <?php echo json_encode(app_lang('error_occurred')); ?>);
                    $submit.prop("disabled", false);
                },
                error: function () {
                    appAlert.error(<?php echo json_encode(app_lang('error_occurred')); ?>);
                    $submit.prop("disabled", false);
                }
            });
        });
    });
</script>
