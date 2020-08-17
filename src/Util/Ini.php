<?php

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
        $filename = (file_exists($filename)) ? $filename : DOCROOT.'/files/my_impianti/'.$filename;

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
     * @param string $contenut
     *
     * @return array
     */
    public static function getFields($contenut)
    {
        $result = [];

        // Caricamento campi dell'eventuale componente selezionato
        if (!empty($contenut)) {
            $random = rand();
            $array = self::read($contenut);

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

                        $opzioni = str_contains($array[$sezione]['opzioni'] ?: '', ',') ? explode(',', $array[$sezione]['opzioni']) : [];
                        $values = [];
                        foreach ($opzioni as $o) {
                            $values[] = '\"'.addslashes(addslashes($o)).'\": \"'.addslashes(addslashes($o)).'\"';
                        }

                        $result[] = '
                    {[ "type": "'.$tipo.'", "label": "'.$nome.':", "name": "'.$nome.'", "value": '.json_encode($valore).', "id": "'.$nome.'_'.$random.'" '.(!empty($values) ? ', "values": "list='.implode(', ', $values).'"' : '').' ]}';
                    }
                }
            }
        }

        return $result;
    }
}
