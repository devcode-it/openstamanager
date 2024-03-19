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

namespace Modules\ListiniCliente;

use Common\SimpleModelTrait;
use Illuminate\Database\Eloquent\Model;

/*
 * Classe per la gestione delle relazioni articolo-prezzo sulla base di un range di quantità e di una specifica anagrafica.
 *
 * @since 2.4.18
 */
class Listino extends Model
{
    use SimpleModelTrait;

    protected $table = 'mg_listini';

    /**
     * Crea una nuova relazione tra Articolo e Anagrafica per la gestione dei prezzi.
     *
     * @return self
     */
    public static function build($nome = null)
    {
        $model = new static();
        $model->nome = $nome;

        $model->save();

        return $model;
    }
}
