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
    public static function getValue($property, $method = null, $parse = false)
    {
        $value = null;

        if (empty($method)) {
            $value = (self::post($property, $parse) !== null) ? self::post($property, $parse) : self::get($property, $parse);
        } elseif (strtolower($method) == 'post') {
            $value = self::post($property, $parse);
        } elseif (strtolower($method) == 'get') {
            $value = self::get($property, $parse);
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
            self::$post['parsed'] = [];
        }

        return self::$post['parsed'];
    }

    /**
     * Restituisce il valore presente nei dati ottenuti dalla sezione POST.
     *
     * @param string $property
     *
     * @return string
     */
    public static function post($property, $parse = false)
    {
        self::getPOST();

        if (!empty($parse) && !isset(self::$post['parsed'][$property])) {
            self::$post['parsed'][$property] = self::parse(post($property));
        }

        $category = !empty($parse) ? 'parsed' : 'raw';

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
            self::$get['parsed'] = [];
        }

        return self::$get['parsed'];
    }

    /**
     * Restituisce il valore presente nei dati ottenuti dalla sezione GET.
     *
     * @param string $property
     *
     * @return string
     */
    public static function get($property, $parse = false)
    {
        self::getGET();

        if (!empty($parse) && !isset(self::$get['parsed'][$property])) {
            self::$get['parsed'][$property] = self::parse(get($property));
        }

        $category = !empty($parse) ? 'parsed' : 'raw';

        if (isset(self::$get[$category][$property])) {
            return self::$get[$category][$property];
        }
    }

    /**
     * Sanitarizza i contenuti dell'input.
     *
     * @param mixed $input Contenuti
     *
     * @return mixed
     */
    public static function sanitize($input)
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
     * Interpreta e formatta correttamente i contenuti dell'input.
     *
     * @param mixed $input Contenuti
     *
     * @return mixed
     */
    public static function parse($input)
    {
        $output = null;
        if (is_array($input)) {
            foreach ($input as $key => $value) {
                $output[$key] = self::parse($value);
            }
        } elseif (!is_null($input)) {
            $output = formatter()->parse($input);
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

            $config->set('HTML.Allowed', 'br,p[style],b[style],strong[style],i[style],em[style],u[style],strike,a[style|href|title|target],ol[style],ul[style],li[style],hr[style],blockquote[style],img[style|alt|title|width|height|src|align],table[style|width|bgcolor|align|cellspacing|cellpadding|border],tr[style],td[style],th[style],tbody,thead,caption,col,colgroup,span[style],sup');

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
            self::$post['parsed'][$property] = $value;
        } elseif (strtolower($method) == 'get') {
            self::$get['parsed'][$property] = $value;
        }
    }
}
