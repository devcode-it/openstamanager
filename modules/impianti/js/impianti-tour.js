/**
 * Tour guidato del modulo Impianti
 * Utilizza Driver.js per guidare l'utente attraverso le funzionalità principali
 */

let impiantiTour = null;
let tourModuleId = typeof globals !== 'undefined' ? globals.id_module : null;

function initImpiantiTour() {
    if (impiantiTour) {
        return Promise.resolve(impiantiTour);
    }

    return waitForDriverJsFactory().then(function(driverFactory) {
        if (typeof driverFactory !== 'function') {
            console.error('Driver.js non è disponibile. Il tour non può essere inizializzato.');
            return null;
        }

        impiantiTour = initImpiantiTourInternal(driverFactory);

        return impiantiTour;
    });
}

function initImpiantiTourInternal(driverFactory) {
    return driverFactory({
        showProgress: false,
        steps: addExitButtonsToTourSteps(getTourSteps(), function() {
            return impiantiTour;
        }),
        onDestroyed: function() {
            if (tourModuleId) {
                saveTourCompletedDB(tourModuleId);
            }
        }
    });
}

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

function getTourSteps() {
    return [
        {
            element: document.body,
            popover: {
                title: 'Benvenuto nel modulo Impianti',
                description: `
                    <div class="tour-step">
                        <p>Vuoi iniziare il tour guidato del modulo Impianti?</p>
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
                const elem = findElementBySelector('.card-title', 'Dati impianto');
                return elem ? elem.closest('.card') : null;
            },
            popover: {
                title: 'Dati Impianto',
                description: `
                    <div class="tour-step">
                        <p>Carica una foto dell'impianto e inserisci matricola, nome e data di installazione. Collega il cliente e specifica sede e tecnico.</p>
                        <p><strong>Prodotto:</strong> Definisci marca, modello, proprietario e stato. Seleziona o aggiungi una categoria.</p>
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
                const elem = findElementBySelector('.card-title', 'Dati impianto');
                return elem ? elem.closest('.card') : null;
            },
            popover: {
                title: 'Dettagli Impianto',
                description: `
                    <div class="tour-step">
                        <p><strong>Descrizione:</strong></p>
                        <ul>
                            <li><strong>Descrizione:</strong> Descrizione dettagliata dell'impianto</li>
                            <li><strong>Note:</strong> Note aggiuntive</li>
                        </ul>
                        <p><strong>Ubicazione:</strong></p>
                        <ul>
                            <li><strong>Ubicazione:</strong> Posizione dove è installato</li>
                        </ul>
                        <p><strong>Dati edificio:</strong></p>
                        <ul>
                            <li><strong>Palazzo:</strong> Nome del palazzo/edificio</li>
                            <li><strong>Scala:</strong> Scala di accesso</li>
                            <li><strong>Piano:</strong> Piano dove è ubicato</li>
                            <li><strong>Interno:</strong> Numero interno</li>
                            <li><strong>Occupante:</strong> Occupante attuale (opzionale)</li>
                        </ul>
                    </div>
                `,
                side: 'bottom',
                align: 'start',
                showButtons: ['previous', 'close'],
                prevBtnText: 'Indietro',
                doneBtnText: 'Termina il tour'
            }
        }
    ];
}

function startImpiantiTour() {
    return initImpiantiTour().then(function(tour) {
        if (tour) {
            tour.drive();
        }
    });
}

function isTourCompleted() {
    return tourModuleId ? isTourCompletedDB(tourModuleId) : Promise.resolve(false);
}

function initTour() {
    if ($('#edit-form').length > 0) {
        isTourCompleted().then(function(completed) {
            if (!completed) {
                setTimeout(function() {
                    startImpiantiTour();
                }, 1000);
            }
        });
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initTour);
} else {
    initTour();
}
