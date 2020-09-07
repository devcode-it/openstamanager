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

namespace Modules\Listini;

use Common\Model;

class Listino extends Model
{
    protected $table = 'mg_listini';

    public static function build($nome, $percentuale)
    {
        $model = parent::build();

        $model->nome = $nome;
        $model->percentuale = $percentuale;
        $model->save();

        return $model;
    }

    public function setPercentualeCombinatoAttribute($value)
    {
        $this->prc_combinato = $value;
    }

    public function getPercentualeCombinatoAttribute()
    {
        return $this->prc_combinato;
    }

    public function setPercentualeAttribute($value)
    {
        $value = floatval($value);
        if (abs($value) > 100) {
            $value = ($value > 0) ? 100 : -100;
        }

        $this->prc_guadagno = $value;
    }

    public function getPercentualeAttribute()
    {
        return $this->prc_guadagno;
    }

    public function save(array $options = [])
    {
        $combinato = $this->prc_combinato;
        if (!empty($combinato)) {
            $this->percentuale = parseScontoCombinato($combinato);
        }

        return parent::save($options);
    }
}
