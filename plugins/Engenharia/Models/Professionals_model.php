<?php
namespace Engenharia\Models;
class Professionals_model extends EngenhariaBaseModel { protected $table = 'eng_professionals'; public function __construct(){ parent::__construct($this->table); } }
