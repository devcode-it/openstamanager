<?php

namespace HTMLBuilder\Handler;

/**
 * @since 2.3
 */
class SelectHandler implements HandlerInterface
{
    public function handle(&$values, &$extras)
    {
        $values['class'][] = (!empty($values['ajax-source'])) ? 'superselectajax' : 'superselect';

        $values['data-source'] = (!empty($values['ajax-source'])) ? $values['ajax-source'] : '';

        $values['value'] = explode(',', $values['value']);
        if (count($values['value']) === 1 && strlen($values['value'][0]) === 0) {
            $values['value'] = [];
        }

        // Se il valore presente non è valido, carica l'eventuale valore predefinito
        if (empty($values['value']) && !is_numeric($values['value']) && !empty($values['valore_predefinito'])) {
            $values['value'] = get_var($values['valore_predefinito']);
        }

        $values['value'] = (array) $values['value'];

        $result = '
    <select |attr|>';

        if (!empty($values['ajax-source'])) {
            if (!empty($values['value']) || is_numeric($values['value'])) {
                $result .= $this->select2($values['ajax-source'], $values['value']);
            }
        }

        // Generazione <select> da query
        elseif (preg_match_all('/^query=(.+?)$/', $values['values'], $matches)) {
            $result .= '
        <option></option>';

            $result .= $this->selectQuery($matches[1][0], $values['value']);
        }

        // Generazione <select> da JSON
        // esempio creazione select con opzioni: Maschio, Femmina, Unisex
        // {[ "type": "select", "label": "Sesso", "name": "sesso", "values": "list=\"\": \"\", \"M\": \"Maschio\", \"F\": \"Femmina\", \"U\": \"Unisex\"", "value": "$sesso$" ]}
        elseif (preg_match_all('/^list=(.+?)$/', $values['values'], $matches)) {
            $result .= '
        <option></option>';

            $result .= $this->selectList(json_decode('{'.$matches[1][0].'}', true), $values);
        } elseif (preg_match_all('/^json=(.+?)$/', $values['values'], $matches)) {
            $result .= '
        <option></option>';

            $result .= $this->selectJSON(json_decode('[{'.$matches[1][0].'}]', true), $values['value']);
        }

        $values['placeholder'] = !empty($values['placeholder']) ? $values['placeholder'] : '- '.tr("Seleziona un'opzione").' -';
        $values['data-placeholder'] = $values['placeholder'];

        unset($values['values']);

        $result .= '
	</select>';

        if (in_array('disabled', $extras) || in_array('readonly', $extras)) {
            $result .= '
	<script>$("#'.$values['id'].'").prop("disabled", true);</script>';
        }

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

    protected function select2($op, $elements)
    {
        // Richiamo alla pagina ajax_select.php per aggiungere il valore iniziale al select
        ob_start();
        $dbo = \Database::getConnection();
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

    protected function selectJSON($array, $values)
    {
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

            $exclude = ['optgroup'];
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

    protected function selectQuery($query, $values)
    {
        $result = '';

        $database = \Database::getConnection();

        $array = $database->fetchArray($query);

        return $this->selectJSON($array, $values);
    }

    protected function selectList($datas, &$values)
    {
        $result = '';

        foreach ($datas as $key => $value) {
            if (!empty($key)) {
                $attributes = [];
                if (in_array($key, $values['value'])) {
                    $attributes[] = 'selected';
                }

                $result .= '
            <option value="'.prepareToField($key).'" '.implode(' ', $attributes).'>'.$value.'</option>';
            } elseif (empty($values['placeholder'])) {
                $values['placeholder'] = $value;
            }
        }

        return $result;
    }
}
