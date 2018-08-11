<?php

use Respect\Validation\Validator as v;
use Models\Setting;

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
            $validator = v::intVal()->validate($value);
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

    public static function input($setting)
    {
        $setting = self::get($setting);

        // Lista predefinita
        if (preg_match("/list\[(.+?)\]/", $setting->tipo, $m)) {
            $m = explode(',', $m[1]);
            $list = '';
            for ($j = 0; $j < count($m); ++$j) {
                if ($j != 0) {
                    $list .= ',';
                }
                $list .= '\\"'.$m[$j].'\\": \\"'.$m[$j].'\\"';
            }
            $result = '
    {[ "type": "select", "label": "'.$setting->nome.'", "name": "setting['.$setting->id.']", "values": "list='.$list.'", "value": "'.$setting->valore.'" ]}';
        }

        // Lista da query
        elseif (preg_match('/^query=(.+?)$/', $setting->tipo, $m)) {
            $result = '
    {[ "type": "select", "label": "'.$setting->nome.'", "name": "setting['.$setting->id.']", "values": "'.$setting->tipo.'", "value": "'.$setting->valore.'" ]}';
        }

        // Boolean (checkbox)
        elseif ($setting->tipo == 'boolean') {
            $result = '
    {[ "type": "checkbox", "label": "'.$setting->nome.'", "name": "setting['.$setting->id.']", "placeholder": "'.tr('Attivo').'", "value": "'.$setting->valore.'" ]}';
        }

        // Textarea
        elseif ($setting->tipo == 'textarea') {
            $result = '
    {[ "type": "textarea", "label": "'.$setting->nome.'", "name": "setting['.$setting->id.']", "value": '.json_encode($setting->valore).' ]}';
        }

        // Campo di testo
        else {
            $numerico = in_array($setting->tipo, ['integer', 'decimal']);

            $tipo = preg_match('/password/i', $setting->nome, $m) ? 'password' : $setting->tipo;
            $tipo = $numerico ? 'number' : 'text';

            $result = '
    {[ "type": "'.$tipo.'", "label": "'.$setting->nome.'", "name": "setting['.$setting->id.']", "value": "'.$setting->valore.'"'.($numerico && $setting->tipo == 'integer' ? ', "decimals": 0' : '').' ]}';
        }

        return $result;
    }
}
