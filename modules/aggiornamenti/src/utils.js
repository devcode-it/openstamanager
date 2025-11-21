/**
 * Utilità JavaScript per il modulo aggiornamenti
 */

/**
 * Determina le classi CSS in base ai conteggi di errori
 * @param {number} dangerCount Numero di errori danger
 * @param {number} warningCount Numero di errori warning
 * @param {number} infoCount Numero di errori info
 * @returns {Object} Oggetto con chiavi: headerClass, titleClass, cardClass, icon
 */
function determineCardClasses(dangerCount = 0, warningCount = 0, infoCount = 0) {
    if (dangerCount > 0) {
        return {
            headerClass: 'requirements-card-header-danger',
            titleClass: 'requirements-card-title-danger',
            cardClass: 'card-danger',
            icon: 'fa-exclamation-triangle'
        };
    } else if (warningCount > 0) {
        return {
            headerClass: 'requirements-card-header-warning',
            titleClass: 'requirements-card-title-warning',
            cardClass: 'card-warning',
            icon: 'fa-exclamation-triangle'
        };
    } else if (infoCount > 0) {
        return {
            headerClass: 'requirements-card-header-info',
            titleClass: 'requirements-card-title-info',
            cardClass: 'card-info',
            icon: 'fa-info-circle'
        };
    }

    return {
        headerClass: 'requirements-card-header-success',
        titleClass: 'requirements-card-title-success',
        cardClass: 'card-success',
        icon: 'fa-check-circle'
    };
}

/**
 * Rimuove tutte le classi di colore da un elemento
 * @param {jQuery} element Elemento jQuery
 */
function removeColorClasses(element) {
    element.removeClass('requirements-card-header-success requirements-card-header-info requirements-card-header-warning requirements-card-header-danger');
}

/**
 * Rimuove tutte le classi di colore dal titolo
 * @param {jQuery} element Elemento jQuery
 */
function removeTitleColorClasses(element) {
    element.removeClass('requirements-card-title-success requirements-card-title-info requirements-card-title-warning requirements-card-title-danger');
}

/**
 * Rimuove tutte le classi di colore dalla card
 * @param {jQuery} element Elemento jQuery
 */
function removeCardColorClasses(element) {
    element.removeClass('card-success card-info card-warning card-danger');
}

/**
 * Aggiorna l'icona di una card
 * @param {jQuery} titleElement Elemento del titolo
 * @param {string} newIcon Nuova icona (es. 'fa-check-circle')
 */
function updateCardIcon(titleElement, newIcon) {
    let iconElement = titleElement.find('.requirements-icon');
    iconElement.removeClass('fa-info-circle fa-exclamation-circle fa-warning fa-times-circle fa-exclamation-triangle');
    iconElement.addClass(newIcon);
}

/**
 * Applica le classi di colore a una card
 * @param {jQuery} headerElement Header della card
 * @param {jQuery} titleElement Titolo della card
 * @param {jQuery} cardElement Card stessa
 * @param {Object} colorClasses Oggetto con le classi di colore
 */
function applyCardColorClasses(headerElement, titleElement, cardElement, colorClasses) {
    removeColorClasses(headerElement);
    removeTitleColorClasses(titleElement);
    removeCardColorClasses(cardElement);

    headerElement.addClass(colorClasses.headerClass);
    titleElement.addClass(colorClasses.titleClass);
    cardElement.addClass(colorClasses.cardClass);
    updateCardIcon(titleElement, colorClasses.icon);
}

/**
 * Formatta i bytes in formato leggibile
 * @param {number} bytes Numero di bytes
 * @param {number} decimals Numero di decimali
 * @returns {string} Stringa formattata
 */
function formatBytes(bytes, decimals = 2) {
    if (bytes === 0) return '0 Bytes';

    const k = 1024;
    const dm = decimals < 0 ? 0 : decimals;
    const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];

    const i = Math.floor(Math.log(bytes) / Math.log(k));

    return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
}

/**
 * Imposta la percentuale di progresso
 * @param {number} percent Percentuale (0-100)
 */
function setPercentage(percent) {
    $('#progress .progress-bar').width(percent + '%');
    $('#progress .progress-bar span').text(percent + '%');
}

/**
 * Carica lo stato del pulsante
 * @param {jQuery} button Elemento pulsante
 * @returns {Array} Array con stato precedente [html, class]
 */
function buttonLoading(button) {
    let $this = $(button);

    let result = [
        $this.html(),
        $this.attr('class')
    ];

    $this.html('<i class="fa fa-spinner fa-pulse fa-fw"></i>');
    $this.addClass('btn-warning');
    $this.prop('disabled', true);

    return result;
}

/**
 * Ripristina lo stato del pulsante
 * @param {jQuery} button Elemento pulsante
 * @param {Array} loadingResult Stato precedente [html, class]
 */
function buttonRestore(button, loadingResult) {
    let $this = $(button);

    $this.html(loadingResult[0]);
    $this.attr('class', '');
    $this.addClass(loadingResult[1]);
    $this.prop('disabled', false);
}

