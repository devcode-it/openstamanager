<?php

use Models\Setting;
use Respect\Validation\Validator as v;

/**
 * Classe per la gestione dell impostazioni del progetto.
 *
 * @since 2.3
 */
class Settings
{
    /** @var array Elenco delle impostazioni disponibili */
    protected static $settings = [];
    protected static $references = [];
    protected static $sections = [];

    /**
     * Restituisce tutte le informazioni di tutti le impostazioni presenti.
     *
     * @return array
     */
    public static function getSettings()
    {
        if (empty(self::$settings)) {
            $settings = [];
            $references = [];
            $sections = [];

            $results = Setting::all();

            foreach ($results as $result) {
                $settings[$result->id] = $result;
                $references[$result->nome] = $result->id;

                if (!isset($sections[$result['sezione']])) {
                    $sections[$result['sezione']] = [];
                }
                $sections[$result['sezione']][] = $result->id;
            }

            self::$settings = $settings;
            self::$references = $references;
            self::$sections = $sections;
        }

        return self::$settings;
    }

    /**
     * Restituisce le informazioni relative a una singolo impostazione specificata.
     *
     * @param string|int $setting
     *
     * @return array
     */
    public static function get($setting)
    {
        $settings = self::getSettings();

        if (!is_numeric($setting) && !empty(self::$references[$setting])) {
            $setting = self::$references[$setting];
        }

        return $settings[$setting];
    }

    /**
     * Restituisce il valore corrente dell'impostazione ricercata.
     *
     * @param string|int $setting
     *
     * @return string
     */
    public static function getValue($setting)
    {
        return self::get($setting)->valore;
    }

    /**
     * Imposta il valore dell'impostazione indicata.
     *
     * @param string|int $setting
     * @param mixed      $value
     *
     * @return bool
     */
    public static function setValue($setting, $value)
    {
        $setting = self::get($setting);

        // Trasformazioni
        // Boolean (checkbox)
        if ($setting->tipo == 'boolean') {
            $value = (empty($value) || $value == 'off') ? false : true;
        }

        // Validazioni
        // integer
        if ($setting->tipo == 'integer') {
            $validator = v::intVal();
        }

        // list
        // verifico che il valore scelto sia nella lista enumerata nel db
        elseif (preg_match("/list\[(.+?)\]/", $setting->tipo, $m)) {
            $validator = v::in(explode(',', $m[1]));
        }

        // Boolean (checkbox)
        elseif ($setting->tipo == 'boolean') {
            $validator = v::boolType();
        }

        if (empty($validator) || $validator->validate($value)) {
            $setting->valore = $value;
            $setting->save();

            return true;
        }

        return  false;
    }

    /**
     * Genera l'input HTML per la modifica dell'impostazione.
     *
     * @param string|int $setting
     * @param bool       $required
     *
     * @return string
     */
    public static function input($setting, $required = false)
    {
        $setting = self::get($setting);

        // Lista predefinita
        if (preg_match("/list\[(.+?)\]/", $setting->tipo, $m)) {
            $values = explode(',', $m[1]);

            $list = [];
            foreach ($values as $value) {
                $list[] = [
                    'id' => $value,
                    'text' => $value,
                ];
            }

            $result = '
    {[ "type": "select", "label": "'.$setting->nome.'", "name": "setting['.$setting->id.']", "values": '.json_encode($list).', "value": "'.$setting->valore.'", "required": "'.intval($required).'", "help": "'.$setting->help.'" ]}';
        }

        // Lista da query
        elseif (preg_match('/^query=(.+?)$/', $setting->tipo, $m)) {
            $result = '
    {[ "type": "select", "label": "'.$setting->nome.'", "name": "setting['.$setting->id.']", "values": "'.$setting->tipo.'", "value": "'.$setting->valore.'", "required": "'.intval($required).'", "help": "'.$setting->help.'"   ]}';
        }

        // Boolean (checkbox)
        elseif ($setting->tipo == 'boolean') {
            $result = '
    {[ "type": "checkbox", "label": "'.$setting->nome.'", "name": "setting['.$setting->id.']", "placeholder": "'.tr('Attivo').'", "value": "'.$setting->valore.'", "required": "'.intval($required).'", "help": "'.$setting->help.'"  ]}';
        }

        // Campi di default
        elseif (in_array($setting->tipo, ['textarea', 'ckeditor', 'timestamp', 'date', 'time'])) {
            $result = '
    {[ "type": "'.$setting->tipo.'", "label": "'.$setting->nome.'", "name": "setting['.$setting->id.']", "value": '.json_encode($setting->valore).', "required": "'.intval($required).'", "help": "'.$setting->help.'"  ]}';
        }

        // Campo di testo
        else {
            $numerico = in_array($setting->tipo, ['integer', 'decimal']);

            $tipo = preg_match('/password/i', $setting->nome, $m) ? 'password' : $setting->tipo;
            $tipo = $numerico ? 'number' : 'text';

            $result = '
    {[ "type": "'.$tipo.'", "label": "'.$setting->nome.'", "name": "setting['.$setting->id.']", "value": "'.$setting->valore.'"'.($numerico && $setting->tipo == 'integer' ? ', "decimals": 0' : '').', "required": "'.intval($required).'", "help": "'.$setting->help.'"  ]}';
        }

        return $result;
    }
}
