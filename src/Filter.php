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
            $config->set('URI.AllowedSchemes', [
                'http' => true,
                'https' => true,
                'mailto' => true,
                'ftp' => true,
                'tel' => true,
                'data' => true,
            ]);

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
