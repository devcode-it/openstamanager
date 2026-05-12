/**
 * Tour guidato del modulo Preventivi
 * Utilizza Driver.js per guidare l'utente attraverso le funzionalità principali
 */

let preventiviTour = null;
let tourModuleId = typeof globals !== 'undefined' ? globals.id_module : null;

function initPreventiviTour() {
    if (preventiviTour) {
        return Promise.resolve(preventiviTour);
    }

    return waitForDriverJsFactory().then(function(driverFactory) {
        if (typeof driverFactory !== 'function') {
            console.error('Driver.js non è disponibile. Il tour non può essere inizializzato.');
            return null;
        }

        preventiviTour = initPreventiviTourInternal(driverFactory);

        return preventiviTour;
    });
}

function initPreventiviTourInternal(driverFactory) {
    return driverFactory({
        showProgress: false,
        steps: addExitButtonsToTourSteps(getTourSteps(), function() {
            return preventiviTour;
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
                title: 'Benvenuto nel modulo Preventivi',
                description: `
                    <div class="tour-step">
                        <p>Vuoi iniziare il tour guidato del modulo Preventivi?</p>
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
                const elem = findElementBySelector('.card-title', 'Dati cliente');
                return elem ? elem.closest('.card') : null;
            },
            popover: {
                title: 'Dati Cliente',
                description: `
                    <div class="tour-step">
                        <ul>
                            <li><strong>Cliente:</strong> Anagrafica del cliente a cui è riferito il preventivo</li>
                            <li><strong>Referente:</strong> Persona di contatto presso il cliente</li>
                            <li><strong>Sede:</strong> Indirizzo o sede specifica per il preventivo</li>
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
                const elem = findElementBySelector('.card-title', 'Intestazione');
                return elem ? elem.closest('.card') : null;
            },
            popover: {
                title: 'Intestazione',
                description: `
                    <div class="tour-step">
                        <p>Inserisci il numero, la data e il nome del preventivo. Definisci la validità e lo stato (bozza, accettato, rifiutato, ecc.)</p>
                        <p><strong>Condizioni:</strong> Scegli il tipo di attività, le condizioni di pagamento, le banche e applica un eventuale sconto in fattura.</p>
                        <p><strong>Dettagli:</strong> Aggiungi descrizione, esclusioni, tempi di consegna, garanzia e condizioni generali.</p>
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
                const elem = findElementBySelector('.card-title', 'Righe');
                return elem ? elem.closest('.card') : null;
            },
            popover: {
                title: 'Righe',
                description: `
                    <div class="tour-step">
                        <ul>
                            <li><strong>Articoli:</strong> Articoli contenuti nel preventivo</li>
                            <li><strong>Descrizione:</strong> Voci di spesa o descrizioni libere</li>
                            <li><strong>Quantità e Prezzo:</strong> Quantità e prezzo unitario</li>
                            <li><strong>IVA:</strong> Aliquota IVA applicata</li>
                            <li><strong>Sconto:</strong> Eventuali sconti applicati</li>
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

function startPreventiviTour() {
    return initPreventiviTour().then(function(tour) {
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
                    startPreventiviTour();
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
