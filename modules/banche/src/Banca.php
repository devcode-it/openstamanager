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

namespace Modules\Banche;

use Common\SimpleModelTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Anagrafiche\Anagrafica;

class Banca extends Model
{
    use SimpleModelTrait;
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
        $model = new static();

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

    public function save(array $options = [])
    {
        $this->fixPredefined();

        return parent::save($options);
    }

    protected function fixPredefined()
    {
        $predefined = isset($this->predefined) ? $this->predefined : false;

        // Selezione automatica per primo record
        $count = self::where('id_anagrafica', $this->id_anagrafica)
            ->where('id', '!=', $this->id)
            ->count();
        if (empty($predefined) && empty($count)) {
            $predefined = true;
        }

        if (!empty($predefined)) {
            self::where('id_anagrafica', $this->id_anagrafica)
                ->where('id', '!=', $this->id)
                ->update([
                    'predefined' => 0,
                ]);

            $this->attributes['predefined'] = $predefined;
        }
    }
}
