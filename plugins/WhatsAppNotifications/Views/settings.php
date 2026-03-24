<div class="card">
    <div class="card-header">
        <h4 class="card-title">WhatsApp Notifications</h4>
        <span class="text-muted">Envia notificacoes do RISE para o gateway WhatsApp usando o fluxo nativo do CRM.</span>
    </div>
    <div class="card-body">
        <?php echo form_open(get_uri("whatsapp_notifications_settings/save"), array("id" => "whatsapp-notifications-form", "class" => "general-form", "role" => "form")); ?>
        <div class="form-group">
            <div class="form-check form-switch">
                <input type="checkbox" class="form-check-input" id="enabled" name="enabled" value="1" <?php echo $enabled ? "checked" : ""; ?>>
                <label class="form-check-label" for="enabled">Habilitar envio via WhatsApp</label>
            </div>
        </div>

        <div class="form-group">
            <label for="api_url">URL da API</label>
            <input type="text" id="api_url" name="api_url" value="<?php echo $api_url ? $api_url : "http://129.121.46.105:3001/api/messages/send"; ?>" class="form-control" autocomplete="off">
            <small class="text-muted">Configuracao salva em <code>whatsapp.apiUrl</code>.</small>
        </div>

        <div class="form-group">
            <label for="token">Token</label>
            <input type="text" id="token" name="token" value="<?php echo $token; ?>" class="form-control" autocomplete="off">
            <small class="text-muted">Enviado como <code>Authorization: Bearer ...</code> e salvo em <code>whatsapp.token</code>.</small>
        </div>

        <div class="form-group">
            <label for="instance_id">School ID / Sessao</label>
            <input type="text" id="instance_id" name="instance_id" value="<?php echo $instance_id; ?>" class="form-control" autocomplete="off">
            <small class="text-muted">Usado nas rotas de sessao e enviado no body como <code>school_id</code>. Padrao atual: <code>260687</code>.</small>
        </div>

        <div class="alert alert-info">
            O plugin envia para os mesmos usuarios gravados na notificacao nativa do RISE, priorizando o campo <code>whatsapp</code> e usando <code>phone</code> como fallback.
        </div>

        <div class="card bg-light border-0 mb15">
            <div class="card-body">
                <div class="d-flex gap-2 flex-wrap mb15">
                    <button type="button" class="btn btn-success" id="wa-connect-session">Conectar / Gerar QR</button>
                    <button type="button" class="btn btn-outline-secondary" id="wa-refresh-status">Atualizar status</button>
                    <button type="button" class="btn btn-outline-danger" id="wa-disconnect-session">Desconectar</button>
                </div>

                <div class="mb10">
                    <strong>Status:</strong> <span id="wa-session-status" class="badge bg-secondary">desconhecido</span>
                </div>
                <div id="wa-session-error" class="text-danger mb10"></div>
                <div id="wa-qr-wrapper" class="hide">
                    <div class="mb10"><strong>QR Code:</strong></div>
                    <img id="wa-qr-image" src="" alt="QR Code do WhatsApp" style="max-width:320px; width:100%; height:auto; border:1px solid #ddd; padding:8px; background:#fff;">
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-primary"><?php echo app_lang('save'); ?></button>
        <?php echo form_close(); ?>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function () {
        var $status = $("#wa-session-status");
        var $error = $("#wa-session-error");
        var $qrWrapper = $("#wa-qr-wrapper");
        var $qrImage = $("#wa-qr-image");
        var pollTimeout = null;

        function setStatus(status) {
            status = status || "desconhecido";
            var badgeClass = "bg-secondary";

            if (status === "connected") {
                badgeClass = "bg-success";
            } else if (status === "qr") {
                badgeClass = "bg-warning";
            } else if (status === "error") {
                badgeClass = "bg-danger";
            } else if (status === "initializing") {
                badgeClass = "bg-info";
            }

            $status.attr("class", "badge " + badgeClass).text(status);
        }

        function clearQr() {
            $qrImage.attr("src", "");
            $qrWrapper.addClass("hide");
        }

        function loadQr() {
            $.get("<?php echo get_uri('whatsapp_notifications_settings/session_qr'); ?>", function (response) {
                if (response && response.success && response.qr) {
                    $qrImage.attr("src", response.qr);
                    $qrWrapper.removeClass("hide");
                }
            });
        }

        function schedulePoll() {
            clearTimeout(pollTimeout);
            pollTimeout = setTimeout(refreshStatus, 5000);
        }

        function refreshStatus() {
            $.get("<?php echo get_uri('whatsapp_notifications_settings/session_status'); ?>", function (response) {
                $error.text("");

                if (!response || response.success === false) {
                    setStatus("error");
                    $error.text(response && response.error ? response.error : "Falha ao consultar status.");
                    return;
                }

                setStatus(response.status);

                if (response.error) {
                    $error.text(response.error);
                }

                if (response.status === "qr" || response.has_qr) {
                    loadQr();
                    schedulePoll();
                } else if (response.status === "initializing") {
                    clearQr();
                    schedulePoll();
                } else if (response.status === "connected") {
                    clearQr();
                } else {
                    clearQr();
                }
            }).fail(function (xhr) {
                setStatus("error");
                $error.text("Falha ao consultar status: " + xhr.status + " " + xhr.statusText);
            });
        }

        $("#wa-connect-session").on("click", function () {
            $error.text("");
            setStatus("initializing");

            $.post("<?php echo get_uri('whatsapp_notifications_settings/connect_session'); ?>", function (response) {
                if (response && response.success) {
                    setStatus(response.status);
                    refreshStatus();
                    return;
                }

                setStatus("error");
                $error.text(response && response.error ? response.error : "Falha ao iniciar sessao.");
            }).fail(function (xhr) {
                setStatus("error");
                $error.text("Falha ao iniciar sessao: " + xhr.status + " " + xhr.statusText);
            });
        });

        $("#wa-refresh-status").on("click", function () {
            refreshStatus();
        });

        $("#wa-disconnect-session").on("click", function () {
            $error.text("");

            $.post("<?php echo get_uri('whatsapp_notifications_settings/disconnect_session'); ?>", function (response) {
                if (response && response.success) {
                    clearTimeout(pollTimeout);
                    clearQr();
                    setStatus(response.status || "disconnected");
                    return;
                }

                setStatus("error");
                $error.text(response && response.error ? response.error : "Falha ao desconectar sessao.");
            }).fail(function (xhr) {
                setStatus("error");
                $error.text("Falha ao desconectar sessao: " + xhr.status + " " + xhr.statusText);
            });
        });

        refreshStatus();
    });
</script>
