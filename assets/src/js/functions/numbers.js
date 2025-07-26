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
 * Funzione per inizializzare i campi di input numerici per la gestione integrata del formato impostato per il gestionale.
 *
 * @deprecated
 */
function initNumbers() {
    $('.number-input').each(function () {
        input(this);
    });
}

/**
 * Funzione per l'inizializzazione dei campi numerici.
 * @param input
 */
function initNumberInput(input) {
    let $input = $(input);
    if (AutoNumeric.isManagedByAutoNumeric(input)) {
        return true;
    }

    let min = $input.attr('min-value') && $input.attr('min-value') !== "undefined" ? $input.attr('min-value') : null;
    let max = $input.attr('max-value') && $input.attr('max-value') !== "undefined" ? $input.attr('max-value') : null;

    let decimals = $input.attr('decimals') ? $input.attr('decimals') : globals.cifre_decimali;

    let autonumeric = new AutoNumeric(input, {
        caretPositionOnFocus: "decimalLeft",
        allowDecimalPadding: true,
        currencySymbolPlacement: "s",
        negativePositiveSignPlacement: "p",
        decimalCharacter: globals.decimals,
        decimalCharacterAlternative: ".",
        digitGroupSeparator: globals.thousands,
        emptyInputBehavior: min ? min : "zero",
        overrideMinMaxLimits: "ignore",
        modifyValueOnWheel: false,
        outputFormat: "string",
        unformatOnSubmit: true,
        watchExternalChanges: true,
        minimumValue: min ? min : "-10000000000000",
        maximumValue: max ? max : "10000000000000",
        decimalPlaces: decimals,
    });

    $input.data("autonumeric", autonumeric);

    return true;
}
