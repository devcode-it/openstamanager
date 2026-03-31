/**
 * Tour guidato del modulo Interventi
 * Utilizza Driver.js per guidare l'utente attraverso le funzionalità principali
 */

let interventiTour = null;
let tourModuleId = typeof globals !== 'undefined' ? globals.id_module : null;

function initInterventiTour() {
    if (interventiTour) {
        return Promise.resolve(interventiTour);
    }

    return waitForDriverJsFactory().then(function(driverFactory) {
        if (typeof driverFactory !== 'function') {
            console.error('Driver.js non è disponibile. Il tour non può essere inizializzato.');
            return null;
        }

        interventiTour = initInterventiTourInternal(driverFactory);

        return interventiTour;
    });
}

function initInterventiTourInternal(driverFactory) {
    return driverFactory({
        showProgress: false,
        steps: addExitButtonsToTourSteps(getTourSteps(), function() {
            return interventiTour;
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
                title: 'Benvenuto nel modulo Interventi',
                description: `
                    <div class="tour-step">
                        <p>Vuoi iniziare il tour guidato del modulo Interventi?</p>
                        <p>Il tour ti guiderà attraverso le sezioni principali del modulo.</p>
                    </div>
                `,
                side: 'top',
                align: 'start',
                showButtons: ['next', 'close'],
                nextBtnText: 'Inizia il tour'
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
                            <li><strong>Cliente:</strong> Anagrafica del cliente a cui è riferito l'intervento</li>
                            <li><strong>Referente:</strong> Persona di contatto presso il cliente</li>
                            <li><strong>Sede:</strong> Indirizzo o sede specifica per l'intervento</li>
                        </ul>
                        <p>Per visualizzare questi dati è necessario espandere la sezione cliccando sul pulsante <i class="fa fa-plus"></i> in alto a destra.</p>
                    </div>
                `,
                side: 'bottom',
                align: 'start',
                showButtons: ['next', 'previous', 'close'],
                prevBtnText: 'Indietro',
                nextBtnText: 'Avanti'
            }
        },
        {
            element: function() {
                const elem = findElementBySelector('.card-title', 'Dati intervento');
                return elem ? elem.closest('.card') : null;
            },
            popover: {
                title: 'Dati Intervento',
                description: `
                    <div class="tour-step">
                        <p>Qui puoi definire le informazioni essenziali dell'attività.</p>
                        <p><strong>Dati temporali:</strong> Inserisci la data e ora di richiesta e scadenza, quindi assegna un tipo di attività (es. manutenzione, installazione) e definisci lo stato corrente (es. in programmazione, in corso, completato).</p>
                        <p><strong>Tecnici:</strong> Seleziona i tecnici che dovranno lavorare all'intervento. Puoi assegnarne più di uno.</p>
                        <p><strong>Collegamenti opzionali:</strong> Collega l'attività a un preventivo, un contratto o un ordine cliente per tenere traccia della provenienza del lavoro.</p>
                    </div>
                `,
                side: 'bottom',
                align: 'start',
                showButtons: ['next', 'previous', 'close'],
                prevBtnText: 'Indietro',
                nextBtnText: 'Avanti'
            }
        },
        {
            element: function() {
                const elem = findElementBySelector('.card-title', 'Sessioni di lavoro');
                return elem ? elem.closest('.card') : null;
            },
            popover: {
                title: 'Sessioni di Lavoro',
                description: `
                    <div class="tour-step">
                        <ul>
                            <li><strong>Aggiungi sessione:</strong> Registra il lavoro svolto da ciascun tecnico</li>
                            <li><strong>Orari:</strong> Orario di inizio e fine di ogni sessione</li>
                            <li><strong>Note:</strong> Note sul lavoro svolto nella sessione</li>
                            <li><strong>Totale ore:</strong> Riepilogo delle ore lavorate</li>
                        </ul>
                    </div>
                `,
                side: 'bottom',
                align: 'start',
                showButtons: ['next', 'previous', 'close'],
                prevBtnText: 'Indietro',
                nextBtnText: 'Avanti'
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
                            <li><strong>Articoli:</strong> Articoli utilizzati nell'intervento</li>
                            <li><strong>Descrizione:</strong> Voci di spesa o descrizioni libere</li>
                            <li><strong>Quantità e Prezzo:</strong> Quantità e prezzo unitario di ogni riga</li>
                            <li><strong>IVA:</strong> Aliquota IVA applicata</li>
                        </ul>
                    </div>
                `,
                side: 'bottom',
                align: 'start',
                showButtons: ['next', 'previous', 'close'],
                prevBtnText: 'Indietro',
                nextBtnText: 'Avanti'
            }
        },
        {
            element: function() {
                const elem = findElementBySelector('.card-title', 'Costi totali');
                return elem ? elem.closest('.card') : null;
            },
            popover: {
                title: 'Costi Totali',
                description: `
                    <div class="tour-step">
                        <ul>
                            <li><strong>Totale imponibile:</strong> Somma delle righe senza IVA</li>
                            <li><strong>Totale IVA:</strong> Somma delle imposte</li>
                            <li><strong>Totale:</strong> Importo totale dell'intervento</li>
                        </ul>
                    </div>
                `,
                side: 'bottom',
                align: 'start',
                showButtons: ['next', 'previous', 'close'],
                prevBtnText: 'Indietro',
                nextBtnText: 'Avanti'
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
                            <li><strong>Preventivi:</strong> Preventivi collegati all'intervento</li>
                            <li><strong>Contratti:</strong> Contratti da cui deriva l'intervento</li>
                            <li><strong>DDT:</strong> Documenti di trasporto associati</li>
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

function startInterventiTour() {
    return initInterventiTour().then(function(tour) {
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
                    startInterventiTour();
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
