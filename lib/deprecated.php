<?php

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
