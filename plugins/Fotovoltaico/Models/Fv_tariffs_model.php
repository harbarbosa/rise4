<?php

namespace Fotovoltaico\Models;

use App\Models\Crud_model;

/**
 * Model para acessar tarifas de distribuidoras.
 */
class Fv_tariffs_model extends Crud_model
{
    protected $table = null;

    public function __construct()
    {
        $this->table = 'fv_tariffs';
        parent::__construct($this->table);
    }
}
