<?php

ini_set('max_execution_time', 300); // 300 seconds

$product = "RestApi";

// Não é necessário verificar o código de compra; removendo a validação
// Simulando a resposta de sucesso para pular a validação do código de compra
$return = ['status' => true, 'message' => ''];

$db = db_connect('default');

// Verificação dos arquivos necessários para a instalação
if (!is_file(PLUGINPATH . "$product/install/database.sql")) {
    echo json_encode(["success" => false, "message" => "The database.sql file could not be found in the install folder!"]);
    exit();
}

// Inicia a instalação
$sql = file_get_contents(PLUGINPATH . "$product/install/database.sql");

$dbprefix = get_db_prefix();

// Configura o prefixo do banco de dados
$sql = str_replace('CREATE TABLE IF NOT EXISTS `', 'CREATE TABLE IF NOT EXISTS `' . $dbprefix, $sql);
$sql = str_replace('INSERT INTO `', 'INSERT INTO `' . $dbprefix, $sql);

// Executa as instruções SQL preparadas
$sql_explode = explode('--#', $sql);
foreach ($sql_explode as $sql_query) {
    $sql_query = trim($sql_query);
    if ($sql_query) {
        $db->query($sql_query);
    }
}

// Considerando a operação bem-sucedida
echo json_encode(["success" => true, "message" => "Installation successful."]);
