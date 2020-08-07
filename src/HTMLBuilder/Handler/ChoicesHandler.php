<?php

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

        // Generazione del codice HTML
        // "+ this.checked" rende il valore booleano un numero
        $result = '
        <div class="form-group">
            <input type="hidden" name="|name|" value="|value|">
            <input type="checkbox" id="|id|" value="|value|" autocomplete="off" class="hidden" |attr| onchange="$(this).parent().find(\'[type = hidden]\').val(+this.checked)"/>
            <div class="btn-group checkbox-buttons">
                <label for="|id|" class="btn btn-default'.$class.'">
                    <span class="fa fa-check text-success"></span>
                    <span class="fa fa-close text-danger"></span>
                </label>
                <label for="|id|" class="btn btn-default active'.$class.'">
                    <span class="text-success">'.tr('Attivato').'</span>
                    <span class="text-danger">'.tr('Disattivato').'</span>
                </label>
            </div>
        </div>';

        return $result;
    }
}
