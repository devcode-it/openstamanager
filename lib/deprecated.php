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

// trigger_error(tr('Funzione deprecata!'), E_USER_DEPRECATED);

/**
 * Individua il codice successivo.
 *
 * @deprecated 2.4
 *
 * @param string $str
 * @param int    $qty
 * @param string $mask
 */
function get_next_code($str, $qty = 1, $mask = '')
{
    trigger_error(tr('Funzione deprecata!'), E_USER_DEPRECATED);

    return Util\Generator::generate($mask, $str, $qty);
}

/**
 * Legge il valore di un'impostazione dalla tabella zz_settings.
 * Se descrizione = 1 e il tipo è 'query=' mi restituisce il valore del campo descrizione della query.
 *
 * @deprecated 2.4.2

 *
 * @param string $name
 * @param string $sezione
 * @param string $descrizione
 *
 * @return mixed
 */
function get_var($nome, $sezione = null, $descrizione = false, $again = false)
{
    return setting($nome, $again);
}

/**
 * Crea le thumbnails di $filename da dentro $dir e le salva in $dir.
 *
 * @param string $tmp
 * @param string $filename
 * @param string $dir
 *
 * @return bool
 */
function create_thumbnails($tmp, $filename, $dir)
{
    $infos = pathinfo($filename);
    $name = $infos['filename'];
    $extension = strtolower($infos['extension']);

    if (!directory($dir)) {
        return false;
    }

    $driver = extension_loaded('gd') ? 'gd' : 'imagick';
    Intervention\Image\ImageManagerStatic::configure(['driver' => $driver]);

    $img = Intervention\Image\ImageManagerStatic::make($tmp);

    $img->resize(600, null, function ($constraint) {
        $constraint->aspectRatio();
    });
    $img->save(slashes($dir.'/'.$name.'.'.$extension));

    $img->resize(250, null, function ($constraint) {
        $constraint->aspectRatio();
    });
    $img->save(slashes($dir.'/'.$name.'_thumb250.'.$extension));

    $img->resize(100, null, function ($constraint) {
        $constraint->aspectRatio();
    });
    $img->save(slashes($dir.'/'.$name.'_thumb100.'.$extension));

    return true;
}

/**
 * Verifica che il nome del file non sia già usato nella cartella inserita, nel qual caso aggiungo un suffisso.
 *
 * @param string $filename
 * @param string $dir
 *
 * @return string
 */
function unique_filename($filename, $dir)
{
    $f = pathinfo($filename);
    $suffix = 1;
    while (file_exists($dir.'/'.$filename)) {
        $filename = $f['filename'].'_'.$suffix.'.'.$f['extension'];
        ++$suffix;
    }

    return $filename;
}

/**
 * Individua la differenza tra le date indicate.
 * $interval può essere:
 * yyyy - Number of full years
 * q - Number of full quarters
 * m - Number of full months
 * y - Difference between day numbers
 * (eg 1st Jan 2004 is "1", the first day. 2nd Feb 2003 is "33". The datediff is "-32".)
 * d - Number of full days
 * w - Number of full weekdays
 * ww - Number of full weeks
 * h - Number of full hours
 * n - Number of full minutes
 * s - Number of full seconds (default).
 *
 * @param unknown $interval
 * @param unknown $datefrom
 * @param unknown $dateto
 * @param string  $using_timestamps
 */
function datediff($interval, $datefrom, $dateto, $using_timestamps = false)
{
    if (!$using_timestamps) {
        $datefrom = strtotime($datefrom, 0);
        $dateto = strtotime($dateto, 0);
    }
    $difference = $dateto - $datefrom; // Difference in seconds
    switch ($interval) {
        case 'yyyy': // Number of full years
            $years_difference = floor($difference / 31536000);
            if (mktime(date('H', $datefrom), date('i', $datefrom), date('s', $datefrom), date('n', $datefrom), date('j', $datefrom), date('Y', $datefrom) + $years_difference) > $dateto) {
                --$years_difference;
            }
            if (mktime(date('H', $dateto), date('i', $dateto), date('s', $dateto), date('n', $dateto), date('j', $dateto), date('Y', $dateto) - ($years_difference + 1)) > $datefrom) {
                ++$years_difference;
            }
            $datediff = $years_difference;
            break;
        case 'q': // Number of full quarters
            $quarters_difference = floor($difference / 8035200);
            while (mktime(date('H', $datefrom), date('i', $datefrom), date('s', $datefrom), date('n', $datefrom) + ($quarters_difference * 3), date('j', $dateto), date('Y', $datefrom)) < $dateto) {
                ++$months_difference;
            }
            --$quarters_difference;
            $datediff = $quarters_difference;
            break;
        case 'm': // Number of full months
            $months_difference = floor($difference / 2678400);
            while (mktime(date('H', $datefrom), date('i', $datefrom), date('s', $datefrom), date('n', $datefrom) + ($months_difference), date('j', $dateto), date('Y', $datefrom)) < $dateto) {
                ++$months_difference;
            }
            --$months_difference;
            $datediff = $months_difference;
            break;
        case 'y': // Difference between day numbers
            $datediff = date('z', $dateto) - date('z', $datefrom);
            break;
        case 'd': // Number of full days
            $datediff = floor($difference / 86400);
            break;
        case 'w': // Number of full weekdays
            $days_difference = floor($difference / 86400);
            $weeks_difference = floor($days_difference / 7); // Complete weeks
            $first_day = date('w', $datefrom);
            $days_remainder = floor($days_difference % 7);
            $odd_days = $first_day + $days_remainder; // Do we have a Saturday or Sunday in the remainder?
            if ($odd_days > 7) { // Sunday
                --$days_remainder;
            }
            if ($odd_days > 6) { // Saturday
                --$days_remainder;
            }
            $datediff = ($weeks_difference * 5) + $days_remainder;
            break;
        case 'ww': // Number of full weeks
            $datediff = floor($difference / 604800);
            break;
        case 'h': // Number of full hours
            $datediff = floor($difference / 3600);
            break;
        case 'n': // Number of full minutes
            $datediff = floor($difference / 60);
            break;
        default: // Number of full seconds (default)
            $datediff = $difference;
            break;
    }

    return $datediff;
}

/**
 * @param $field
 * @param $id_riga
 * @param $old_qta
 * @param $new_qta
 * @param $dir
 *
 * @throws Exception
 *
 * @return bool
 *
 * @deprecated
 */
function controlla_seriali($field, $id_riga, $old_qta, $new_qta, $dir)
{
    $dbo = database();

    $new_qta = abs($new_qta);
    $old_qta = abs($old_qta);

    if ($old_qta >= $new_qta) {
        // Controllo sulla possibilità di rimuovere i seriali (se non utilizzati da documenti di vendita)
        if ($dir == 'uscita' && $new_qta < count(seriali_non_rimuovibili($field, $id_riga, $dir))) {
            return false;
        } else {
            // Controllo sul numero di seriali effettivi da rimuovere
            $count = $dbo->fetchArray('SELECT COUNT(*) AS tot FROM mg_prodotti WHERE '.$field.'='.prepare($id_riga))[0]['tot'];
            if ($new_qta < $count) {
                $deletes = $dbo->fetchArray("SELECT id FROM mg_prodotti WHERE serial NOT IN (SELECT serial FROM mg_prodotti WHERE dir = 'entrata' AND ".$field.'!='.prepare($id_riga).') AND '.$field.'='.prepare($id_riga).' ORDER BY serial DESC LIMIT '.abs($count - $new_qta));

                // Rimozione
                foreach ($deletes as $delete) {
                    $dbo->query('DELETE FROM mg_prodotti WHERE id = '.prepare($delete['id']));
                }
            }
        }
    }

    return true;
}

/**
 * Individua i seriali non rimuovibili poichè utilizzati in documenti rilasciati.
 *
 * @param string $field
 * @param int    $id_riga
 * @param string $dir
 *
 * @return array
 *
 * @deprecated
 */
function seriali_non_rimuovibili($field, $id_riga, $dir)
{
    $dbo = database();

    $results = [];

    if ($dir == 'uscita') {
        $results = $dbo->fetchArray("SELECT serial FROM mg_prodotti WHERE serial IN (SELECT serial FROM mg_prodotti WHERE dir = 'entrata') AND ".$field.'='.prepare($id_riga));
    }

    return $results;
}

/**
 * Restistuisce le informazioni sull'eventuale riferimento ai documenti.
 *
 * @param $info
 * @param $dir
 * @param array $ignore
 *
 * @deprecated
 *
 * @throws Exception
 *
 * @return array
 */
function doc_references($info, $dir, $ignore = [])
{
    $dbo = database();

    // Rimozione valori da non controllare
    foreach ($ignore as $field) {
        if (isset($info[$field])) {
            unset($info[$field]);
        }
    }

    $module = null;
    $id = null;

    // DDT
    if (!empty($info['idddt'])) {
        $data = $dbo->fetchArray("SELECT IF(numero_esterno != '', numero_esterno, numero) AS numero, data FROM dt_ddt WHERE id=".prepare($info['idddt']));

        $module = ($dir == 'entrata') ? 'Ddt di vendita' : 'Ddt di acquisto';
        $id = $info['idddt'];

        $document = tr('Ddt');
    }

    // Ordine
    elseif (!empty($info['idordine'])) {
        $data = $dbo->fetchArray("SELECT IF(numero_esterno != '', numero_esterno, numero) AS numero, data FROM or_ordini WHERE id=".prepare($info['idordine']));

        $module = ($dir == 'entrata') ? 'Ordini cliente' : 'Ordini fornitore';
        $id = $info['idordine'];

        $document = tr('Ordine');
    }

    // Preventivo
    elseif (!empty($info['idpreventivo'])) {
        $data = $dbo->fetchArray('SELECT numero, data_bozza AS data FROM co_preventivi WHERE id='.prepare($info['idpreventivo']));

        $module = 'Preventivi';
        $id = $info['idpreventivo'];

        $document = tr('Preventivo');
    }

    // Contratto
    elseif (!empty($info['idcontratto'])) {
        $data = $dbo->fetchArray('SELECT numero, data_bozza AS data FROM co_contratti WHERE id='.prepare($info['idcontratto']));

        $module = 'Contratti';
        $id = $info['idcontratto'];

        $document = tr('Contratto');
    }

    // Intervento
    elseif (!empty($info['idintervento'])) {
        $data = $dbo->fetchArray('SELECT codice AS numero, IFNULL( (SELECT MIN(orario_inizio) FROM in_interventi_tecnici WHERE in_interventi_tecnici.idintervento=in_interventi.id), data_richiesta) AS data FROM in_interventi WHERE id='.prepare($info['idintervento']));

        $module = 'Interventi';
        $id = $info['idintervento'];

        $document = tr('Intervento');
    }

    // Testo relativo
    if (!empty($module) && !empty($id)) {
        $document = Stringy\Stringy::create($document)->toLowerCase();

        if (!empty($data)) {
            $description = tr('Rif. _DOC_ num. _NUM_ del _DATE_', [
                '_DOC_' => $document,
                '_NUM_' => $data[0]['numero'],
                '_DATE_' => Translator::dateToLocale($data[0]['data']),
            ]);
        } else {
            $description = tr('_DOC_ di riferimento _ID_ eliminato', [
                '_DOC_' => $document->upperCaseFirst(),
                '_ID_' => $id,
            ]);
        }

        return [
            'module' => $module,
            'id' => $id,
            'description' => $description,
        ];
    }

    return [];
}

/**
 * Restituisce i mesi tradotti nella lingua corrente.
 * Da sostituire con il relativo corretto utilizzo delle date PHP.
 *
 * @deprecated
 *
 * @return array
 */
function months()
{
    return [
        1 => tr('Gennaio'),
        2 => tr('Febbraio'),
        3 => tr('Marzo'),
        4 => tr('Aprile'),
        5 => tr('Maggio'),
        6 => tr('Giugno'),
        7 => tr('Luglio'),
        8 => tr('Agosto'),
        9 => tr('Settembre'),
        10 => tr('Ottobre'),
        11 => tr('Novembre'),
        12 => tr('Dicembre'),
    ];
}
