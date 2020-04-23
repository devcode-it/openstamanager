<?php

namespace Modules\Articoli;

use Common\Model;
use Traits\HierarchyTrait;

class Categoria extends Model
{
    use HierarchyTrait;

    protected $table = 'mg_categorie';
    protected static $parent_identifier = 'parent';

    public static function build($nome)
    {
        $model = parent::build();

        $model->nome = $nome;
        $model->save();

        return $model;
    }

    public function articoli()
    {
        return $this->hasMany(Articolo::class, 'id_categoria');
    }
}
