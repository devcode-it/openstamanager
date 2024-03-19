<?php
/*
 * OpenSTAManager: il software gestionale open source per l'assistenza tecnica e la fatturazione
 * Copyright (C) DevCode s.r.l.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

namespace Plugins\ComponentiImpianti;

use Common\SimpleModelTrait;
use Illuminate\Database\Eloquent\Model;
use Modules\Articoli\Articolo;
use Modules\Impianti\Impianto;
use Modules\Interventi\Intervento;

/*
 * Classe per la gestione dei Componenti degli Impianti.
 *
 * @since 2.4.25
 */
class Componente extends Model
{
    use SimpleModelTrait;

    protected $table = 'my_componenti';

    protected $dates = [
        'data_registrazione',
        'data_sostituzione',
        'data_installazione',
        'data_rimozione',
    ];

    /**
     * Crea un nuovo Componente per Impianti.
     *
     * @return self
     */
    public static function build(Impianto $impianto = null, Articolo $articolo = null, $data_registrazione = null)
    {
        $model = new static();

        $model->impianto()->associate($impianto);
        $model->articolo()->associate($articolo);

        $model->data_registrazione = $data_registrazione;
        $model->save();

        return $model;
    }

    // Relazioni Eloquent

    public function articolo()
    {
        return $this->belongsTo(Articolo::class, 'id_articolo');
    }

    public function impianto()
    {
        return $this->belongsTo(Impianto::class, 'id_impianto');
    }

    public function sostituzione()
    {
        return $this->hasMany(self::class, 'id_sostituzione');
    }

    public function intervento()
    {
        return $this->hasMany(Intervento::class, 'id_intervento');
    }
}
