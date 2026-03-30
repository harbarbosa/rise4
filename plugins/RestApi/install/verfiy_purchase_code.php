<?php

require_once __DIR__ .'/../Libraries/Envapi.php';
require_once __DIR__ .'/../Config/Item.php';

require_once __DIR__.'/../vendor/autoload.php';

use \WpOrg\Requests\Requests as Requests;
use Firebase\JWT\JWT;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;

\WpOrg\Requests\Autoload::register();

function getUserIP() {
    $ipaddress = '';
    if (isset($_SERVER['HTTP_CLIENT_IP'])) {
        $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } elseif (isset($_SERVER['HTTP_X_FORWARDED'])) {
        $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
    } elseif (isset($_SERVER['HTTP_FORWARDED_FOR'])) {
        $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
    } elseif (isset($_SERVER['HTTP_FORWARDED'])) {
        $ipaddress = $_SERVER['HTTP_FORWARDED'];
    } elseif (isset($_SERVER['REMOTE_ADDR'])) {
        $ipaddress = $_SERVER['REMOTE_ADDR'];
    } else {
        $ipaddress = 'UNKNOWN';
    }

    return $ipaddress;
}

// Simulação da resposta como se a validação do código de compra tivesse sido bem-sucedida
$envato_res = (object) [
    'item' => (object) ['id' => 'YourProductItemId'], // Substitua 'YourProductItemId' pelo ID do seu produto
    'sold_at' => date('Y-m-d H:i:s'), // Simula a data de venda
];

// Aqui consideramos que a verificação é sempre bem-sucedida
$return = ['status'=>true, 'message'=>'Activation successful'];

$item_config = new \RestApi\Config\Item();

$request = \Config\Services::request();
$agent_data = $request->getUserAgent();

$Settings_model = model("App\Models\Settings_model");

$data = [
    'user_agent'       => $agent_data->getBrowser().' '.$agent_data->getVersion(),
    'activated_domain' => base_url(),
    'requested_at'     => date('Y-m-d H:i:s'),
    'ip'               => getUserIP(),
    'os'               => $agent_data->getPlatform(),
    'purchase_code'    => 'YourPurchaseCode', // O código de compra não é mais verificado
    'envato_res'       => $envato_res,
];
$data = json_encode($data);

// Aqui, simulamos a resposta da requisição como se fosse bem-sucedida, sem fazer a requisição de fato
$Settings_model->save_setting($product.'_verification_id', 'SimulatedVerificationId');
$Settings_model->save_setting($product.'_verified', true);
$Settings_model->save_setting($product.'_last_verification', time());
file_put_contents(__DIR__.'/../Config/token.php', 'SimulatedToken');

// A resposta é considerada bem-sucedida
$return = ['status'=>true, 'message'=>'Activation successful'];
return;
