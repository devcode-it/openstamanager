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

namespace Util;

/**
 * Classe dedicata alla gestione e all'interpretazione dei file INI.
 *
 * @since 2.3
 */
class Ini
{
    /**
     * Predispone la struttura per il salvataggio dei contenuti INI a partire da una struttura precedente.
     *
     * @param string $content
     * @param array  $values
     *
     * @return string
     */
    public static function write($content, $values)
    {
        $result = '';

        // Lettura info componente
        if (!empty($content)) {
            // Converto 'contenuto' di questo componente in un array
            $array = self::read($content);

            // Per ogni sezione dell'array estratto dal file ini
            foreach ($array as $sezione => $valori) {
                $sezione = str_replace(["\r", "\n"], '<br/>', $sezione);
                $result .= '['.$sezione.']'.PHP_EOL;

                if (array_key_exists('valore', $valori)) {
                    $valori = array_replace($valori, ['valore' => $valori['valore']], ['valore' => $values[str_replace(' ', '_', $sezione)]]);
                }

                foreach ($valori as $key => $value) {
                    $result .= $key.' = "'.addslashes($value).'"'.PHP_EOL;
                }
            }
        }

        return $result;
    }

    /**
     * Interpreta i contenuti di una stringa in formato INI.
     *
     * @param string $string
     *
     * @return array
     */
    public static function read($string)
    {
        return (array) parse_ini_string($string, true);
    }

    /**
     * Interpreta i contenuti di un file INI.
     *
     * @param string $filename
     *
     * @return array
     */
    public static function readFile($filename)
    {
        $filename = (file_exists($filename)) ? $filename : base_dir().'/files/impianti/'.$filename;

        $contents = file_get_contents($filename);

        return !empty($contents) ? self::read($contents) : [];
    }

    /**
     * Restituisce la lista di tutti i file INI presenti all'interno della cartella indicata.
     *
     * @param string $dir
     * @param array  $exclude
     */
    public static function getList($dir, $exclude = [])
    {
        $results = [];

        // Lettura dei files nella cartella indicata
        $exclude = !empty($exclude) ? $exclude : [];
        $exclude = is_array($exclude) ? $exclude : explode(',', $exclude);

        // Aggiungo tutti i componenti di possibile installazione
        $files = glob(realpath($dir).'/*.ini');
        foreach ($files as $file) {
            if (!in_array(basename($file), $exclude)) {
                $results[] = [basename($file), self::getValue(self::readFile($file), 'Nome').' ('.basename($file).')'];
            }
        }

        return $results;
    }

    /**
     * Ottiene il valore di un campo contenuto all'interno della struttura INI.
     *
     * @param array  $content
     * @param string $value
     *
     * @return mixed
     */
    public static function getValue($content, $value)
    {
        $result = !empty($content) && isset($content[$value]['valore']) ? $content[$value]['valore'] : '';

        return $result;
    }

    /**
     * Predispone il form dedicato alla modifica dei contenuti della struttura INI.
     *
     * @param string $contenuto
     *
     * @return array
     */
    public static function getFields($contenuto)
    {
        $result = [];

        // Caricamento campi dell'eventuale componente selezionato
        if (!empty($contenuto)) {
            $random = rand();
            $array = self::read($contenuto);

            if (is_array($array)) {
                $result[] = '
                    <h4>'.tr('Attributi per _NAME_', [
                        '_NAME_' => $array['Nome']['valore'],
                    ]).'</h4>
                    <input type="hidden" name="Nome" value="'.$array['Nome']['valore'].'">';

                foreach ($array as $sezione => $array_impostazioni) {
                    if ($sezione != 'Nome') {
                        $nome = $sezione;
                        $tipo = ($array[$sezione]['tipo'] == 'input') ? 'text' : $array[$sezione]['tipo'];
                        $valore = $array[$sezione]['valore'];

                        $opzioni = string_contains($array[$sezione]['opzioni'] ?: '', ',') ? explode(',', $array[$sezione]['opzioni']) : [];
                        $values = [];
                        foreach ($opzioni as $o) {
                            $values[] = '\"'.addslashes(addslashes($o)).'\": \"'.addslashes(addslashes($o)).'\"';
                        }

                        $input = '
                    {[ "type": "'.$tipo.'", "label": "'.$nome.':", "name": "'.$nome.'", "value": '.json_encode($valore).', "id": "'.$nome.'_'.$random.'" '.(!empty($values) ? ', "values": "list='.implode(', ', $values).'"' : '').' ]}';

                        if ($tipo == 'span') {
                            $input .= '
                    <input type="hidden" name="'.$nome.'" value="'.$valore.'">';
                        }

                        $result[] = $input;
                    }
                }
            }
        }

        return $result;
    }
}
