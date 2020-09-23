function initNumbers() {
    let inputs = $('.decimal-number').not('.bound');

    for (const input of inputs) {
        let $input = $(input);

        if (AutoNumeric.isManagedByAutoNumeric(input)) {
            continue;
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

        $input.data("autonumeric", autonumeric)
            .addClass('bound');
    }
}
