<?php

namespace LaudosTecnicos\install;

function laudostecnicos_uninstall()
{
    try {
        log_message('info', '[LaudosTecnicos] Uninstall requested. Data preserved by design.');
    } catch (\Throwable $e) {
        log_message('error', '[LaudosTecnicos] Uninstall hook error: ' . $e->getMessage());
    }

    return true;
}
