<?php

namespace Engenharia\install;

function engenharia_uninstall()
{
    // Dados preservados por padrao. Nenhum rollback ou exclusao automatica.
    log_message('info', '[Engenharia] Uninstall requested. Data preserved by design.');
    return true;
}
