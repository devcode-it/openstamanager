/**
 * Funzione per standardizzare l'accorciamento del testo
 * Utilizza readmore.js come libreria standard per tutto il progetto
 */

/**
 * Inizializza readmore.js su elementi con classe .shorten
 * Mantiene la compatibilità con il vecchio sistema jquery.shorten
 */
function initTextShortener() {
    // Converti elementi che usano jquery.shorten a readmore.js
    $('.shorten').each(function() {
        // Verifica se l'elemento è già stato inizializzato
        if ($(this).data('readmore')) {
            return;
        }

        // Ottieni eventuali opzioni personalizzate dall'elemento
        const showChars = $(this).data('show-chars') || 70;

        // Inizializza readmore.js con opzioni compatibili
        $(this).readmore({
            collapsedHeight: 0,  // Collassa completamente
            heightMargin: 0,     // Nessun margine
            moreLink: '<a href="#">' + (globals.translations.readmore || 'Mostra tutto') + '</a>',
            lessLink: '<a href="#">' + (globals.translations.readless || 'Comprimi') + '</a>',
            beforeToggle: function(trigger, element, expanded) {
                // Compatibilità con jquery.shorten
                if (!expanded) {
                    // Limita il testo visibile
                    const text = $(element).text();
                    if (text.length > showChars) {
                        const visibleText = text.substr(0, showChars);
                        const hiddenText = text.substr(showChars, text.length - showChars);

                        // Ricostruisci il contenuto usando nodi di testo per evitare XSS
                        $(element).empty();

                        const $shortSpan = $('<span class="shortcontent"></span>');
                        $shortSpan.text(visibleText + '...');

                        const $allSpan = $('<span class="allcontent"></span>');
                        $allSpan.text(text);

                        $(element).append($shortSpan).append($allSpan);

                        $(element).find('.allcontent').hide();
                    }
                } else {
                    // Mostra tutto il testo
                    const originalText = $(element).find('.allcontent').text();
                    if (originalText) {
                        $(element).text(originalText);
                    }
                }
            }
        });
    });

    // Inizializza readmore.js su elementi con classe .readmore
    $('.readmore').each(function() {
        // Verifica se l'elemento è già stato inizializzato
        if ($(this).data('readmore')) {
            return;
        }

        const height = $(this).data('height') ? parseInt($(this).data('height')) : 50;

        $(this).readmore({
            collapsedHeight: height,
            moreLink: '<a href="#">' + (globals.translations.readmore || 'Leggi tutto') + '</a>',
            lessLink: '<a href="#">' + (globals.translations.readless || 'Chiudi') + '</a>',
            beforeToggle: function() {
                setTimeout('alignMaxHeight(".module-header .card");', 300);
            }
        });
    });
}

/**
 * Reinizializza readmore.js su elementi specifici
 * Utile quando il contenuto viene caricato dinamicamente
 * @param {string} selector - Selettore CSS per gli elementi da reinizializzare
 */
function reinitReadmore(selector) {
    // Rimuovi l'inizializzazione precedente
    $(selector).each(function() {
        if ($(this).data('readmore')) {
            $(this).readmore('destroy');
        }
    });

    // Reinizializza
    initTextShortener();
}

// Inizializza quando il documento è pronto
$(document).ready(function() {
    initTextShortener();

    // Reinizializza quando si cambia tab o si carica un plugin
    $(document).on('shown.bs.tab', 'a[data-toggle="tab"]', function() {
        // Timeout per assicurarsi che il contenuto sia completamente caricato
        setTimeout(function() {
            initTextShortener();
        }, 100);
    });
});
