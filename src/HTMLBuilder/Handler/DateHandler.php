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

/**
 * Gestione dell'input di tipo "timestamp", "date" e "time".
 *
 * @since 2.3
 */
class DateHandler implements HandlerInterface
{
    public function handle(&$values, &$extras)
    {
        // Impostazione alla data corrente se il contenuto corrisponde a "now"
        $detect = [
            'value',
            'max-date',
            'min-date',
        ];
        foreach ($detect as $attr) {
            if (isset($values[$attr]) && $values[$attr] == '-now-') {
                $values[$attr] = date(\Intl\Formatter::getStandardFormats()['timestamp']);
            }
        }

        // Restrizione dei valori permessi
        // Timestamp
        if ($values['type'] == 'timestamp' && formatter()->isStandardTimestamp($values['value'])) {
            $values['value'] = \Translator::timestampToLocale($values['value']);
        }

        // Data
        elseif ($values['type'] == 'date' && formatter()->isStandardDate($values['value'])) {
            $values['value'] = \Translator::dateToLocale($values['value']);
        }

        // Orario
        elseif ($values['type'] == 'time' && formatter()->isStandardTime($values['value'])) {
            $values['value'] = \Translator::timeToLocale($values['value']);
        }

        // Controllo sulla correttezza sintattica del valore impostato
        if (!(
            ($values['type'] == 'timestamp' && formatter()->isFormattedTimestamp($values['value'])) ||
            ($values['type'] == 'date' && formatter()->isFormattedDate($values['value'])) ||
            ($values['type'] == 'time' && formatter()->isFormattedTime($values['value']))
        )) {
            $values['value'] = '';
        }

        // Delega della gestione al metodo specifico per il tipo di input richiesto
        $result = $this->{$values['type']}($values, $extras);

        $values['type'] = 'text';

        // Generazione del codice HTML di default
        if (empty($result)) {
            if (empty($values['label'])) {
                $result = '
<div class="form-group">';

                if (empty($values['icon-before']) || empty($values['icon-after'])) {
                    $result .= '
    <div class="input-group" style="width: 100%;">';
                }
            }

            $result .= '
    <input |attr| autocomplete="off">';

            if (empty($values['label'])) {
                if (empty($values['icon-before']) || empty($values['icon-after'])) {
                    $result .= '
    </div>';
                }

                $result .= '
</div>';
            }
        }

        return $result;
    }

    /**
     * Gestione dell'input di tipo "timestamp".
     * Esempio: {[ "type": "timestamp", "label": "Timestamp di test", "name": "timestamp", "value": "2018-01-01 12:00:30" ]}.
     *
     * @param array $values
     * @param array $extras
     *
     * @return string
     */
    protected function timestamp(&$values, &$extras)
    {
        $values['class'][] = 'text-center';
        $values['class'][] = 'timestamp-picker';
        $values['class'][] = 'timestamp-mask';
    }

    /**
     * Gestione dell'input di tipo "date".
     * Esempio: {[ "type": "date", "label": "Data di test", "name": "date", "value": "2018-01-01" ]}.
     *
     * @param array $values
     * @param array $extras
     *
     * @return string
     */
    protected function date(&$values, &$extras)
    {
        $values['class'][] = 'text-center';
        $values['class'][] = 'datepicker';
        $values['class'][] = 'date-mask';
    }

    /**
     * Gestione dell'input di tipo "time".
     * Esempio: {[ "type": "time", "label": "Orario di test", "name": "time", "value": "12:00:30" ]}.
     *
     * @param array $values
     * @param array $extras
     *
     * @return string
     */
    protected function time(&$values, &$extras)
    {
        $values['class'][] = 'text-center';
        $values['class'][] = 'timepicker';
    }
}
