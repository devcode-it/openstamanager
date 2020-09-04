function initNumbers() {
    $('.decimal-number').not('.bound').each(function () {
        let $this = $(this);

        let min = $this.attr('min-value') && $this.attr('min-value') !== "undefined" ? $this.attr('min-value') : "-10000000000000";
        let max = $this.attr('max-value') && $this.attr('max-value') !== "undefined" ? $this.attr('max-value') : "10000000000000";

        let decimals = $this.attr('decimals') ? $this.attr('decimals') : globals.cifre_decimali;

        let autonumeric = new AutoNumeric(this, {
            caretPositionOnFocus: "decimalLeft",
            allowDecimalPadding: true,
            currencySymbolPlacement: "s",
            negativePositiveSignPlacement: "p",
            decimalCharacter: globals.decimals,
            decimalCharacterAlternative: ".",
            digitGroupSeparator: globals.thousands,
            emptyInputBehavior: "zero",
            modifyValueOnWheel: false,
            outputFormat: "string",
            unformatOnSubmit: true,
            watchExternalChanges: true,
            minimumValue: min,
            maximumValue: max,
            decimalPlaces: decimals,
        });

        $this.data("autonumeric", autonumeric);
    }).addClass('bound');
}
