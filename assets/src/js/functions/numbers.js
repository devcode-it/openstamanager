function initNumbers() {
    $('.decimal-number').not('.bound').each(function () {
        let $this = $(this);

        let min = $this.attr('min-value') && $this.attr('min-value') !== "undefined" ? $this.attr('min-value') : null;
        let max = $this.attr('max-value') && $this.attr('max-value') !== "undefined" ? $this.attr('max-value') : null;

        let decimals = $this.attr('decimals') ? $this.attr('decimals') : globals.cifre_decimali;

        let autonumeric = new AutoNumeric(this, {
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

        $this.data("autonumeric", autonumeric);
    }).addClass('bound');
}
