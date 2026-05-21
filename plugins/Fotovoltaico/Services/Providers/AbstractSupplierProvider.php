<?php

namespace Fotovoltaico\Services\Providers;

abstract class AbstractSupplierProvider implements SupplierProviderInterface
{
    protected function sanitize_config($config = array())
    {
        if (!is_array($config)) {
            return array();
        }

        return $this->mask_secrets($config);
    }

    protected function mask_secrets($payload)
    {
        if (!is_array($payload)) {
            return $payload;
        }

        $secret_keys = array('token', 'secret', 'password', 'api_key', 'apikey', 'authorization', 'client_secret');
        $result = array();
        foreach ($payload as $key => $value) {
            if (is_array($value)) {
                $result[$key] = $this->mask_secrets($value);
                continue;
            }

            if (in_array(strtolower((string) $key), $secret_keys, true)) {
                $result[$key] = $this->mask_value($value);
            } else {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    protected function mask_value($value)
    {
        $value = (string) $value;
        if ($value === '') {
            return '';
        }

        $length = strlen($value);
        if ($length <= 4) {
            return str_repeat('*', $length);
        }

        return substr($value, 0, 2) . str_repeat('*', max(0, $length - 4)) . substr($value, -2);
    }
}
