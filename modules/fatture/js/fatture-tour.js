/**
 * Tour guidato del modulo Fatture
 * Utilizza Driver.js per guidare l'utente attraverso le funzionalità principali
 */

let fattureTour = null;
let tourModuleId = typeof globals !== 'undefined' ? globals.id_module : null;

function initFattureTour() {
    if (fattureTour) {
        return Promise.resolve(fattureTour);
    }

    return waitForDriverJsFactory().then(function(driverFactory) {
        if (typeof driverFactory !== 'function') {
            console.error('Driver.js non è disponibile. Il tour non può essere inizializzato.');
            return null;
        }

        fattureTour = initFattureTourInternal(driverFactory);

        return fattureTour;
    });
}

function initFattureTourInternal(driverFactory) {
    return driverFactory({
        showProgress: false,
        steps: addExitButtonsToTourSteps(getTourSteps(), function() {
            return fattureTour;
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
    const isVendita = datiClienteElement !== null;
    const fattAccElement = findElementBySelector('.card-title', 'Dati Fattura accompagnatoria');
    
    const steps = [
        {
            element: document.body,
            popover: {
                title: 'Benvenuto nel modulo Fatture',
                description: `
                    <div class="tour-step">
                        <p>Vuoi iniziare il tour guidato del modulo Fatture?</p>
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
                        <p>Seleziona l'anagrafica ${datiClienteElement ? 'cliente' : 'fornitore'} a cui è riferita la fattura${datiClienteElement ? '. Puoi indicare anche un agente di riferimento.' : ''}</p>
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
                        <p><strong>Campi obbligatori per l'emissione:</strong></p>
                        <ul>
                            <li><strong>Tipo documento:</strong> Seleziona il tipo (fattura, nota di credito, ecc.)</li>
                            <li><strong>Data emissione:</strong> Data in cui viene emessa la fattura</li>
                            <li><strong>Sede partenza/destinazione:</strong> Luoghi di partenza e arrivo della merce</li>
                        </ul>
                        <p><strong>Opzioni aggiuntive:</strong></p>
                        <ul>
                            <li><strong>Split payment:</strong> ${!isVendita ? 'Per acquisti: ' : ''}Abilita lo split payment per questo documento. Le aliquote IVA con natura N6.X (reverse charge) non saranno disponibili.</li>
                            ${isVendita ? '<li><strong>Fattura per conto terzi:</strong> Indica che la fattura è emessa per conto di un terzo. Nell\'XML della Fattura Elettronica il tuo fornitore (azienda) sarà indicato come cessionario e il cliente come cedente/prestatore. Serve per l\'emissione dell\'autofattura nelle cooperative agricole.</li>' : ''}
                            ${isVendita ? '<li><strong>Sconto in fattura:</strong> Sconto applicabile sul totale del documento.</li>' : ''}
                            <li><strong>Ritenuta previdenziale:</strong> Ritenuta previdenziale da applicare alle righe.</li>
                            ${isVendita ? '<li><strong>Dichiarazione d\'intent:</strong> Dichiarazione di intento collegata all\'anagrafica del cliente.</li>' : ''}
                        </ul>
                        <p><em>Nota: Il numero fattura viene generato automaticamente al salvataggio. Le scadenze di pagamento vengono calcolate in base alle condizioni selezionate.</em></p>
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
                return findElementBySelector('.card-title', 'Righe');
            },
            popover: {
                title: 'Righe',
                description: `
                    <div class="tour-step">
                        <p>Aggiungi articoli dal magazzino, righe descrizione o collegati da altri documenti. Per ogni riga definisci quantità, prezzo, IVA e sconto.</p>
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
                            <li><strong>Preventivi:</strong> Preventivi collegati alla fattura</li>
                            <li><strong>Contratti:</strong> Contratti da cui deriva la fattura</li>
                            <li><strong>DDT:</strong> Documenti di trasporto associati</li>
                            <li><strong>Interventi:</strong> Interventi fatturati</li>
                            <li><strong>Ordini:</strong> Ordini a cui si riferisce la fattura</li>
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
    
    if (fattAccElement) {
        steps.splice(3, 0, {
            element: function() {
                const elem = findElementBySelector('.card-title', 'Dati Fattura accompagnatoria');
                return elem ? elem.closest('.card') : null;
            },
            popover: {
                title: 'Dati Fattura Accompagnatoria',
                description: `
                    <div class="tour-step">
                        <p><strong>Opzioni di trasporto:</strong></p>
                        <ul>
                            <li><strong>Aspetto beni:</strong> Descrizione dell'aspetto dei beni trasportati</li>
                            <li><strong>Causale trasporto:</strong> Motivo del trasporto</li>
                            <li><strong>Porto:</strong> Condizione del trasporto (franco, assegnato, ecc.)</li>
                            <li><strong>N. colli:</strong> Numero di colli trasportati</li>
                            <li><strong>Tipo di spedizione:</strong> Modalità di spedizione</li>
                            <li><strong>Vettore:</strong> Trasportatore incaricato</li>
                            <li><strong>Tipo Resa:</strong> Condizione di consegna (EXW, FOB, ecc.)</li>
                        </ul>
                        <p><strong>Dati fisici:</strong></p>
                        <ul>
                            <li><strong>Peso:</strong> Peso totale dei beni (calcolato o manuale)</li>
                            <li><strong>Volume:</strong> Volume totale dei beni (calcolato o manuale)</li>
                        </ul>
                    </div>
                `,
                side: 'bottom',
                align: 'start',
                showButtons: ['next', 'previous', 'close'],
                prevBtnText: 'Indietro',
                nextBtnText: 'Avanti',
            }
        });
    }
    
    return steps;
}

function startFattureTour() {
    return initFattureTour().then(function(tour) {
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
                    startFattureTour();
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
