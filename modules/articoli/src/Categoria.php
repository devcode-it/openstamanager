<?php

namespace Modules\Articoli;

use Common\Model;
use Traits\HierarchyTrait;

class Categoria extends Model
{
    use HierarchyTrait;

    protected $table = 'mg_categorie';
    protected static $parent_identifier = 'parent';

    public function articoli()
    {
        return $this->hasMany(Articolo::class, 'id_categoria');
    }
}
