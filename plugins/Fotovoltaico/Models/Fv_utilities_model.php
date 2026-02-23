<?php

namespace Fotovoltaico\Models;

use App\Models\Crud_model;

/**
 * Model para acessar distribuidoras.
 */
class Fv_utilities_model extends Crud_model
{
    protected $table = null;

    public function __construct()
    {
        $this->table = 'fv_utilities';
        parent::__construct($this->table);
    }
}
