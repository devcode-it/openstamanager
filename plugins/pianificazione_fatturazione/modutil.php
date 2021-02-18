<?php

    include_once __DIR__.'/../../core.php';

    function variables($descrizione = '', $inizio = null, $fine = null)
    {
        $mese = [
            '01' => 'Gennaio',
            '02' => 'Febbraio',
            '03' => 'Marzo',
            '04' => 'Aprile',
            '05' => 'Maggio',
            '06' => 'Giugno',
            '07' => 'Luglio',
            '08' => 'Agosto',
            '09' => 'Settembre',
            '11' => 'Ottobre',
            '11' => 'Novembre',
            '12' => 'Dicembre',
        ];

        $result['list'] = '<ul>
                <li><code>{periodo}</code></li>
                <li><code>{data_inizio}</code></li>
                <li><code>{data_fine}</code></li>
                <li><code>{mese_fatturazione}</code></li>
            </ul>';

        if (!empty($descrizione)) {
            $result['descrizione'] = str_replace('{periodo}', 'durata dal '.Translator::dateToLocale($inizio).' al '.Translator::dateToLocale($fine), $descrizione);
            $result['descrizione'] = str_replace('{data_inizio}', Translator::dateToLocale($inizio), $result['descrizione']);
            $result['descrizione'] = str_replace('{data_fine}', Translator::dateToLocale($fine), $result['descrizione']);
            $result['descrizione'] = str_replace('{mese_fatturazione}', $mese[date('m', strtotime($inizio))], $result['descrizione']);
        }

        return $result;
    }
