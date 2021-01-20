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

namespace Intl;

use DateTime;
use NumberFormatter;

/**
 * Classe per gestire la formattazione di date e numeri in convenzioni differenti.
 *
 * @since 2.3
 */
class Formatter
{
    protected static $standards = [
        'timestamp' => 'Y-m-d H:i:s',
        'date' => 'Y-m-d',
        'time' => 'H:i:s',
        'number' => [
            'decimals' => '.',
            'thousands' => '',
        ],
    ];

    /** @var NumberFormatter|array Oggetto dedicato alla formattazione dei numeri */
    protected $numberFormatter;
    protected $precision;

    /** @var string Pattern per i timestamp */
    protected $timestampPattern;
    /** @var string Pattern per le date */
    protected $datePattern;
    /** @var string Pattern per gli orari */
    protected $timePattern;

    /** @var array Pattern per SQL */
    protected $sql = [];

    public function __construct($locale, $timestamp = null, $date = null, $time = null, $number = [])
    {
        if (class_exists('NumberFormatter')) {
            $this->numberFormatter = new NumberFormatter($locale, NumberFormatter::DECIMAL);

            $this->numberFormatter->setAttribute(NumberFormatter::ROUNDING_MODE, NumberFormatter::ROUND_HALFUP);
        } else {
            $this->numberFormatter = $number;
        }

        $this->setTimestampPattern($timestamp);

        $this->setDatePattern($date);

        $this->setTimePattern($time);
    }

    /**
     * Restituisce gli elementi di separazione secondo la formattazione in utilizzo.
     *
     * @return array
     */
    public static function getStandardFormats()
    {
        return static::$standards;
    }

    /**
     * Restituisce gli elementi di separazione secondo la formattazione in utilizzo.
     *
     * @return mixed
     */
    public function format($value)
    {
        if (!empty($value)) {
            if ($this->isStandardDate($value)) {
                $value = $this->formatDate($value);
            } elseif ($this->isStandardTime($value)) {
                $value = $this->formatTime($value);
            } elseif ($this->isStandardTimestamp($value)) {
                $value = $this->formatTimestamp($value);
            } elseif ($this->isStandardNumber($value)) {
                $value = $this->formatNumber($value);
            }
        }

        return $value;
    }

    /**
     * Restituisce gli elementi di separazione secondo la formattazione in utilizzo.
     *
     * @return mixed
     */
    public function parse($value)
    {
        if (!empty($value)) {
            if ($this->isFormattedDate($value)) {
                $value = $this->parseDate($value);
            } elseif ($this->isFormattedTime($value)) {
                $value = $this->parseTime($value);
            } elseif ($this->isFormattedTimestamp($value)) {
                $value = $this->parseTimestamp($value);
            } elseif ($this->isFormattedNumber($value)) {
                $value = $this->parseNumber($value);
            }
        }

        return $value;
    }

    // Gestione della conversione dei numeri

    /**
     * Converte un numero da una formattazione all'altra.
     *
     * @param string $value
     * @param int    $decimals
     *
     * @return string|bool
     */
    public function formatNumber($value, $decimals = null)
    {
        $value = trim($value);
        $value = floatval($value);

        if (isset($decimals)) {
            $original = $this->getPrecision();
            $this->setPrecision($decimals);
        }

        if (is_object($this->numberFormatter)) {
            $result = $this->numberFormatter->format($value);
        } else {
            $number = number_format($value, $this->getPrecision(), self::getStandardFormats()['number']['decimals'], self::getStandardFormats()['number']['thousands']);

            $result = $this->customNumber($number, self::getStandardFormats()['number'], $this->getNumberSeparators());
        }

        if (isset($decimals)) {
            $this->setPrecision($original);
        }

        return is_numeric($value) ? $result : false;
    }

    /**
     * Converte un numero da una formattazione all'altra.
     *
     * @param string $value
     *
     * @return float|bool
     */
    public function parseNumber($value)
    {
        // Controllo sull'effettiva natura del numero
        $sign = null;
        if ($value[0] == '+' || $value[0] == '-') {
            $sign = $value[0];
            $value = substr($value, 1);
        }

        $number = str_replace(array_values($this->getNumberSeparators()), '', $value);

        $pieces = explode($this->getNumberSeparators()['decimals'], $value);
        $integer = str_replace(array_values($this->getNumberSeparators()), '', $pieces[0]);

        if (!ctype_digit($number) || (strlen($integer) != strlen((int) $integer)) || is_numeric($value)) {
            return false;
        }

        $value = $sign.$value;

        if (is_object($this->numberFormatter)) {
            $result = $this->numberFormatter->parse($value);
        } else {
            $result = $this->customNumber($value, $this->getNumberSeparators(), self::getStandardFormats()['number']);
        }

        $result = is_numeric($result) ? floatval($result) : false;

        return $result;
    }

    /**
     * Controlla se l'elemento indicato è un numero.
     *
     * @param string $value
     *
     * @return bool
     */
    public function isStandardNumber($value)
    {
        $result = $this->formatNumber($value);

        return !is_bool($result);
    }

    /**
     * Controlla se l'elemento indicato è un numero.
     *
     * @param string $value
     *
     * @return bool
     */
    public function isFormattedNumber($value)
    {
        $result = $this->parseNumber($value);

        return !is_bool($result);
    }

    /**
     * Imposta la precisione di default per i numeri da formattare.
     *
     * @param int $decimals
     */
    public function setPrecision($decimals)
    {
        if (is_object($this->numberFormatter)) {
            $this->numberFormatter->setAttribute(NumberFormatter::FRACTION_DIGITS, $decimals);
        } else {
            $this->precision = $decimals;
        }
    }

    /**
     * Restituisce la precisione di default per i numeri da formattare.
     *
     * @return int
     */
    public function getPrecision()
    {
        return is_object($this->numberFormatter) ? $this->numberFormatter->getAttribute(NumberFormatter::FRACTION_DIGITS) : $this->precision;
    }

    /**
     * Restituisce gli elementi di separazione secondo la formattazione in utilizzo.
     *
     * @return array
     */
    public function getNumberSeparators()
    {
        return [
            'decimals' => is_object($this->numberFormatter) ? $this->numberFormatter->getSymbol(NumberFormatter::DECIMAL_SEPARATOR_SYMBOL) : $this->numberFormatter['decimals'],
            'thousands' => is_object($this->numberFormatter) ? $this->numberFormatter->getSymbol(NumberFormatter::GROUPING_SEPARATOR_SYMBOL) : $this->numberFormatter['thousands'],
        ];
    }

    /**
     * Converte l'elemento in una rappresentazione numerica.
     *
     * @param string $value
     *
     * @return string|bool
     */
    public function customNumber($value, $current, $format)
    {
        $value = trim($value);

        if (strlen($value) == 0) {
            return false;
        }

        $sign = null;
        if ($value[0] == '+' || $value[0] == '-') {
            $sign = $value[0];
            $value = trim(substr($value, 1));
        } elseif (!is_numeric($value[0])) {
            return false;
        }

        if (strlen($value) == 0) {
            return false;
        }

        $pieces = explode($current['decimals'], $value);
        if (count($pieces) > 2) {
            return false;
        }
        $integer = $pieces[0];
        $decimal = (isset($pieces[1])) ? $pieces[1] : null;

        if (!empty($current['thousands'])) {
            $error = true;
            if (floor(strlen($integer) / 4) == substr_count($integer, $current['thousands'])) {
                $values = str_split(strrev($integer), 4);

                foreach ($values as $key => $value) {
                    if (strlen($value) == 4 && string_ends_with($value, $current['thousands'])) {
                        $values[$key] = substr($value, 0, -1);
                    }
                }

                $integer = strrev(implode($values));

                $error = substr_count($integer, $current['thousands']);
            }

            if (!empty($error)) {
                return false;
            }
        }

        if (!ctype_digit($integer) || (strlen($integer) != strlen((int) $integer)) || (isset($decimal) && !ctype_digit($decimal))) {
            return false;
        }

        $result = $sign.number_format($integer, 0, '', $format['thousands']);

        if (isset($decimal)) {
            $result .= $format['decimals'].$decimal;
        }

        return $result;
    }

    // Gestione della conversione dei timestamp

    /**
     * Converte un timestamp da una formattazione all'altra.
     *
     * @param string $value
     *
     * @return string|bool
     */
    public function formatTimestamp($value)
    {
        $object = DateTime::createFromFormat(static::$standards['timestamp'], $value);
        $result = is_object($object) ? $object->format($this->getTimestampPattern()) : false;

        return $result;
    }

    /**
     * Converte un timestamp da una formattazione all'altra.
     *
     * @param string $value
     *
     * @return string|bool
     */
    public function parseTimestamp($value)
    {
        $object = DateTime::createFromFormat($this->getTimestampPattern(), $value);
        $result = is_object($object) ? $object->format(static::$standards['timestamp']) : false;

        return $result;
    }

    /**
     * Controlla se l'elemento indicato è un timestamp.
     *
     * @param string $value
     *
     * @return bool
     */
    public function isStandardTimestamp($value)
    {
        $result = $this->formatTimestamp($value);

        return !is_bool($result);
    }

    /**
     * Controlla se l'elemento indicato è un timestamp.
     *
     * @param string $value
     *
     * @return bool
     */
    public function isFormattedTimestamp($value)
    {
        $result = $this->parseTimestamp($value);

        return !is_bool($result);
    }

    /**
     * Restituisce il formato del timestamp.
     *
     * @return string
     */
    public function getTimestampPattern($type = null)
    {
        return $this->timestampPattern;
    }

    /**
     * Imposta il formato del timestamp.
     *
     * @return string
     */
    public function setTimestampPattern($pattern)
    {
        return $this->timestampPattern = $pattern;
    }

    // Gestione della conversione delle date

    /**
     * Converte una data da una formattazione all'altra.
     *
     * @param string $value
     *
     * @return string|bool
     */
    public function formatDate($value)
    {
        $object = DateTime::createFromFormat(static::$standards['date'], $value);

        // Fallback per la gestione dei timestamp
        $object = !is_object($object) ? DateTime::createFromFormat(static::$standards['timestamp'], $value) : $object;

        $result = is_object($object) ? $object->format($this->getDatePattern()) : false;

        return $result;
    }

    /**
     * Converte una data da una formattazione all'altra.
     *
     * @param string $value
     *
     * @return string|bool
     */
    public function parseDate($value)
    {
        $object = DateTime::createFromFormat($this->getDatePattern(), $value);
        $result = is_object($object) ? $object->format(static::$standards['date']) : false;

        return $result;
    }

    /**
     * Controlla se l'elemento indicato è una data.
     *
     * @param string $value
     *
     * @return bool
     */
    public function isStandardDate($value)
    {
        $result = $this->formatDate($value);

        return !is_bool($result);
    }

    /**
     * Controlla se l'elemento indicato è una data.
     *
     * @param string $value
     *
     * @return bool
     */
    public function isFormattedDate($value)
    {
        $result = $this->parseDate($value);

        return !is_bool($result);
    }

    /**
     * Restituisce il formato del timestamp.
     *
     * @return string
     */
    public function getDatePattern()
    {
        return $this->datePattern;
    }

    /**
     * Imposta il formato del timestamp.
     *
     * @return string
     */
    public function setDatePattern($pattern)
    {
        return $this->datePattern = $pattern;
    }

    // Gestione della conversione degli orarii

    /**
     * Converte un orario da una formattazione all'altra.
     *
     * @param string $value
     *
     * @return string|bool
     */
    public function formatTime($value)
    {
        $object = DateTime::createFromFormat(static::$standards['time'], $value);

        // Fallback per la gestione dei timestamp
        $object = !is_object($object) ? DateTime::createFromFormat(static::$standards['timestamp'], $value) : $object;

        $result = is_object($object) ? $object->format($this->getTimePattern()) : false;

        return $result;
    }

    /**
     * Converte un orario da una formattazione all'altra.
     *
     * @param string $value
     *
     * @return string|bool
     */
    public function parseTime($value)
    {
        $object = DateTime::createFromFormat($this->getTimePattern(), $value);
        $result = is_object($object) ? $object->format(static::$standards['time']) : false;

        return $result;
    }

    /**
     * Controlla se l'elemento indicato è un orario.
     *
     * @param string $value
     *
     * @return bool
     */
    public function isStandardTime($value)
    {
        $result = $this->formatTime($value);

        return !is_bool($result);
    }

    /**
     * Controlla se l'elemento indicato è un orario.
     *
     * @param string $value
     *
     * @return bool
     */
    public function isFormattedTime($value)
    {
        $result = $this->parseTime($value);

        return !is_bool($result);
    }

    /**
     * Restituisce il formato del orario.
     *
     * @return string
     */
    public function getTimePattern($type = null)
    {
        return $this->timePattern;
    }

    /**
     * Imposta il formato del orario.
     *
     * @return string
     */
    public function setTimePattern($pattern)
    {
        return $this->timePattern = $pattern;
    }

    public function getSQLPatterns()
    {
        if (empty($this->sql)) {
            $formats = [
                'd' => '%d',
                'm' => '%m',
                'Y' => '%Y',
                'H' => '%H',
                'i' => '%i',
            ];

            $result = [
                'timestamp' => $this->getTimestampPattern(),
                'date' => $this->getDatePattern(),
                'time' => $this->getTimePattern(),
            ];

            foreach ($result as $key => $value) {
                $result[$key] = replace($value, $formats);
            }

            $this->sql = $result;
        }

        return $this->sql;
    }
}
