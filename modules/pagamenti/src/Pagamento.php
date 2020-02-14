<?php

namespace Modules\Pagamenti;

use Common\Model;
use DateTime;

class Pagamento extends Model
{
    protected $table = 'co_pagamenti';

    public function fatture()
    {
        return $this->hasMany(Fattura::class, 'idpagamento');
    }

    public function rate()
    {
        return $this->hasMany(Pagamento::class, 'descrizione', 'descrizione');
    }

    public function calcola($importo, $data)
    {
        $rate = $this->rate->sortBy('num_giorni');
        $number = count($rate);

        $totale = 0.0;

        $results = [];
        $count = 0;
        foreach ($rate as $key => $rata) {
            $date = new DateTime($data);

            // X giorni esatti
            if ($rata['giorno'] == 0) {
                // Offset della rata
                $date->modify('+'.($rata['num_giorni']).' day');
            }

            // Ultimo del mese
            elseif ($rata['giorno'] < 0) {
                // Offset della rata in mesi
                $add = floor($rata['num_giorni'] / 30);
                for ($c = 0; $c < $add; ++$c) {
                    $date->modify('last day of next month');
                }

                // Opzione ultimo del mese più X giorni
                $giorni = -$rata['giorno'] - 1;
                if ($giorni > 0) {
                    $date->modify('+'.($giorni).' day');
                } else {
                    $date->modify('last day of this month');
                }
            }

            // Giorno preciso del mese
            else {
                // Offset della rata
                $date->modify('+'.($rata['num_giorni']).' day');

                // Individuazione giorno effettivo (se il giorno indicato è eccessivamente grande, viene preso il massimo possibile)
                $date->modify('last day of this month');
                $last_day = $date->format('d');
                $day = $rata['giorno'] > $last_day ? $last_day : $rata['giorno'];

                // Correzione data
                $date->setDate($date->format('Y'), $date->format('m'), $day);
            }

            // Comversione della data in stringa standard
            $scadenza = $date->format('Y-m-d');

            // All'ultimo ciclo imposto come cifra da pagare il totale della fattura meno gli importi già inseriti in scadenziario per evitare di inserire cifre arrotondate "male"
            if ($count + 1 == $number) {
                $da_pagare = sum($importo, -$totale, 2);
            }

            // Totale da pagare (totale x percentuale di pagamento nei casi pagamenti multipli)
            else {
                $da_pagare = sum($importo / 100 * $rata['prc'], 0, 2);
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
}
