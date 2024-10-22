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

// Aggiunta dell'ingranaggio all'unload della pagina
$(window).on("beforeunload", function () {
    $("#main_loading").show().find('img').show().removeClass('animation__shake').addClass('animation__shake');
});

// Fix multi-modal
$(document).on('hidden.bs.modal', '.modal', function () {
    $(this).remove();
    $('.modal:visible').length && $(document.body).addClass('modal-open');
});

$(document).ready(function () {
    // Standard per i popup
    toastr.options = {
        "closeButton": true,
        "debug": false,
        "newestOnTop": false,
        "progressBar": true,
        "positionClass": "toast-bottom-right",
        //"preventDuplicates": true,
        "onclick": null,
        "showDuration": "300",
        "hideDuration": "1000",
        "timeOut": "12000",
        "extendedTimeOut": "8000",
        "showEasing": "swing",
        "hideEasing": "linear",
        "showMethod": "fadeIn",
        "hideMethod": "fadeOut"
    };

    // Imposta lo standard per la conversione dei numeri
    if (numeral.locales['current_locale'] === undefined) {
        numeral.register('locale', 'current_locale', {
            delimiters: {
                thousands: globals.thousands,
                decimal: globals.decimals,
            },
            abbreviations: {
                thousand: 'k',
                million: 'm',
                billion: 'b',
                trillion: 't'
            },
            currency: {
                symbol: '€'
            }
        });
    }
    numeral.locale('current_locale');
    numeral.defaultFormat('0,0.' + ('0').repeat(globals.cifre_decimali));

    // Richiamo alla generazione di Datatables
    start_datatables( $('.main-records') );

    // Avvio datatables dei plugin solo al primo click
    $('.nav-tabs li').not('.clicked').on('click', function(){
        $(this).addClass('clicked');
        start_datatables( $(".tab-pane.active .main-records-plugins") );
    });

    // Calendario principale
    start_complete_calendar("#daterange", function (start, end) {
        // Esegue il submit del periodo selezionato e ricarica la pagina
        $.get(globals.rootdir + '/core.php?period_start=' + start.format('YYYY-MM-DD') + '&period_end=' + end.format('YYYY-MM-DD'), function (data) {
            location.reload();
        });
    });

    // Messaggi automatici di eliminazione
    $(document).on('click', '.ask', function () {
        message(this);
    });

    // Forza l'evento "blur" nei campi di testo per formattare i numeri con
    // jquery inputmask prima del submit
    setTimeout(function () {
        $('form').on('submit', function () {
            $('input').trigger('blur');
        });
    }, 1000);

    alignMaxHeight(".module-header .card");

    $("#main_loading").fadeOut()
});

/*
 * Hacky fix for a bug in select2 with jQuery 3.6.0's new nested-focus "protection"
 * see: https://github.com/select2/select2/issues/5993
 * see: https://github.com/jquery/jquery/issues/4382
 *
 * TODO: Recheck with the select2 GH issue and remove once this is fixed on their side
 */
$(document).on('select2:open', () => {
    document.querySelector('.select2-container--open .select2-search__field').focus();
});

//Send a WhatsApp message using JavaScript
function sendWhatsAppMessage(phoneNumber, message) {
    // Rimuove eventuali spazi bianchi dal numero di telefono
    phoneNumber = phoneNumber.replace(/\s/g, '');

    // Rimuove il simbolo "+" all'inizio del numero, se presente
    if (phoneNumber.startsWith('+')) {
        phoneNumber = phoneNumber.slice(1);
    }

    var text = message ? "&text=" + encodeURIComponent(message) : "";
    var url = "https://api.whatsapp.com/send?phone=" + phoneNumber + text;
    window.open(url);
}

function alignMaxHeight(element){
    max_height = 0;
    $(element).each( function(){
        if($(this).height() > max_height){
            max_height = $(this).height();
        }
    });
    $(element).height(max_height);
}
