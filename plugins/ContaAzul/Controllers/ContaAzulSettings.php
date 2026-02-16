<?php

namespace ContaAzul\Controllers;

use App\Controllers\Security_Controller;
use App\Models\Settings_model;
use App\Models\Clients_model;
use App\Models\Labels_model;
use App\Models\Items_model;
use App\Models\Item_categories_model;
use ContaAzul\Libraries\ContaAzulClient;

class ContaAzulSettings extends Security_Controller
{
    public $Settings_model;
    public $Clients_model;
    public $Items_model;
    public $Item_categories_model;

    public function __construct()
    {
        parent::__construct();
        $this->access_only_admin();
        $this->Settings_model = new Settings_model();
        $this->Clients_model = new Clients_model();
        $this->Items_model = new Items_model();
        $this->Item_categories_model = new Item_categories_model();
    }

    public function index()
    {
        $redirect_uri = get_setting("contaazul_redirect_uri");
        if (!$redirect_uri) {
            $redirect_uri = get_uri("contaazul/callback");
        }

        $data = [
            "title" => "Conta Azul",
            "client_id" => get_setting("contaazul_client_id"),
            "client_secret" => get_setting("contaazul_client_secret"),
            "access_token" => get_setting("contaazul_access_token"),
            "refresh_token" => get_setting("contaazul_refresh_token"),
            "expires_at" => get_setting("contaazul_token_expires_at"),
            "cron_key" => get_setting("contaazul_cron_key"),
            "redirect_uri" => $redirect_uri,
            "scope" => get_setting("contaazul_scope") ?: "openid profile aws.cognito.signin.user.admin",
            "authorize_url" => get_uri("contaazul/authorize"),
            "logs" => $this->getLogs(20)
        ];

        return $this->template->rander('ContaAzul\Views\settings', $data);
    }

    public function save()
    {
        $redirect_uri = $this->request->getPost('redirect_uri') ?: get_uri("contaazul/callback");
        $scope = $this->request->getPost('scope') ?: "openid profile aws.cognito.signin.user.admin";

        $fields = [
            "contaazul_client_id" => $this->request->getPost('client_id'),
            "contaazul_client_secret" => $this->request->getPost('client_secret'),
            "contaazul_access_token" => $this->request->getPost('access_token'),
            "contaazul_refresh_token" => $this->request->getPost('refresh_token'),
            "contaazul_token_expires_at" => $this->request->getPost('expires_at'),
            "contaazul_cron_key" => $this->request->getPost('cron_key'),
            "contaazul_redirect_uri" => $redirect_uri,
            "contaazul_scope" => $scope,
        ];

        foreach ($fields as $name => $value) {
            $this->Settings_model->save_setting($name, $value);
        }

        $this->session->setFlashdata("success_message", "Configurações salvas.");
        return app_redirect('contaazul');
    }

    public function authorize()
    {
        $clientId = get_setting("contaazul_client_id");
        $clientSecret = get_setting("contaazul_client_secret");

        if (!$clientId || !$clientSecret) {
            $this->session->setFlashdata("error_message", "Preencha Client ID e Client Secret antes de conectar.");
            return app_redirect('contaazul');
        }

        $redirectUri = get_setting("contaazul_redirect_uri") ?: get_uri("contaazul/callback");
        $scope = get_setting("contaazul_scope") ?: "openid profile aws.cognito.signin.user.admin";

        $state = bin2hex(random_bytes(16));
        $this->session->set("contaazul_oauth_state", $state);

        $client = new ContaAzulClient($clientId, $clientSecret, $redirectUri, $scope);
        $authUrl = $client->getAuthorizationUrl($state);

        return redirect()->to($authUrl);
    }

    public function callback()
    {
        $code = $this->request->getGet('code');
        $state = $this->request->getGet('state');
        $savedState = $this->session->get("contaazul_oauth_state");

        if (!$code || !$state || !$savedState || $state !== $savedState) {
            $this->session->setFlashdata("error_message", "Autorizacao invalida. Tente novamente.");
            return app_redirect('contaazul');
        }

        $clientId = get_setting("contaazul_client_id");
        $clientSecret = get_setting("contaazul_client_secret");
        $redirectUri = get_setting("contaazul_redirect_uri") ?: get_uri("contaazul/callback");
        $scope = get_setting("contaazul_scope") ?: "openid profile aws.cognito.signin.user.admin";

        $client = new ContaAzulClient($clientId, $clientSecret, $redirectUri, $scope);
        $result = $client->exchangeCode($code);

        if (!$result["ok"]) {
            $this->session->setFlashdata("error_message", "Falha ao trocar token: " . $result["body"]);
            return app_redirect('contaazul');
        }

        $this->persistTokens($client->getTokens());
        $this->session->remove("contaazul_oauth_state");
        $this->session->setFlashdata("success_message", "Conta Azul conectada com sucesso.");

        return app_redirect('contaazul');
    }

    /**
     * Importa pessoas (clientes) usando access_token/refresh_token já informados.
     */
    public function import_clients()
    {
        $result = $this->runImport('manual');
        return $this->response->setJSON($result);
    }

    public function import_items()
    {
        $result = $this->runItemsImport('manual');
        return $this->response->setJSON($result);
    }

    public function import_general()
    {
        $result = $this->runGeneralImport('manual');
        return $this->response->setJSON($result);
    }

    
    public function import_cost_centers()
    {
        $result = $this->runCostCentersImport('manual');
        return $this->response->setJSON($result);
    }

    public function import_cost_center_transactions()
    {
        $result = $this->runCostCenterTransactionsImport('manual');
        return $this->response->setJSON($result);
    }

    // Preview cost centers from ContaAzul without saving (simple browser list)
    public function cost_centers_preview()
    {
        $client = $this->makeClient();
        if ($client->isExpired() && get_setting("contaazul_refresh_token")) {
            $refresh = $client->refreshAccessToken(get_setting("contaazul_refresh_token"));
            if ($refresh["ok"]) {
                $this->persistTokens($client->getTokens());
            } else {
                return $this->response->setStatusCode(400)->setBody("Token expirado e falha ao renovar: {$refresh['body']}");
            }
        }

        $page = 1;
        $size = 100;
        $resp = $client->listCostCenters($page, $size);
        if (!$resp["ok"]) {
            return $this->response->setStatusCode(400)->setBody("Falha ao consultar centros de custo (HTTP {$resp['status']}): {$resp['body']}");
        }

        $items = $this->extractCustomers($resp["data"]);
        if (empty($items) && is_array($resp["data"])) {
            $fallbackKeys = ["centrosDeCusto", "centros_de_custo", "centros_custo", "centros", "itens", "items", "data", "content"];
            foreach ($fallbackKeys as $key) {
                if (isset($resp["data"][$key]) && is_array($resp["data"][$key])) {
                    $items = $resp["data"][$key];
                    break;
                }
            }
        }

        $html = "<h3>Centros de custo (preview)</h3>";
        if (empty($items)) {
            $html .= "<div>Nenhum centro de custo retornado.</div>";
            return $this->response->setBody($html);
        }

        $html .= "<table border='1' cellpadding='6' cellspacing='0'>";
        $html .= "<tr><th>ID</th><th>Código</th><th>Descrição</th><th>Ativo</th></tr>";
        foreach ($items as $item) {
            $item = is_array($item) ? $item : (array)$item;
            $id = get_array_value($item, "id", "");
            $code = $this->getFirstValue($item, ["codigo", "code"]);
            $title = $this->getFirstValue($item, ["descricao", "description", "nome", "name", "descricaoCentroDeCusto", "nomeCentroDeCusto"]);
            $active = get_array_value($item, "ativo", get_array_value($item, "active", true)) ? "sim" : "nao";
            $html .= "<tr><td>" . esc($id) . "</td><td>" . esc($code) . "</td><td>" . esc($title) . "</td><td>" . esc($active) . "</td></tr>";
        }
        $html .= "</table>";

        return $this->response->setBody($html);
    }
    /**
     * Endpoint para rodar via cron: GET contaazul/cron-import?key=...
     */
    public function cron_import()
    {
        $key = $this->request->getGet('key');
        $savedKey = get_setting('contaazul_cron_key');

        if (!$savedKey || $key !== $savedKey) {
            return $this->response->setJSON(["success" => false, "message" => "Acesso negado ao cron (key inválida)."]);
        }

        $result = $this->runImport('cron');
        return $this->response->setJSON($result);
    }

    /**
     * Reuso da importação para cron e UI.
     */
    private function runImport($source = 'manual')
    {
        // garante que estruturas mínimas existam (caso o install não tenha rodado)
        $this->ensureLogTable();
        $this->ensureContaAzulColumn();

        $client = $this->makeClient();
        if ($client->isExpired() && get_setting("contaazul_refresh_token")) {
            $refresh = $client->refreshAccessToken(get_setting("contaazul_refresh_token"));
            if ($refresh["ok"]) {
                $this->persistTokens($client->getTokens());
            } else {
                return ["success" => false, "message" => "Token expirado e falha ao renovar: {$refresh['body']}"];
            }
        }

        $page = 1;
        $size = 50;
        $imported = 0;
        $updated = 0;
        $suppliers_imported = 0;
        $suppliers_updated = 0;
        $transportadoras_imported = 0;
        $transportadoras_updated = 0;
        $errors = [];
        $sample = null;
        $processed = 0;

        $label_id = $this->getContaAzulLabelId();
        $suppliers_model = $this->getPurchasesSuppliersModel();
        $transportadoras_model = $this->getPurchasesTransportadorasModel();

        while (true) {
            $resp = $client->listPeople($page, $size);
            if (!$resp["ok"]) {
                return [
                    "success" => false,
                    "message" => "Falha ao consultar clientes (HTTP {$resp['status']}): {$resp['body']}"
                ];
            }

            $customers = $this->extractCustomers($resp["data"]);

            if (empty($customers)) {
                break;
            }

            $processed += count($customers);

            foreach ($customers as $customer) {
                $mapped = $this->mapCustomer($customer);
                if (!$mapped) {
                    continue;
                }

                $existing = null;
                if (!empty($mapped["vat_number"])) {
                    $existing = $this->Clients_model->get_one_where(["vat_number" => $mapped["vat_number"], "deleted" => 0]);
                }
                if (!$existing || !get_array_value((array)$existing, "id")) {
                    $existing = $this->Clients_model->get_one_where(["company_name" => $mapped["company_name"], "deleted" => 0]);
                }

                if ($label_id) {
                    $current_labels = $existing && isset($existing->labels) ? $existing->labels : "";
                    $mapped["labels"] = $this->mergeLabelId($current_labels, $label_id);
                }

                $mapped = clean_data($mapped);

                if ($existing && get_array_value((array)$existing, "id")) {
                    $save = $this->Clients_model->ci_save($mapped, $existing->id);
                    $updated += $save ? 1 : 0;
                    if (!$save) {
                        $dbErr = $this->Clients_model->db->error();
                        $errors[] = "Falha ao atualizar {$mapped['company_name']} (" . ($dbErr['message'] ?? 'erro desconhecido') . ")";
                    }
                } else {
                    $save = $this->Clients_model->ci_save($mapped);
                    $imported += $save ? 1 : 0;
                    if (!$save) {
                        $dbErr = $this->Clients_model->db->error();
                        $errors[] = "Falha ao inserir {$mapped['company_name']} (" . ($dbErr['message'] ?? 'erro desconhecido') . ")";
                    }
                }

                if ($suppliers_model && $this->hasContaAzulProfile($customer, "fornecedor")) {
                    $supplier_data = $this->mapSupplier($customer);
                    if ($supplier_data) {
                        $supplier_result = $this->saveSupplier($suppliers_model, $supplier_data);
                        if ($supplier_result === "updated") {
                            $suppliers_updated++;
                        } elseif ($supplier_result === "imported") {
                            $suppliers_imported++;
                        }
                    }
                }

                if ($transportadoras_model && $this->hasContaAzulProfile($customer, "transportadora")) {
                    $transportadora_data = $this->mapTransportadora($customer);
                    if ($transportadora_data) {
                        $transportadora_result = $this->saveTransportadora($transportadoras_model, $transportadora_data);
                        if ($transportadora_result === "updated") {
                            $transportadoras_updated++;
                        } elseif ($transportadora_result === "imported") {
                            $transportadoras_imported++;
                        }
                    }
                }
            }

            if (count($customers) < $size) {
                break;
            }
            $page++;
        }

        $this->saveLog($imported, $updated, $errors, $source, [
            "suppliers_imported" => $suppliers_imported,
            "suppliers_updated" => $suppliers_updated,
            "transportadoras_imported" => $transportadoras_imported,
            "transportadoras_updated" => $transportadoras_updated
        ]);

        return [
            "success" => true,
            "processed" => $processed,
            "imported" => $imported,
            "updated" => $updated,
            "suppliers_imported" => $suppliers_imported,
            "suppliers_updated" => $suppliers_updated,
            "transportadoras_imported" => $transportadoras_imported,
            "transportadoras_updated" => $transportadoras_updated,
            "errors" => $errors
        ];
    }

    private function runItemsImport($source = 'manual')
    {
        $this->ensureLogTable();

        $client = $this->makeClient();
        if ($client->isExpired() && get_setting("contaazul_refresh_token")) {
            $refresh = $client->refreshAccessToken(get_setting("contaazul_refresh_token"));
            if ($refresh["ok"]) {
                $this->persistTokens($client->getTokens());
            } else {
                return ["success" => false, "message" => "Token expirado e falha ao renovar: {$refresh['body']}"];
            }
        }

        $page = 0;
        $size = 50;
        $processed = 0;
        $imported = 0;
        $updated = 0;
        $errors = [];
        $items_table = $this->Items_model->db->prefixTable('items');
        $has_ca_code = $this->Items_model->db->fieldExists('ca_code', $items_table);
        $has_cost = $this->Items_model->db->fieldExists('cost', $items_table);
        $has_sale = $this->Items_model->db->fieldExists('sale', $items_table);
        $has_markup = $this->Items_model->db->fieldExists('markup', $items_table);

        while (true) {
            $resp = $client->listProducts($page, $size);
            if (!$resp["ok"]) {
                return [
                    "success" => false,
                    "message" => "Falha ao consultar produtos (HTTP {$resp['status']}): {$resp['body']}"
                ];
            }

            $products = $this->extractCustomers($resp["data"]);
            if (empty($products)) {
                break;
            }
            
            $processed += count($products);

            foreach ($products as $product) {
                $mapped = $this->mapProduct($product);
                
                if (!$mapped) {
                    continue;
                }

                $needs_detail = $this->isEmptyValue(get_array_value($mapped, "unit_type"))
                    || $this->isEmptyNumber(get_array_value($mapped, "cost"))
                    || $this->isEmptyNumber(get_array_value($mapped, "sale"));

                if ($needs_detail && isset($product["id"]) && $product["id"]) {
                    $detail_resp = $client->getProduct($product["id"]);
                    if ($detail_resp["ok"] && is_array($detail_resp["data"])) {
                        $mapped = $this->mergeProductDetail($mapped, $detail_resp["data"]);
                    }
                }

                if (!$has_ca_code) {
                    unset($mapped["ca_code"]);
                }
                if (!$has_cost) {
                    unset($mapped["cost"]);
                }
                if (!$has_sale) {
                    unset($mapped["sale"]);
                }
                if (!$has_markup) {
                    unset($mapped["markup"]);
                }

                $existing = null;
                if ($has_ca_code && !empty($mapped["ca_code"])) {
                    $existing = $this->Items_model->db->table($items_table)
                        ->select("id")
                        ->where("deleted", 0)
                        ->where("ca_code", $mapped["ca_code"])
                        ->get()
                        ->getRow();
                }
                if (!$existing) {
                    $existing = $this->Items_model->db->table($items_table)
                        ->select("id")
                        ->where("deleted", 0)
                        ->where("title", $mapped["title"])
                        ->get()
                        ->getRow();
                }
                $mapped = clean_data($mapped);

                if ($existing && get_array_value((array)$existing, "id")) {
                    $save = $this->Items_model->ci_save($mapped, $existing->id);
                    $updated += $save ? 1 : 0;
                    if (!$save) {
                        $dbErr = $this->Items_model->db->error();
                        $errors[] = "Falha ao atualizar {$mapped['title']} (" . ($dbErr['message'] ?? 'erro desconhecido') . ")";
                    }
                } else {
                    $save = $this->Items_model->ci_save($mapped);
                    $imported += $save ? 1 : 0;
                    if (!$save) {
                        $dbErr = $this->Items_model->db->error();
                        $errors[] = "Falha ao inserir {$mapped['title']} (" . ($dbErr['message'] ?? 'erro desconhecido') . ")";
                    }
                }
            }

            if (count($products) < $size) {
                break;
            }

            $page++;
        }

        $this->saveLog($imported, $updated, $errors, $source, [
            "items_imported" => $imported,
            "items_updated" => $updated
        ]);

        return [
            "success" => true,
            "processed" => $processed,
            "imported" => $imported,
            "updated" => $updated,
            "errors" => $errors
        ];
    }

    private function runCostCenterTransactionsImport($source = 'manual')
    {
        $this->ensureLogTable();

        $client = $this->makeClient();
        if ($client->isExpired() && get_setting("contaazul_refresh_token")) {
            $refresh = $client->refreshAccessToken(get_setting("contaazul_refresh_token"));
            if ($refresh["ok"]) {
                $this->persistTokens($client->getTokens());
            } else {
                return ["success" => false, "message" => "Token expirado e falha ao renovar: {$refresh['body']}"];
            }
        }

        $db = db_connect('default');
        $projects_table = $db->prefixTable('projects');
        $cc_table = $db->prefixTable('contaazul_cost_centers');
        $cost_table = $db->prefixTable('projectanalizer_cost_realized');
        $revenue_table = $db->prefixTable('projectanalizer_revenue_realized');

        if (!$db->tableExists($cc_table)) {
            return ["success" => false, "message" => "Tabela de centros de custo não encontrada. Importe primeiro os centros de custo."];
        }
        if (!$db->tableExists($cost_table) || !$db->tableExists($revenue_table)) {
            return ["success" => false, "message" => "Tabelas do ProjectAnalizer não encontradas. Atualize/instale o plugin."];
        }

        $projects = $db->table($projects_table . " p")
            ->select("p.id as project_id, p.title as project_title, p.cost_center_id, cc.ca_id, cc.title as cost_center_title")
            ->join($cc_table . " cc", "cc.id=p.cost_center_id", "left")
            ->where("p.deleted", 0)
            ->where("p.cost_center_id IS NOT NULL", null, false)
            ->get()
            ->getResult();

        if (!$projects) {
            return ["success" => true, "processed" => 0, "imported" => 0, "updated" => 0, "errors" => []];
        }

        $processed = 0;
        $imported = 0;
        $updated = 0;
        $errors = [];
        $pageSize = 100;

        $cost_realized_model = model("ProjectAnalizer\\Models\\Cost_realized_model");
        $revenue_realized_model = model("ProjectAnalizer\\Models\\Revenue_realized_model");

        foreach ($projects as $project) {
            $project_id = (int)$project->project_id;
            $cost_center_ca_id = trim((string)$project->ca_id);
            if (!$cost_center_ca_id) {
                $errors[] = "Projeto #{$project_id} sem ca_id no centro de custo.";
                continue;
            }

            $page = 1;
            while (true) {
                $resp = $client->listPayables($cost_center_ca_id, $page, $pageSize);
                if (!$resp["ok"]) {
                    $errors[] = "Falha ao consultar contas a pagar (HTTP {$resp['status']}) para projeto #{$project_id}: {$resp['body']}";
                    break;
                }
                $items = $this->extractItemsFromResponse($resp["data"]);
                if (!$items) {
                    break;
                }

                $processed += count($items);
                foreach ($items as $item) {
                    $mapped = $this->mapPayableItem($item);
                    if (!$mapped) {
                        continue;
                    }
                    if (!$mapped["date"] || $mapped["value"] === null) {
                        $errors[] = "Conta a pagar sem data/valor (projeto #{$project_id}).";
                        continue;
                    }

                    $reference = "ca:payable:" . $mapped["ca_id"];
                    $existing = $db->table($cost_table)
                        ->select("id")
                        ->where("project_id", $project_id)
                        ->where("reference", $reference)
                        ->where("deleted", 0)
                        ->get()
                        ->getRow();

                    $payload = [
                        "project_id" => $project_id,
                        "task_id" => null,
                        "cost_type" => $mapped["cost_type"],
                        "date" => $mapped["date"],
                        "value" => $mapped["value"],
                        "description" => $mapped["description"],
                        "reference" => $reference,
                        "created_by" => $this->login_user ? $this->login_user->id : 1
                    ];
                    if ($existing && $existing->id) {
                        $payload["id"] = $existing->id;
                        $save = $cost_realized_model->save($payload);
                        $updated += $save ? 1 : 0;
                    } else {
                        $save = $cost_realized_model->save($payload);
                        $imported += $save ? 1 : 0;
                    }
                }

                if (count($items) < $pageSize) {
                    break;
                }
                $page++;
            }

            $page = 1;
            while (true) {
                $resp = $client->listReceivables($cost_center_ca_id, $page, $pageSize);
                if (!$resp["ok"]) {
                    $errors[] = "Falha ao consultar contas a receber (HTTP {$resp['status']}) para projeto #{$project_id}: {$resp['body']}";
                    break;
                }
                $items = $this->extractItemsFromResponse($resp["data"]);
                if (!$items) {
                    break;
                }

                $processed += count($items);
                foreach ($items as $item) {
                    $mapped = $this->mapReceivableItem($item);
                    if (!$mapped) {
                        continue;
                    }
                    if (!$mapped["date"] || $mapped["value"] === null) {
                        $errors[] = "Conta a receber sem data/valor (projeto #{$project_id}).";
                        continue;
                    }

                    $reference = "ca:receivable:" . $mapped["ca_id"];
                    $existing = $db->table($revenue_table)
                        ->select("id")
                        ->where("project_id", $project_id)
                        ->where("document_ref", $reference)
                        ->where("deleted", 0)
                        ->get()
                        ->getRow();

                    $payload = [
                        "project_id" => $project_id,
                        "planned_id" => null,
                        "realized_date" => $mapped["date"],
                        "realized_value" => $mapped["value"],
                        "document_ref" => $reference,
                        "notes" => $mapped["description"],
                        "created_by" => $this->login_user ? $this->login_user->id : 1
                    ];
                    $data_ref = $payload;
                    if ($existing && $existing->id) {
                        $save = $revenue_realized_model->ci_save($data_ref, $existing->id);
                        $updated += $save ? 1 : 0;
                    } else {
                        $save = $revenue_realized_model->ci_save($data_ref, 0);
                        $imported += $save ? 1 : 0;
                    }
                }

                if (count($items) < $pageSize) {
                    break;
                }
                $page++;
            }
        }

        $this->saveLog($imported, $updated, $errors, $source, [
            "cost_center_transactions_imported" => $imported,
            "cost_center_transactions_updated" => $updated
        ]);

        return [
            "success" => true,
            "processed" => $processed,
            "imported" => $imported,
            "updated" => $updated,
            "errors" => $errors
        ];
    }

    private function runCostCentersImport($source = 'manual')
    {
        $this->ensureLogTable();
        $this->ensureCostCentersTable();

        $client = $this->makeClient();
        if ($client->isExpired() && get_setting("contaazul_refresh_token")) {
            $refresh = $client->refreshAccessToken(get_setting("contaazul_refresh_token"));
            if ($refresh["ok"]) {
                $this->persistTokens($client->getTokens());
            } else {
                return ["success" => false, "message" => "Token expirado e falha ao renovar: {$refresh['body']}"];
            }
        }

        $db = db_connect('default');
        $table = $db->prefixTable('contaazul_cost_centers');

        $page = 0;
        $size = 50;
        $processed = 0;
        $imported = 0;
        $updated = 0;
        $errors = [];

        while (true) {
            $resp = $client->listCostCenters($page, $size);
            if (!$resp["ok"]) {
                return [
                    "success" => false,
                    "message" => "Falha ao consultar centros de custo (HTTP {$resp['status']}): {$resp['body']}"
                ];
            }

            $items = $this->extractCustomers($resp["data"]);
            if (empty($items) && is_array($resp["data"])) {
                $fallbackKeys = ["centrosDeCusto", "centros_de_custo", "centros_custo", "centros", "itens", "items", "data", "content"];
                foreach ($fallbackKeys as $key) {
                    if (isset($resp["data"][$key]) && is_array($resp["data"][$key])) {
                        $items = $resp["data"][$key];
                        break;
                    }
                }
            }
            if ($page === 1 && !empty($items) && $sample === null) {
                $sample = $items[0];
            }
            if (empty($items)) {
                break;
            }

            $processed += count($items);

            foreach ($items as $item) {
                $item = is_array($item) ? $item : (array)$item;
                $caId = get_array_value($item, "id");
                $title = $this->getFirstValue(
                    $item,
                    ["descricao", "description", "nome", "name", "descricaoCentroDeCusto", "nomeCentroDeCusto"]
                );
                $code = $this->getFirstValue($item, ["codigo", "code"]);
                $isActive = get_array_value($item, "ativo", get_array_value($item, "active", true)) ? 1 : 0;

                $title = trim((string)$title);
                if (!$title) {
                    continue;
                }

                $existing = null;
                if ($caId) {
                    $existing = $db->table($table)
                        ->select("id")
                        ->where("ca_id", $caId)
                        ->get()
                        ->getRow();
                }
                if (!$existing && $code) {
                    $existing = $db->table($table)
                        ->select("id")
                        ->where("code", $code)
                        ->get()
                        ->getRow();
                }

                $data = clean_data([
                    "ca_id" => $caId,
                    "code" => $code,
                    "title" => $title,
                    "is_active" => $isActive,
                    "updated_at" => date('Y-m-d H:i:s')
                ]);

                if ($existing && get_array_value((array)$existing, "id")) {
                    $save = $db->table($table)->where("id", $existing->id)->update($data);
                    $updated += $save ? 1 : 0;
                    if (!$save) {
                        $dbErr = $db->error();
                        $errors[] = "Falha ao atualizar {$title} (" . ($dbErr['message'] ?? 'erro desconhecido') . ")";
                    }
                } else {
                    $data["created_at"] = date('Y-m-d H:i:s');
                    $save = $db->table($table)->insert($data);
                    $imported += $save ? 1 : 0;
                    if (!$save) {
                        $dbErr = $db->error();
                        $errors[] = "Falha ao inserir {$title} (" . ($dbErr['message'] ?? 'erro desconhecido') . ")";
                    }
                }
            }

            if (count($items) < $size) {
                break;
            }

            $page++;
        }

        $this->saveLog($imported, $updated, $errors, $source, [
            "cost_centers_imported" => $imported,
            "cost_centers_updated" => $updated
        ]);

        return [
            "success" => true,
            "processed" => $processed,
            "imported" => $imported,
            "updated" => $updated,
            "errors" => $errors
        ];
    }

    private function runGeneralImport($source = 'manual')
    {
        $this->ensureLogTable();
        $this->ensureContaAzulUnitsTable();

        $client = $this->makeClient();
        if ($client->isExpired() && get_setting("contaazul_refresh_token")) {
            $refresh = $client->refreshAccessToken(get_setting("contaazul_refresh_token"));
            if ($refresh["ok"]) {
                $this->persistTokens($client->getTokens());
            } else {
                return ["success" => false, "message" => "Token expirado e falha ao renovar: {$refresh['body']}"];
            }
        }

        $categories_imported = 0;
        $categories_updated = 0;
        $units_imported = 0;
        $units_updated = 0;
        $errors = [];

        $categories_resp = $client->listProductCategories();
        if (!$categories_resp["ok"]) {
            return [
                "success" => false,
                "message" => "Falha ao consultar categorias (HTTP {$categories_resp['status']}): {$categories_resp['body']}"
            ];
        }

        $categories = $this->extractCustomers($categories_resp["data"]);
        foreach ($categories as $category) {
            $title = $this->getFirstValue($category, ["descricao", "description", "nome", "name"]);
            $title = trim((string)$title);
            if (!$title) {
                continue;
            }

            $existing = $this->Item_categories_model->db->table($this->Item_categories_model->db->prefixTable('item_categories'))
                ->select("id")
                ->where("deleted", 0)
                ->where("title", $title)
                ->get()
                ->getRow();

            $data = clean_data([
                "title" => $title,
                "deleted" => 0
            ]);

            if ($existing && get_array_value((array)$existing, "id")) {
                $save = $this->Item_categories_model->ci_save($data, $existing->id);
                $categories_updated += $save ? 1 : 0;
            } else {
                $save = $this->Item_categories_model->ci_save($data);
                $categories_imported += $save ? 1 : 0;
            }
        }

        $page = 0;
        $size = 50;
        while (true) {
            $resp = $client->listProducts($page, $size);
            if (!$resp["ok"]) {
                $errors[] = "Falha ao consultar produtos (HTTP {$resp['status']}): {$resp['body']}";
                break;
            }

            $products = $this->extractCustomers($resp["data"]);
            if (empty($products)) {
                break;
            }

            foreach ($products as $product) {
                if (!isset($product["id"]) || !$product["id"]) {
                    continue;
                }
                $detail_resp = $client->getProduct($product["id"]);
                if (!$detail_resp["ok"] || !is_array($detail_resp["data"])) {
                    continue;
                }

                $unit = $this->extractUnitFromProduct($detail_resp["data"]);
                if (!$unit) {
                    continue;
                }

                $save_result = $this->saveContaAzulUnit($unit);
                if ($save_result === "updated") {
                    $units_updated++;
                } elseif ($save_result === "imported") {
                    $units_imported++;
                }
            }

            if (count($products) < $size) {
                break;
            }
            $page++;
        }

        $this->saveLog(0, 0, $errors, $source, [
            "categories_imported" => $categories_imported,
            "categories_updated" => $categories_updated,
            "units_imported" => $units_imported,
            "units_updated" => $units_updated
        ]);

        return [
            "success" => true,
            "categories_imported" => $categories_imported,
            "categories_updated" => $categories_updated,
            "units_imported" => $units_imported,
            "units_updated" => $units_updated,
            "errors" => $errors
        ];
    }

    private function runServicesImport($source = 'manual')
    {
        $this->ensureLogTable();

        $db = db_connect('default');
        $services_table = $db->prefixTable('os_servicos');
        if (!$db->tableExists($services_table)) {
            return ["success" => false, "message" => "Tabela os_servicos nao encontrada. Instale o plugin Ordem de Servico primeiro."];
        }

        $client = $this->makeClient();
        if ($client->isExpired() && get_setting("contaazul_refresh_token")) {
            $refresh = $client->refreshAccessToken(get_setting("contaazul_refresh_token"));
            if ($refresh["ok"]) {
                $this->persistTokens($client->getTokens());
            } else {
                return ["success" => false, "message" => "Token expirado e falha ao renovar: {$refresh['body']}"];
            }
        }

        $page = 0;
        $size = 50;
        $processed = 0;
        $imported = 0;
        $updated = 0;
        $errors = [];

        while (true) {
            $resp = $client->listServices($page, $size);
            if (!$resp["ok"]) {
                return [
                    "success" => false,
                    "message" => "Falha ao consultar servicos (HTTP {$resp['status']}): {$resp['body']}"
                ];
            }

            $services = $this->extractCustomers($resp["data"]);
            if (empty($services)) {
                break;
            }

            $processed += count($services);

            foreach ($services as $service) {
                $mapped = $this->mapService($service);
                if (!$mapped) {
                    continue;
                }

                $existing = $db->table($services_table)
                    ->select("id")
                    ->where("deleted", 0)
                    ->where("descricao", $mapped["descricao"])
                    ->get()
                    ->getRow();

                $mapped = clean_data($mapped);

                if ($existing && get_array_value((array)$existing, "id")) {
                    $save = model('OrdemServico\\Models\\OsServicos_model')->ci_save($mapped, $existing->id);
                    $updated += $save ? 1 : 0;
                    if (!$save) {
                        $dbErr = $db->error();
                        $errors[] = "Falha ao atualizar {$mapped['descricao']} (" . ($dbErr['message'] ?? 'erro desconhecido') . ")";
                    }
                } else {
                    $save = model('OrdemServico\\Models\\OsServicos_model')->ci_save($mapped);
                    $imported += $save ? 1 : 0;
                    if (!$save) {
                        $dbErr = $db->error();
                        $errors[] = "Falha ao inserir {$mapped['descricao']} (" . ($dbErr['message'] ?? 'erro desconhecido') . ")";
                    }
                }
            }

            if (count($services) < $size) {
                break;
            }

            $page++;
        }

        $this->saveLog($imported, $updated, $errors, $source, [
            "services_imported" => $imported,
            "services_updated" => $updated
        ]);

        return [
            "success" => true,
            "processed" => $processed,
            "imported" => $imported,
            "updated" => $updated,
            "errors" => $errors
        ];
    }

    /**
     * Salva log da execução.
     */
    private function saveLog($imported, $updated, $errors, $source = 'manual', $meta = [])
    {
        $db = db_connect('default');
        $table = get_db_prefix() . 'contaazul_logs';
        if (!$db->tableExists($table)) {
            return;
        }
        $builder = $db->table($table);
        try {
            $builder->insert([
                'run_at' => date('Y-m-d H:i:s'),
                'source' => $source ?: 'manual',
                'imported' => $imported,
                'updated' => $updated,
                'errors' => $errors ? json_encode($errors) : null,
                'meta' => $meta ? json_encode($meta) : null
            ]);
        } catch (\Exception $ex) {
            log_message('error', 'ContaAzul log insert failed: {exception}', ['exception' => $ex]);
        }
    }

    /**
     * Recupera histórico (limit).
     */
    private function getLogs($limit = 20)
    {
        $db = db_connect('default');
        $table = get_db_prefix() . 'contaazul_logs';

        // se tabela não existir (instalação antiga), retorna lista vazia
        if (!$db->tableExists($table)) {
            return [];
        }

        try {
            $builder = $db->table($table);
            $query = $builder->orderBy('run_at', 'DESC')->limit($limit)->get();
            return $query ? $query->getResult() : [];
        } catch (\Exception $ex) {
            return [];
        }
    }

    /**
     * Cria a coluna id_conta_azul em clients se não existir.
     */
    private function ensureContaAzulColumn()
    {
        $db = db_connect('default');
        $clientsTable = get_db_prefix() . 'clients';
        $colId = $db->query("SHOW COLUMNS FROM `{$clientsTable}` LIKE 'id_conta_azul'")->getResult();
        if (empty($colId)) {
            $db->query("ALTER TABLE `{$clientsTable}` ADD COLUMN `id_conta_azul` VARCHAR(100) NULL DEFAULT NULL");
        }
        // Código interno (id_legado) retornado pelo Conta Azul para a pessoa
        $colCodigo = $db->query("SHOW COLUMNS FROM `{$clientsTable}` LIKE 'codigo_conta_azul'")->getResult();
        if (empty($colCodigo)) {
            $db->query("ALTER TABLE `{$clientsTable}` ADD COLUMN `codigo_conta_azul` VARCHAR(100) NULL DEFAULT NULL");
        }
    }

    /**
     * Cria a tabela de logs, se não existir, e adiciona coluna source.
     */
    
    private function ensureLogTable()
    {
        $db = db_connect('default');
        $table = get_db_prefix() . 'contaazul_logs';

        if (!$db->tableExists($table)) {
            $sql = "CREATE TABLE IF NOT EXISTS `{$table}` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `run_at` DATETIME NOT NULL,
                `source` VARCHAR(20) NOT NULL DEFAULT 'manual',
                `imported` INT NOT NULL DEFAULT 0,
                `updated` INT NOT NULL DEFAULT 0,
                `errors` TEXT NULL,
                `meta` TEXT NULL,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;";
            $db->query($sql);
        }

        $col = $db->query("SHOW COLUMNS FROM `{$table}` LIKE 'source'")->getResult();
        if (empty($col)) {
            $db->query("ALTER TABLE `{$table}` ADD COLUMN `source` VARCHAR(20) NOT NULL DEFAULT 'manual'");
        }
    }

    private function ensureCostCentersTable()
    {
        $db = db_connect('default');
        $table = $db->prefixTable('contaazul_cost_centers');
        if ($db->tableExists($table)) {
            return;
        }

        $sql = "CREATE TABLE IF NOT EXISTS `{$table}` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `ca_id` VARCHAR(100) NULL,
            `code` VARCHAR(100) NULL,
            `title` VARCHAR(255) NOT NULL,
            `is_active` TINYINT(1) NOT NULL DEFAULT 1,
            `created_at` DATETIME NULL,
            `updated_at` DATETIME NULL,
            PRIMARY KEY (`id`),
            INDEX (`ca_id`),
            INDEX (`code`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;";
        $db->query($sql);
    }

    private function makeClient($data = null)
    {
        $cfg = $data ?: [
            "client_id" => get_setting("contaazul_client_id"),
            "client_secret" => get_setting("contaazul_client_secret"),
            "redirect_uri" => get_setting("contaazul_redirect_uri"),
            "scope" => get_setting("contaazul_scope"),
            "access_token" => get_setting("contaazul_access_token"),
            "refresh_token" => get_setting("contaazul_refresh_token"),
            "expires_at" => get_setting("contaazul_token_expires_at"),
        ];

        return new ContaAzulClient(
            $cfg["client_id"],
            $cfg["client_secret"],
            $cfg["redirect_uri"],
            $cfg["scope"],
            $cfg["access_token"],
            $cfg["refresh_token"],
            $cfg["expires_at"]
        );
    }

    private function persistTokens($tokens)
    {
        $this->Settings_model->save_setting("contaazul_access_token", $tokens["access_token"]);
        $this->Settings_model->save_setting("contaazul_refresh_token", $tokens["refresh_token"]);
        $this->Settings_model->save_setting("contaazul_token_expires_at", $tokens["expires_at"]);
    }

    private function extractCustomers($payload)
    {
        if (!$payload) {
            return [];
        }

        if (isset($payload["items"]) && is_array($payload["items"])) {
            return $payload["items"];
        }

        if (isset($payload["_embedded"]["customers"])) return $payload["_embedded"]["customers"];
        if (isset($payload["content"])) return $payload["content"];
        if (is_array($payload) && isset($payload[0])) return $payload;
        return [];
    }

    private function extractItemsFromResponse($payload)
    {
        $items = $this->extractCustomers($payload);
        if (!empty($items)) {
            return $this->expandFinancialItems($items);
        }
        if (is_array($payload)) {
            $fallbackKeys = ["items", "content", "data", "itens", "registros", "results"];
            foreach ($fallbackKeys as $key) {
                if (isset($payload[$key]) && is_array($payload[$key])) {
                    return $this->expandFinancialItems($payload[$key]);
                }
            }
        }
        return [];
    }

    private function expandFinancialItems($items)
    {
        $expanded = [];
        foreach ($items as $item) {
            $row = is_array($item) ? $item : (array)$item;
            $parcelas = $this->getFirstValue($row, ["parcelas", "installments", "lancamentos", "pagamentos", "items"]);
            if (is_array($parcelas) && !empty($parcelas)) {
                $parentId = $this->getFirstValue($row, ["id", "idContaAzul", "id_conta_azul", "codigo", "code"]);
                $index = 0;
                foreach ($parcelas as $parcela) {
                    $p = is_array($parcela) ? $parcela : (array)$parcela;
                    $merged = $row;
                    $merged["parcela"] = $p;
                    $merged["valor"] = $this->getFirstValue($p, ["valor", "value", "valor_total", "valorTotal", "amount", "total"]);
                    $merged["valor_original"] = $this->getFirstValue($p, ["valor_original", "valorOriginal"]);
                    $merged["valor_recebido"] = $this->getFirstValue($p, ["valor_recebido", "valorRecebido"]);
                    $merged["data_vencimento"] = $this->getFirstValue($p, ["data_vencimento", "dataVencimento", "vencimento", "due_date", "dueDate"]);
                    $merged["data_recebimento"] = $this->getFirstValue($p, ["data_recebimento", "dataRecebimento", "data_baixa", "dataBaixa", "data_recebimento_efetivo", "received_date", "receipt_date"]);
                    $merged["data_pagamento"] = $this->getFirstValue($p, ["data_pagamento", "dataPagamento"]);
                    $merged["id"] = $this->getFirstValue($p, ["id", "id_parcela", "idParcela"]) ?: ($parentId ? ($parentId . ":" . $index) : null);
                    $expanded[] = $merged;
                    $index++;
                }
            } else {
                $expanded[] = $row;
            }
        }

        return $expanded;
    }

    private function mapCustomer($customer)
    {
        if (!is_array($customer)) return null;

        $name = get_array_value($customer, "nome") ?? get_array_value($customer, "business_name") ?? get_array_value($customer, "name");
        if (!$name) return null;

        $taxNumber = get_array_value($customer, "documento") ??
            get_array_value($customer, "federalTaxNumber") ??
            get_array_value($customer, "federal_tax_number") ??
            get_array_value($customer, "cpf_cnpj") ??
            get_array_value($customer, "tax_id") ?? "";

        $personType = strtolower(get_array_value($customer, "tipo_pessoa", "juridica"));
        $type = in_array($personType, ["fisica", "natural", "pf", "person"]) ? "person" : "organization";

        $now = get_current_utc_time();
        $contaAzulId = get_array_value($customer, "id", "");
        $contaAzulCodigo = get_array_value($customer, "id_legado", "");

        return [
            "company_name" => $name,
            "type" => $type,
            "address" => "",
            "city" => "",
            "state" => "",
            "zip" => "",
            "country" => "",
            "created_date" => $now,
            "website" => "",
            "phone" => get_array_value($customer, "telefone", get_array_value($customer, "business_phone", get_array_value($customer, "mobile_phone", ""))),
            "currency_symbol" => "",
            "starred_by" => "",
            "group_ids" => "",
            "deleted" => 0,
            "is_lead" => 0,
            "lead_status_id" => 0,
            "owner_id" => $this->login_user ? $this->login_user->id : 1,
            "created_by" => $this->login_user ? $this->login_user->id : 1,
            "sort" => 0,
            "lead_source_id" => 0,
            "last_lead_status" => "",
            "client_migration_date" => date("Y-m-d"),
            "vat_number" => $taxNumber,
            "gst_number" => "",
            "stripe_customer_id" => "",
            "stripe_card_ending_digit" => 0,
            "currency" => "",
            "disable_online_payment" => 0,
            "labels" => "",
            "managers" => "",
            "id_conta_azul" => $contaAzulId,
            "codigo_conta_azul" => $contaAzulCodigo
        ];
    }

    private function hasContaAzulProfile($customer, $profileType)
    {
        if (!is_array($customer)) {
            return false;
        }

        $profiles = get_array_value($customer, "perfis");
        if (!is_array($profiles)) {
            return false;
        }

        foreach ($profiles as $profile) {
            $type = strtolower(get_array_value((array)$profile, "tipo_perfil", ""));
            if ($type === strtolower($profileType)) {
                return true;
            }
        }

        return false;
    }


    private function mapSupplier($customer)
    {
        if (!is_array($customer)) {
            return null;
        }

        $name = get_array_value($customer, "nome") ?? get_array_value($customer, "business_name") ?? get_array_value($customer, "name");
        if (!$name) {
            return null;
        }

        $taxNumber = get_array_value($customer, "documento") ??
            get_array_value($customer, "federalTaxNumber") ??
            get_array_value($customer, "federal_tax_number") ??
            get_array_value($customer, "cpf_cnpj") ??
            get_array_value($customer, "tax_id") ?? "";

        $address_parts = array_filter([
            get_array_value($customer, "endereco") ?? get_array_value($customer, "address"),
            get_array_value($customer, "bairro"),
            get_array_value($customer, "cidade") ?? get_array_value($customer, "city"),
            get_array_value($customer, "estado") ?? get_array_value($customer, "state"),
            get_array_value($customer, "cep") ?? get_array_value($customer, "zip")
        ]);

        return [
            "company_id" => 0,
            "name" => $name,
            "email" => get_array_value($customer, "email") ?? "",
            "phone" => get_array_value($customer, "telefone", get_array_value($customer, "business_phone", get_array_value($customer, "mobile_phone", ""))),
            "tax_id" => $taxNumber,
            "address" => $address_parts ? implode(" - ", $address_parts) : "",
            "created_at" => get_current_utc_time(),
            "created_by" => $this->login_user ? $this->login_user->id : 1,
            "updated_at" => get_current_utc_time(),
            "deleted" => 0
        ];
    }

    private function mapProduct($product)
    {
        if (!is_array($product)) {
            return null;
        }

        $title = get_array_value($product, "nome") ??
            get_array_value($product, "name") ??
            get_array_value($product, "descricao") ??
            get_array_value($product, "description");

        $title = trim((string)$title);
        if (!$title) {
            return null;
        }

        $description = get_array_value($product, "descricao") ??
            get_array_value($product, "description") ??
            "";

        $unit = $this->extractUnitFromProduct($product);

        $cost_raw = $this->getFirstValue($product, [
            "custo",
            "preco_custo",
            "custo_medio",
            "cost",
            "cost_price"
        ]);
        $sale_raw = $this->getFirstValue($product, [
            "valor",
            "preco_venda",
            "valor_venda",
            "sale_price",
            "price",
            "valor_venda"
        ]);

        $cost = $this->parseDecimalNullable($this->extractScalar($cost_raw, ["valor", "value", "preco", "price", "custo", "cost", "amount"]));
        $sale = $this->parseDecimalNullable($this->extractScalar($sale_raw, ["valor", "value", "preco", "price", "venda", "sale", "amount"]));

        $markup_raw = $this->getFirstValue($product, [
            "mk",
            "markup",
            "margem",
            "margem_lucro",
            "margem_lucro_percentual",
            "percentual_markup"
        ]);
        $markup = $this->parseDecimalNullable($this->extractScalar($markup_raw, ["valor", "value", "percentual", "percent", "porcentagem"]));

        if (($markup === null || $markup <= 0) && $cost !== null && $sale !== null && $cost > 0 && $sale > 0) {
            $markup = (($sale / $cost) - 1) * 100;
        }

        if (($sale === null || $sale <= 0) && $cost !== null && $cost > 0) {
            $settings = model('Proposals\\Models\\Proposals_module_settings_model')->get_settings($this->_get_company_id());
            $default_markup = isset($settings->default_markup_percent) ? (float)$settings->default_markup_percent : 0;
            if ($default_markup > 0) {
                $sale = $cost * (1 + ($default_markup / 100));
                if ($markup === null || $markup <= 0) {
                    $markup = $default_markup;
                }
            }
        }

        $rate = $cost !== null && $cost > 0 ? $cost : ($sale !== null ? $sale : 0);

        $taxable = get_array_value($product, "tributavel");
        if ($taxable === null) {
            $taxable = get_array_value($product, "taxable", 0);
        }
        $taxable = $taxable ? 1 : 0;

        $ca_code_raw = $this->getFirstValue($product, [
            "codigo",
            "codigo_interno",
            "codigoInterno",
            "sku",
            "id_legado",
            "id"
        ]);
        $ca_code = $this->extractScalar($ca_code_raw, ["codigo", "code", "id", "sku", "valor"]);

        $data = [
            "title" => $title,
            "description" => $description,
            "unit_type" => $unit,
            "rate" => $rate,
            "files" => "",
            "show_in_client_portal" => 0,
            "category_id" => 1,
            "taxable" => $taxable,
            "sort" => 0,
            "deleted" => 0
        ];

        if ($ca_code !== null && $ca_code !== '') {
            $data["ca_code"] = $ca_code;
        }
        if ($cost !== null) {
            $data["cost"] = $cost;
        }
        if ($sale !== null) {
            $data["sale"] = $sale;
        }
        if ($markup !== null) {
            $data["markup"] = $markup;
        }

        return $data;
    }

    private function mapService($service)
    {
        if (!is_array($service)) {
            return null;
        }

        $descricao = $this->getFirstValue($service, ["descricao", "name", "nome", "description"]);
        $descricao = trim((string)$descricao);
        if (!$descricao) {
            return null;
        }

        $cost_raw = $this->getFirstValue($service, ["custo", "preco_custo", "custo_medio", "cost", "cost_price"]);
        $sale_raw = $this->getFirstValue($service, ["valor_venda", "valor", "preco_venda", "sale_price", "price"]);
        $markup_raw = $this->getFirstValue($service, ["margem", "markup", "percentual_markup", "margem_lucro"]);

        $custo = $this->parseDecimalNullable($this->extractScalar($cost_raw, ["valor", "value", "preco", "price", "custo", "cost", "amount"])) ?? 0;
        $valor_venda = $this->parseDecimalNullable($this->extractScalar($sale_raw, ["valor", "value", "preco", "price", "venda", "sale", "amount"])) ?? 0;
        $margem = $this->parseDecimalNullable($this->extractScalar($markup_raw, ["valor", "value", "percentual", "percent", "porcentagem"])) ?? 0;

        if ($margem <= 0 && $custo > 0 && $valor_venda > 0) {
            $margem = (($valor_venda / $custo) - 1) * 100;
        }

        $categoria = $this->getFirstValue($service, ["categoria_receita", "categoriaReceita", "categoria", "category", "categoria_id", "category_id"]);
        $categoria = $this->extractScalar($categoria, ["id", "codigo", "code"]);
        $categoria_receita = is_numeric($categoria) ? (int)$categoria : null;

        return [
            "tipo" => "ordem_servico",
            "descricao" => $descricao,
            "categoria_receita" => $categoria_receita,
            "custo" => $custo,
            "margem" => $margem,
            "valor_venda" => $valor_venda,
            "servico_locacao" => 0,
            "bloquear_inadimplencia" => 0,
            "created_at" => get_current_utc_time(),
            "updated_at" => get_current_utc_time(),
            "deleted" => 0
        ];
    }

    private function getFirstValue($data, $keys)
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $data) && $data[$key] !== null && $data[$key] !== "") {
                return $data[$key];
            }
        }
        return null;
    }

    private function extractScalar($value, $keys = [])
    {
        if (is_array($value)) {
            foreach ($keys as $key) {
                if (array_key_exists($key, $value) && $value[$key] !== null && $value[$key] !== "") {
                    return $value[$key];
                }
            }
            return null;
        }

        if (is_object($value)) {
            foreach ($keys as $key) {
                if (isset($value->$key) && $value->$key !== null && $value->$key !== "") {
                    return $value->$key;
                }
            }
            return null;
        }

        return $value;
    }

    private function extractUnitFromProduct($product)
    {
        if (!is_array($product)) {
            return "";
        }

        $unit_raw = $this->getFirstValue($product, [
            "unidade",
            "unidadeComercial",
            "unidade_comercial",
            "unidadeComercialDescricao",
            "unidade_comercial_descricao",
            "unidadeDeMedida",
            "unidade_de_medida",
            "unidade_medida",
            "unit",
            "unit_type",
            "unitType",
            "uom",
            "uom_code"
        ]);
        $unit = $this->extractScalar($unit_raw, ["descricao", "description", "nome", "name", "sigla", "abrev", "codigo", "code", "unidade", "unit"]);

        if (!$unit) {
            $fiscal = get_array_value($product, "fiscal");
            $fiscal_unit = $this->extractScalar(get_array_value((array)$fiscal, "unidade_medida"), ["descricao", "name", "sigla", "codigo"]);
            if ($fiscal_unit) {
                $unit = $fiscal_unit;
            }
        }

        if (!$unit) {
            $convs = get_array_value($product, "conversao_unidade_medida");
            if (is_array($convs) && isset($convs[0]) && is_array($convs[0])) {
                $conv_unit = $this->extractScalar(get_array_value($convs[0], "unidade_medida"), ["descricao", "name", "sigla", "codigo"]);
                if ($conv_unit) {
                    $unit = $conv_unit;
                }
            }
        }

        return trim((string)$unit);
    }

    private function mergeProductDetail($mapped, $detail)
    {
        if (!is_array($mapped) || !is_array($detail)) {
            return $mapped;
        }

        if ($this->isEmptyValue(get_array_value($mapped, "unit_type"))) {
            $unit = $this->extractUnitFromProduct($detail);
            if ($unit) {
                $mapped["unit_type"] = $unit;
            }
        }

        $stock = get_array_value($detail, "estoque");
        $stock_cost = $this->parseDecimalNullable($this->extractScalar(get_array_value((array)$stock, "custo_medio"), ["valor", "value", "price", "cost", "amount"]));
        $stock_sale = $this->parseDecimalNullable($this->extractScalar(get_array_value((array)$stock, "valor_venda"), ["valor", "value", "price", "sale", "amount"]));

        if ($this->isEmptyNumber(get_array_value($mapped, "cost")) && $stock_cost !== null && $stock_cost > 0) {
            $mapped["cost"] = $stock_cost;
            $mapped["rate"] = $stock_cost;
        }
        if ($this->isEmptyNumber(get_array_value($mapped, "sale")) && $stock_sale !== null && $stock_sale > 0) {
            $mapped["sale"] = $stock_sale;
        }

        $cost = get_array_value($mapped, "cost");
        $sale = get_array_value($mapped, "sale");
        $markup = get_array_value($mapped, "markup");
        if ($this->isEmptyNumber($markup) && $cost && $sale && (float)$cost > 0 && (float)$sale > 0) {
            $mapped["markup"] = (((float)$sale / (float)$cost) - 1) * 100;
        }

        return $mapped;
    }

    private function isEmptyValue($value)
    {
        return $value === null || trim((string)$value) === "";
    }

    private function isEmptyNumber($value)
    {
        if ($value === null || $value === "") {
            return true;
        }
        if (!is_numeric($value)) {
            return true;
        }
        return (float)$value <= 0;
    }

    private function parseDecimalNullable($value)
    {
        if ($value === null) {
            return null;
        }
        $text = trim((string)$value);
        if ($text === '') {
            return null;
        }

        $text = preg_replace('/[^\d,\.\-]/', '', $text);
        $last_comma = strrpos($text, ',');
        $last_dot = strrpos($text, '.');

        if ($last_comma !== false && $last_dot !== false) {
            if ($last_comma > $last_dot) {
                $text = str_replace('.', '', $text);
                $text = str_replace(',', '.', $text);
            } else {
                $text = str_replace(',', '', $text);
            }
        } elseif ($last_comma !== false) {
            $text = str_replace('.', '', $text);
            $text = str_replace(',', '.', $text);
        } else {
            $text = str_replace(',', '', $text);
        }

        if ($text === '' || $text === '-' || $text === '.') {
            return null;
        }

        $num = (float)$text;
        return is_nan($num) ? null : $num;
    }

    private function normalizeDate($value)
    {
        if ($value === null || $value === "") {
            return null;
        }

        if (is_array($value) || is_object($value)) {
            return null;
        }

        $text = trim((string)$value);
        if ($text === "") {
            return null;
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}/', $text)) {
            return substr($text, 0, 10);
        }

        if (preg_match('/^\d{2}\/\d{2}\/\d{4}/', $text)) {
            $parts = explode("/", substr($text, 0, 10));
            if (count($parts) === 3) {
                return $parts[2] . "-" . $parts[1] . "-" . $parts[0];
            }
        }

        $dt = \DateTime::createFromFormat("Y-m-d H:i:s", $text);
        if ($dt) {
            return $dt->format("Y-m-d");
        }

        return null;
    }

    private function mapPayableItem($item)
    {
        $item = is_array($item) ? $item : (array)$item;
        $ca_id = $this->getFirstValue($item, ["id", "idContaAzul", "id_conta_azul", "code", "codigo"]);
        if (!$ca_id) {
            return null;
        }

        $value_raw = $this->getFirstValue($item, [
            "valor",
            "value",
            "valor_original",
            "valorOriginal",
            "valor_total",
            "valorTotal",
            "amount",
            "total",
            "valor_documento",
            "valorDocumento",
            "valor_nominal",
            "valorNominal",
            "valor_liquido",
            "valorLiquido",
            "valor_pago",
            "valorPago",
            "valor_baixado",
            "valorBaixado"
        ]);
        $value = $this->parseDecimalNullable($this->extractScalar($value_raw, ["valor", "value", "amount", "total"]));

        $paid_date_raw = $this->getFirstValue($item, [
            "data_pagamento",
            "dataPagamento",
            "data_baixa",
            "dataBaixa",
            "data_pagto",
            "dataPagamentoEfetivo",
            "payment_date",
            "paid_date",
            "data_pagamento_efetivo",
            "dataPagamentoEfetivo"
        ]);
        $due_date_raw = $this->getFirstValue($item, [
            "data_vencimento",
            "dataVencimento",
            "vencimento",
            "due_date",
            "dueDate",
            "data_prevista",
            "dataPrevista",
            "data_emissao",
            "dataEmissao",
            "data_competencia",
            "dataCompetencia"
        ]);
        $date = $this->normalizeDate($paid_date_raw ?: $due_date_raw);

        if ((!$value || !$date) && isset($item["parcela"]) && is_array($item["parcela"])) {
            $parcela = $item["parcela"];
            if (!$value) {
                $value_raw = $this->getFirstValue($parcela, [
                    "valor",
                    "value",
                    "valor_original",
                    "valorOriginal",
                    "valor_total",
                    "valorTotal",
                    "amount",
                    "valor_documento",
                    "valorDocumento",
                    "valor_nominal",
                    "valorNominal",
                    "valor_liquido",
                    "valorLiquido",
                    "valor_pago",
                    "valorPago",
                    "valor_baixado",
                    "valorBaixado"
                ]);
                $value = $this->parseDecimalNullable($this->extractScalar($value_raw, ["valor", "value", "amount", "total"]));
            }
            if (!$date) {
                $paid_date_raw = $this->getFirstValue($parcela, [
                    "data_pagamento",
                    "dataPagamento",
                    "data_baixa",
                    "dataBaixa",
                    "data_pagto",
                    "dataPagamentoEfetivo",
                    "payment_date",
                    "paid_date"
                ]);
                $due_date_raw = $this->getFirstValue($parcela, [
                    "data_vencimento",
                    "dataVencimento",
                    "vencimento",
                    "due_date",
                    "dueDate",
                    "data_prevista",
                    "dataPrevista",
                    "data_emissao",
                    "dataEmissao",
                    "data_competencia",
                    "dataCompetencia"
                ]);
                $date = $this->normalizeDate($paid_date_raw ?: $due_date_raw);
            }
        }

        $description = $this->getFirstValue($item, ["descricao", "description", "historico", "observacao", "notes", "nome", "name"]);
        $description = $description ? (string)$description : "";

        $cost_type = $this->guessCostType($description);

        return [
            "ca_id" => (string)$ca_id,
            "value" => $value,
            "date" => $date,
            "description" => $description,
            "cost_type" => $cost_type
        ];
    }

    private function mapReceivableItem($item)
    {
        $item = is_array($item) ? $item : (array)$item;
        $ca_id = $this->getFirstValue($item, ["id", "idContaAzul", "id_conta_azul", "code", "codigo"]);
        if (!$ca_id) {
            return null;
        }

        $value_raw = $this->getFirstValue($item, [
            "valor",
            "value",
            "valor_original",
            "valorOriginal",
            "valor_total",
            "valorTotal",
            "amount",
            "total",
            "valor_documento",
            "valorDocumento",
            "valor_nominal",
            "valorNominal",
            "valor_liquido",
            "valorLiquido",
            "valor_recebido",
            "valorRecebido"
        ]);
        $value = $this->parseDecimalNullable($this->extractScalar($value_raw, ["valor", "value", "amount", "total"]));

        $received_date_raw = $this->getFirstValue($item, [
            "data_recebimento",
            "dataRecebimento",
            "data_baixa",
            "dataBaixa",
            "data_recebimento_efetivo",
            "received_date",
            "receipt_date",
            "data_pagamento",
            "dataPagamento"
        ]);
        $due_date_raw = $this->getFirstValue($item, [
            "data_vencimento",
            "dataVencimento",
            "vencimento",
            "due_date",
            "dueDate",
            "data_prevista",
            "dataPrevista",
            "data_emissao",
            "dataEmissao",
            "data_competencia",
            "dataCompetencia"
        ]);
        $date = $this->normalizeDate($received_date_raw ?: $due_date_raw);

        $description = $this->getFirstValue($item, ["descricao", "description", "historico", "observacao", "notes", "nome", "name"]);
        $description = $description ? (string)$description : "";

        return [
            "ca_id" => (string)$ca_id,
            "value" => $value,
            "date" => $date,
            "description" => $description
        ];
    }

    private function guessCostType($text)
    {
        $text = mb_strtolower((string)$text);
        $text = str_replace(["ã", "á", "à", "â", "é", "ê", "í", "ó", "ô", "õ", "ú", "ç"], ["a", "a", "a", "a", "e", "e", "i", "o", "o", "o", "u", "c"], $text);

        if (strpos($text, "servico") !== false || strpos($text, "serviço") !== false) {
            return "servico";
        }
        if (strpos($text, "mao de obra") !== false || strpos($text, "mão de obra") !== false || strpos($text, "mao") !== false) {
            return "mao_obra";
        }
        if (strpos($text, "material") !== false) {
            return "material";
        }
        if (strpos($text, "terceir") !== false) {
            return "terceiros";
        }

        return "outros";
    }

    private function mapTransportadora($customer)
    {
        return $this->mapSupplier($customer);
    }

    private function saveSupplier($suppliers_model, $supplier_data)
    {
        $db = db_connect('default');
        $table = $db->prefixTable('purchases_suppliers');

        $existing_id = 0;
        if (!empty($supplier_data["tax_id"])) {
            $row = $db->table($table)
                ->select("id")
                ->where("deleted", 0)
                ->where("tax_id", $supplier_data["tax_id"])
                ->get()
                ->getRow();
            $existing_id = $row ? (int)$row->id : 0;
        }

        if (!$existing_id) {
            $row = $db->table($table)
                ->select("id")
                ->where("deleted", 0)
                ->where("name", $supplier_data["name"])
                ->get()
                ->getRow();
            $existing_id = $row ? (int)$row->id : 0;
        }

        $data = $supplier_data;
        if ($existing_id) {
            $suppliers_model->ci_save($data, $existing_id);
            return "updated";
        } else {
            $suppliers_model->ci_save($data);
            return "imported";
        }
    }

    private function saveTransportadora($transportadoras_model, $transportadora_data)
    {
        $db = db_connect('default');
        $table = $db->prefixTable('purchases_transportadoras');

        $existing_id = 0;
        if (!empty($transportadora_data["tax_id"])) {
            $row = $db->table($table)
                ->select("id")
                ->where("deleted", 0)
                ->where("tax_id", $transportadora_data["tax_id"])
                ->get()
                ->getRow();
            $existing_id = $row ? (int)$row->id : 0;
        }

        if (!$existing_id) {
            $row = $db->table($table)
                ->select("id")
                ->where("deleted", 0)
                ->where("name", $transportadora_data["name"])
                ->get()
                ->getRow();
            $existing_id = $row ? (int)$row->id : 0;
        }

        $data = $transportadora_data;
        if ($existing_id) {
            $transportadoras_model->ci_save($data, $existing_id);
            return "updated";
        } else {
            $transportadoras_model->ci_save($data);
            return "imported";
        }
    }

    private function getPurchasesSuppliersModel()
    {
        $purchases_path = PLUGINPATH . "Purchases";
        if (!is_dir($purchases_path)) {
            return null;
        }

        if (!class_exists("\\Purchases\\Models\\Purchases_suppliers_model")) {
            return null;
        }

        $db = db_connect('default');
        $table = $db->prefixTable('purchases_suppliers');
        if (!$db->tableExists($table)) {
            return null;
        }

        return model('Purchases\\Models\\Purchases_suppliers_model');
    }

    private function getPurchasesTransportadorasModel()
    {
        $purchases_path = PLUGINPATH . "Purchases";
        if (!is_dir($purchases_path)) {
            return null;
        }

        if (!class_exists("\\Purchases\\Models\\Purchases_transportadoras_model")) {
            return null;
        }

        $db = db_connect('default');
        $table = $db->prefixTable('purchases_transportadoras');
        if (!$db->tableExists($table)) {
            return null;
        }

        return model('Purchases\\Models\\Purchases_transportadoras_model');
    }

    private function getContaAzulLabelId()
    {
        $Labels_model = new Labels_model();
        $label = $Labels_model->get_one_where([
            "title" => "CA",
            "context" => "client",
            "deleted" => 0
        ]);

        if ($label && get_array_value((array)$label, "id")) {
            return $label->id;
        }

        $label_data = [
            "title" => "CA",
            "color" => "#1f78d1",
            "context" => "client",
            "user_id" => 0,
            "sort" => 0
        ];

        return $Labels_model->ci_save($label_data);
    }

    private function mergeLabelId($labels, $label_id)
    {
        if (!$label_id) {
            return $labels;
        }

        $labels = trim((string)$labels);
        if ($labels === "") {
            return (string)$label_id;
        }

        $list = array_filter(array_map('trim', explode(',', $labels)));
        if (!in_array((string)$label_id, $list, true)) {
            $list[] = (string)$label_id;
        }

        return implode(',', $list);
    }

    private function _get_company_id()
    {
        if (isset($this->login_user->company_id) && $this->login_user->company_id) {
            return $this->login_user->company_id;
        }

        return get_default_company_id();
    }

    private function ensureContaAzulUnitsTable()
    {
        $db = db_connect('default');
        $table = $db->prefixTable('contaazul_units');

        if ($db->tableExists($table)) {
            return;
        }

        $db->query("CREATE TABLE IF NOT EXISTS `$table` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `name` VARCHAR(190) NOT NULL,
            `created_at` DATETIME NULL,
            `updated_at` DATETIME NULL,
            `deleted` TINYINT(1) NOT NULL DEFAULT 0,
            PRIMARY KEY (`id`),
            UNIQUE KEY `name` (`name`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    private function saveContaAzulUnit($name)
    {
        $name = trim((string)$name);
        if ($name === "") {
            return null;
        }

        $db = db_connect('default');
        $table = $db->prefixTable('contaazul_units');
        if (!$db->tableExists($table)) {
            return null;
        }

        $existing = $db->table($table)
            ->select("id")
            ->where("deleted", 0)
            ->where("name", $name)
            ->get()
            ->getRow();

        $now = get_current_utc_time();
        if ($existing && get_array_value((array)$existing, "id")) {
            $db->table($table)->update(["updated_at" => $now], ["id" => $existing->id]);
            return "updated";
        }

        $db->table($table)->insert([
            "name" => $name,
            "created_at" => $now,
            "updated_at" => $now,
            "deleted" => 0
        ]);

        return "imported";
    }
}


