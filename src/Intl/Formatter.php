<?php

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
    ];

    /** @var NumberFormatter Oggetto dedicato alla formattazione dei numeri */
    protected $numberFormatter;

    /** @var string Pattern per le date */
    protected $datePattern;
    /** @var string Pattern per gli orari */
    protected $timePattern;
    /** @var string Pattern per i timestamp */
    protected $timestampPattern;

    public function __construct($locale, $timestamp = null, $date = null, $time = null)
    {
        $this->numberFormatter = new NumberFormatter($locale, NumberFormatter::DECIMAL);

        $this->setTimestampPattern($timestamp);

        $this->setDatePattern($date);

        $this->setTimePattern($time);
    }

    /**
     * Restituisce gli elementi di separazione secondo la formattazione in utilizzo.
     *
     * @return array
     */
    public function getStandardFormats()
    {
        return static::$standards;
    }

    // Gestione della conversione dei numeri

    /**
     * Converte un numero da una formattazione all'altra.
     *
     * @param string $value
     *
     * @return string
     */
    public function formatNumber($value, $decimals = null)
    {
        $value = trim($value);

        if (!empty($decimals)) {
            $original = $this->numberFormatter->getAttribute(NumberFormatter::FRACTION_DIGITS);
            $this->numberFormatter->setAttribute(NumberFormatter::FRACTION_DIGITS, $decimals);
        }

        $result = $this->numberFormatter->format($value);

        if (!empty($decimals)) {
            $this->numberFormatter->setAttribute(NumberFormatter::FRACTION_DIGITS, $original);
        }

        return is_numeric($value) ? $result : false;
    }

    /**
     * Converte un numero da una formattazione all'altra.
     *
     * @param string $value
     *
     * @return string
     */
    public function parseNumber($value)
    {
        return ctype_digit(str_replace(array_values($this->getNumberSeparators()), '', $value)) ? $this->numberFormatter->parse($value) : false;
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

        return !empty($result);
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

        return !empty($result);
    }

    /**
     * Imposta la precisione di default per i numeri da formattare.
     *
     * @param int $decimals
     */
    public function setPrecision($decimals)
    {
        $this->numberFormatter->setAttribute(NumberFormatter::FRACTION_DIGITS, $decimals);
    }

    /**
     * Restituisce gli elementi di separazione secondo la formattazione in utilizzo.
     *
     * @return array
     */
    public function getNumberSeparators()
    {
        return [
            'decimals' => $this->numberFormatter->getSymbol(NumberFormatter::DECIMAL_SEPARATOR_SYMBOL),
            'thousands' => $this->numberFormatter->getSymbol(NumberFormatter::GROUPING_SEPARATOR_SYMBOL),
        ];
    }

    // Gestione della conversione dei timestamp

    /**
     * Converte un timestamp da una formattazione all'altra.
     *
     * @param string $value
     *
     * @return string
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
     * @return string
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

        return !empty($result);
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

        return !empty($result);
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
     * @return string
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
     * @return string
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

        return !empty($result);
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

        return !empty($result);
    }

    /**
     * Restituisce il formato del timestamp.
     *
     * @return string
     */
    public function getDatePattern($type = null)
    {
        return $this->timestampPattern;
    }

    /**
     * Imposta il formato del timestamp.
     *
     * @return string
     */
    public function setDatePattern($pattern)
    {
        return $this->timestampPattern = $pattern;
    }

    // Gestione della conversione degli orarii

    /**
     * Converte un orario da una formattazione all'altra.
     *
     * @param string $value
     *
     * @return string
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
     * @return string
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

        return !empty($result);
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

        return !empty($result);
    }

    /**
     * Restituisce il formato del orario.
     *
     * @return string
     */
    public function getTimePattern($type = null)
    {
        return $this->orarioPattern;
    }

    /**
     * Imposta il formato del orario.
     *
     * @return string
     */
    public function setTimePattern($pattern)
    {
        return $this->orarioPattern = $pattern;
    }
}
