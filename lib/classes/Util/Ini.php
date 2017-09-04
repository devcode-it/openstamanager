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
     * Interpreta i contentuti di una stringa in formato INI.
     *
     * @param string $string
     *
     * @return array
     */
    public static function read($string)
    {
        return parse_ini_string($string, true);
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

        return self::read(file_get_contents($filename));
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
        $exclude = (!empty($exclude)) ? $exclude : [];
        $exclude = (is_string($exclude)) ? explode(',', $list) : $exclude;

        // Aggiungo tutti i componenti di possibile installazione
        $files = (array) glob(realpath($dir).'/*.ini');
        foreach ($files as $file) {
            if (!in_array(basename($file), $exclude)) {
                $results[] = [basename($file), self::getValue(self::readFile($file), 'Nome')];
            }
        }

        return $results;
    }

    /**
     * Ottiene il valore di un campo contenuto all'interno della struttura INI.
     *
     * @param string $content
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
                    <h4>'.str_replace('_NAME_', $array['Nome']['valore'], tr('Attributi per _NAME_')).'</h4>
                    <input type="hidden" name="Nome" value="'.$array['Nome']['valore'].'">';

                foreach ($array as $sezione => $array_impostazioni) {
                    if ($sezione != 'Nome') {
                        $nome = $sezione;
                        $tipo = ($array[$sezione]['tipo'] == 'input') ? 'text' : $array[$sezione]['tipo'];
                        $valore = $array[$sezione]['valore'];

                        $opzioni = str_contains($array[$sezione]['opzioni'], ',') ? explode(',', $array[$sezione]['opzioni']) : [];
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
