/**
 * Tour guidato del modulo Anagrafiche
 * Utilizza Driver.js per guidare l'utente attraverso le funzionalità principali
 */

// Variabile globale per il tour
let anagraficheTour = null;
let tourModuleId = typeof globals !== 'undefined' ? globals.id_module : null;

/**
 * Inizializza il tour guidato delle anagrafiche
 */
function initAnagraficheTour() {
    if (anagraficheTour) {
        return Promise.resolve(anagraficheTour);
    }

    return waitForDriverJsFactory().then(function(driverFactory) {
        if (typeof driverFactory !== 'function') {
            console.error('Driver.js non è disponibile. Il tour non può essere inizializzato.');
            return null;
        }

        anagraficheTour = initAnagraficheTourInternal(driverFactory);

        return anagraficheTour;
    });
}

/**
 * Funzione interna per inizializzare il tour
 */
function initAnagraficheTourInternal(driverFactory) {
    return driverFactory({
        showProgress: false,
        steps: addExitButtonsToTourSteps(getTourSteps(), function() {
            return anagraficheTour;
        }),
        onDestroyed: function() {
            if (tourModuleId) {
                saveTourCompletedDB(tourModuleId);
            }
        }
    });
}

/**
 * Aggiunge i passaggi del tour
 */
function getTourSteps() {
    return [
        {
            element: document.body,
            popover: {
                title: 'Benvenuto nel modulo Anagrafiche',
                description: `
                    <div class="tour-step">
                        <p>Vuoi iniziare il tour guidato del modulo Anagrafiche?</p>
                        <p>Il tour ti guiderà attraverso le sezioni principali del modulo.</p>
                    </div>
                `,
                side: 'top',
                align: 'start',
                showButtons: ['next', 'close'],
                nextBtnText: 'Inizia il tour',
            }
        },
        {
            element: function() {
                const elem = findElementBySelector('.card-primary .card-title', 'Dati anagrafici');
                return elem ? elem.closest('.card') : null;
            },
            popover: {
                title: 'Dati Anagrafici',
                description: `
                    <div class="tour-step">
                        <ul>
                            <li><strong>Denominazione:</strong> Nome dell'azienda o identificativo principale</li>
                            <li><strong>Partita IVA:</strong> Codice fiscale/partita IVA per la fatturazione</li>
                            <li><strong>Tipologia:</strong> Azienda, Ente pubblico o Privato</li>
                            <li><strong>Codice anagrafica:</strong> Codice univoco per identificare l'anagrafica</li>
                        </ul>
                        <p><strong>Nota:</strong> Compila sempre questi campi con cura, poiché vengono utilizzati in tutta l'applicazione.</p>
                    </div>
                `,
                side: 'bottom',
                align: 'start',
                showButtons: ['next', 'previous', 'close'],
                prevBtnText: 'Indietro',
                nextBtnText: 'Avanti',
            }
        },
        {
            element: function() {
                const elem = findElementBySelector('.card-primary .card-title', 'Sede legale');
                return elem ? elem.closest('.card') : null;
            },
            popover: {
                title: 'Sede Legale',
                description: `
                    <div class="tour-step">
                        <ul>
                            <li><strong>Indirizzo e Civico:</strong> Via e numero civico</li>
                            <li><strong>C.A.P. e Città:</strong> Codice postale e località</li>
                            <li><strong>Provincia e Nazione:</strong> Provincia e paese</li>
                            <li><strong>Contatti:</strong> Telefono, cellulare, email e fax</li>
                        </ul>
                    </div>
                `,
                side: 'bottom',
                align: 'start',
                showButtons: ['next', 'previous', 'close'],
                prevBtnText: 'Indietro',
                nextBtnText: 'Avanti',
            }
        },
        {
            element: function() {
                const elem = findElementBySelector('.card-primary .card-title', 'Informazioni per tipo di anagrafica');
                return elem ? elem.closest('.card') : null;
            },
            popover: {
                title: 'Informazioni per Tipo',
                description: `
                    <div class="tour-step">
                        <ul>
                            <li><strong>Cliente:</strong> Pagamenti, banche, listini, agente, ecc.</li>
                            <li><strong>Fornitore:</strong> Pagamenti, banche, iva predefinita, ecc.</li>
                            <li><strong>Tecnico:</strong> Colore per il calendario attività</li>
                        </ul>
                        <p><strong>Nota:</strong> I tab disponibili cambiano in base ai tipi di anagrafica selezionati.</p>
                    </div>
                `,
                side: 'bottom',
                align: 'start',
                showButtons: ['next', 'previous', 'close'],
                prevBtnText: 'Indietro',
                nextBtnText: 'Avanti',
            }
        },
        {
            element: function() {
                const elem = findElementBySelector('.card-primary .card-title', 'Informazioni aggiuntive');
                return elem ? elem.closest('.card') : null;
            },
            popover: {
                title: 'Informazioni Aggiuntive',
                description: `
                    <div class="tour-step">
                        <ul>
                            <li><strong>Registro imprese:</strong> Numero iscrizione, codice REA</li>
                            <li><strong>Settore merceologico:</strong> Settore di attività</li>
                            <li><strong>Marche trattate:</strong> Brand commercializzati</li>
                            <li><strong>Note:</strong> Informazioni aggiuntive e commenti</li>
                        </ul>
                    </div>
                `,
                side: 'bottom',
                align: 'start',
                showButtons: ['next', 'previous', 'close'],
                prevBtnText: 'Indietro',
                nextBtnText: 'Avanti',
            }
        },
        {
            element: function() {
                return findElementBySelector('.content-header .btn-group, .content-header .btn-primary');
            },
            popover: {
                title: 'Pulsanti di Azione',
                description: `
                    <div class="tour-step">
                        <ul>
                            <li><strong>Crea...:</strong> Aggiungi una nuova anagrafica</li>
                            <li><strong>Firma GDPR:</strong> Gestisci la firma per il trattamento dei dati personali</li>
                            <li><strong>Salva:</strong> Salva le modifiche apportate all'anagrafica</li>
                        </ul>
                        <p><strong>Nota:</strong> I pulsanti disponibili cambiano in base al tipo di anagrafica (cliente, fornitore, tecnico, ecc.).</p>
                    </div>
                `,
                side: 'bottom',
                align: 'start',
                showButtons: ['next', 'previous', 'close'],
                prevBtnText: 'Indietro',
                nextBtnText: 'Avanti',
            }
        },
        {
            element: function() {
                return findElementBySelector('.control-sidebar-button');
            },
            popover: {
                title: 'Plugin',
                description: `
                    <div class="tour-step">
                        <ul>
                            <li><strong>Sedi aggiuntive:</strong> Gestisci sedi secondarie dell'anagrafica</li>
                            <li><strong>Referenti:</strong> Aggiungi persone di riferimento</li>
                            <li><strong>Impianti del cliente:</strong> Visualizza gli impianti installati</li>
                            <li><strong>Contratti del cliente:</strong> Gestisci i contratti attivi</li>
                            <li><strong>Note interne:</strong> Aggiungi note private sull'anagrafica</li>
                            <li><strong>Checklist:</strong> Gestisci checklist e task</li>
                        </ul>
                        <p><strong>Nota:</strong> Clicca su un plugin per accedere alle funzionalità specifiche.</p>
                    </div>
                `,
                side: 'left',
                align: 'start',
                showButtons: ['previous', 'close'],
                prevBtnText: 'Indietro',
                doneBtnText: 'Termina il tour'
            }
        }
    ];
}

/**
 * Avvia il tour guidato
 */
function startAnagraficheTour() {
    return initAnagraficheTour().then(function(tour) {
        if (tour) {
            tour.drive();
        }
    });
}

/**
 * Funzione helper per trovare un elemento usando jQuery e restituire l'elemento DOM
 * @param {string} selector - Selettore jQuery
 * @param {string} containsText - Testo da cercare nel titolo (opzionale)
 * @returns {HTMLElement|null} - Elemento DOM o null se non trovato
 */
function findElementBySelector(selector, containsText) {
    try {
        if (typeof jQuery === 'undefined' && typeof $ === 'undefined') {
            console.error('jQuery non è disponibile');
            return null;
        }

        const $ = typeof jQuery !== 'undefined' ? jQuery : window.$;
        
        if (containsText) {
            const $element = $(selector).filter(function() {
                return $(this).text().includes(containsText);
            });
            
            if ($element.length > 0) {
                return $element[0];
            }
            return null;
        } else {
            const $element = $(selector);
            if ($element.length > 0) {
                return $element[0];
            }
            return null;
        }
    } catch (error) {
        console.error('Errore nella ricerca dell\'elemento:', error);
        return null;
    }
}

/**
 * Verifica se il tour è già stato completato (versione asincrona)
 */
function isTourCompleted() {
    return tourModuleId ? isTourCompletedDB(tourModuleId) : Promise.resolve(false);
}

/**
 * Funzione per inizializzare il tour
 */
function initTour() {
    if ($('#edit-form').length > 0) {
        isTourCompleted().then(function(completed) {
            if (!completed) {
                setTimeout(function() {
                    startAnagraficheTour();
                }, 1000);
            }
        });
    }
}

// Inizializza quando il documento è pronto
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initTour);
} else {
    initTour();
}
