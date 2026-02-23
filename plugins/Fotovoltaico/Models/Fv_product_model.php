<?php

namespace Fotovoltaico\Models;

use App\Models\Crud_model;

/**
 * Model para acessar produtos fotovoltaicos.
 */
class Fv_product_model extends Crud_model
{
    protected $table = null;

    public function __construct()
    {
        $this->table = 'fv_product';
        parent::__construct($this->table);
    }
}
