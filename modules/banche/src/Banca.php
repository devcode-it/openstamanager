<?php
/*
 * OpenSTAManager: il software gestionale open source per l'assistenza tecnica e la fatturazione
 * Copyright (C) DevCode s.n.c.
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

namespace Modules\Banche;

use Common\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Anagrafiche\Anagrafica;

class Banca extends Model
{
    use SoftDeletes;

    protected $table = 'co_banche';

    /**
     * Crea una nuovo banca.
     *
     * @param string $nome
     * @param string $iban
     * @param string $bic
     *
     * @return self
     */
    public static function build(Anagrafica $anagrafica, $nome, $iban, $bic)
    {
        $model = parent::build();

        // Informazioni di base
        $model->anagrafica()->associate($anagrafica);
        $model->nome = $nome;
        $model->iban = $iban;
        $model->bic = $bic;

        // Salvataggio delle informazioni
        $model->save();

        return $model;
    }

    public function anagrafica()
    {
        return $this->belongsTo(Anagrafica::class, 'id_anagrafica');
    }
}
