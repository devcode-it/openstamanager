/**
 * Tour guidato del modulo Ordini
 * Utilizza Driver.js per guidare l'utente attraverso le funzionalità principali
 */

let ordiniTour = null;
let tourModuleId = typeof globals !== 'undefined' ? globals.id_module : null;

function initOrdiniTour() {
    if (ordiniTour) {
        return Promise.resolve(ordiniTour);
    }

    return waitForDriverJsFactory().then(function(driverFactory) {
        if (typeof driverFactory !== 'function') {
            console.error('Driver.js non è disponibile. Il tour non può essere inizializzato.');
            return null;
        }

        ordiniTour = initOrdiniTourInternal(driverFactory);

        return ordiniTour;
    });
}

function initOrdiniTourInternal(driverFactory) {
    return driverFactory({
        showProgress: false,
        steps: addExitButtonsToTourSteps(getTourSteps(), function() {
            return ordiniTour;
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
    const datiClienteElement = findElementBySelector('.card-title', 'Dati cliente');
    
    return [
        {
            element: document.body,
            popover: {
                title: 'Benvenuto nel modulo Ordini',
                description: `
                    <div class="tour-step">
                        <p>Vuoi iniziare il tour guidato del modulo Ordini?</p>
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
                const datiDestElement = findElementBySelector('.card-title', 'Dati cliente');
                const datiFornitoreElement = findElementBySelector('.card-title', 'Dati fornitore');
                const datiDest = datiDestElement || datiFornitoreElement;
                return datiDest ? datiDest.closest('.card') : null;
            },
            popover: {
                title: datiClienteElement ? 'Dati Cliente' : 'Dati Fornitore',
                description: `
                    <div class="tour-step">
                        <ul>
                            <li><strong>Anagrafica:</strong> Cliente o fornitore a cui è riferito l'ordine</li>
                            <li><strong>Referente:</strong> Persona di contatto</li>
                            <li><strong>Sede:</strong> Indirizzo di spedizione o sede specifica</li>
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
                        <p>Inserisci la data e definisci le sedi di partenza e destinazione della merce.</p>
                        <p><strong>Spedizione:</strong> Scegli il tipo di spedizione, il vettore e il porto (condizione del trasporto). Aggiungi eventuali condizioni generali e note.</p>
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
                            <li><strong>Articoli:</strong> Articoli contenuti nell'ordine</li>
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
                            <li><strong>Preventivi:</strong> Preventivi collegati all'ordine</li>
                            <li><strong>DDT:</strong> Documenti di trasporto associati</li>
                            <li><strong>Fatture:</strong> Fatture emesse basate sull'ordine</li>
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

function startOrdiniTour() {
    return initOrdiniTour().then(function(tour) {
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
                    startOrdiniTour();
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
