<?php

namespace OrdemServico\Models;

use App\Models\Crud_model;

class OsProducts_items_model extends Crud_model
{
    public function __construct($db = null)
    {
        parent::__construct('os_products_items', $db);
    }
}


