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

/**
 * Funzione per l'inizializzazione delle maschere sui campi impostati.
 * @param input
 */
function initMaskInput(input) {
    let $input = $(input);

    if ($input.hasClass('email-mask')) {
        $input.inputmask('Regex', {
            regex: "^[a-zA-Z0-9_!#$%&'*+/=?`{|}~^-]+(?:\\.[a-zA-Z0-9_!#$%&'*+/=?`{|}~^-]+)*@[a-zA-Z0-9-]+(?:\\.[a-zA-Z0-9-]+)*$",
        });
    } else if ($input.hasClass('rea-mask')) {
        $input.inputmask({
            mask: "AA-999999{1,15}",
            casing: "upper",
        });
    } else if ($input.hasClass('provincia-mask')) {
        $input.inputmask({
            mask: "AA",
            casing: "upper",
        });
    } else if ($input.hasClass('alphanumeric-mask')) {
        $input.inputmask('Regex', {
            regex: "[A-Za-z0-9#_|\/\\-.]*",
        });
    } else if ($input.hasClass('math-mask')) {
        $input.inputmask('Regex', {
            regex: "[0-9,.+\-]*",
        });
    }

    return true;
}

/**
 * Inputmask.
 *
 * @param element
 */
function start_inputmask(element) {
    if (element === undefined) {
        element = '';
    } else {
        element = element + ' ';
    }

    let masks = ['math-mask', 'alphanumeric-mask', 'provincia-mask', 'rea-mask', 'email-mask'];

    let selector = element + '.' + masks.join(', ' + element + '.')
    $(selector).each(function () {
        input(this);
    });
}
