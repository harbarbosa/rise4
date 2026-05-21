<?php

namespace Fotovoltaico\Services\Providers;

interface SupplierProviderInterface
{
    public function getKey();

    public function getLabel();

    public function authenticate($config = array());

    public function testConnection($config = array());

    public function consultProducts($query = array(), $config = array());

    public function consultKits($query = array(), $config = array());

    public function consultFreight($query = array(), $config = array());

    public function getQuote($query = array(), $config = array());
}
