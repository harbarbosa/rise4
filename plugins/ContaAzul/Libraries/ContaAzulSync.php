<?php

namespace ContaAzul\Libraries;

use App\Models\Clients_model;
use App\Models\Settings_model;

class ContaAzulSync
{
    private $Clients_model;
    private $Settings_model;

    public function __construct()
    {
        $this->Clients_model = new Clients_model();
        $this->Settings_model = new Settings_model();
    }

    /**
     * Sincroniza cliente do Rise para Conta Azul.
     * $action: create|update
     */
    public function syncClient($clientId, $action = 'create')
    {
        $enable = get_setting('contaazul_sync_on_create');
        if ($action === 'create' && $enable != '1') {
            log_message('debug', 'ContaAzul sync: criaÇõÇœo ignorada pois sync_on_create estÇ­ desativado.');
            return;
        }

        $client = $this->Clients_model->get_one($clientId);
        if (!$client || !$client->id) {
            return;
        }

        $clientApi = $this->makeClient();

        // se expirado, tenta refresh
        if ($clientApi->isExpired() && get_setting("contaazul_refresh_token")) {
            $refresh = $clientApi->refreshAccessToken(get_setting("contaazul_refresh_token"));
            if ($refresh["ok"]) {
                $this->persistTokens($clientApi->getTokens());
            } else {
                $this->logError('refresh', $clientId, $refresh);
                return;
            }
        }

        $payload = $this->mapToContaAzul($client);
        if (!$payload) {
            $this->logError('validate', $clientId, [
                'status' => 0,
                'body' => 'Dados insuficientes para enviar ao Conta Azul (documento ausente).'
            ]);
            return;
        }

        $contaAzulId = $client->id_conta_azul ?? '';

        if ($action === 'update' && $contaAzulId) {
            $resp = $clientApi->updatePerson($contaAzulId, $payload);

          
         
            if (!$resp['ok']) {
                $this->logError('update', $clientId, $resp);
            }
            return;
        }

        $resp = $clientApi->createPerson($payload);

        var_dump($resp);
        exit;
        if ($resp['ok']) {
            $newId = get_array_value($resp['data'], 'id');
            if ($newId) {
                $this->Clients_model->ci_save(['id_conta_azul' => $newId], $clientId);
            }
        } else {
            $this->logError('create', $clientId, $resp);
        }
    }

    private function mapToContaAzul($client)
    {
        // tipo exigido pela API: FÇðsica, JurÇðdica ou Estrangeira
        $tipo = 'Jurídica';
        if ($client->type === 'person') {
            $tipo = 'Física';
        }

       
        return [
            "nome" => $client->company_name,
            
            "email" => "",
            "telefone" => $client->phone ?: "",
            "tipo_pessoa" => $tipo,
            "ativo" => true,
            // perfis aceitos: Cliente, Fornecedor, Transportadora
            "perfis" => [
                [
                    "tipo_perfil" => "Cliente"
                ]
            ]
        ];
    }

    private function logError($action, $clientId, $resp)
    {
        $status = isset($resp['status']) ? $resp['status'] : 'n/a';
        $body = isset($resp['body']) ? $resp['body'] : '';
        log_message('error', "ContaAzul sync {$action}: cliente {$clientId} falhou - HTTP {$status} - {$body}");
    }

    private function makeClient()
    {
        return new ContaAzulClient(
            get_setting("contaazul_client_id"),
            get_setting("contaazul_client_secret"),
            get_setting("contaazul_redirect_uri"),
            get_setting("contaazul_scope"),
            get_setting("contaazul_access_token"),
            get_setting("contaazul_refresh_token"),
            get_setting("contaazul_token_expires_at")
        );
    }

    private function persistTokens($tokens)
    {
        $this->Settings_model->save_setting("contaazul_access_token", $tokens["access_token"]);
        $this->Settings_model->save_setting("contaazul_refresh_token", $tokens["refresh_token"]);
        $this->Settings_model->save_setting("contaazul_token_expires_at", $tokens["expires_at"]);
    }
}

