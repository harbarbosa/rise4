<div class="card">
    <div class="card-header">
        <h4 class="card-title">Conta Azul - OAuth2</h4>
        <span class="text-muted">Configure credenciais, gere o link de autorização e conecte.</span>
    </div>
    <div class="card-body">
        <?php echo form_open(get_uri("contaazul/save"), array("id" => "contaazul-settings-form", "class" => "general-form", "role" => "form")); ?>
            <div class="form-group">
                <label for="client_id">Client ID</label>
                <input type="text" id="client_id" name="client_id" value="<?php echo isset($client_id) ? $client_id : ''; ?>" class="form-control" autocomplete="off">
            </div>
            <div class="form-group">
                <label for="client_secret">Client Secret</label>
                <input type="text" id="client_secret" name="client_secret" value="<?php echo isset($client_secret) ? $client_secret : ''; ?>" class="form-control" autocomplete="off">
            </div>
            <div class="form-group">
                <label for="redirect_uri">Redirect URI (cadastre no Conta Azul)</label>
                <input type="text" id="redirect_uri" name="redirect_uri" value="<?php echo isset($redirect_uri) ? $redirect_uri : ''; ?>" class="form-control" autocomplete="off">
                <small class="text-muted">Use este endereÇĖo no cadastro do aplicativo.</small>
            </div>
            <div class="form-group">
                <label for="scope">Scope</label>
                <input type="text" id="scope" name="scope" value="<?php echo isset($scope) ? $scope : ''; ?>" class="form-control" autocomplete="off">
            </div>
            <div class="form-group">
                <label for="access_token">Access Token</label>
                <input type="text" id="access_token" name="access_token" value="<?php echo $access_token; ?>" class="form-control" autocomplete="off">
            </div>
            <div class="form-group">
                <label for="refresh_token">Refresh Token</label>
                <input type="text" id="refresh_token" name="refresh_token" value="<?php echo $refresh_token; ?>" class="form-control" autocomplete="off">
            </div>
            <div class="form-group">
                <label for="expires_at">Expira em (timestamp ou data)</label>
                <input type="text" id="expires_at" name="expires_at" value="<?php echo $expires_at; ?>" class="form-control" autocomplete="off">
            </div>
            <div class="form-group">
                <label for="cron_key">Cron Key (para execuções GET contaazul/cron-import?key=...)</label>
                <input type="text" id="cron_key" name="cron_key" value="<?php echo isset($cron_key) ? $cron_key : ''; ?>" class="form-control" autocomplete="off">
                <small class="text-muted">Defina um valor secreto e use-o na URL do cron.</small>
            </div>
            <div class="form-group">
                <label for="cron_url">URL do Cron</label>
                <?php
                $cron_url = "";
                if (!empty($cron_key)) {
                    $cron_url = get_uri("contaazul/cron-import") . "?key=" . urlencode($cron_key);
                }
                ?>
                <input type="text" id="cron_url" value="<?php echo $cron_url; ?>" class="form-control" readonly>
                <small class="text-muted">Use esta URL no seu agendador (Cron).</small>
            </div>
            <div class="form-group">
                <label for="contaazul_cron">Comando de Cron (Conta Azul)</label>
                <?php
                $contaazul_cron = "";
                if (!empty($cron_key)) {
                    $contaazul_cron = "curl -s " . get_uri("contaazul/cron-import") . "?key=" . urlencode($cron_key);
                }
                ?>
                <input type="text" id="contaazul_cron" value="<?php echo $contaazul_cron; ?>" class="form-control" readonly>
                <small class="text-muted">Use este comando no seu agendador (Cron).</small>
            </div>
            <div class="mt-3">
                <button type="submit" class="btn btn-primary"><?php echo app_lang('save'); ?></button>
                <a href="<?php echo isset($authorize_url) ? $authorize_url : '#'; ?>" class="btn btn-success ms-2">Conectar Conta Azul</a>
                <button type="button" id="contaazul-import-clients" class="btn btn-secondary ms-2">Importar clientes (pessoas)</button>
                <button type="button" id="contaazul-import-items" class="btn btn-secondary ms-2">Importar produtos (items)</button>
                <button type="button" id="contaazul-import-general" class="btn btn-secondary ms-2">Importar cadastros gerais</button>
                <button type="button" id="contaazul-import-services" class="btn btn-secondary ms-2">Importar servicos</button>
                <button type="button" id="contaazul-import-cost-centers" class="btn btn-secondary ms-2">Importar centros de custo</button>
                <button type="button" id="contaazul-import-cost-center-transactions" class="btn btn-secondary ms-2">Importar lançamentos por centro de custo</button>
                <div id="contaazul-import-status" class="text-muted mt-2"></div>
                <div id="contaazul-import-items-status" class="text-muted mt-2"></div>
                <div id="contaazul-import-general-status" class="text-muted mt-2"></div>
                <div id="contaazul-import-services-status" class="text-muted mt-2"></div>
                <div id="contaazul-import-cost-centers-status" class="text-muted mt-2"></div>
                <div id="contaazul-import-cost-center-transactions-status" class="text-muted mt-2"></div>
            </div>
        <?php echo form_close(); ?>
    </div>
</div>

<div class="card mt-4">
    <div class="card-header">
        <h4 class="card-title">Histórico de Execução</h4>
        <span class="text-muted">Últimas execuções do import (cron ou manual).</span>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-sm">
                <thead>
                    <tr>
                        <th>Data/Hora</th>
                        <th>Inseridos</th>
                        <th>Atualizados</th>
                        <th>Erros</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($logs)): ?>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td><?php echo $log->run_at; ?></td>
                                <td><?php echo $log->imported; ?></td>
                                <td><?php echo $log->updated; ?></td>
                                <td>
                                    <?php
                                    if ($log->errors) {
                                        $errs = json_decode($log->errors, true);
                                        if (is_array($errs) && count($errs)) {
                                            echo '<ul class="mb-0">';
                                            foreach ($errs as $err) {
                                                echo '<li>' . htmlspecialchars($err) . '</li>';
                                            }
                                            echo '</ul>';
                                        } else {
                                            echo htmlspecialchars($log->errors);
                                        }
                                    } else {
                                        echo '<span class="text-muted">-</span>';
                                    }
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="4" class="text-muted">Nenhuma execução registrada.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<script type="text/javascript">
    $(document).ready(function () {
        $("#contaazul-import-clients").on("click", function () {
            var $status = $("#contaazul-import-status");
            $status.text("Importando...");

            $.post("<?php echo get_uri('contaazul/import-clients'); ?>", {}, function (response) {
                if (response && response.success) {
                    var processed = response.processed ? response.processed : 0;
                    $status.html("Importação ok. Processados: " + processed + " | Inseridos: " + response.imported + " | Atualizados: " + response.updated);
                    if (response.errors && response.errors.length) {
                        var errList = response.errors.map(function (e) { return $("<div/>").text(e).html(); }).join(" | ");
                        $status.append("<br><span class='text-danger'>Erros: " + errList + "</span>");
                    }
                } else {
                    var msg = response && response.message ? response.message : "Falha ao importar.";
                    $status.html("<span class='text-danger'>" + msg + "</span>");
                }
            }).fail(function (xhr) {
                $status.html("<span class='text-danger'>Falha: " + xhr.status + " " + xhr.statusText + "</span>");
            });
        });

        $("#contaazul-import-items").on("click", function () {
            var $status = $("#contaazul-import-items-status");
            $status.text("Importando...");

            $.post("<?php echo get_uri('contaazul/import-items'); ?>", {}, function (response) {
                if (response && response.success) {
                    var processed = response.processed ? response.processed : 0;
                    $status.html("Importação ok. Processados: " + processed + " | Inseridos: " + response.imported + " | Atualizados: " + response.updated);
                    if (response.errors && response.errors.length) {
                        var errList = response.errors.map(function (e) { return $("<div/>").text(e).html(); }).join(" | ");
                        $status.append("<br><span class='text-danger'>Erros: " + errList + "</span>");
                    }
                } else {
                    var msg = response && response.message ? response.message : "Falha ao importar.";
                    $status.html("<span class='text-danger'>" + msg + "</span>");
                }
            }).fail(function (xhr) {
                $status.html("<span class='text-danger'>Falha: " + xhr.status + " " + xhr.statusText + "</span>");
            });
        });

        $("#contaazul-import-general").on("click", function () {
            var $status = $("#contaazul-import-general-status");
            $status.text("Importando...");

            $.post("<?php echo get_uri('contaazul/import-general'); ?>", {}, function (response) {
                if (response && response.success) {
                    var msg = "ImportaÇõÇœo ok. Categorias: " + (response.categories_imported || 0) + " inseridas, " + (response.categories_updated || 0) + " atualizadas.";
                    msg += " Unidades: " + (response.units_imported || 0) + " inseridas, " + (response.units_updated || 0) + " atualizadas.";
                    $status.html(msg);
                    if (response.errors && response.errors.length) {
                        var errList = response.errors.map(function (e) { return $("<div/>").text(e).html(); }).join(" | ");
                        $status.append("<br><span class='text-danger'>Erros: " + errList + "</span>");
                    }
                } else {
                    var msg = response && response.message ? response.message : "Falha ao importar.";
                    $status.html("<span class='text-danger'>" + msg + "</span>");
                }
            }).fail(function (xhr) {
                $status.html("<span class='text-danger'>Falha: " + xhr.status + " " + xhr.statusText + "</span>");
            });
        });

        $("#contaazul-import-services").on("click", function () {
            var $status = $("#contaazul-import-services-status");
            $status.text("Importando...");

            $.post("<?php echo get_uri('contaazul/import-services'); ?>", {}, function (response) {
                if (response && response.success) {
                    var processed = response.processed ? response.processed : 0;
                    $status.html("Importacao ok. Processados: " + processed + " | Inseridos: " + response.imported + " | Atualizados: " + response.updated);
                    if (response.errors && response.errors.length) {
                        var errList = response.errors.map(function (e) { return $("<div/>").text(e).html(); }).join(" | ");
                        $status.append("<br><span class='text-danger'>Erros: " + errList + "</span>");
                    }
                } else {
                    var msg = response && response.message ? response.message : "Falha ao importar.";
                    $status.html("<span class='text-danger'>" + msg + "</span>");
                }
            }).fail(function (xhr) {
                $status.html("<span class='text-danger'>Falha: " + xhr.status + " " + xhr.statusText + "</span>");
            });
        });
        $("#contaazul-import-cost-centers").on("click", function () {
            var $status = $("#contaazul-import-cost-centers-status");
            $status.text("Importando...");

            $.post("<?php echo get_uri('contaazul/import-cost-centers'); ?>", {}, function (response) {
                if (response && response.success) {
                    var processed = response.processed ? response.processed : 0;
                    $status.html("Importação ok. Processados: " + processed + " | Inseridos: " + response.imported + " | Atualizados: " + response.updated);
                    if (response.errors && response.errors.length) {
                        var errList = response.errors.map(function (e) { return $("<div/>").text(e).html(); }).join(" | ");
                        $status.append("<br><span class='text-danger'>Erros: " + errList + "</span>");
                    }
                } else {
                    var msg = response && response.message ? response.message : "Falha ao importar.";
                    $status.html("<span class='text-danger'>" + msg + "</span>");
                }
            }).fail(function (xhr) {
                $status.html("<span class='text-danger'>Falha: " + xhr.status + " " + xhr.statusText + "</span>");
            });
        });

        $("#contaazul-import-cost-center-transactions").on("click", function () {
            var $status = $("#contaazul-import-cost-center-transactions-status");
            $status.text("Importando...");

            $.post("<?php echo get_uri('contaazul/import-cost-center-transactions'); ?>", {}, function (response) {
                if (response && response.success) {
                    var processed = response.processed ? response.processed : 0;
                    $status.html("Importação ok. Processados: " + processed + " | Inseridos: " + response.imported + " | Atualizados: " + response.updated);
                    if (response.errors && response.errors.length) {
                        var errList = response.errors.map(function (e) { return $("<div/>").text(e).html(); }).join(" | ");
                        $status.append("<br><span class='text-danger'>Erros: " + errList + "</span>");
                    }
                } else {
                    var msg = response && response.message ? response.message : "Falha ao importar.";
                    $status.html("<span class='text-danger'>" + msg + "</span>");
                }
            }).fail(function (xhr) {
                $status.html("<span class='text-danger'>Falha: " + xhr.status + " " + xhr.statusText + "</span>");
            });
        });
    });
</script>



