<?php

/**
 * Classe per gestire la sanitarizzazione degli input, basata sul framework open-source HTMLPurifier.
 *
 * @since 2.3
 */
class Filter
{
    /** @var HTMLPurifier */
    protected static $purifier;

    /** @var array Elenco dei contenuti inviati via POST */
    protected static $post = [];
    /** @var array Elenco dei contenuti inviati via GET */
    protected static $get = [];

    /**
     * Restituisce il valore presente nei dati ottenuti dall'input dell'utente.
     *
     * @param string $property
     * @param string $method
     *
     * @return string
     */
    public static function getValue($property, $method = null, $raw = false)
    {
        $value = null;

        if (empty($method)) {
            $value = (self::post($property, $raw) !== null) ? self::post($property, $raw) : self::get($property, $raw);
        } elseif (strtolower($method) == 'post') {
            $value = self::post($property, $raw);
        } elseif (strtolower($method) == 'get') {
            $value = self::get($property, $raw);
        }

        return $value;
    }

    /**
     * Restituisce i contenuti dalla sezione POST.
     *
     * @return array
     */
    public static function getPOST()
    {
        if (empty(self::$post)) {
            self::$post['raw'] = self::sanitize($_POST);
            self::$post['data'] = self::parse(self::$post['raw']);
        }

        return self::$post['data'];
    }

    /**
     * Restituisce il valore presente nei dati ottenuti dalla sezione POST.
     *
     * @param string $property
     *
     * @return string
     */
    public static function post($property, $raw = false)
    {
        self::getPOST();

        $category = empty($raw) ? 'data' : 'raw';

        if (isset(self::$post[$category][$property])) {
            return self::$post[$category][$property];
        }
    }

    /**
     * Restituisce i contenuti dalla sezione GET.
     *
     * @return array
     */
    public static function getGET()
    {
        if (empty(self::$get)) {
            self::$get['raw'] = self::sanitize($_GET);
            self::$get['data'] = self::parse(self::$get['raw']);
        }

        return self::$get['data'];
    }

    /**
     * Restituisce il valore presente nei dati ottenuti dalla sezione GET.
     *
     * @param string $property
     *
     * @return string
     */
    public static function get($property, $raw = false)
    {
        self::getGET();

        $category = empty($raw) ? 'data' : 'raw';

        if (isset(self::$get[$category][$property])) {
            return self::$get[$category][$property];
        }
    }

    /**
     * Sanitarizza il testo inserito.
     *
     * @param mixed $input Testo da sanitarizzare
     *
     * @return mixed
     */
    protected static function sanitize($input)
    {
        $output = null;
        if (is_array($input)) {
            foreach ($input as $key => $value) {
                $output[$key] = self::sanitize($value);
            }
        } else {
            $output = trim(self::getPurifier()->purify($input));
        }

        return $output;
    }

    /**
     * Undocumented function.
     */
    public static function parse($input)
    {
        $output = null;
        if (is_array($input)) {
            foreach ($input as $key => $value) {
                $output[$key] = self::parse($value);
            }
        } elseif (!is_null($input)) {
            $output = Translator::getFormatter()->parse($input);
        }

        return $output;
    }

    /**
     * Restituisce l'istanza di HTMLPurifier in utilizzo.
     *
     * @return \HTMLPurifier
     */
    public static function getPurifier()
    {
        if (empty(self::$purifier)) {
            $config = \HTMLPurifier_Config::createDefault();
            $config->set('HTML.Allowed', 'a[href|target|title],img[class|src|border|alt|title|hspace|vspace|width|height|align|name],hr[class|width|size|noshade],font[face|size|color|style],span[class|style],br,p[class]');
            //$config->set('Cache.SerializerPath', realpath(__DIR__.'/cache/HTMLPurifier'));
            $config->set('Cache.DefinitionImpl', null);

            self::$purifier = new \HTMLPurifier($config);
        }

        return self::$purifier;
    }

    /**
     * Imposta una proprietà specifica a un valore personalizzato.
     *
     * @param string $method
     * @param string $property
     * @param mixed  $value
     */
    public static function set($method, $property, $value)
    {
        if (strtolower($method) == 'post') {
            self::$post['data'][$property] = $value;
        } elseif (strtolower($method) == 'get') {
            self::$get['data'][$property] = $value;
        }
    }
}
