<?php

namespace HTMLBuilder\Handler;

/**
 * Gestione dell'input di tipo "select".
 *
 * @since 2.3
 */
class SelectHandler implements HandlerInterface
{
    public function handle(&$values, &$extras)
    {
        // Individuazione della classe per la corretta gestione JavaScript
        $values['class'][] = !empty($values['ajax-source']) ? 'superselectajax' : 'superselect';

        // Individuazione della richiesta AJAX (se presente)
        $values['data-source'] = !empty($values['ajax-source']) ? $values['ajax-source'] : '';

        // Individuazione e gestione dei valori tramite array
        $values['value'] = explode(',', $values['value']);
        if (count($values['value']) === 1 && strlen($values['value'][0]) === 0) {
            $values['value'] = [];
        }

        // Se il valore presente non è valido, carica l'eventuale valore predefinito
        if (empty($values['value']) && !is_numeric($values['value']) && !empty($values['valore_predefinito'])) {
            $values['value'] = setting($values['valore_predefinito']);
        }

        $values['value'] = (array) $values['value'];

        // Inizializzazione del codice HTML
        $result = '
    <select |attr|>';

        // Delega della generazione del codice HTML in base alle caratteristiche del formato
        // Gestione delle richieste AJAX (se il campo "ajax-source" è impostato)
        if (!empty($values['ajax-source'])) {
            if (!empty($values['value']) || is_numeric($values['value'])) {
                $result .= $this->select2($values['ajax-source'], $values['value']);
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
            elseif (preg_match_all('/^query=(.+?)$/', $values['values'], $matches)) {
                $result .= $this->selectQuery($matches[1][0], $values['value']);
            }

            // Gestione del select dal formato JSON parziale (valori singoli)
            elseif (preg_match_all('/^list=(.+?)$/', $values['values'], $matches)) {
                $result .= $this->selectList(json_decode('{'.$matches[1][0].'}', true), $values);
            }
        }

        // Impostazione del placeholder
        $values['placeholder'] = !empty($values['placeholder']) ? $values['placeholder'] : '- '.tr("Seleziona un'opzione").' -';
        $values['data-placeholder'] = $values['placeholder'];

        unset($values['values']);

        $result .= '
	</select>';

        // Gestione delle proprietà "disabled" e "readonly"
        if (in_array('disabled', $extras) || in_array('readonly', $extras)) {
            $result .= '
	<script>$("#'.$values['id'].'").prop("disabled", true);</script>';
        }

        // Ulteriore gestione della proprietà "readonly" (per rendere il select utilizzabile dopo il submit)
        if (in_array('readonly', $extras) && empty($values['ajax-source'])) {
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
     * Esempio: {[ "type": "select", "label": "Select di test", "name": "test", "ajax-source": "test" ]}.
     *
     * @param array $values
     * @param array $extras
     *
     * @return string
     */
    protected function select2($op, $elements)
    {
        // Richiamo del file dedicato alle richieste AJAX per ottenere il valore iniziale del select
        ob_start();
        $dbo = database();
        include DOCROOT.'/ajax_select.php';
        $text = ob_get_clean();

        $result = '';

        $array = (array) json_decode($text, true);
        foreach ($array as $element) {
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

            $result .= '
        <option value="'.prepareToField($element['id']).'" '.implode(' ', $attributes).'>'.$element['text'].'</option>';
        }

        return $result;
    }

    /**
     * Gestione dell'input di tipo "select" basato su un array associativo.
     * Esempio: {[ "type": "select", "name": "tipo", "values": [{"id":"M","text":"Maschio"},{"id":"F","text":"Femmina"},{"id":"U","text":"Unisex"}], "value": "U", "placeholder": "Non specificato" ]}.
     *
     * @param array $values
     * @param array $extras
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

            $exclude = [
                'optgroup',
            ];
            // Leggo ulteriori campi oltre a id e descrizione per inserirli nell'option nella forma "data-nomecampo1", "data-nomecampo2", ecc
            foreach ($element as $key => $value) {
                if (!in_array($key, $exclude)) {
                    $attributes[] = 'data-'.$key.'="'.prepareToField($value).'"';
                }
            }

            $result .= '
        <option value="'.prepareToField($element['id']).'" '.implode(' ', $attributes).'>'.$element['text'].'</option>';
        }

        return $result;
    }

    /**
     * Gestione dell'input di tipo "select" basato su una query specifica.
     * Esempio: {[ "type": "select", "label": "Select di test", "name": "select", "values": "query=SELECT id, name as text FROM table" ]}.
     *
     * @param array $values
     * @param array $extras
     *
     * @return string
     */
    protected function selectQuery($query, $values)
    {
        $result = '';

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
