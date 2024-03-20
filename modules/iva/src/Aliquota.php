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

namespace Modules\Iva;

use Common\SimpleModelTrait;
use Illuminate\Database\Eloquent\Model;
use Traits\RecordTrait;
class Aliquota extends Model
{
    use SimpleModelTrait;
    use RecordTrait;
    protected $table = 'co_iva';

    protected static $translated_fields = [
        'name',
    ];
    public static function build($esente = null, $percentuale = null, $indetraibile = null, $dicitura = null, $codice = null, $codice_natura_fe = null, $esigibilita = null)
    {
        $model = new static();
        $model->esente = $esente;
        $model->percentuale = $percentuale;
        $model->indetraibile = $indetraibile;
        $model->dicitura = $dicitura;
        $model->codice = $codice;
        $model->codice_natura_fe = $codice_natura_fe;
        $model->esigibilita = $esigibilita;
        $model->save();

        return $model;
    }

    public function getModuleAttribute()
    {
        return 'Stato dei contratti';
    }

    public static function getTranslatedFields(){
        return self::$translated_fields;
    }
}
