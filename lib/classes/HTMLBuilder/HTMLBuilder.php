<?php

namespace HTMLBuilder;

/**
 * Classe dedicata alla gestione della conversione di tag in codice HTML.
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

    /** @var array Lista degli attributi inseriit nel formato che necessitano solo di essere presenti */
    protected static $specifics = [
        'multiple',
        'checked',
        'disabled',
        'readonly',
        'required',
    ];

    /** @var array Lista dei gestori dei campi HTML */
    protected static $handlers = [
        'list' => [
            'default' => 'HTMLBuilder\Handler\DefaultHandler',
            'image' => 'HTMLBuilder\Handler\MediaHandler',
            'select' => 'HTMLBuilder\Handler\SelectHandler',
            'checkbox' => 'HTMLBuilder\Handler\ChoicesHandler',
            'radio' => 'HTMLBuilder\Handler\ChoicesHandler',
            'bootswitch' => 'HTMLBuilder\Handler\ChoicesHandler',
            'timestamp' => 'HTMLBuilder\Handler\DateHandler',
            'date' => 'HTMLBuilder\Handler\DateHandler',
            'time' => 'HTMLBuilder\Handler\DateHandler',
        ],
        'instances' => [],
    ];

    /** @var array Generatore del contenitore per i campi HTML */
    protected static $wrapper = [
        'class' => 'HTMLBuilder\Wrapper\HTMLWrapper',
        'istance' => null,
    ];

    /** @var array Lista dei gestori delle strutture HTML */
    protected static $managers = [
        'list' => [
            'filelist_and_upload' => 'HTMLBuilder\Manager\FileManager',
            'csrf' => 'HTMLBuilder\Manager\CSRFManager',
        ],
        'instances' => [],
    ];

    public static function replace($html)
    {
        preg_match_all('/'.preg_quote(self::$open['manager']).'(.+?)'.preg_quote(self::$close['manager']).'/i', $html, $managers);

        foreach ($managers[0] as $value) {
            $json = self::decode($value, 'manager');
            $class = self::getManager($json['name']);

            $html = str_replace($value, !empty($class) ? $class->manage($json) : '', $html);
        }

        preg_match_all('/'.preg_quote(self::$open['handler']).'(.+?)'.preg_quote(self::$close['handler']).'/i', $html, $handlers);

        foreach ($handlers[0] as $value) {
            $json = self::decode($value, 'handler');
            $html = str_replace($value, self::generate($json), $html);
        }

        return $html;
    }

    protected static function generate($json)
    {
        // Elaborazione del formato
        list($values, $extras) = self::elaborate($json);

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

    protected static function decode($string, $type)
    {
        $string = '{'.substr($string, strlen(self::$open[$type]), -strlen(self::$close[$type])).'}';

        $json = (array) json_decode($string, true, 2);

        return $json;
    }

    protected static function elaborate($json)
    {
        global $records;

        $values = [];
        $extras = [];

        if (!empty($json)) {
            // Conversione delle variabili con i campi di database ($records)
            foreach ($json as $key => $value) {
                if (empty($value) && !is_numeric($value)) {
                    unset($json[$key]);
                }
                // Sostituzione delle variabili $nome$ col relativo valore da database
                elseif (preg_match_all('/\$([a-z0-9\_]+)\$/i', $json[$key], $m)) {
                    for ($i = 0; $i < count($m[0]); ++$i) {
                        $record = isset($records[0][$m[1][$i]]) ? $records[0][$m[1][$i]] : '';
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
                $values[trim($key)] = trim($value);
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

    protected static function process($result, $values, $extras)
    {
        unset($values['label']);

        $values['class'] = array_unique($values['class']);

        foreach ($values as $key => $value) {
            // Fix per la presenza di apici doppi
            $value = prepareToField(is_array($value) ? implode(' ', $value) : $value);
            if (str_contains($result, '|'.$key.'|')) {
                $result = str_replace('|'.$key.'|', $value, $result);
            } elseif (!empty($value) || is_numeric($value)) {
                $attributes[] = $key.'="'.$value.'"';
            }
        }

        $attributes = array_unique(array_merge($attributes, $extras));

        $result = str_replace('|attr|', implode(' ', $attributes), $result);

        return $result;
    }

    public static function getHandlerName($input)
    {
        $result = empty(self::$handlers['list'][$input]) ? self::$handlers['list']['default'] : self::$handlers['list'][$input];

        return $result;
    }

    public static function getHandler($input)
    {
        $class = self::getHandlerName($input);
        if (empty(self::$handlers['instances'][$class])) {
            self::$handlers['instances'][$class] = new $class();
        }

        return self::$handlers['instances'][$class];
    }

    public static function setHandler($input, $class)
    {
        $original = $class;

        $class = is_object($class) ? $class : new $class();

        if ($class instanceof Handler\HandlerInterface) {
            self::$handlers['list'][$input] = $original;
            self::$handlers['instances'][$original] = $class;
        }
    }

    public static function getWrapper()
    {
        if (empty(self::$wrapper['instance'])) {
            $class = self::$wrapper['class'];
            self::$wrapper['instance'] = new $class();
        }

        return self::$wrapper['instance'];
    }

    public static function setWrapper($class)
    {
        $original = $class;

        $class = is_object($class) ? $class : new $class();

        if ($class instanceof Wrapper\WrapperInterface) {
            self::$wrapper['class'] = $original;
            self::$wrapper['instance'] = $class;
        }
    }

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

    public static function setManager($input, $class)
    {
        $original = $class;

        $class = is_object($class) ? $class : new $class();

        if ($class instanceof Handler\ManagerInterface) {
            self::$managers['list'][$input] = $original;
            self::$managers['instances'][$original] = $class;
        }
    }
}

function prepareToField($string)
{
    return str_replace('"', '&quot;', $string);
}
