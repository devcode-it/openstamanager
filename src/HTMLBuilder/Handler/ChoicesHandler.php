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

namespace HTMLBuilder\Handler;

/**
 * Gestione dell'input di tipo "checkbox".
 *
 * @since 2.3
 */
class ChoicesHandler implements HandlerInterface
{
    public function handle(&$values, &$extras)
    {
        // Delega della gestione al metodo specifico per il tipo di input richiesto
        $result = $this->{$values['type']}($values, $extras);

        return $result;
    }

    /**
     * Gestione dell'input di tipo "checkbox".
     * Esempio: {[ "type": "checkbox", "label": "Checkbox di test", "placeholder": "Test", "name": "checkbox", "value": "1" ]}.
     *
     * @param array $values
     * @param array $extras
     *
     * @return string
     */
    protected function checkbox(&$values, &$extras)
    {
        unset($values['class'][0]);

        // Restrizione dei valori permessi
        $values['value'] = (empty($values['value']) || $values['value'] == 'off') ? false : true;

        // Gestione della proprietà "checked"
        if (!empty($values['value']) && !in_array('checked', $extras)) {
            $extras[] = 'checked';
        }

        // Gestione della proprietà "readonly"
        if (in_array('readonly', $extras)) {
            $extras[] = 'disabled';
        }

        $class = '';
        if (in_array('disabled', $extras)) {
            $class = ' disabled';
        }

        // Gestione dei placeholder
        $values['placeholder'] = isset($values['placeholder']) ? $values['placeholder'] : $values['label'];

        // Gestione valori custom
        if ( !empty($values['values']) ){
            $valori_custom = explode(",",$values['values']);
            $options = '<span class="text-success">'.str_replace('"','',$valori_custom[0]).'</span>
            <span class="text-danger">'.str_replace('"','',$valori_custom[1]).'</span>';
        }

        // Generazione del codice HTML
        // "+ this.checked" rende il valore booleano un numero
        $result = '
        <input type="hidden" name="|name|" value="|value|" class="openstamanager-input">
        <input type="checkbox" id="|id|" value="|value|" class="hidden" |attr| onchange="$(this).parent().find(\'[type = hidden]\').val(+this.checked).trigger(\'change\')"/>
        <div class="btn-group checkbox-buttons">
            <label for="|id|" class="btn btn-default'.$class.'">
                <span class="fa fa-check text-success"></span>
                <span class="fa fa-close text-danger"></span>
            </label>
            <label for="|id|" class="btn btn-default active'.$class.'">';
                if( !empty($options) ){
                    $result .= $options;
                }else{
                    $result .= '
                    <span class="text-success">'.tr('Attivato').'</span>
                    <span class="text-danger">'.tr('Disattivato').'</span>';
                }
        $result .= '
            </label>
        </div>';

        return $result;
    }
}
