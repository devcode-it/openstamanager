/**
 * Tour guidato del modulo DDT
 * Utilizza Driver.js per guidare l'utente attraverso le funzionalità principali
 */

let ddtTour = null;
let tourModuleId = typeof globals !== 'undefined' ? globals.id_module : null;

function initDdtTour() {
    if (ddtTour) {
        return Promise.resolve(ddtTour);
    }

    return waitForDriverJsFactory().then(function(driverFactory) {
        if (typeof driverFactory !== 'function') {
            console.error('Driver.js non è disponibile. Il tour non può essere inizializzato.');
            return null;
        }

        ddtTour = initDdtTourInternal(driverFactory);

        return ddtTour;
    });
}

function initDdtTourInternal(driverFactory) {
    return driverFactory({
        showProgress: false,
        steps: addExitButtonsToTourSteps(getTourSteps(), function() {
            return ddtTour;
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
                title: 'Benvenuto nel modulo DDT',
                description: `
                    <div class="tour-step">
                        <p>Vuoi iniziare il tour guidato del modulo DDT?</p>
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
                const datiDestElement = findElementBySelector('.card-title', 'Dati destinatario');
                const datiMittElement = findElementBySelector('.card-title', 'Dati mittente');
                const datiSoggettoElement = datiDestElement || datiMittElement;
                return datiSoggettoElement ? datiSoggettoElement.closest('.card') : null;
            },
            popover: {
                title: 'Dati Destinatario/Mittente',
                description: `
                    <div class="tour-step">
                        <ul>
                            <li><strong>Anagrafica:</strong> Destinatario o mittente a cui è riferito il DDT</li>
                            <li><strong>Referente:</strong> Persona di contatto</li>
                            <li><strong>Sede:</strong> Indirizzo di spedizione o provenienza</li>
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
                        <p>Inserisci i numeri (primario e secondario) e la data del documento. Definisci le sedi di partenza e destinazione.</p>
                        <p><strong>Condizioni:</strong> Scegli le condizioni di pagamento e applica un eventuale sconto in fattura.</p>
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
                const elem = findElementBySelector('.card-title', 'Dati ddt');
                return elem ? elem.closest('.card') : null;
            },
            popover: {
                title: 'Dati DDT',
                description: `
                    <div class="tour-step">
                        <p><strong>Opzioni di trasporto:</strong></p>
                        <ul>
                            <li><strong>Aspetto beni:</strong> Descrizione dell'aspetto dei beni trasportati</li>
                            <li><strong>Causale trasporto:</strong> Motivo del trasporto</li>
                            <li><strong>Tipo di spedizione:</strong> Modalità di spedizione</li>
                            <li><strong>N. colli:</strong> Numero di colli trasportati</li>
                            <li><strong>Porto:</strong> Condizione del trasporto (franco o assegnato)</li>
                            <li><strong>Vettore:</strong> Trasportatore incaricato</li>
                            <li><strong>Data ora trasporto:</strong> Data e ora di inizio del trasporto</li>
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
                const elem = findElementBySelector('.card-title', 'Righe');
                return elem ? elem.closest('.card') : null;
            },
            popover: {
                title: 'Righe',
                description: `
                    <div class="tour-step">
                        <ul>
                            <li><strong>Articoli:</strong> Articoli trasportati</li>
                            <li><strong>Descrizione:</strong> Voci di spesa o descrizioni libere</li>
                            <li><strong>Quantità e Prezzo:</strong> Quantità e prezzo unitario</li>
                            <li><strong>IVA:</strong> Aliquota IVA applicata</li>
                            <li><strong>Sconto:</strong> Eventuali sconti applicati</li>
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
                return findElementBySelector('#documenti-collegati-title');
            },
            popover: {
                title: 'Documenti Collegati',
                description: `
                    <div class="tour-step">
                        <ul>
                            <li><strong>Ordini:</strong> Ordini a cui è associato il DDT</li>
                            <li><strong>Fatture:</strong> Fatture collegate al DDT</li>
                            <li><strong>Preventivi:</strong> Preventivi associati</li>
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

function startDdtTour() {
    return initDdtTour().then(function(tour) {
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
                    startDdtTour();
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
