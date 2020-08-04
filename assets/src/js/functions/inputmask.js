// Inputmask
function start_inputmask(element) {
    if (element == undefined) {
        element = '';
    } else {
        element = element + ' ';
    }

    var date = dateFormatMoment(globals.date_format).toLowerCase();

    $(element + ".date-mask").not('.bound').inputmask(date, {
        placeholder: date
    }).addClass('bound');

    $(element + '.email-mask').not('.bound').inputmask('Regex', {
        regex: "^[a-zA-Z0-9_!#$%&'*+/=?`{|}~^-]+(?:\\.[a-zA-Z0-9_!#$%&'*+/=?`{|}~^-]+)*@[a-zA-Z0-9-]+(?:\\.[a-zA-Z0-9-]+)*$",
    }).addClass('bound');

    $(element + '.rea-mask').not('.bound').inputmask( {
        mask: "AA-999999{1,15}",
        casing: "upper",
    }).addClass('bound');

    $(element + '.provincia-mask').not('.bound').inputmask( {
        mask: "AA",
        casing: "upper",
    }).addClass('bound');

    $(element + '.alphanumeric-mask').not('.bound').inputmask('Regex', {
        regex: "[A-Za-z0-9#_|\/\\-.]*",
    }).addClass('bound');

    $(element + '.math-mask').not('.bound').inputmask('Regex', {
        regex: "[0-9,.+\-]*",
    }).addClass('bound');

    if (globals.is_mobile) {
        $(element + '.inputmask-decimal, ' + element + '.date-mask, ' + element + '.timestamp-mask').each(function () {
            $(this).attr('type', 'tel');
        }).addClass('bound');
    } else {
        $(element + '.inputmask-decimal').not('.bound').each(function () {
            var $this = $(this);

            var min = $this.attr('min-value');
            if (min == 'undefined') {
                min = false;
            }

            var max = $this.attr('max-value');
            if (max == 'undefined') {
                max = false;
            }

            $this.inputmask("decimal", {
                min: min ? min : undefined,
                allowMinus: !min || min < 0 ? true : false,
                max: max ? max : undefined,
                allowPlus: !max || max < 0 ? true : false,
                digits: $this.attr('decimals') ? $this.attr('decimals') : globals.cifre_decimali,
                digitsOptional: true, // Necessario per un problema di inputmask con i numeri negativi durante l'init
                enforceDigitsOnBlur: true,
                rightAlign: true,
                autoGroup: true,
                radixPoint: globals.decimals,
                groupSeparator: globals.thousands,
                onUnMask: function (maskedValue, unmaskedValue) {
                    return maskedValue.toEnglish();
                },
            });

            $this.on('keyup', function () {
                if (min && $(this).val().toEnglish() < min) {
                    $(this).val(min);
                }
            });
        }).addClass('bound');
    }
}
