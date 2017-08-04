<?php

namespace HTMLBuilder\Handler;

/**
 * @since 2.3
 */
class DateHandler implements HandlerInterface
{
    public function handle(&$values, &$extras)
    {
        // Impostazione alla data corrente se il contenuto corrisponde a "now"
        if ($values['value'] == '-now-') {
            $values['value'] = date(\Translator::getEnglishFormatter()->getTimestampPattern());
        }

        if ($values['max-date'] == '-now-') {
            $values['max-date'] = date(\Translator::getEnglishFormatter()->getTimestampPattern());
        }

        if ($values['min-date'] == '-now-') {
            $values['min-date'] = date(\Translator::getEnglishFormatter()->getTimestampPattern());
        }

        if (\Translator::getEnglishFormatter()->isTimestamp($values['value']) && $values['type'] == 'timestamp') {
            $values['value'] = \Translator::timestampToLocale($values['value']);
        } elseif (\Translator::getEnglishFormatter()->isDate($values['value']) && $values['type'] == 'date') {
            $values['value'] = \Translator::dateToLocale($values['value']);
        } elseif (\Translator::getEnglishFormatter()->isTime($values['value']) && $values['type'] == 'time') {
            $values['value'] = \Translator::timeToLocale($values['value']);
        }

        $resetValues = [
            \Translator::timestampToLocale('0000-00-00 00:00:00'),
            \Translator::dateToLocale('0000-00-00'),
            \Translator::timeToLocale('00:00:00'),
        ];

        $values['value'] = in_array($values['value'], $resetValues) ? '' : $values['value'];

        $result = $this->{$values['type']}($values, $extras);

        $values['type'] = 'text';

        if (empty($result)) {
            $result = '
    <input |attr|>';
        }

        return $result;
    }

    protected function timestamp(&$values, &$extras)
    {
        $values['class'][] = 'text-center';
        $values['class'][] = 'timestamp-picker';

        $values['value'] = (\Translator::getLocaleFormatter()->isTimestamp($values['value'])) ? $values['value'] : '';
    }

    protected function date(&$values, &$extras)
    {
        $values['class'][] = 'text-center';
        $values['class'][] = 'datepicker';
        $values['class'][] = 'date-mask';

        $values['value'] = (\Translator::getLocaleFormatter()->isDate($values['value'])) ? $values['value'] : '';
    }

    protected function time(&$values, &$extras)
    {
        $values['class'][] = 'text-center';
        $values['class'][] = 'timepicker';

        $values['value'] = (\Translator::getLocaleFormatter()->isTime($values['value'])) ? $values['value'] : '';
    }
}
