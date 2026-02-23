<?php

/**
 * Executa migrations do plugin Fotovoltaico.
 */

$result = array(
    'success' => true,
    'errors' => array(),
    'executed' => array()
);

$db = db_connect('default');
$dbprefix = get_db_prefix();

$run_sql_install = function () use ($db, $dbprefix, &$result) {
    $install_file = __DIR__ . '/install.sql';
    if (!file_exists($install_file)) {
        $result['success'] = false;
        $result['errors'][] = 'install.sql not found';
        return;
    }

    $sql = file_get_contents($install_file);
    if (!$sql) {
        $result['success'] = false;
        $result['errors'][] = 'install.sql is empty';
        return;
    }

    $sql = str_replace('{{DB_PREFIX}}', $dbprefix, $sql);
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    foreach ($statements as $statement) {
        if (!$statement) {
            continue;
        }
        $ok = $db->query($statement);
        $result['executed'][] = $statement;
        if (!$ok) {
            $result['success'] = false;
            $result['errors'][] = 'Failed: ' . $statement;
        }
    }
};

try {
    $migrations = \Config\Services::migrations();
    $migrations->setNamespace('Fotovoltaico');
    $migrations->latest();
    $result['executed'][] = 'Fotovoltaico migrations latest';
} catch (\Throwable $e) {
    $result['success'] = false;
    $result['errors'][] = $e->getMessage();
}

// Fallback: garantir tabelas via SQL se migrations falharem ou tabelas não existirem
try {
    $tables = array('fv_projects', 'fv_products', 'fv_kits', 'fv_kit_items', 'fv_utilities', 'fv_tariffs', 'fv_project_tariff_snapshots', 'fv_irradiation_cache', 'fv_project_irradiation_snapshots', 'fv_proposals', 'fv_project_assistant_data');
    foreach ($tables as $table) {
        if (!$db->tableExists($db->prefixTable($table))) {
            $run_sql_install();
            break;
        }
    }
} catch (\Throwable $e) {
    $result['success'] = false;
    $result['errors'][] = $e->getMessage();
}

return $result;
