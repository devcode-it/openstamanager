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
            // X giorni esatti
            if ($rata['giorno'] == 0) {
                $scadenza = date('Y-m-d', strtotime($data.' +'.$rata['num_giorni'].' day'));
            }

            // Ultimo del mese
            elseif ($rata['giorno'] < 0) {
                $date = new DateTime($data);

                $add = floor($rata['num_giorni'] / 30);
                for ($c = 0; $c < $add; ++$c) {
                    $date->modify('last day of next month');
                }

                // Ultimo del mese più X giorni
                $giorni = -$rata['giorno'] - 1;
                if ($giorni > 0) {
                    $date->modify('+'.($giorni).' day');
                } else {
                    $date->modify('last day of this month');
                }

                $scadenza = $date->format('Y-m-d');
            }

            // Giorno preciso del mese
            else {
                $scadenza = date('Y-m-'.$rata['giorno'], strtotime($data.' +'.$rata['num_giorni'].' day'));
            }

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

            $count++;
        }

        return $results;
    }
}
