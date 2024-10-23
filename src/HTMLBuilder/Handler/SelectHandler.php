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
        $values['data-select2-id'][] = $values['id'].'_'.random_int(0, 999);

        $source = $values['ajax-source'] ?? $values['select-source'] ?? null;

        // Individuazione della classe per la corretta gestione JavaScript
        $values['class'][] = !empty($source) ? 'superselectajax' : 'superselect';

        // Individuazione e gestione dei valori tramite array
        if (in_array('multiple', $extras)) {
            $values['value'] = explode(',', (string) $values['value']);
        } else {
            $values['value'] = (array) $values['value'];
        }

        if (count($values['value']) === 1 && strlen((string) $values['value'][0]) === 0) {
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
    <select autocomplete="off" |attr|>';

        // Delega della generazione del codice HTML in base alle caratteristiche del formato
        // Gestione delle richieste AJAX (se il campo "ajax-source" è impostato)
        if (!empty($source)) {
            // Impostazione della risorsa da consultare (AJAX)
            $values['data-source'] = $source;
            unset($values['ajax-source']);
            unset($values['select-source']);

            // Informazioni aggiuntive per il select
            $infos = $values['select-options'] ?? [];
            $values['data-select-options'] = json_encode($infos);
            unset($values['select-options']);

            if (!empty($values['value']) || is_numeric($values['value'])) {
                $result .= $this->select2($source, $values['value'], $infos, $values['link']);
            }
        } else {
            if (!in_array('multiple', $extras)) {
                $result .= '
            <option></option>';
            }

            // Gestione del select dal formato JSON completo, convertito in array
            if (is_array($values['values'])) {
                $result .= $this->selectArray($values['values'], $values['value'], $values['link']);
            }

            // Gestione del select da query specifica (se il campo "values" è impostato a "query=SQL")
            elseif (string_starts_with($values['values'], 'query=')) {
                $query = substr((string) $values['values'], strlen('query='));

                $result .= $this->selectQuery($query, $values['value'], $values['link']);
            }

            // Gestione del select dal formato JSON parziale (valori singoli)
            elseif (string_starts_with($values['values'], 'list=')) {
                $list = substr((string) $values['values'], strlen('list='));

                $result .= $this->selectList(json_decode('{'.$list.'}', true), $values, $values['link']);
            }
        }

        // Impostazione del placeholder
        $values['placeholder'] = !empty($values['placeholder']) ? $values['placeholder'] : tr("Seleziona un'opzione");
        $values['data-placeholder'] = $values['placeholder'] ?? null;

        $values['data-maximum-selection-length'] = $values['maximum-selection-length'] ?? null;

        unset($values['values']);

        $result .= '
	</select>';

        // Rimozione proprietà "readonly" in favore di "disabled"
        $pos = array_search('readonly', $extras);
        if ($pos !== false) {
            $extras[$pos] = 'disabled';
            $extras = array_unique($extras);
        }

        // Gestione delle proprietà "disabled"
        if (in_array('disabled', $extras)) {
            $result .= '
	<script>$("#'.$values['id'].'").prop("disabled", true);</script>';
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
    protected function select2($op, $elements, $info, $link = null)
    {
        // Richiamo del file dedicato alle richieste AJAX per ottenere il valore iniziale del select
        $response = \AJAX::select($op, $elements, null, 0, 100, $info);

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

            if ($link == 'stampa') {
                $element['title'] = ' ';
                $element['text'] = '<a href="'.\Prints::getHref($element['id'], get('id_record')).'" class="text-black" target="_blank">'.$element['text'].' <i class="fa fa-external-link"></i></a>';
            } elseif ($link == 'allegato') {
                $element['title'] = ' ';
                $element['text'] = '<a href="'.base_path().'/view.php?file_id='.$element['id'].'" class="text-black" target="_blank">'.$element['text'].' <i class="fa fa-external-link"></i></a>';
            }

            if (!empty($element['_bgcolor_'])) {
                $attributes[] = 'style="background:'.$element['_bgcolor_'].'; color:'.color_inverse($element['_bgcolor_'].';"');
            }

            // Leggo ulteriori campi oltre a id e descrizione per inserirli nell'option nella forma "data-nomecampo1", "data-nomecampo2", ecc
            unset($element['optgroup']);
            $attributes[] = "data-select-attributes='".htmlspecialchars(json_encode($element), ENT_QUOTES)."'";
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
    protected function selectArray($array, $values, $link = null)
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

            $element['text'] = ($element['text'] == '' || $element['text'] == null) ? $element['descrizione'] : $element['text'];

            if ($link == 'stampa') {
                $element['title'] = ' ';
                $element['text'] = '<a href="'.\Prints::getHref($element['id'], get('id_record')).'" class="text-black" target="_blank">'.$element['text'].' <i class="fa fa-external-link"></i></a>';
            } elseif ($link == 'allegato') {
                $element['title'] = ' ';
                $element['text'] = '<a href="'.base_path().'/view.php?file_id='.$element['id'].'" class="text-black" target="_blank">'.$element['text'].' <i class="fa fa-external-link"></i></a>';
            }

            $attributes = [];
            if (in_array($element['id'], $values)) {
                $attributes[] = 'selected';
            }

            if (!empty($element['_bgcolor_'])) {
                $attributes[] = 'style="background:'.$element['_bgcolor_'].'; color:'.color_inverse($element['_bgcolor_']).';"';
            }

            // Leggo ulteriori campi oltre a id e descrizione per inserirli nell'option nella forma "data-nomecampo1", "data-nomecampo2", ecc
            unset($element['optgroup']);
            $attributes[] = "data-select-attributes='".htmlspecialchars(json_encode($element), ENT_QUOTES)."'";
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
    protected function selectQuery($query, $values, $link = null)
    {
        $database = database();

        $array = $database->fetchArray($query);

        return $this->selectArray($array, $values, $link);
    }

    /**
     * Gestione dell'input di tipo "select" basato su una lista parzialmente JSON.
     * Esempio: {[ "type": "select", "label": "Sesso", "name": "sesso", "values": "list=\"\": \"Non specificato\", \"M\": \"Maschio\", \"F\": \"Femmina\", \"U\": \"Unisex\"", "value": "M" ]}.
     *
     * @param array $values
     *
     * @return string
     */
    protected function selectList($datas, &$values, $link = null)
    {
        $array = [];

        foreach ($datas as $key => $value) {
            if (!empty($key)) {
                $array[] = ['id' => $key, 'text' => $value];
            } elseif (empty($values['placeholder'])) {
                $values['placeholder'] = $value;
            }
        }

        return $this->selectArray($array, $values['value'], $link);
    }
}
