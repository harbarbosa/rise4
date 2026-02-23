<?php

namespace Fotovoltaico\Models;

use App\Models\Crud_model;

/**
 * Model para acessar projetos fotovoltaicos.
 */
class Fv_projects_model extends Crud_model
{
    protected $table = null;

    public function __construct()
    {
        $this->table = 'fv_projects';
        parent::__construct($this->table);
    }
}
