<?php

namespace Modules\CategorieDocumentali;

use Common\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Categoria extends Model
{
    use SoftDeletes;

    protected $table = 'do_categorie';

    public static function build($descrizione)
    {
        $model = parent::build();
        $model->descrizione = $descrizione;

        $model->save();

        $gruppi = database()->fetchArray('SELECT `id` FROM `zz_groups`');
        $model->syncPermessi($gruppi);

        return $model;
    }

    public function syncPermessi(array $groups)
    {
        $groups[] = 1;

        $database = database();
        $database->sync('do_permessi', ['id_categoria' => $this->id], [
           'id_gruppo' => $groups,
       ]);
    }
}
