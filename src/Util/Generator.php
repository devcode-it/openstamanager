<?php

namespace Util;

use Illuminate\Database\Capsule\Manager;

/**
 * Classe dedicata alla gestione e all'interpretazione delle stringhe personalizzate.
 *
 * @since 2.3
 */
class Generator
{
    /** @var array Elenco delle varabili da sostituire nel pattern */
    protected static $replaces = [
        'YYYY' => [
            'date' => 'Y',
        ],
        'yy' => [
            'date' => 'y',
        ],
        'm' => [
            'date' => 'm',
        ],
        'd' => [
            'date' => 'd',
        ],
        'H' => [
            'date' => 'H',
        ],
        'i' => [
            'date' => 'i',
        ],
        's' => [
            'date' => 'd',
        ],
    ];

    /**
     * Genera un pattern sulla base del precedente.
     *
     * @param string $pattern
     * @param string $last
     * @param int    $quantity
     * @param array  $values
     *
     * @return string
     */
    public static function generate($pattern, $last = null, $quantity = 1, $values = [])
    {
        // Costruzione del pattern
        $result = self::complete($pattern, $values);
        $length = substr_count($result, '#');

        // Individuazione dei valori precedenti
        $previous = self::read($pattern, $last);

        $number = 1;
        if (isset($previous['number'])) {
            $number = intval($previous['number']) + $quantity;
        }

        $result = preg_replace('/#{1,}/', str_pad($number, $length, '0', STR_PAD_LEFT), $result);

        return $result;
    }

    /**
     * Completa un determinato pattern con le informazioni di base.
     *
     * @return string
     */
    public static function complete($pattern, $values = [])
    {
        // Costruzione del pattern
        $replaces = array_merge(self::getReplaces(), $values);

        $keys = array_keys($replaces);
        $values = array_column($replaces, 'value');

        $result = str_replace($keys, $values, $pattern);

        return $result;
    }

    /**
     * Restituisce i valori utilizzati sul pattern.
     *
     * @return array
     */
    public static function read($pattern, $string)
    {
        // Costruzione del pattern
        $replaces = self::getReplaces();

        $replaces['#'] = [
            'regex' => '(?<number>[0-9]+)',
        ];

        $values = array_column($replaces, 'regex');

        $pattern = preg_replace('/#{1,}/', '#', $pattern);
        $pattern = preg_quote($pattern, '/');
        $pattern = str_replace(array_keys($replaces), array_values($values), $pattern);

        // Individuazione dei valori
        preg_match('/^'.$pattern.'/', $string, $m);

        return array_filter($m, 'is_string', ARRAY_FILTER_USE_KEY);
    }

    /**
     * Restituisce l'elenco delle variabili da sostituire normalizzato per l'utilizzo.
     *
     * @return array
     */
    public static function getReplaces()
    {
        $replaces = self::$replaces;

        foreach ($replaces as $key => $value) {
            if (!isset($replaces[$key]['value'])) {
                if (isset($replaces[$key]['date'])) {
                    $replaces[$key]['value'] = date($replaces[$key]['date']);
                } else {
                    $replaces[$key]['value'] = $key;
                }
            }

            if (!isset($replaces[$key]['regex'])) {
                $replaces[$key]['regex'] = '(?<'.preg_quote($key).'>.{'.strlen($replaces[$key]['value']).'})';
            }
        }

        return $replaces;
    }

    /**
     * Interpreta una data specifica per la sostituzione nei pattern.
     *
     * @return array
     */
    public static function dateToPattern($date)
    {
        $replaces = self::$replaces;

        $date = strtotime($date);
        $results = [];

        foreach ($replaces as $key => $value) {
            if (isset($replaces[$key]['date'])) {
                $results[$key]['value'] = date($replaces[$key]['date'], $date);
            }
        }

        return $results;
    }

    /**
     * Restituisce la maschera specificata per il segmento indicato.
     *
     * @param int $id_segment
     *
     * @return string
     */
    public static function getMaschera($id_segment)
    {
        $database = database();

        $maschera = $database->fetchOne('SELECT pattern FROM zz_segments WHERE id = :id_segment', [
            ':id_segment' => $id_segment,
        ]);

        return $maschera['pattern'];
    }

    public static function getPreviousFrom($maschera, $table, $field, $where = [])
    {
        $order = static::getMascheraOrder($maschera, $field);

        $maschera = Generator::complete($maschera);
        $maschera = str_replace('#', '%', $maschera);

        $query = Manager::table($table)->select($field)->where($field, 'like', $maschera)->orderByRaw($order);

        foreach ($where as $and) {
            $query->whereRaw($and);
        }

        $result = $query->first();

        return $result->{$field};
    }

    /**
     * Metodo per l'individuazione del tipo di ordine da impostare per la corretta interpretazione della maschera.
     * Esempi:
     * - maschere con testo iniziale (FT-####-YYYY) necessitano l'ordinamento alfabetico
     * - maschere di soli numeri (####-YYYY) è necessario l'ordinamento numerico forzato.
     *
     * @param string $maschera
     * @param string $field
     *
     * @return string
     */
    protected static function getMascheraOrder($maschera, $field)
    {
        // Query di default
        $query = $field.' DESC';

        // Estraggo blocchi di caratteri standard
        preg_match('/[#]+/', $maschera, $m1);

        $pos1 = strpos($maschera, $m1[0]);
        if ($pos1 == 0) {
            $query = 'CAST('.$field.' AS UNSIGNED) DESC';
        }

        return $query;
    }
}
