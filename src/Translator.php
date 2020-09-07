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

use Carbon\Carbon;
use Carbon\CarbonInterval;

/**
 * Classe per gestire le traduzioni del progetto.
 *
 * @since 2.3
 */
class Translator extends Util\Singleton
{
    /** @var Intl\Formatter Oggetto per la conversione di date e numeri nella lingua selezionata */
    protected static $formatter;
    /** @var string Simbolo della valuta corrente */
    protected static $currency;

    /** @var Symfony\Component\Translation\Translator Oggetto dedicato alle traduzioni */
    protected $translator;

    /** @var array Lingue disponibili */
    protected $locales = [];
    /** @var string Lingua selezionata */
    protected $locale;

    public function __construct($default_locale = 'it_IT', $fallback_locales = ['it_IT'])
    {
        $translator = new Symfony\Component\Translation\Translator($default_locale);
        $translator->setFallbackLocales($fallback_locales);
        // Imposta la classe per il caricamento
        $translator->addLoader('default', new Intl\FileLoader());

        $this->translator = $translator;

        $this->locale = $default_locale;
        self::setFormatter($default_locale, []);
    }

    /**
     * Ricerca e aggiunge le traduzioni presenti nei percorsi predefiniti (cartella locale sia nella root che nei diversi moduli).
     *
     * @param string $string
     */
    public function addLocalePath($string)
    {
        $paths = glob($string);
        foreach ($paths as $path) {
            $this->addLocales($path);
        }
    }

    /**
     * Restituisce l'elenco dei linguaggi disponibili.
     *
     * @return array
     */
    public function getAvailableLocales()
    {
        return $this->locales;
    }

    /**
     * Controlla se il linguaggio indicato è disponibile.
     *
     * @param string $language
     *
     * @return bool
     */
    public function isLocaleAvailable($language)
    {
        return in_array($language, $this->getAvailableLocales());
    }

    /**
     * Imposta il linguaggio in utilizzo.
     *
     * @param string $locale
     */
    public function setLocale($locale, $formatter = [])
    {
        if (!empty($locale) && $this->isLocaleAvailable($locale)) {
            $this->translator->setLocale($locale);
            $this->locale = $locale;

            $result = setlocale(LC_TIME, $locale);
            Carbon::setLocale($locale);

            if (empty($result)) {
                $result = setlocale(LC_TIME, $locale.'.UTF-8');
            } else {
                Carbon::setUtf8(true);
            }

            $reduced = explode('_', $locale)[0];
            CarbonInterval::setLocale($reduced);

            if (empty($result)) {
                $result = setlocale(LC_TIME, $reduced);
            }

            self::setFormatter($locale, $formatter);
        }
    }

    /**
     * Restituisce il linguaggio attualmente in utilizzo.
     *
     * @return string
     */
    public function getCurrentLocale()
    {
        return $this->locale;
    }

    /**
     * Restituisce l'oggetto responsabile della gestione delle traduzioni.
     *
     * @return Symfony\Component\Translation\Translator
     */
    public function getTranslator()
    {
        return $this->translator;
    }

    /**
     * Restituisce la traduzione richiesta.
     *
     * @param string $string
     * @param string $parameters
     * @param string $domain
     * @param string $locale
     *
     * @return string
     */
    public static function translate($string, $parameters = [], $operations = [])
    {
        $result = self::getInstance()->getTranslator()->trans($string, $parameters);

        // Operazioni aggiuntive sul risultato
        if (!empty($operations)) {
            $result = new Stringy\Stringy($result);

            if (!empty($operations['upper'])) {
                $result = $result->toUpperCase();
            } elseif (!empty($operations['lower'])) {
                $result = $result->toLowerCase();
            }
        }

        return (string) $result;
    }

    /**
     * Restituisce l'oggetto responsabile della localizzazione di date e numeri.
     *
     * @return Intl\Formatter
     */
    public static function getFormatter()
    {
        return self::$formatter;
    }

    /**
     * Restituisce il simbolo della valuta del gestione.
     *
     * @since 2.4.9
     *
     * @return string
     */
    public static function getCurrency()
    {
        if (!isset(self::$currency)) {
            $id = setting('Valuta');
            $valuta = database()->fetchOne('SELECT symbol FROM zz_currencies WHERE id = '.prepare($id));

            self::$currency = $valuta['symbol'];
        }

        return self::$currency;
    }

    /**
     * Converte il numero dalla formattazione locale a quella inglese.
     *
     * @param string $string
     *
     * @return string
     */
    public static function numberToEnglish($string)
    {
        return self::getFormatter()->parseNumber($string);
    }

    /**
     * Converte il numero dalla formattazione inglese a quella locale.
     *
     * @param string     $string
     * @param string|int $decimals
     *
     * @return string
     */
    public static function numberToLocale($string, $decimals = null)
    {
        $string = !isset($string) ? 0 : $string;

        if (!empty($decimals) && is_string($decimals)) {
            $decimals = ($decimals == 'qta') ? setting('Cifre decimali per quantità') : null;
        }

        return self::getFormatter()->formatNumber($string, $decimals);
    }

    /**
     * Converte la data dalla formattazione locale a quella inglese.
     *
     * @param string $string
     *
     * @return string
     */
    public static function dateToEnglish($string)
    {
        return self::getFormatter()->parseDate($string);
    }

    /**
     * Converte la data dalla formattazione inglese a quella locale.
     *
     * @param string $string
     * @param string $fail
     *
     * @return string
     */
    public static function dateToLocale($string)
    {
        return self::getFormatter()->formatDate($string);
    }

    /**
     * Converte la data dalla formattazione locale a quella inglese.
     *
     * @param string $string
     *
     * @return string
     */
    public static function timeToEnglish($string)
    {
        return self::getFormatter()->parseTime($string);
    }

    /**
     * Converte la data dalla formattazione inglese a quella locale.
     *
     * @param string $string
     * @param string $fail
     *
     * @return string
     */
    public static function timeToLocale($string)
    {
        return self::getFormatter()->formatTime($string);
    }

    /**
     * Converte un timestamp dalla formattazione locale a quella inglese.
     *
     * @param string $timestamp
     *
     * @return string
     */
    public static function timestampToEnglish($string)
    {
        return self::getFormatter()->parseTimestamp($string);
    }

    /**
     * Converte un timestamp dalla formattazione inglese a quella locale.
     *
     * @param string $timestamp
     * @param string $fail
     *
     * @return string
     */
    public static function timestampToLocale($string)
    {
        return self::getFormatter()->formatTimestamp($string);
    }

    /**
     * Aggiunge i contenuti della cartella specificata alle traduzioni disponibili.
     *
     * @param string $path
     */
    protected function addLocales($path)
    {
        // Individua i linguaggi disponibili
        $dirs = glob($path.DIRECTORY_SEPARATOR.'*', GLOB_ONLYDIR);
        foreach ($dirs as $dir) {
            $this->addLocale(basename($dir));
        }

        // Aggiunge le singole traduzioni
        foreach ($this->locales as $lang) {
            $done = [];

            $files = glob($path.DIRECTORY_SEPARATOR.$lang.DIRECTORY_SEPARATOR.'*.*');
            foreach ($files as $file) {
                if (!in_array(basename($file), $done)) {
                    $this->translator->addResource('default', $file, $lang);

                    $done[] = basename($file);
                }
            }
        }
    }

    /**
     * Aggiunge il linguaggio indicato all'elenco di quelli disponibili.
     *
     * @param string $language
     */
    protected function addLocale($language)
    {
        if (!$this->isLocaleAvailable($language)) {
            $this->locales[] = $language;
        }
    }

    /**
     * Imposta l'oggetto responsabile della localizzazione di date e numeri.
     */
    protected static function setFormatter($locale, $options)
    {
        self::$formatter = new Intl\Formatter(
            $locale,
            empty($options['timestamp']) ? 'd/m/Y H:i' : $options['timestamp'],
            empty($options['date']) ? 'd/m/Y' : $options['date'],
            empty($options['time']) ? 'H:i' : $options['time'],
            empty($options['number']) ? [
                'decimals' => ',',
                'thousands' => '.',
            ] : $options['number']
        );

        self::$formatter->setPrecision(auth()->check() ? setting('Cifre decimali per importi') : 2);
    }
}
