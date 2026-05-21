<?php

namespace Fotovoltaico\Services\Providers;

class MockSupplierProvider extends AbstractSupplierProvider
{
    public function getKey()
    {
        return 'mock';
    }

    public function getLabel()
    {
        return 'Mock Supplier';
    }

    public function authenticate($config = array())
    {
        return array(
            'success' => true,
            'provider' => $this->getKey(),
            'message' => 'Mock authentication successful',
            'http_status' => 200,
        );
    }

    public function testConnection($config = array())
    {
        return array(
            'success' => true,
            'provider' => $this->getKey(),
            'message' => 'Mock connection OK',
            'http_status' => 200,
            'endpoint' => 'mock://connection',
        );
    }

    public function consultProducts($query = array(), $config = array())
    {
        return $this->_build_result('products', $query);
    }

    public function consultKits($query = array(), $config = array())
    {
        return $this->_build_result('kits', $query);
    }

    public function consultFreight($query = array(), $config = array())
    {
        $total = 0;
        $items = get_array_value($query, 'items');
        if (is_array($items)) {
            foreach ($items as $item) {
                $total += (float) get_array_value($item, 'unit_price') * (float) get_array_value($item, 'quantity');
            }
        }

        return array(
            'success' => true,
            'provider' => $this->getKey(),
            'type' => 'freight',
            'http_status' => 200,
            'payload' => array(
                'freight_value' => round(max(49.9, $total * 0.03), 2),
                'delivery_days' => 5,
            ),
        );
    }

    public function getQuote($query = array(), $config = array())
    {
        $products = $this->consultProducts($query, $config);
        $kits = $this->consultKits($query, $config);
        $freight = $this->consultFreight($query, $config);
        $products_total = 0;
        $kits_total = 0;

        foreach ((array) get_array_value($products, 'payload') as $product) {
            $products_total += (float) get_array_value($product, 'price');
        }

        foreach ((array) get_array_value($kits, 'payload') as $kit) {
            $kits_total += (float) get_array_value($kit, 'price');
        }

        $freight_value = (float) get_array_value(get_array_value($freight, 'payload'), 'freight_value');

        return array(
            'success' => true,
            'provider' => $this->getKey(),
            'http_status' => 200,
            'payload' => array(
                'products_total' => round($products_total, 2),
                'kits_total' => round($kits_total, 2),
                'freight_value' => round($freight_value, 2),
                'grand_total' => round($products_total + $kits_total + $freight_value, 2),
                'currency' => 'BRL',
                'availability' => 'available',
            ),
        );
    }

    private function _build_result($type, $query)
    {
        $items = array();
        $base_price = $type === 'kits' ? 8900 : 1490;
        $count = $type === 'kits' ? 3 : 5;
        for ($i = 1; $i <= $count; $i++) {
            $items[] = array(
                'id' => $type . '-' . $i,
                'sku' => strtoupper($type) . '-' . str_pad((string) $i, 3, '0', STR_PAD_LEFT),
                'name' => ucfirst($type) . ' ' . $i,
                'price' => round($base_price * $i, 2),
                'stock' => 100 - ($i * 7),
                'available' => true,
            );
        }

        return array(
            'success' => true,
            'provider' => $this->getKey(),
            'type' => $type,
            'http_status' => 200,
            'payload' => $items,
            'query' => $this->sanitize_config($query),
        );
    }
}
