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

namespace Models;

use Common\SimpleModelTrait;
use Illuminate\Database\Eloquent\Model;
use Respect\Validation\Validator as v;
use Traits\LocalPoolTrait;

class Setting extends Model
{
    use SimpleModelTrait;
    use LocalPoolTrait;

    protected $table = 'zz_settings';

    protected $appends = [
        'description',
    ];

    public function getDescriptionAttribute()
    {
        $value = $this->valore;

        // Valore corrispettivo
        $query = str_replace('query=', '', $this->tipo);
        if ($query != $this->tipo) {
            $data = database()->fetchArray($query);
            if (!empty($data)) {
                $value = $data[0]['descrizione'];
            }
        }

        return $value;
    }

    public static function getPoolNameColumn()
    {
        return 'nome';
    }

    /**
     * Imposta il valore dell'impostazione indicata.
     *
     * @param mixed $value
     *
     * @return bool
     */
    public function setValue($value)
    {
        // Trasformazioni
        // Boolean (checkbox)
        if ($this->tipo == 'boolean') {
            $value = (empty($value) || $value == 'off') ? false : true;
        }

        // Validazioni
        // integer
        if ($this->tipo == 'integer') {
            $validator = v::intVal();
        }

        // list
        // verifico che il valore scelto sia nella lista enumerata nel db
        elseif (preg_match("/list\[(.+?)\]/", $this->tipo, $m)) {
            $validator = v::in(explode(',', $m[1]));
        }

        // Boolean (checkbox)
        elseif ($this->tipo == 'boolean') {
            $validator = v::boolType();
        }

        if (empty($validator) || $validator->validate($value)) {
            $this->valore = $value;
            $this->save();

            return true;
        }

        return false;
    }

    /**
     * Genera l'input HTML per la modifica dell'impostazione.
     *
     * @param bool $required
     *
     * @return string
     */
    public function input($required = false)
    {
        // Lista predefinita
        if (preg_match("/list\[(.+?)\]/", $this->tipo, $m)) {
            $values = explode(',', $m[1]);

            $list = [];
            foreach ($values as $value) {
                $list[] = [
                    'id' => $value,
                    'text' => $value,
                ];
            }

            $result = '
    {[ "type": "select", "label": '.json_encode($this->nome).', "name": "setting['.$this->id.']", "values": '.json_encode($list).', "value": "'.$this->valore.'", "required": "'.intval($required).'", "help": "'.$this->help.'" ]}';
        }

        // Lista da query
        elseif (preg_match('/^query=(.+?)$/', $this->tipo, $m)) {
            $result = '
    {[ "type": "select", "label": '.json_encode($this->nome).', "name": "setting['.$this->id.']", "values": "'.str_replace('"', '\"', $this->tipo).'", "value": "'.$this->valore.'", "required": "'.intval($required).'", "help": "'.$this->help.'"   ]}';
        }

        // Boolean (checkbox)
        elseif ($this->tipo == 'boolean') {
            $result = '
    {[ "type": "checkbox", "label": '.json_encode($this->nome).', "name": "setting['.$this->id.']", "placeholder": "'.tr('Attivo').'", "value": "'.$this->valore.'", "required": "'.intval($required).'", "help": "'.$this->help.'"  ]}';
        }

        // Campi di default
        elseif (in_array($this->tipo, ['textarea', 'ckeditor', 'timestamp', 'date', 'time'])) {
            $result = '
    {[ "type": "'.$this->tipo.'", "label": '.json_encode($this->nome).', "name": "setting['.$this->id.']", "value": '.json_encode($this->valore).', "required": "'.intval($required).'", "help": "'.$this->help.'"  ]}';
        }

        // Campo di testo
        else {
            $numerico = in_array($this->tipo, ['integer', 'decimal']);

            $tipo = preg_match('/password/i', $this->nome, $m) ? 'password' : $this->tipo;
            $tipo = $numerico ? 'number' : 'text';

            $result = '
    {[ "type": "'.$tipo.'", "label": '.json_encode($this->nome).', "name": "setting['.$this->id.']", "value": "'.$this->valore.'"'.($numerico && $this->tipo == 'integer' ? ', "decimals": 0' : '').', "required": "'.intval($required).'", "help": "'.$this->help.'"  ]}';
        }

        return $result;
    }
}
