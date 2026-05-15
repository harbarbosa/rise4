<?php echo form_open(get_uri("rise_plugins/stage_update/$plugin_name"), array("id" => "plugin-update-form", "class" => "general-form", "role" => "form")); ?>
<div class="modal-body clearfix">
    <div class="container-fluid">
        <div class="alert alert-info">
            A atualização sera baixada do GitHub e aplicada de forma staged no proximo carregamento do sistema.
        </div>

        <div class="table-responsive">
            <table class="table table-bordered">
                <tbody>
                    <tr>
                        <th style="width: 35%;">Plugin</th>
                        <td><?php echo esc(get_array_value($plugin_info, 'plugin_name') ?: $plugin_name); ?></td>
                    </tr>
                    <tr>
                        <th>Versao local</th>
                        <td><?php echo esc(get_array_value($update_info, 'local_version') ?: '-'); ?></td>
                    </tr>
                    <tr>
                        <th>Versao remota</th>
                        <td><?php echo esc(get_array_value($update_info, 'remote_version') ?: '-'); ?></td>
                    </tr>
                    <tr>
                        <th>Release tag</th>
                        <td><?php echo esc(get_array_value($update_info, 'release_tag') ?: '-'); ?></td>
                    </tr>
                    <tr>
                        <th>Repositorio</th>
                        <td><?php echo esc(get_array_value($update_info, 'repository_url') ?: '-'); ?></td>
                    </tr>
                    <tr>
                        <th>Manifest</th>
                        <td><?php echo esc(get_array_value($update_info, 'manifest_url') ?: '-'); ?></td>
                    </tr>
                    <tr>
                        <th>Pacote</th>
                        <td><?php echo esc(get_array_value($update_info, 'zip_url') ?: '-'); ?></td>
                    </tr>
                    <?php if (get_array_value($update_info, 'notes')) { ?>
                        <tr>
                            <th>Notas</th>
                            <td><?php echo nl2br(esc(get_array_value($update_info, 'notes'))); ?></td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>

        <?php if (!empty($update_info->message)) { ?>
            <div class="alert alert-warning mtop20">
                <?php echo esc($update_info->message); ?>
            </div>
        <?php } ?>
    </div>
</div>

<div class="modal-footer">
    <button type="button" class="btn btn-default cancel-upload" data-bs-dismiss="modal">
        <span data-feather="x" class="icon-16"></span> <?php echo app_lang('close'); ?>
    </button>
    <button type="submit" class="btn btn-primary start-upload" <?php echo empty($can_stage_update) ? 'disabled="disabled"' : ''; ?>>
        <span data-feather="refresh-cw" class="icon-16"></span> Preparar atualizacao
    </button>
</div>
<?php echo form_close(); ?>

<script type="text/javascript">
    $(document).ready(function () {
        $("#plugin-update-form").appForm({
            onSuccess: function (result) {
                if (result.success) {
                    $("#plugin-table").appTable({reload: true});
                }
            }
        });
    });
</script>
