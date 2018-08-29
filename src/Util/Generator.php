<?php

namespace Util;

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
     * Predispone la struttura per il salvataggio dei contenuti INI a partire da una struttura precedente.
     *
     * @param string    $pattern
     * @param string    $last
     * @param array|int $quantity
     *
     * @return string
     */
    public static function generate($pattern, $last = null, $quantity = 1)
    {
        // Costruzione del pattern
        $replaces = self::getReplaces();
        $regexs = array_column($replaces, 'regex');

        $result = self::complete($pattern);
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
    public static function complete($pattern)
    {
        // Costruzione del pattern
        $replaces = self::getReplaces();
        $values = array_column($replaces, 'value');

        $result = str_replace(array_keys($replaces), array_values($values), $pattern);

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
}
