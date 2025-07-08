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
     * Restituisce le informazioni relative ad una singola impostazione specificata.
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
     *
     * @return bool
     */
    public static function setValue($setting, $value)
    {
        $setting = Setting::where('id', '=', $setting)->orWhere('nome', '=', $setting)->first();
        $value = (is_array($value) ? implode(',', $value) : $value);

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
        elseif (preg_match("/list\[(.+?)\]/", (string) $setting->tipo, $m)) {
            $validator = v::in(explode(',', $m[1]));
        }

        // multiple
        // verifico che il valore scelto sia nella lista enumerata nel db
        elseif (preg_match("/multiple\[(.+?)\]/", (string) $setting->tipo, $m[0][0])) {
            // $validator =  v::in(explode(',', $m[0][0][1]));
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

        return false;
    }

    /**
     * Genera l'input HTML per la modifica dell'impostazione.
     *
     * @param string|int $setting
     * @param bool       $required
     *
     * @return string
     */
    public static function input($setting, $required = false, $value_user = null)
    {
        $setting = Setting::where('nome', '=', $setting)->orWhere('id', '=', $setting)->first();

        if ($value_user !== null) {
            $input_value = is_array($value_user) ? implode(',', $value_user) : $value_user;
        } else {
            $input_value = $setting->valore;
        }

        // Definizione icona per evidenziare le impostazioni personalizzate e personalizzabili
        $user = Auth::user();
        $user_options = [];
        $user_setting_icon = '';
        $tooltip = $setting->getTranslation('help');

        if ($user) {
            $user_options = json_decode((string) $user->options ?: '', true);
        }

        if ($user_options['settings'][$setting->id] !== null) {
            $user_setting_icon = '<i class="fa fa-user text-primary"></i>';
            $tooltip .= ($tooltip ? '<br>' : '').'<em>'.tr('Personalizzata dall\'utente').'</em>';
        } elseif ($setting->is_user_setting) {
            $user_setting_icon = '<i class="fa fa-user text-secondary"></i>';
            $tooltip .= ($tooltip ? '<br>' : '').'<em>'.tr('Personalizzabile dall\'utente').'</em>';
        }

        // Lista predefinita
        if (preg_match("/list\[(.+?)\]/", (string) $setting->tipo, $m)) {
            $values = explode(',', $m[1]);

            $list = [];
            foreach ($values as $value) {
                $list[] = [
                    'id' => $value,
                    'text' => $value,
                ];
            }

            $result = '
    {[ "type": "select", "multiple": 0, "label": '.json_encode($user_setting_icon.' '.$setting->getTranslation('title')).', "readonly": "'.!$setting->editable.'", "name": "setting['.$setting->id.']", "values": '.json_encode($list).', "value": "'.$input_value.'", "required": "'.intval($required).'", "help": "'.$tooltip.'" ]}';
        }

        // Lista multipla
        elseif (preg_match("/multiple\[(.+?)\]/", (string) $setting->tipo, $m)) {
            $list = [];

            // Gestisco il multiple da query trasformando i risultati in formato List
            if (strstr((string) $setting->tipo, 'query=')) {
                $database = database();

                $value = str_replace(']', '', explode('[', (string) $setting->tipo)[1]);
                $query = str_replace('query=', '', $value);
                //$query = str_replace('"', '\"', $query);
                $rs = $database->fetchArray($query);
                foreach ($rs as $r) {
                    $list[] = [
                        'id' => $r['id'],
                        'text' => $r['descrizione'],
                    ];
                }
            } else {
                $values = explode(',', $m[1]);

                foreach ($values as $value) {
                    $list[] = [
                        'id' => $value,
                        'text' => $value,
                    ];
                }
            }

            $result = '
        {[ "type": "select", "multiple": 1, "label": '.json_encode($user_setting_icon.' '.$setting->getTranslation('title')).', "readonly": "'.!$setting->editable.'", "name": "setting['.$setting->id.'][]", "values": '.json_encode($list).', "value": "'.$input_value.'", "required": "'.intval($required).'", "help": "'.$tooltip.'" ]}';
        }

        // Lista da query
        elseif (preg_match('/^query=(.+?)$/', (string) $setting->tipo, $m)) {
            $result = '
    {[ "type": "select", "label": '.json_encode($user_setting_icon.' '.$setting->getTranslation('title')).', "readonly": "'.!$setting->editable.'", "name": "setting['.$setting->id.']", "values": "'.str_replace('"', '\"', $setting->tipo).'", "value": "'.$input_value.'", "required": "'.intval($required).'", "help": "'.$tooltip.'"   ]}';
        }

        // Boolean (checkbox)
        elseif ($setting->tipo == 'boolean') {
            $result = '
    {[ "type": "checkbox", "label": '.json_encode($user_setting_icon.' '.$setting->getTranslation('title')).', "readonly": "'.!$setting->editable.'", "name": "setting['.$setting->id.']", "placeholder": "'.tr('Attivo').'", "value": "'.$input_value.'", "required": "'.intval($required).'", "help": "'.$tooltip.'"  ]}';
        }

        // Editor
        elseif ($setting->tipo == 'ckeditor') {
            $result = input([
                'type' => 'ckeditor',
                'label' => $user_setting_icon.' '.$setting->getTranslation('title'),
                'readonly' => !$setting->editable,
                'name' => 'setting['.$setting->id.']',
                'value' => $input_value,
                'required' => intval($required),
                'help' => $tooltip,
            ]);
        }

        // Campi di default
        elseif (in_array($setting->tipo, ['textarea', 'timestamp', 'date', 'time'])) {
            $result = '
    {[ "type": "'.$setting->tipo.'", "label": '.json_encode($user_setting_icon.' '.$setting->getTranslation('title')).', "readonly": "'.!$setting->editable.'", "name": "setting['.$setting->id.']", "value": '.json_encode($input_value).', "required": "'.intval($required).'", "help": "'.$tooltip.'"  ]}';
        }

        // Campo di testo
        else {
            $numerico = in_array($setting->tipo, ['integer', 'decimal']);

            $tipo = preg_match('/password/i', (string) $setting->getTranslation('title'), $m) ? 'password' : $setting->tipo;
            $tipo = $numerico ? 'number' : 'text';

            $result = '
    {[ "type": "'.$tipo.'", "label": '.json_encode($user_setting_icon.' '.$setting->getTranslation('title')).', "readonly": "'.!$setting->editable.'", "name": "setting['.$setting->id.']", "value": "'.$input_value.'"'.($numerico && $setting->tipo == 'integer' ? ', "decimals": 0' : '').', "required": "'.intval($required).'", "help": "'.$tooltip.'"  ]}';
        }

        return $result;
    }
}
