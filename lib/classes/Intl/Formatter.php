<?php

namespace Intl;

use DateTime;
use UnexpectedValueException;
use Exception;

/**
 * Classe per gestire le conversione di date e numeri tra diversi formati.
 *
 * @since 2.3
 */
class Formatter
{
    protected $numberSeparators;

    protected $datePattern;
    protected $timePattern;
    protected $timestampPattern;

    public function __construct($numberSeparators = [], $date = null, $time = null, $timestamp = null)
    {
        $this->setNumberSeparators($numberSeparators);

        $this->setDatePattern($date);

        $this->setTimePattern($time);

        $this->setTimestampPattern($timestamp);
    }

    /**
     * Imposta il formato dei numeri.
     *
     * @param string $values
     */
    protected function setNumberSeparators($values)
    {
        $decimals = empty($values['decimals']) ? '.' : $values['decimals'];
        $thousands = !isset($values['thousands']) ? '' : $values['thousands'];

        if ($decimals == $thousands) {
            throw new Exception('Bad separators');
        }

        $this->numberSeparators = [
            'decimals' => $decimals,
            'thousands' => $thousands,
        ];
    }

    /**
     * Restituisce i separatori utilizzati per la formattazione.
     *
     * @return array
     */
    public function getNumberSeparators()
    {
        return $this->numberSeparators;
    }

    /**
     * Imposta il formato della data.
     *
     * @param string $value
     */
    protected function setDatePattern($value)
    {
        $value = empty($value) ? 'Y-m-d' : $value;

        if (is_array($value)) {
            $pattern = implode($value['separator'], $value['order']);
            $pattern = str_replace(['day', 'month', 'year'], ['d', 'm', 'Y'], $pattern);
        } else {
            $pattern = $value;
        }

        $this->datePattern = $pattern;
    }

    /**
     * Restituisce il formato della data.
     *
     * @return string
     */
    public function getDatePattern()
    {
        return $this->datePattern;
    }

    /**
     * Imposta il formato dell'orario.
     *
     * @param string $value
     */
    protected function setTimePattern($value)
    {
        $value = empty($value) ? 'H:i:s' : $value;

        if (is_array($value)) {
            $pattern = implode($value['separator'], $value['order']);
            $pattern = str_replace(['hours', 'minutes', 'seconds'], ['H', 'i', 's'], $pattern);
        } else {
            $pattern = $value;
        }

        $this->timePattern = $pattern;
    }

    /**
     * Restituisce il formato dell'orario.
     *
     * @return string
     */
    public function getTimePattern()
    {
        return $this->timePattern;
    }

    /**
     * Imposta il formato del timestamp.
     *
     * @param string $value
     */
    protected function setTimestampPattern($value)
    {
        $value = empty($value) ? [
            'order' => ['date', 'time'],
            'separator' => ' ',
         ] : $value;

        if (is_array($value)) {
            $pattern = implode($value['separator'], $value['order']);
            $pattern = str_replace(['date', 'time'], [$this->getDatePattern(), $this->getTimePattern()], $pattern);
        } else {
            $pattern = $value;
        }

        $this->timestampPattern = $pattern;
    }

    /**
     * Restituisce il formato del timestamp.
     *
     * @return string
     */
    public function getTimestampPattern()
    {
        return $this->timestampPattern;
    }

    /**
     * Controlla se l'elemento indicato è un numero.
     *
     * @param string $value
     *
     * @return bool
     */
    public function isNumber($value)
    {
        try {
            $this->toNumberObject($value);
        } catch (Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * Controlla se l'elemento indicato è un timestamp.
     *
     * @param string $value
     *
     * @return bool
     */
    public function isTimestamp($value)
    {
        try {
            $this->toTimestampObject($value);
        } catch (Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * Controlla se l'elemento indicato è una data.
     *
     * @param string $value
     *
     * @return bool
     */
    public function isDate($value)
    {
        try {
            $this->toDateObject($value);
        } catch (Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * Controlla se l'elemento indicato è un orario.
     *
     * @param string $value
     *
     * @return bool
     */
    public function isTime($value)
    {
        try {
            $this->toTimeObject($value);
        } catch (Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * Converte l'elemento in una rappresentazione numerica.
     *
     * @param string $value
     *
     * @return array
     */
    public function toNumberObject($value)
    {
        $value = trim($value);

        if (strlen($value) == 0) {
            throw new UnexpectedValueException('Format not supported');
        }

        $sign = null;
        if ($value[0] == '+' || $value[0] == '-') {
            $sign = $value[0];
            $value = trim(substr($value, 1));
        } elseif (!is_numeric($value[0])) {
            throw new UnexpectedValueException('Format not supported');
        }

        if (strlen($value) == 0) {
            throw new UnexpectedValueException('Format not supported');
        }

        $pieces = explode($this->getNumberSeparators()['decimals'], $value);
        if (count($pieces) > 2) {
            throw new UnexpectedValueException('Format not supported');
        }
        $integer = $pieces[0];
        $decimal = (isset($pieces[1])) ? $pieces[1] : null;

        if (!empty($this->getNumberSeparators()['thousands'])) {
            $error = true;
            if (floor(strlen($integer) / 4) == substr_count($integer, $this->getNumberSeparators()['thousands'])) {
                $values = str_split(strrev($integer), 4);

                foreach ($values as $key => $value) {
                    if (strlen($value) == 4 && ends_with($value, '.')) {
                        $values[$key] = substr($value, 0, -1);
                    }
                }

                $integer = strrev(implode($values));

                $error = substr_count($integer, $this->getNumberSeparators()['thousands']);
            }

            if (!empty($error)) {
                throw new UnexpectedValueException('Format not supported');
            }
        }

        if (!ctype_digit($integer) || (strlen($integer) != strlen((int) $integer)) || (isset($decimal) && !ctype_digit($decimal))) {
            throw new UnexpectedValueException('Format not supported');
        }

        return [$sign, $integer, $decimal];
    }

    /**
     * Converte l'elemento dal formato personalizzato a quello predefinito di PHP.
     *
     * @param string $value
     *
     * @return string
     */
    public function toTimestampObject($value)
    {
        $value = trim($value);

        $result = DateTime::createFromFormat($this->getTimestampPattern(), $value);

        if (!is_object($result)) {
            throw new UnexpectedValueException('Format not supported');
        }

        return $result;
    }

    /**
     * Converte l'elemento dal formato personalizzato a quello predefinito di PHP.
     *
     * @param string $value
     *
     * @return string
     */
    public function toDateObject($value)
    {
        $value = trim($value);

        $result = DateTime::createFromFormat($this->getDatePattern(), $value);

        $result = !is_object($result) ? $this->toTimestampObject($value) : $result;

        if (!is_object($result)) {
            throw new UnexpectedValueException('Format not supported');
        }

        return $result;
    }

    /**
     * Converte l'elemento dal formato personalizzato a quello predefinito di PHP.
     *
     * @param string $value
     *
     * @return string
     */
    public function toTimeObject($value)
    {
        $value = trim($value);

        $result = DateTime::createFromFormat($this->getTimePattern(), $value);

        $result = !is_object($result) ? $this->toTimestampObject($value) : $result;

        if (!is_object($result)) {
            throw new UnexpectedValueException('Format not supported');
        }

        return $result;
    }

    /**
     * Converte un numero da una formattazione all'altra.
     *
     * @param Formatter $formatter
     * @param string    $value
     *
     * @return string
     */
    public function convertNumberTo($formatter, $value)
    {
        $pieces = $this->toNumberObject($value);

        $result = (!empty($pieces[0])) ? $pieces[0] : '';

        $result .= number_format($pieces[1], 0, '', $formatter->getNumberSeparators()['thousands']);

        if (isset($pieces[2])) {
            $result .= $formatter->getNumberSeparators()['decimals'].$pieces[2];
        }

        return $result;
    }

    /**
     * Converte un timestamp da una formattazione all'altra.
     *
     * @param Formatter $formatter
     * @param string    $value
     *
     * @return string
     */
    public function convertTimestampTo($formatter, $value)
    {
        $result = $this->toTimestampObject($value);

        return $result->format($formatter->getTimestampPattern());
    }

    /**
     * Converte una data da una formattazione all'altra.
     *
     * @param Formatter $formatter
     * @param string    $value
     *
     * @return string
     */
    public function convertDateTo($formatter, $value)
    {
        $result = $this->toDateObject($value);

        return $result->format($formatter->getDatePattern());
    }

    /**
     * Converte un orario da una formattazione all'altra.
     *
     * @param Formatter $formatter
     * @param string    $value
     *
     * @return string
     */
    public function convertTimeTo($formatter, $value)
    {
        $result = $this->toTimeObject($value);

        return $result->format($formatter->getTimePattern());
    }

    /**
     * Converte un numero da una formattazione all'altra.
     *
     * @param Formatter $formatter
     * @param string    $value
     *
     * @return string
     */
    public function getNumberFrom($formatter, $value)
    {
        return $formatter->convertNumberTo($this, $value);
    }

    /**
     * Converte un timestamp da una formattazione all'altra.
     *
     * @param Formatter $formatter
     * @param string    $value
     *
     * @return string
     */
    public function getTimestampFrom($formatter, $value)
    {
        return $formatter->convertTimestampTo($this, $value);
    }

    /**
     * Converte una data da una formattazione all'altra.
     *
     * @param Formatter $formatter
     * @param string    $value
     *
     * @return string
     */
    public function getDateFrom($formatter, $value)
    {
        return $formatter->convertDateTo($this, $value);
    }

    /**
     * Converte un orario da una formattazione all'altra.
     *
     * @param Formatter $formatter
     * @param string    $value
     *
     * @return string
     */
    public function getTimeFrom($formatter, $value)
    {
        return $formatter->convertTimeTo($this, $value);
    }
}
