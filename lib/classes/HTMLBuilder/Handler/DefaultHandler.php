<?php

namespace HTMLBuilder\Handler;

/**
 * @since 2.3
 */
class DefaultHandler implements HandlerInterface
{
    public function handle(&$values, &$extras)
    {
        if (in_array($values['type'], get_class_methods($this))) {
            $result = $this->{$values['type']}($values, $extras);
        } else {
            $result = $this->custom($values, $extras);
        }

        return $result;
    }

    protected function text(&$values, &$extras)
    {
        return '
    <input |attr|>';
    }

    protected function file(&$values, &$extras)
    {
        return $this->text($values, $extras);
    }

    protected function password(&$values, &$extras)
    {
        return $this->text($values, $extras);
    }

    protected function hidden(&$values, &$extras)
    {
        $original = $values;

        $values = [];
        $values['type'] = $original['type'];
        $values['value'] = $original['value'];
        $values['name'] = $original['name'];
        $values['class'] = [];

        return $this->text($values, $extras);
    }

    protected function email(&$values, &$extras)
    {
        $values['class'][] = 'email-mask';

        $values['type'] = 'text';

        return $this->text($values, $extras);
    }

    protected function number(&$values, &$extras)
    {
        $values['class'][] = 'inputmask-decimal';

        $values['value'] = !empty($values['value']) ? $values['value'] : 0;

        $decimals = true;
        if (isset($values['decimals'])) {
            if (is_numeric($values['decimals'])) {
                $decimals = $values['decimals'];
            } elseif (starts_with($values['decimals'], 'qta')) {
                $parts = explode('|', $values['decimals']);
                $values['min-value'] = isset($parts[1]) ? $parts[1] : 1;

                $decimals = \Settings::get('Cifre decimali per quantità');
                $values['decimals'] = $decimals;
            }
        }

        $values['value'] = (\Translator::getEnglishFormatter()->isNumber($values['value'])) ? \Translator::numberToLocale($values['value'], $decimals) : $values['value'];

        $values['type'] = 'text';

        return $this->text($values, $extras);
    }

    protected function custom(&$values, &$extras)
    {
        return '
    <span |attr|>|value|</span>';
    }

    protected function textarea(&$values, &$extras)
    {
        $values['class'][] = 'autosize';

        return '
    <textarea |attr|>|value|</textarea>';
    }
}
