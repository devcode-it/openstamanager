<?php

namespace HTMLBuilder\Handler;

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
}
