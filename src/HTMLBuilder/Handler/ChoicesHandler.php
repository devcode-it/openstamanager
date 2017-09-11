<?php

namespace HTMLBuilder\Handler;

// Utilizzo della funzione prepareToField (PHP 5.6+)
// use function \HTMLBuilder\prepareToField;

/**
 * @since 2.3
 */
class ChoicesHandler implements HandlerInterface
{
    public function handle(&$values, &$extras)
    {
        $result = $this->{$values['type']}($values, $extras);

        return $result;
    }

    protected function checkbox(&$values, &$extras)
    {
        unset($values['class'][0]);

        $values['value'] = (empty($values['value']) || $values['value'] == 'off') ? false : true;

        if (!empty($values['value']) && !in_array('checked', $extras)) {
            $extras[] = 'checked';
        }

        if (in_array('readonly', $extras)) {
            $extras[] = 'disabled';
        }

        $values['placeholder'] = (isset($values['placeholder'])) ? $values['placeholder'] : $values['label'];

        $result .= '
    <div class="input-group">
        <span class="input-group-addon">
            <input |attr| onchange="$(this).parent().find(\'[type=hidden]\').val( + this.checked)">
            <input type="hidden" name="|name|" value="|value|">
        </span>
        <input type="text" class="form-control" placeholder="|placeholder|" disabled>
    </div>';

        return $result;
    }

    protected function bootswitch(&$values, &$extras)
    {
        unset($values['class'][0]);

        $values['class'][] = 'bootstrap-switch';

        $values['value'] = (empty($values['value']) || $values['value'] == 'off') ? false : true;

        if (!empty($values['value']) && !in_array('checked', $extras)) {
            $extras[] = 'checked';
        }

        return '
    <div class="input-group">
        <input type="checkbox" |attr|>
        <input type="hidden" name="checkbox['.\HTMLBuilder\prepareToField($values['name']).']" value="'.\HTMLBuilder\prepareToField($values['value']).'">
    </div>';
    }

    protected function radio(&$values, &$extras)
    {
        $result = '';

        $originalExtras = $extras;

        $radios = json_decode('{'.$values['values'].'}', true);

        $values['value'] = !array_key_exists($values['value'], $radios) ? array_keys($radios)[0] : $values['value'];

        foreach ($radios as $key => $value) {
            $checked = false;
            if ($key === $values['value']) {
                $checked = true;
            }

            $result .= '
        <input type="radio" class="bootstrap-switch" name="'.\HTMLBuilder\prepareToField($values['name']).'" id="'.\HTMLBuilder\prepareToField($values['id'].'_'.$key).'" value="'.\HTMLBuilder\prepareToField($key).'" data-label-text="'.\HTMLBuilder\prepareToField($value).'"'.($checked ? ' checked' : '').'>';
        }

        return $result;
    }
}
