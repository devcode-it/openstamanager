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

namespace HTMLBuilder;

/**
 * Classe dedicata alla gestione della conversione di tag in codice HTML.
 *
 * <b>Tag di input</b>
 *
 * Campo di input generico:
 * {[ "type": "text", "required": 1, "value": "$idintervento$" ]}
 *
 * Campo di testo normale e non modificabile:
 * {[ "type": "span", "value": "$testo$" ]}
 *
 * Campo select automatizzatp:
 * {[ "type": "select", "required": 1, "values": "query=SELECT id, descrizione FROM co_contratti WHERE idanagrafica=$idanagrafica$", "value": "$idcontratto$" ]}
 *
 * La sostituzione dei parametri compresi tra $$ viene effettuata attraverso il parametro $records.
 *
 * <b>Tag personalizzati</b>
 *
 * Struttura per l'inserimento e la visualizzazione dei file:
 * {( "name": "filelist_and_upload", "id_module": "3", "id_record": "17" )}
 *
 * @since 2.3
 */
class HTMLBuilder
{
    /** @var array Codici di apertura dei tag personalizzati */
    public static $open = [
        'handler' => '{[',
        'manager' => '{(',
    ];

    /** @var array Codici di chiusura dei tag personalizzati */
    public static $close = [
        'handler' => ']}',
        'manager' => ')}',
    ];

    /** @var array Elenco degli attributi che necessitano esclusivamente di essere presenti */
    protected static $specifics = [
        'multiple',
        'checked',
        'disabled',
        'readonly',
        'required',
    ];

    /** @var array Elenco dei gestori dei campi HTML */
    protected static $handlers = [
        'list' => [
            'default' => Handler\DefaultHandler::class,
            'image' => Handler\MediaHandler::class,
            'select' => Handler\SelectHandler::class,
            'checkbox' => Handler\ChoicesHandler::class,
            'radio' => Handler\ChoicesHandler::class,
            'bootswitch' => Handler\ChoicesHandler::class,
            'timestamp' => Handler\DateHandler::class,
            'date' => Handler\DateHandler::class,
            'time' => Handler\DateHandler::class,
            'ckeditor' => Handler\CKEditorHandler::class,
        ],
        'instances' => [],
    ];

    /** @var array Generatore del contenitore per i campi HTML */
    protected static $wrapper = [
        'class' => Wrapper\HTMLWrapper::class,
        'istance' => null,
    ];

    /** @var array Elenco dei gestori delle strutture HTML */
    protected static $managers = [
        'list' => [
            'filelist_and_upload' => Manager\FileManager::class,
            'button' => Manager\ButtonManager::class,
            //'csrf' => Manager\CSRFManager::class,
            'custom_fields' => Manager\FieldManager::class,
            'widgets' => Manager\WidgetManager::class,
            'log_email' => Manager\EmailManager::class,
        ],
        'instances' => [],
    ];

    /** @var int Limite di ricorsione interna */
    protected static $max_recursion = 10;

    /** @var array Elenco degli elementi abilitati per la sostituzione automatica nei valori ($nome$) */
    protected static $record = [];

    /**
     * Esegue la sostituzione dei tag personalizzati con il relativo codice HTML.
     *
     * @param string $html
     *
     * @return string
     */
    public static function replace($html, $depth = 0)
    {
        // Gestione dei manager generici
        preg_match_all('/'.preg_quote(self::$open['manager']).'(.+?)'.preg_quote(self::$close['manager']).'/is', $html, $managers);

        foreach ($managers[0] as $value) {
            $json = self::decode($value, 'manager');
            $class = self::getManager($json['name']);

            $result = '';
            try {
                $result = !empty($class) ? $class->manage($json) : '';
            } catch (\Exception $exception) {
                logger()->error($exception->getMessage(), [
                    'code' => $exception->getCode(),
                    'message' => $exception->getMessage(),
                    'file' => $exception->getFile(),
                    'line' => $exception->getLine(),
                    'trace' => $exception->getTraceAsString(),
                ]);
            }

            // Ricorsione
            if ($depth < self::$max_recursion) {
                $result = self::replace($result, $depth + 1);
            }

            $html = str_replace($value, !empty($result) ? $result : $value, $html);
        }

        // Gestione del formato di input HTML semplificato
        preg_match_all('/'.preg_quote(self::$open['handler']).'(.+?)'.preg_quote(self::$close['handler']).'/is', $html, $handlers);

        foreach ($handlers[0] as $value) {
            $json = self::decode($value, 'handler');

            $result = null;
            try {
                $result = self::generate($json);
            } catch (\Exception $exception) {
                logger()->error($exception->getMessage(), [
                    'code' => $exception->getCode(),
                    'message' => $exception->getMessage(),
                    'file' => $exception->getFile(),
                    'line' => $exception->getLine(),
                    'trace' => $exception->getTraceAsString(),
                ]);
            }

            // Ricorsione
            if ($depth < self::$max_recursion) {
                $result = self::replace($result, $depth + 1);
            }

            $html = str_replace($value, !empty($result) ? $result : $value, $html);
        }

        return $html;
    }

    /**
     * Esegue la sostituzione per un singolo tag personalizzato con il relativo codice HTML.
     *
     * @return string
     */
    public static function parse(array $json)
    {
        $result = null;
        try {
            $result = self::generate($json);
        } catch (\Exception $exception) {
            logger()->error($exception->getMessage(), [
                'code' => $exception->getCode(),
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString(),
            ]);
        }

        // Ricorsione
        $result = self::replace($result);

        return !empty($result) ? $result : json_encode($json);
    }

    /**
     * Restituisce il nome della classe resposabile per la gestione di una determinata tipologia di tag di input.
     *
     * @param string $input
     *
     * @return string
     */
    public static function getHandlerName($input)
    {
        $result = empty(self::$handlers['list'][$input]) ? self::$handlers['list']['default'] : self::$handlers['list'][$input];

        return $result;
    }

    /**
     * Restituisce l'istanza della classe resposabile per la gestione di una determinata tipologia di tag di input.
     *
     * @param string $input
     *
     * @return mixed
     */
    public static function getHandler($input)
    {
        $class = self::getHandlerName($input);
        if (empty(self::$handlers['instances'][$class])) {
            self::$handlers['instances'][$class] = new $class();
        }

        return self::$handlers['instances'][$class];
    }

    /**
     * Imposta una determinata classe come resposabile per la gestione di una determinata tipologia di tag di input.
     *
     * @param string       $input
     * @param string|mixed $class
     */
    public static function setHandler($input, $class)
    {
        $original = $class;

        $class = is_object($class) ? $class : new $class();

        if ($class instanceof Handler\HandlerInterface) {
            self::$handlers['list'][$input] = $original;
            self::$handlers['instances'][$original] = $class;
        }
    }

    /**
     * Restituisce l'oggetto responsabile per la costruzione del codice HTML contenente gli input effettivi.
     *
     * @return mixed
     */
    public static function getWrapper()
    {
        if (empty(self::$wrapper['instance'])) {
            $class = self::$wrapper['class'];
            self::$wrapper['instance'] = new $class();
        }

        return self::$wrapper['instance'];
    }

    /**
     * Imposta l'oggetto responsabile per la costruzione del codice HTML contenente gli input effettivi.
     *
     * @param string|mixed $class
     */
    public static function setWrapper($class)
    {
        $original = $class;

        $class = is_object($class) ? $class : new $class();

        if ($class instanceof Wrapper\WrapperInterface) {
            self::$wrapper['class'] = $original;
            self::$wrapper['instance'] = $class;
        }
    }

    /**
     * Restituisce l'oggetto responsabile per la costruzione del codice HTML per il tag personalizzato.
     *
     * @param string $input
     *
     * @return mixed
     */
    public static function getManager($input)
    {
        $result = null;

        $class = self::$managers['list'][$input];
        if (!empty($class)) {
            if (empty(self::$managers['instances'][$class])) {
                self::$managers['instances'][$class] = new $class();
            }

            $result = self::$managers['instances'][$class];
        }

        return $result;
    }

    /**
     * Imposta l'oggetto responsabile per la costruzione del codice HTML per il tag personalizzato.
     *
     * @param string       $input
     * @param string|mixed $class
     */
    public static function setManager($input, $class)
    {
        $original = $class;

        $class = is_object($class) ? $class : new $class();

        if ($class instanceof Manager\ManagerInterface) {
            self::$managers['list'][$input] = $original;
            self::$managers['instances'][$original] = $class;
        }
    }

    /**
     * Imposta l'oggetto responsabile per la costruzione del codice HTML per il tag personalizzato.
     *
     * @param array $record
     */
    public static function setRecord($record)
    {
        self::$record = $record;
    }

    /**
     * Genera il codice HTML per i singoli tag di input.
     *
     * @param string $json
     *
     * @return string
     */
    protected static function generate($json)
    {
        // Elaborazione del formato
        $elaboration = self::elaborate($json);
        $values = $elaboration[0];
        $extras = $elaboration[1];

        $result = null;
        if (!empty($values)) {
            // Generazione dell'elemento
            $html = self::getHandler($values['type'])->handle($values, $extras);

            // Generazione del parte iniziale del contenitore
            $before = self::getWrapper()->before($values, $extras);

            // Generazione del parte finale del contenitore
            $after = self::getWrapper()->after($values, $extras);

            $result = $before.$html.$after;

            // Elaborazione del codice HTML
            $result = self::process($result, $values, $extras);
        }

        return $result;
    }

    /**
     * Decodifica i tag personalizzati e li converte in un array basato sul formato JSON.
     *
     * @param string $string
     * @param string $type
     *
     * @return array
     */
    protected static function decode($string, $type)
    {
        $string = '{'.substr($string, strlen(self::$open[$type]), -strlen(self::$close[$type])).'}';

        // Fix per contenuti con newline integrati
        $string = str_replace(["\n", "\r"], ['\\n', '\\r'], $string);

        $json = (array) json_decode($string, true);

        return $json;
    }

    /**
     * Elabora l'array contenente le impostazioni del tag per renderlo fruibile alla conversione in HTML (per i tag di input).
     *
     * @param array $json
     *
     * @return array
     */
    protected static function elaborate($json)
    {
        $values = [];
        $extras = [];

        if (!empty($json)) {
            // Conversione delle variabili con i campi di database ($record)
            foreach ($json as $key => $value) {
                if (empty($value) && !is_numeric($value)) {
                    unset($json[$key]);
                }
                // Sostituzione delle variabili $nome$ col relativo valore da database
                elseif (is_string($json[$key]) && preg_match_all('/\$([a-z0-9\_]+)\$/i', $json[$key], $m)) {
                    for ($i = 0; $i < count($m[0]); ++$i) {
                        $record = isset(self::$record[$m[1][$i]]) ? self::$record[$m[1][$i]] : '';
                        $json[$key] = str_replace($m[0][$i], prepareToField($record), $json[$key]);
                    }
                }
            }

            // Valori speciali che richiedono solo la propria presenza
            foreach (self::$specifics as $specific) {
                if (isset($json[$specific])) {
                    if (!empty($json[$specific])) {
                        $extras[] = trim($specific);
                    }
                    unset($json[$specific]);
                }
            }

            // Campo personalizzato "extra"
            if (isset($json['extra'])) {
                if (!empty($json['extra'])) {
                    $extras[] = trim($json['extra']);
                }
                unset($json['extra']);
            }

            // Attributi normali
            foreach ($json as $key => $value) {
                $values[trim($key)] = is_string($value) ? trim($value) : $value;
            }

            // Valori particolari
            $values['name'] = str_replace(' ', '_', $values['name']);
            $values['id'] = empty($values['id']) ? $values['name'] : $values['id'];
            $values['id'] = str_replace(['[', ']', ' '], ['', '', '_'], $values['id']);
            $values['value'] = isset($values['value']) ? $values['value'] : '';

            // Gestione delle classi CSS
            $values['class'] = [];
            $values['class'][] = 'form-control';
            if (!empty($json['class'])) {
                $classes = explode(' ', $json['class']);
                foreach ($classes as $class) {
                    if (!empty($class)) {
                        $values['class'][] = trim($class);
                    }
                }
            }

            // Gestione grafica dell'attributo required
            if (in_array('required', $extras)) {
                if (!empty($values['label'])) {
                    $values['label'] .= '*';
                } elseif (!empty($values['placeholder'])) {
                    $values['placeholder'] .= '*';
                }
            }
        }

        return [$values, $extras];
    }

    /**
     * Sostituisce i placeholder delle impostazioni con i relativi valori (per i tag di input).
     *
     * @param string $result
     * @param array  $values
     * @param array  $extras
     *
     * @return string
     */
    protected static function process($result, $values, $extras)
    {
        unset($values['label']);

        $values['class'] = array_unique($values['class']);

        $attributes = [];
        foreach ($values as $key => $value) {
            // Fix per la presenza di apici doppi
            $value = prepareToField(is_array($value) ? implode(' ', $value) : $value);
            if (string_contains($result, '|'.$key.'|')) {
                $result = str_replace('|'.$key.'|', $value, $result);
            } elseif (!empty($value) || is_numeric($value)) {
                $attributes[] = $key.'="'.$value.'"';
            }
        }

        $attributes = array_unique(array_merge($attributes, $extras));

        $result = str_replace('|attr|', implode(' ', $attributes), $result);

        return $result;
    }
}
