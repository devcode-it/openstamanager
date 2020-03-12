<?php

namespace Modules\Newsletter;

use Common\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Anagrafiche\Anagrafica;
use Traits\RecordTrait;

class Lista extends Model
{
    use SoftDeletes;
    use RecordTrait;

    protected $table = 'em_lists';

    public static function build($name)
    {
        $model = parent::build();
        $model->name = $name;

        $model->save();

        return $model;
    }

    public function save(array $options = [])
    {
        $result = parent::save($options);

        $query = $this->query;
        if (!empty($query)) {
            $results = database()->fetchArray($query);

            $anagrafiche = array_column($results, 'id');
            $this->anagrafiche()->sync($anagrafiche);
        }

        return $result;
    }

    // Relazione Eloquent

    public function anagrafiche()
    {
        return $this->belongsToMany(Anagrafica::class, 'em_list_anagrafica', 'id_list', 'id_anagrafica')->withTrashed();
    }

    /**
     * Restituisce il nome del modulo a cui l'oggetto è collegato.
     *
     * @return string
     */
    public function getModuleAttribute()
    {
        return 'Liste';
    }
}
