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

namespace Modules\Pagamenti;

use Carbon\Carbon;
use Common\SimpleModelTrait;
use Illuminate\Database\Eloquent\Model;
use Modules\Fatture\Fattura;
use Traits\RecordTrait;

class Pagamento extends Model
{
    use SimpleModelTrait;
    use RecordTrait;
    protected $table = 'co_pagamenti';

    protected static $translated_fields = [
        'name',
    ];

    public static function build($codice = null)
    {
        $model = new static();
        $model->codice_modalita_pagamento_fe = $codice;
        $model->save();

        return $model;
    }

    public function fatture()
    {
        return $this->hasMany(Fattura::class, 'idpagamento');
    }

    public function rate()
    {
        return $this->hasMany(Pagamento::class, 'id');
    }

    public function calcola($importo, $data, $id_anagrafica)
    {
        $rate = PagamentoLang::where('name', '=', $this->getTranslation('name'))->get()->sortBy('num_giorni')->pluck('id')->toArray();
        $number = count($rate);

        $totale = 0.0;

        $results = [];
        $count = 0;
        foreach ($rate as $key => $rata) {
            $date = new Carbon($data);
            $rata = Pagamento::find($rata);
            // X giorni esatti
            if ($rata->giorno == 0) {
                // Offset della rata
                if ($rata->num_giorni % 30 == 0) {
                    $date->addMonthsNoOverflow(round($rata->num_giorni / 30));
                } else {
                    $date->addDay($rata->num_giorni);
                }
            }

            // Ultimo del mese
            elseif ($rata->giorno < 0) {
                // Offset della rata
                if ($rata->num_giorni % 30 == 0) {
                    $date->addMonthsNoOverflow(round($rata->num_giorni / 30));
                } else {
                    $date->addDay($rata->num_giorni);
                }

                $date->modify('last day of this month');

                // Opzione ultimo del mese più X giorni
                $giorni = -$rata->giorno - 1;
                if ($giorni > 0) {
                    $date->modify('+'.$giorni.' day');
                } else {
                    $date->modify('last day of this month');
                }
            }

            // Giorno preciso del mese
            else {
                // Offset della rata
                if ($rata->num_giorni % 30 == 0) {
                    $date->addMonthsNoOverflow(round($rata->num_giorni / 30));
                } else {
                    $date->addDay($rata->num_giorni);
                }

                // Individuazione giorno effettivo (se il giorno indicato è eccessivamente grande, viene preso il massimo possibile)
                $date->modify('last day of this month');
                $last_day = $date->format('d');
                $day = $rata->giorno > $last_day ? $last_day : $rata->giorno;

                // Correzione data
                $date->setDate($date->format('Y'), $date->format('m'), $day);
            }

            // Posticipo la scadenza in base alle regole pagamenti dell'anagrafica
            $regola_pagamento = database()->selectOne('an_pagamenti_anagrafiche', '*', ['idanagrafica' => $id_anagrafica, 'mese' => $date->format('m')]);
            if (!empty($regola_pagamento)) {
                $date->modify('last day of this month');
                $date->addDay($regola_pagamento->giorno_fisso);
            }

            // Conversione della data in stringa standard
            $scadenza = $date->format('Y-m-d');

            // All'ultimo ciclo imposto come cifra da pagare il totale della fattura meno gli importi già inseriti in scadenziario per evitare di inserire cifre arrotondate "male"
            if ($count + 1 == $number) {
                $da_pagare = sum($importo, -$totale, 2);
            }

            // Totale da pagare (totale x percentuale di pagamento nei casi pagamenti multipli)
            else {
                $da_pagare = sum($importo / 100 * $rata->prc, 0, 2);
            }

            $totale = sum($da_pagare, $totale, 2);

            $results[] = [
                'scadenza' => $scadenza,
                'importo' => $da_pagare,
            ];

            ++$count;
        }

        return $results;
    }

    /**
     * @return bool
     */
    public function isRiBa()
    {
        return $this->codice_modalita_pagamento_fe == 'MP12';
    }

    public function getModuleAttribute()
    {
        return 'Pagamenti';
    }

    public static function getTranslatedFields()
    {
        return self::$translated_fields;
    }
}
