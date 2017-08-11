<?php

/**
 * Classe per gestire le traduzioni del progetto.
 *
 * @since 2.3
 */
class Translator extends Util\Singleton
{
    /** @var Intl\Formatter Oggetto per la conversione di date e numeri nella lingua selezionata */
    protected static $localeFormatter;
    /** @var Intl\Formatter Oggetto per la conversione di date e numeri nella formattazione originale */
    protected static $englishFormatter;

    /** @var Symfony\Component\Translation\Translator Oggetto dedicato alle traduzioni */
    protected $translator;

    /** @var array Lingue disponibili */
    protected $locales = [];
    /** @var string Lingua selezionata */
    protected $locale;

    public function __construct($default_locale = 'it', $fallback_locales = ['it'])
    {
        if (!empty($instance)) {
            throw new Exception();
        }

        if (version_compare(PHP_VERSION, '5.5.9') >= 0) {
            $translator = new Symfony\Component\Translation\Translator($default_locale);
            $this->locale = $default_locale;
            $translator->setFallbackLocales($fallback_locales);

            // Imposta la classe per il caricamento
            $translator->addLoader('default', new Intl\FileLoader());

            $this->translator = $translator;
        }

        $instance = $this;
    }

    /**
     * Ricerca e aggiunge le traduzioni presenti nei percorsi predefiniti (cartella locale sia nella root che nei diversi moduli).
     *
     * @param [type] $string
     */
    public function addLocalePath($string)
    {
        $paths = glob($string);
        foreach ($paths as $path) {
            $this->addLocales($path);
        }
    }

    /**
     * Aggiunge i contenuti della cartella specificata alle traduzioni disponibili.
     *
     * @param string $path
     */
    protected function addLocales($path)
    {
        if (!empty($this->$translator)) {
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
    public function setLocale($locale)
    {
        if (!empty($locale) && $this->isLocaleAvailable($locale)) {
            if (!empty($this->translator)) {
                self::$translator->setLocale($locale);
            }
            $this->locale = $locale;
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
    public static function translate($string, $parameters = [], $domain = null, $locale = null)
    {
        $translator = self::getInstance();
        if (!empty($translator)) {
            return $translator->trans($string, $parameters, $domain, $locale);
        } else {
            return $string;
        }
    }

    /**
     * Genera l'oggetto dedicato alla gestione delle conversioni nella lingua locale.
     *
     * @param array $formatter
     */
    public static function setLocaleFormatter($formatter = [])
    {
        self::$localeFormatter = new Intl\Formatter(
            empty($formatter['numbers']) ? [
                'decimals' => ',',
                'thousands' => '.',
            ] : $formatter['numbers'],
            empty($formatter['date']) ? 'd/m/Y' : $formatter['date'],
            empty($formatter['time']) ? 'H:i' : $formatter['time'],
            empty($formatter['timestamp']) ? null : $formatter['timestamp']);
    }

    /**
     * Restituisce il formato locale della data.
     *
     * @return Intl\Formatter
     */
    public static function getLocaleFormatter()
    {
        if (empty(self::$localeFormatter)) {
            self::setLocaleFormatter();
        }

        return self::$localeFormatter;
    }

    /**
     * Restituisce il formato locale della data.
     *
     * @return Intl\Formatter
     */
    public static function getEnglishFormatter()
    {
        if (empty(self::$englishFormatter)) {
            self::$englishFormatter = new Intl\Formatter();
        }

        return self::$englishFormatter;
    }

    /**
     * Restituisce il formato locale della data.
     *
     * @return string
     */
    public static function getLocaleDatePattern()
    {
        return self::getLocaleFormatter()->getDatePattern();
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
        return floatval(self::getLocaleFormatter()->convertNumberTo(self::getEnglishFormatter(), $string));
    }

    /**
     * Converte il numero dalla formattazione inglese a quella locale.
     *
     * @param string $string
     * @param mixed  $decimals
     *
     * @return string
     */
    public static function numberToLocale($string, $decimals = true)
    {
        $string = !isset($string) ? 0 : $string;

        if (isset($decimals) && (is_int($decimals) || !empty($decimals))) {
            $decimals = is_numeric($decimals) ? $decimals : Settings::get('Cifre decimali per importi');

            $string = number_format($string, $decimals, self::getEnglishFormatter()->getNumberSeparators()['decimals'], self::getEnglishFormatter()->getNumberSeparators()['thousands']);
        }

        return self::getEnglishFormatter()->convertNumberTo(self::getLocaleFormatter(), $string);
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
        return self::getLocaleFormatter()->convertDateTo(self::getEnglishFormatter(), $string);
    }

    /**
     * Converte la data dalla formattazione inglese a quella locale.
     *
     * @param string $string
     * @param string $fail
     *
     * @return string
     */
    public static function dateToLocale($string, $fail = null)
    {
        if (!self::isValid($string)) {
            return $fail;
        }

        return self::getEnglishFormatter()->convertDateTo(self::getLocaleFormatter(), $string);
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
        return self::getLocaleFormatter()->convertTimeTo(self::getEnglishFormatter(), $string);
    }

    /**
     * Converte la data dalla formattazione inglese a quella locale.
     *
     * @param string $string
     * @param string $fail
     *
     * @return string
     */
    public static function timeToLocale($string, $fail = null)
    {
        if (!self::isValid($string)) {
            return $fail;
        }

        return self::getEnglishFormatter()->convertTimeTo(self::getLocaleFormatter(), $string);
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
        return self::getLocaleFormatter()->convertTimestampTo(self::getEnglishFormatter(), $string);
    }

    /**
     * Converte un timestamp dalla formattazione inglese a quella locale.
     *
     * @param string $timestamp
     * @param string $fail
     *
     * @return string
     */
    public static function timestampToLocale($string, $fail = null)
    {
        if (!self::isValid($string)) {
            return $fail;
        }

        return self::getEnglishFormatter()->convertTimestampTo(self::getLocaleFormatter(), $string);
    }

    /**
     * Controlla se una data inserita nella formattazione inglese è valida.
     *
     * @param string $timestamp
     *
     * @return bool
     */
    protected static function isValid($string)
    {
        return !in_array($string, ['0000-00-00 00:00:00', '0000-00-00', '00:00:00']);
    }
}
