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
                        
                        $(element).html(
                            '<span class="shortcontent">' + visibleText + '...</span>' +
                            '<span class="allcontent">' + text + '</span>'
                        );
                        
                        $(element).find('.allcontent').hide();
                    }
                } else {
                    // Mostra tutto il testo
                    const originalText = $(element).find('.allcontent').text();
                    if (originalText) {
                        $(element).html(originalText);
                    }
                }
            }
        });
    });

    // Inizializza readmore.js su elementi con classe .readmore
    $('.readmore').each(function() {
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

// Inizializza quando il documento è pronto
$(document).ready(function() {
    initTextShortener();
});
