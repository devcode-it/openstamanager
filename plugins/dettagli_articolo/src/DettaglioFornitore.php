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

namespace Plugins\DettagliArticolo;

use Common\SimpleModelTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Anagrafiche\Anagrafica;
use Modules\Articoli\Articolo;

/*
 * Classe per la gestione delle relazioni tra articolo e fornitore.
 *
 * @since 2.4.15
 */
class DettaglioFornitore extends Model
{
    use SimpleModelTrait;
    use SoftDeletes;

    protected $table = 'mg_fornitore_articolo';

    /**
     * Crea una nuova relazione tra Articolo e Fornitore.
     *
     * @return self
     */
    public static function build(Anagrafica $fornitore, Articolo $articolo)
    {
        $model = new static();

        $model->anagrafica()->associate($fornitore);
        $model->articolo()->associate($articolo);

        $model->save();

        return $model;
    }

    // Relazioni Eloquent

    public function anagrafica()
    {
        return $this->belongsTo(Anagrafica::class, 'id_fornitore');
    }

    public function articolo()
    {
        return $this->belongsTo(Articolo::class, 'id_articolo');
    }
}
