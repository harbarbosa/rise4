<?php

defined('PLUGINPATH') or exit('No direct script access allowed');

/*
  Plugin Name: ContaAzul
  Description: Integração Conta Azul com OAuth2 (Authorization Code) e importação de clientes.
  Version: 0.1.0
  Requires at least: 3.0
 */

if (!defined('CONTA_AZUL_MODULE_NAME')) {
    define('CONTA_AZUL_MODULE_NAME', 'ContaAzul');
}

// adiciona item no menu de configurações (Plugins)
app_hooks()->add_filter('app_filter_admin_settings_menu', function ($settings_menu) {
    $settings_menu["plugins"][] = array("name" => "contaazul", "url" => "contaazul");
    return $settings_menu;
});

// Exportacao desativada: este plugin apenas importa clientes do Conta Azul.

app_hooks()->add_action('app_hook_head_extension', function () {
    ?>
    <script type="text/javascript">
        (function () {
            function getCaBadgeHtml(color) {
                var safeColor = color || "#1f78d1";
                return ' <span class="mt0 badge ms-1 ca-client-badge" style="background-color:' + safeColor + ';" title="Conta Azul">CA</span>';
            }

            function findCaBadge($container) {
                var $badge = $container.find(".badge").filter(function () {
                    return $.trim($(this).text()).toLowerCase() === "ca";
                }).first();
                return $badge;
            }

            function applyCaBadgeToClientHeader() {
                var $header = $(".clients-view-button .page-title h1");
                if (!$header.length || $header.find(".ca-client-badge").length) {
                    return;
                }

                var $caLabel = findCaBadge($("a[data-field='labels']"));
                if ($caLabel.length) {
                    var color = $caLabel.css("background-color");
                    $header.append(getCaBadgeHtml(color));
                }
            }

            function applyCaBadgeToClientTable() {
                var $table = $("#client-table");
                if (!$table.length) {
                    return;
                }

                $table.find("tbody tr").each(function () {
                    var $cells = $(this).find("td");
                    if ($cells.length < 6) {
                        return;
                    }

                    var $labelCell = $cells.eq(5);
                    var $caLabel = findCaBadge($labelCell);
                    if (!$caLabel.length) {
                        return;
                    }

                    var $nameCell = $cells.eq(1);
                    if ($nameCell.find(".ca-client-badge").length) {
                        return;
                    }

                    var color = $caLabel.css("background-color");
                    $nameCell.append(getCaBadgeHtml(color));
                });
            }

            function initCaBadges() {
                applyCaBadgeToClientHeader();
                applyCaBadgeToClientTable();
            }

            $(document).ready(function () {
                initCaBadges();
                $("#client-table").on("draw.dt", function () {
                    applyCaBadgeToClientTable();
                });
            });
        })();
    </script>
    <?php
});

// hook de instalação
register_installation_hook(CONTA_AZUL_MODULE_NAME, function () {
    require_once __DIR__ . '/install.php';
});

// hook de atualizaÃ§Ã£o
register_update_hook(CONTA_AZUL_MODULE_NAME, function () {
    require_once __DIR__ . '/install.php';
});
