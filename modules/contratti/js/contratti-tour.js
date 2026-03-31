/**
 * Tour guidato del modulo Contratti
 * Utilizza Driver.js per guidare l'utente attraverso le funzionalità principali
 */

let contrattiTour = null;
let tourModuleId = typeof globals !== 'undefined' ? globals.id_module : null;

function initContrattiTour() {
    if (contrattiTour) {
        return Promise.resolve(contrattiTour);
    }

    return waitForDriverJsFactory().then(function(driverFactory) {
        if (typeof driverFactory !== 'function') {
            console.error('Driver.js non è disponibile. Il tour non può essere inizializzato.');
            return null;
        }

        contrattiTour = initContrattiTourInternal(driverFactory);

        return contrattiTour;
    });
}

function initContrattiTourInternal(driverFactory) {
    return driverFactory({
        showProgress: false,
        steps: addExitButtonsToTourSteps(getTourSteps(), function() {
            return contrattiTour;
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
                title: 'Benvenuto nel modulo Contratti',
                description: `
                    <div class="tour-step">
                        <p>Vuoi iniziare il tour guidato del modulo Contratti?</p>
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
                            <li><strong>Cliente:</strong> Anagrafica del cliente a cui è riferito il contratto</li>
                            <li><strong>Referente:</strong> Persona di contatto presso il cliente</li>
                            <li><strong>Sede:</strong> Indirizzo o sede specifica per il contratto</li>
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
                        <p>Inserisci numero, data e definisci la validità e lo stato del contratto.</p>
                        <p><strong>Categorizzazione:</strong> Scegli le categorie, il tipo di attività predefinito e le condizioni di pagamento.</p>
                        <p><strong>Rinnovo:</strong> Se il contratto è rinnovabile, puoi impostare il rinnovo automatico, i giorni di preavviso e le ore residue.</p>
                        <p><strong>Dettagli:</strong> Aggiungi esclusioni, descrizione, note interne e condizioni generali.</p>
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
                const elem = findElementBySelector('.card-title', 'Costi unitari');
                return elem ? elem.closest('.card') : null;
            },
            popover: {
                title: 'Costi Unitari',
                description: `
                    <div class="tour-step">
                        <ul>
                            <li><strong>Costo orario:</strong> Costo orario per interventi</li>
                            <li><strong>Costo chilometrico:</strong> Costo al km per spostamenti</li>
                            <li><strong>Diritto di chiamata:</strong> Costo fisso per ogni intervento</li>
                            <li><strong>Costo ore tecniche:</strong> Costo per ore tecniche extra</li>
                            <li><strong>Costo km tecnici:</strong> Costo chilometrico per i tecnici</li>
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
                            <li><strong>Articoli:</strong> Articoli previsti nel contratto</li>
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
                            <li><strong>Interventi:</strong> Interventi collegati al contratto</li>
                            <li><strong>DDT:</strong> Documenti di trasporto associati</li>
                            <li><strong>Preventivi:</strong> Preventivi da cui deriva il contratto</li>
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

function startContrattiTour() {
    return initContrattiTour().then(function(tour) {
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
                    startContrattiTour();
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
