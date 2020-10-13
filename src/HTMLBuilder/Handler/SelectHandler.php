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

namespace HTMLBuilder\Handler;

use AJAX;

/**
 * Gestione dell'input di tipo "select".
 *
 * @since 2.3
 */
class SelectHandler implements HandlerInterface
{
    public function handle(&$values, &$extras)
    {
        $values['class'][] = 'openstamanager-input';
        $values['class'][] = 'select-input';

        $source = isset($values['ajax-source']) ? $values['ajax-source'] : (isset($values['select-source']) ? $values['select-source'] : null);

        // Individuazione della classe per la corretta gestione JavaScript
        $values['class'][] = !empty($source) ? 'superselectajax' : 'superselect';

        // Individuazione e gestione dei valori tramite array
        $values['value'] = explode(',', $values['value']);
        if (count($values['value']) === 1 && strlen($values['value'][0]) === 0) {
            $values['value'] = [];
        }

        // Se il valore presente non è valido, carica l'eventuale valore predefinito
        if (empty($values['value']) && !is_numeric($values['value']) && !empty($values['valore_predefinito'])) {
            $values['value'] = setting($values['valore_predefinito']);
        }

        // Cast del valore impostato in array
        $values['value'] = (array) $values['value'];

        // Inizializzazione del codice HTML
        $result = '
    <select |attr|>';

        // Delega della generazione del codice HTML in base alle caratteristiche del formato
        // Gestione delle richieste AJAX (se il campo "ajax-source" è impostato)
        if (!empty($source)) {
            // Impostazione della risorsa da consultare (AJAX)
            $values['data-source'] = $source;
            unset($values['ajax-source']);
            unset($values['select-source']);

            // Informazioni aggiuntive per il select
            $infos = isset($values['select-options']) ? $values['select-options'] : [];
            $values['data-select-options'] = json_encode($infos);
            unset($values['select-options']);

            if (!empty($values['value']) || is_numeric($values['value'])) {
                $result .= $this->select2($source, $values['value'], $infos);
            }
        } else {
            if (!in_array('multiple', $extras)) {
                $result .= '
            <option></option>';
            }

            // Gestione del select dal formato JSON completo, convertito in array
            if (is_array($values['values'])) {
                $result .= $this->selectArray($values['values'], $values['value']);
            }

            // Gestione del select da query specifica (se il campo "values" è impostato a "query=SQL")
            elseif (starts_with($values['values'], 'query=')) {
                $query = substr($values['values'], strlen('query='));

                $result .= $this->selectQuery($query, $values['value']);
            }

            // Gestione del select dal formato JSON parziale (valori singoli)
            elseif (starts_with($values['values'], 'list=')) {
                $list = substr($values['values'], strlen('list='));

                $result .= $this->selectList(json_decode('{'.$list.'}', true), $values);
            }
        }

        // Impostazione del placeholder
        $values['placeholder'] = !empty($values['placeholder']) ? $values['placeholder'] : tr("Seleziona un'opzione");
        $values['data-placeholder'] = isset($values['placeholder']) ? $values['placeholder'] : null;

        $values['data-maximum-selection-length'] = isset($values['maximum-selection-length']) ? $values['maximum-selection-length'] : null;

        unset($values['values']);

        $result .= '
	</select>';

        // Gestione delle proprietà "disabled" e "readonly"
        if (in_array('disabled', $extras) || in_array('readonly', $extras)) {
            $result .= '
	<script>$("#'.$values['id'].'").prop("disabled", true);</script>';
        }

        // Ulteriore gestione della proprietà "readonly" (per rendere il select utilizzabile dopo il submit)
        if (in_array('readonly', $extras) && empty($source)) {
            $result .= '
	<select class="hide" name="'.prepareToField($values['name']).'"'.((in_array('multiple', $extras)) ? ' multiple' : '').'>';

            foreach ($values['value'] as $value) {
                $result .= '
		<option value="'.prepareToField($value).'" selected></option>';
            }

            $result .= '
	</select>';
        }

        return $result;
    }

    /**
     * Gestione dell'input di tipo "select" con richieste AJAX (nome della richiesta indicato tramite attributo "ajax-source").
     * Esempio: {[ "type": "select", "label": "Select di test", "name": "test", "ajax-source": "test", "select-options": "id_test=1,test=si" ]}.
     *
     * @param string $op
     * @param array  $elements
     * @param array  $info
     *
     * @return string
     */
    protected function select2($op, $elements, $info)
    {
        // Richiamo del file dedicato alle richieste AJAX per ottenere il valore iniziale del select
        $response = AJAX::select($op, $elements, null, 0, 100, $info);

        $html = '';
        $results = $response['results'];
        foreach ($results as $element) {
            $element = (array) $element;
            if (isset($element['children'][0])) {
                $element = (array) $element['children'][0];
            }

            $attributes = [];
            if (in_array($element['id'], $elements)) {
                $attributes[] = 'selected';
            }

            if (!empty($element['_bgcolor_'])) {
                $attributes[] = 'style="background:'.$element['_bgcolor_'].'; color:'.color_inverse($element['_bgcolor_'].';"');
            }

            $exclude = ['id', 'text'];
            // Leggo ulteriori campi oltre a id e descrizione per inserirli nell'option nella forma "data-nomecampo1", "data-nomecampo2", ecc
            foreach ($element as $key => $value) {
                if (!in_array($key, $exclude)) {
                    $attributes[] = 'data-'.$key.'="'.prepareToField($value).'"';
                }
            }

            $html .= '
        <option value="'.prepareToField($element['id']).'" '.implode(' ', $attributes).(!empty($element['disabled']) ? 'disabled' : '').'>'.$element['text'].'</option>';
        }

        return $html;
    }

    /**
     * Gestione dell'input di tipo "select" basato su un array associativo.
     * Esempio: {[ "type": "select", "name": "tipo", "values": [{"id":"M","text":"Maschio"},{"id":"F","text":"Femmina"},{"id":"U","text":"Unisex"}], "value": "U", "placeholder": "Non specificato" ]}.
     *
     * @param array $array
     * @param array $values
     *
     * @return string
     */
    protected function selectArray($array, $values)
    {
        $result = '';

        $prev = '';
        foreach ($array as $element) {
            if (!empty($element['optgroup'])) {
                if ($prev != $element['optgroup']) {
                    $result .= '
        <optgroup label="'.prepareToField($element['optgroup']).'"></optgroup>';
                    $prev = $element['optgroup'];
                }
            }

            $element['text'] = empty($element['text']) ? $element['descrizione'] : $element['text'];

            $attributes = [];
            if (in_array($element['id'], $values)) {
                $attributes[] = 'selected';
            }

            if (!empty($element['_bgcolor_'])) {
                $attributes[] = 'style="background:'.$element['_bgcolor_'].'; color:'.color_inverse($element['_bgcolor_']).';"';
            }

            // Leggo ulteriori campi oltre a id e descrizione per inserirli nell'option nella forma "data-nomecampo1", "data-nomecampo2", ecc
            unset($element['optgroup']);
            $attributes[] = "data-select-attributes='".replace(json_encode($element), ["'" => "\'"])."'";

            $result .= '
        <option value="'.prepareToField($element['id']).'" '.implode(' ', $attributes).'>'.$element['text'].'</option>';
        }

        return $result;
    }

    /**
     * Gestione dell'input di tipo "select" basato su una query specifica.
     * Esempio: {[ "type": "select", "label": "Select di test", "name": "select", "values": "query=SELECT id, name as text FROM table" ]}.
     *
     * @param string $query
     * @param array  $values
     *
     * @throws \Exception
     *
     * @return string
     */
    protected function selectQuery($query, $values)
    {
        $database = database();

        $array = $database->fetchArray($query);

        return $this->selectArray($array, $values);
    }

    /**
     * Gestione dell'input di tipo "select" basato su una lista parzialmente JSON.
     * Esempio: {[ "type": "select", "label": "Sesso", "name": "sesso", "values": "list=\"\": \"Non specificato\", \"M\": \"Maschio\", \"F\": \"Femmina\", \"U\": \"Unisex\"", "value": "M" ]}.
     *
     * @param array $values
     * @param array $extras
     *
     * @return string
     */
    protected function selectList($datas, &$values)
    {
        $array = [];

        foreach ($datas as $key => $value) {
            if (!empty($key)) {
                $array[] = ['id' => $key, 'text' => $value];
            } elseif (empty($values['placeholder'])) {
                $values['placeholder'] = $value;
            }
        }

        return $this->selectArray($array, $values['value']);
    }
}
