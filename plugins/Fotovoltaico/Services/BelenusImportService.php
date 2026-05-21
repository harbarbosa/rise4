<?php

namespace Fotovoltaico\Services;

use Fotovoltaico\Models\Belenus_import_logs_model;
use Fotovoltaico\Models\Kit_items_model;
use Fotovoltaico\Models\Kits_model;
use Fotovoltaico\Models\Products_model;

class BelenusImportService
{
    private $Api_service;
    private $Products_model;
    private $Kits_model;
    private $Kit_items_model;
    private $Import_logs_model;

    public function __construct()
    {
        $this->Api_service = new BelenusApiService();
        $this->Products_model = model(Products_model::class);
        $this->Kits_model = model(Kits_model::class);
        $this->Kit_items_model = model(Kit_items_model::class);
        $this->Import_logs_model = model(Belenus_import_logs_model::class);
    }

    public function importProduct($externalProductId)
    {
        $externalProductId = (int) $externalProductId;
        if (!$externalProductId) {
            return $this->result(false, 'ID de produto inválido.');
        }

        $response = $this->Api_service->getProductById($externalProductId);
        if (!get_array_value($response, 'success')) {
            return $response;
        }

        return $this->importOrUpdateProduct($this->unwrapData(get_array_value($response, 'data')), $externalProductId);
    }

    public function importProducts(array $externalProductIds)
    {
        $results = array();
        foreach ($externalProductIds as $externalProductId) {
            $results[] = $this->importProduct($externalProductId);
        }

        return $this->aggregateResults($results);
    }

    public function importOrUpdateProduct(array $externalData)
    {
        $external_id = (int) get_array_value($externalData, 'id');
        return $this->importOrUpdateProductWithId($externalData, $external_id);
    }

    public function updateProductPrice($localProductId)
    {
        $product = $this->Products_model->get_one((int) $localProductId);
        if (!$product) {
            return $this->result(false, 'Produto local não encontrado.');
        }

        $external_id = trim((string) ($product->external_id ?? ''));
        if ($external_id === '') {
            return $this->result(false, 'Produto não possui referência externa da Belenus.');
        }

        $price = $this->Api_service->getProductPrice($external_id);
        if (!get_array_value($price, 'success')) {
            return $price;
        }

        return $this->applyProductPrice($product, $this->unwrapData(get_array_value($price, 'data')));
    }

    public function updateProductPricesBatch(array $localProductIds)
    {
        $ids = array();
        $localProducts = array();
        foreach ($localProductIds as $localProductId) {
            $product = $this->Products_model->get_one((int) $localProductId);
            if ($product && !empty($product->external_id)) {
                $ids[] = (int) $product->external_id;
                $localProducts[(int) $product->id] = $product;
            }
        }

        if (!$ids) {
            return $this->result(false, 'Nenhum produto com referência Belenus encontrada.');
        }

        $response = $this->Api_service->getProductPricesBatch($ids);
        if (!get_array_value($response, 'success')) {
            $fallback = array();
            foreach ($localProducts as $product) {
                $fallback[] = $this->updateProductPrice($product->id);
            }
            return $this->aggregateResults($fallback);
        }

        $data = $this->unwrapData(get_array_value($response, 'data'));
        $items = array();
        if (isset($data['items']) && is_array($data['items'])) {
            $items = $data['items'];
        } elseif (isset($data['items']) === false && isset($data[0])) {
            $items = $data;
        }

        $results = array();
        foreach ($items as $item) {
            $external_id = (string) get_array_value($item, 'produtoId');
            foreach ($localProducts as $product) {
                if ((string) $product->external_id === $external_id) {
                    $results[] = $this->applyProductPrice($product, $item);
                    break;
                }
            }
        }

        return $this->aggregateResults($results);
    }

    public function importKit($externalKitId)
    {
        $externalKitId = (int) $externalKitId;
        if (!$externalKitId) {
            return $this->result(false, 'ID de kit inválido.');
        }

        $response = $this->Api_service->getKitById($externalKitId);
        if (!get_array_value($response, 'success')) {
            return $response;
        }

        $kitResult = $this->importOrUpdateKit($this->unwrapData(get_array_value($response, 'data')), $externalKitId);
        if (!get_array_value($kitResult, 'success')) {
            return $kitResult;
        }

        $kit = $this->Kits_model->get_by_external_reference('belenus', (string) $externalKitId);
        $kitData = $this->unwrapData(get_array_value($response, 'data'));
        $items = get_array_value($kitData, 'itens');
        if (!is_array($items) || !$items) {
            $this->registerImportLog('kit', (string) $externalKitId, $kit ? (int) $kit->id : 0, 'import', 'warning', 'Kit sem itens.');
            return $kitResult;
        }

        if ($kit && $kit->id) {
            $this->importKitItems($kit->id, $items);
        }

        return $kitResult;
    }

    public function importKits(array $externalKitIds)
    {
        $results = array();
        foreach ($externalKitIds as $externalKitId) {
            $results[] = $this->importKit($externalKitId);
        }

        return $this->aggregateResults($results);
    }

    public function importOrUpdateKit(array $externalKitData)
    {
        $external_id = (string) get_array_value($externalKitData, 'id');
        $kit = $this->Kits_model->get_by_external_reference('belenus', $external_id, (string) get_array_value($externalKitData, 'codigo'), (string) get_array_value($externalKitData, 'nome'));
        $is_new = !$kit || !$kit->id;
        $payload = $this->mapKitData($externalKitData);
        $payload['external_provider'] = 'belenus';
        $payload['external_id'] = $external_id;
        $payload['external_payload_json'] = json_encode($externalKitData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $payload['last_sync_at'] = get_my_local_time();
        $payload['last_import_at'] = get_my_local_time();
        $payload['updated_at'] = get_my_local_time();

        if ($is_new) {
            $payload['created_by'] = get_array_value($externalKitData, 'created_by') ?: 0;
            $payload['created_at'] = get_my_local_time();
        }

        $saved = $this->Kits_model->ci_save($payload, $kit && $kit->id ? $kit->id : 0);
        if (!$saved) {
            return $this->result(false, 'Não foi possível salvar o kit local.');
        }

        $kit = $this->Kits_model->get_one($saved);
        $this->registerImportLog('kit', $external_id, $saved, 'import', 'completed', 'Kit importado com sucesso.', $externalKitData);
        return $this->result(true, 'Kit importado com sucesso.', array('local_id' => $saved, 'kit' => $kit, 'created' => $is_new));
    }

    public function importKitItems($localKitId, array $items)
    {
        $localKitId = (int) $localKitId;
        if (!$localKitId) {
            return $this->result(false, 'Kit local inválido.');
        }

        $normalized = array();
        foreach ($items as $index => $item) {
            $externalProductId = (int) get_array_value($item, 'produtoId') ?: (int) get_array_value($item, 'productId');
            $product = null;
            if ($externalProductId) {
                $product = $this->Products_model->get_by_external_reference('belenus', (string) $externalProductId);
                if (!$product) {
                    $productImport = $this->importProduct($externalProductId);
                    if (get_array_value($productImport, 'success')) {
                        $product = $this->Products_model->get_by_external_reference('belenus', (string) $externalProductId);
                    }
                }
            }

            if (!$product) {
                continue;
            }

            $quantity = (float) get_array_value($item, 'quantidade') ?: (float) get_array_value($item, 'quantity') ?: 1;
            $unitPrice = (float) get_array_value($item, 'precoVenda') ?: (float) get_array_value($item, 'unitPrice') ?: (float) ($product->sale_price ?? 0);
            $unitCost = (float) $product->cost_price;
            $normalized[] = array(
                'product_id' => (int) $product->id,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'unit_cost' => $unitCost,
                'total_price' => $quantity * $unitPrice,
                'total_cost' => $quantity * $unitCost,
                'notes' => trim((string) get_array_value($item, 'descricao')) ?: trim((string) get_array_value($item, 'description')),
                'sort' => $index + 1,
            );
        }

        $this->Kit_items_model->replace_kit_items($localKitId, $normalized);
        $this->Kits_model->recalculate_totals($localKitId);
        return $this->result(true, 'Itens do kit importados com sucesso.', array('kit_id' => $localKitId, 'items_count' => count($normalized)));
    }

    public function updateKitPrice($localKitId)
    {
        $kit = $this->Kits_model->get_one((int) $localKitId);
        if (!$kit) {
            return $this->result(false, 'Kit local não encontrado.');
        }

        $external_id = trim((string) ($kit->external_id ?? ''));
        if ($external_id === '') {
            return $this->result(false, 'Kit não possui referência externa da Belenus.');
        }

        $response = $this->Api_service->getKitPrice($external_id);
        if (!get_array_value($response, 'success')) {
            return $response;
        }

        $price = $this->unwrapData(get_array_value($response, 'data'));
        $payload = array(
            'total_price' => (float) get_array_value($price, 'precoVenda') ?: (float) $kit->total_price,
            'promotional_price' => (float) get_array_value($price, 'precoPromocional') ?: 0,
            'stock' => (float) get_array_value($price, 'estoque') ?: 0,
            'last_price_sync_at' => get_my_local_time(),
            'updated_at' => get_my_local_time(),
        );

        $this->Kits_model->ci_save($payload, $kit->id);
        return $this->result(true, 'Preço do kit atualizado.', $payload);
    }

    public function syncProducts($filters = array())
    {
        $page = 1;
        $pageSize = (int) get_array_value($filters, 'pageSize') ?: 50;
        $limit = (int) get_array_value($filters, 'limit');
        $created = 0;
        $updated = 0;
        $errors = array();

        while (true) {
            $payload = $filters;
            $payload['page'] = $page;
            $payload['pageSize'] = $pageSize;
            $response = $this->Api_service->getProducts($payload);
            if (!get_array_value($response, 'success')) {
                return $response;
            }

            $data = $this->unwrapData(get_array_value($response, 'data'));
            $items = get_array_value($data, 'items');
            if (!is_array($items) || !$items) {
                break;
            }

            foreach ($items as $item) {
                $import = $this->importOrUpdateProduct($item);
                if (get_array_value($import, 'success')) {
                    $meta = get_array_value($import, 'data');
                    if (get_array_value($meta, 'created')) {
                        $created++;
                    } else {
                        $updated++;
                    }
                } else {
                    $errors[] = get_array_value($import, 'message');
                }
            }

            if ($limit > 0 && ($created + $updated) >= $limit) {
                break;
            }

            $total = (int) get_array_value($data, 'total');
            if (($page * $pageSize) >= $total) {
                break;
            }

            $page++;
        }

        return $this->result(true, 'Sincronização de produtos concluída.', array(
            'created' => $created,
            'updated' => $updated,
            'errors' => $errors,
            'page' => $page,
        ));
    }

    public function syncKits($filters = array())
    {
        $page = 1;
        $pageSize = (int) get_array_value($filters, 'pageSize') ?: 25;
        $limit = (int) get_array_value($filters, 'limit');
        $created = 0;
        $updated = 0;
        $errors = array();

        while (true) {
            $payload = $filters;
            $payload['page'] = $page;
            $payload['pageSize'] = $pageSize;
            $response = $this->Api_service->getKits($payload);
            if (!get_array_value($response, 'success')) {
                return $response;
            }

            $data = $this->unwrapData(get_array_value($response, 'data'));
            $items = get_array_value($data, 'items');
            if (!is_array($items) || !$items) {
                break;
            }

            foreach ($items as $item) {
                $import = $this->importOrUpdateKit($item);
                if (get_array_value($import, 'success')) {
                    if (get_array_value(get_array_value($import, 'data'), 'created')) {
                        $created++;
                    } else {
                        $updated++;
                    }
                } else {
                    $errors[] = get_array_value($import, 'message');
                }
            }

            if ($limit > 0 && ($created + $updated) >= $limit) {
                break;
            }

            $total = (int) get_array_value($data, 'total');
            if (($page * $pageSize) >= $total) {
                break;
            }

            $page++;
        }

        return $this->result(true, 'Sincronização de kits concluída.', array(
            'created' => $created,
            'updated' => $updated,
            'errors' => $errors,
            'page' => $page,
        ));
    }

    public function importOrUpdateProductWithId(array $externalData, $external_id)
    {
        $external_id = (string) $external_id;
        $product = $this->Products_model->get_by_external_reference('belenus', $external_id, (string) get_array_value($externalData, 'codigo'), (string) get_array_value($externalData, 'nome'), (string) get_array_value($externalData, 'marca'));
        $is_new = !$product || !$product->id;
        $payload = $this->mapProductData($externalData);
        $payload['external_provider'] = 'belenus';
        $payload['external_id'] = $external_id;
        $payload['external_payload_json'] = json_encode($externalData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $payload['last_sync_at'] = get_my_local_time();
        $payload['last_import_at'] = get_my_local_time();
        $payload['updated_at'] = get_my_local_time();

        if ($is_new) {
            $payload['created_by'] = get_array_value($externalData, 'created_by') ?: 0;
            $payload['created_at'] = get_my_local_time();
        }

        $saved = $this->Products_model->ci_save($payload, $product && $product->id ? $product->id : 0);
        if (!$saved) {
            return $this->result(false, 'Não foi possível salvar o produto local.');
        }

        $product = $this->Products_model->get_one($saved);
        $this->registerImportLog('product', $external_id, $saved, 'import', 'completed', 'Produto importado com sucesso.', $externalData);
        return $this->result(true, 'Produto importado com sucesso.', array('local_id' => $saved, 'product' => $product, 'created' => $is_new));
    }

    private function mapProductData(array $externalData)
    {
        $name = trim((string) get_array_value($externalData, 'nome'));
        $code = trim((string) get_array_value($externalData, 'codigo'));
        $brand = trim((string) get_array_value($externalData, 'marca'));
        $category = trim((string) get_array_value($externalData, 'categoria'));
        $price = (float) get_array_value($externalData, 'precoVenda');
        $promo = (float) get_array_value($externalData, 'precoPromocional');
        $stock = (float) get_array_value($externalData, 'estoque');

        return array(
            'category_id' => null,
            'distributor_id' => null,
            'product_type' => $this->inferProductType($name, $category),
            'sku' => $code ?: null,
            'title' => $name ?: ('Produto ' . get_array_value($externalData, 'id')),
            'description' => trim((string) get_array_value($externalData, 'descricao')) ?: null,
            'brand' => $brand ?: null,
            'model' => trim((string) get_array_value($externalData, 'modelo')) ?: null,
            'unit' => 'un',
            'warranty' => null,
            'power_rating' => (float) get_array_value($externalData, 'potencia') ?: 0,
            'efficiency' => 0,
            'voltage' => null,
            'cost_price' => 0,
            'sale_price' => $promo > 0 ? $promo : $price,
            'promotional_price' => $promo > 0 ? $promo : 0,
            'stock' => $stock,
            'tax_rate' => 0,
            'technical_specs_json' => json_encode($externalData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'active' => get_array_value($externalData, 'ativo') ? 1 : 0,
        );
    }

    private function mapKitData(array $externalData)
    {
        $name = trim((string) get_array_value($externalData, 'nome'));
        $code = trim((string) get_array_value($externalData, 'codigo'));
        $power = (float) get_array_value($externalData, 'potencia');
        $price = (float) get_array_value($externalData, 'precoVenda');
        $promo = (float) get_array_value($externalData, 'precoPromocional');
        $stock = (float) get_array_value($externalData, 'estoque');

        return array(
            'category_id' => null,
            'distributor_id' => null,
            'title' => $name ?: ('Kit ' . get_array_value($externalData, 'id')),
            'code' => $code ?: null,
            'description' => trim((string) get_array_value($externalData, 'descricao')) ?: null,
            'power_kwp' => $power > 0 ? ($power / 1000) : 0,
            'notes' => null,
            'status' => trim((string) get_array_value($externalData, 'status')) ?: 'ativo',
            'total_cost' => 0,
            'total_price' => $price,
            'promotional_price' => $promo > 0 ? $promo : 0,
            'stock' => $stock,
            'margin_value' => 0,
            'margin_percent' => 0,
            'active' => strtolower((string) get_array_value($externalData, 'status')) === 'ativo' ? 1 : 0,
        );
    }

    private function applyProductPrice($product, array $priceData)
    {
        $payload = array(
            'sale_price' => (float) get_array_value($priceData, 'precoVenda') ?: (float) ($product->sale_price ?? 0),
            'promotional_price' => (float) get_array_value($priceData, 'precoPromocional') ?: 0,
            'stock' => (float) get_array_value($priceData, 'estoque') ?: 0,
            'last_price_sync_at' => get_my_local_time(),
            'updated_at' => get_my_local_time(),
        );

        $this->Products_model->ci_save($payload, $product->id);
        return $this->result(true, 'Preço do produto atualizado.', $payload);
    }

    private function inferProductType($name, $category)
    {
        $text = strtolower(trim($name . ' ' . $category));
        if (strpos($text, 'inversor') !== false) {
            return 'inversor';
        }
        if (strpos($text, 'estrutura') !== false) {
            return 'estrutura';
        }
        if (strpos($text, 'serv') !== false) {
            return 'servico';
        }
        return 'modulo';
    }

    private function registerImportLog($entity_type, $external_id, $local_id, $action, $status, $message, $payload = array(), $response = array())
    {
        return $this->Import_logs_model->register_log(array(
            'provider' => 'belenus',
            'entity_type' => $entity_type,
            'external_id' => $external_id,
            'local_id' => $local_id,
            'action' => $action,
            'status' => $status,
            'message' => $message,
            'payload_json' => json_encode($this->sanitize($payload), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'response_json' => json_encode($this->sanitize($response), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'created_by' => 0,
        ));
    }

    private function sanitize($payload)
    {
        if (!is_array($payload)) {
            return $payload;
        }

        $secret_keys = array('token', 'secret', 'password', 'senha', 'api_key', 'apikey', 'authorization');
        $result = array();
        foreach ($payload as $key => $value) {
            if (is_array($value)) {
                $result[$key] = $this->sanitize($value);
                continue;
            }
            $result[$key] = in_array(strtolower((string) $key), $secret_keys, true) ? '***' : $value;
        }
        return $result;
    }

    private function unwrapData($data)
    {
        if (is_array($data)) {
            return $data;
        }

        if (is_string($data)) {
            $decoded = json_decode($data, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
        }

        return array();
    }

    private function aggregateResults(array $results)
    {
        $success = false;
        $messages = array();
        foreach ($results as $result) {
            $success = $success || (bool) get_array_value($result, 'success');
            $message = get_array_value($result, 'message');
            if ($message) {
                $messages[] = $message;
            }
        }

        return $this->result($success, implode(' | ', array_unique($messages)), array('results' => $results));
    }

    private function result($success, $message, $data = array())
    {
        return array(
            'success' => (bool) $success,
            'message' => $message,
            'data' => $data,
        );
    }
}
