/**
 * Tour guidato del modulo Articoli
 * Utilizza Driver.js per guidare l'utente attraverso le funzionalità principali
 */

let articoliTour = null;
let tourModuleId = typeof globals !== 'undefined' ? globals.id_module : null;

function initArticoliTour() {
    if (articoliTour) {
        return Promise.resolve(articoliTour);
    }

    return waitForDriverJsFactory().then(function(driverFactory) {
        if (typeof driverFactory !== 'function') {
            console.error('Driver.js non è disponibile. Il tour non può essere inizializzato.');
            return null;
        }

        articoliTour = initArticoliTourInternal(driverFactory);

        return articoliTour;
    });
}

function initArticoliTourInternal(driverFactory) {
    return driverFactory({
        showProgress: false,
        steps: addExitButtonsToTourSteps(getTourSteps(), function() {
            return articoliTour;
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
                title: 'Benvenuto nel modulo Articoli',
                description: `
                    <div class="tour-step">
                        <p>Vuoi iniziare il tour guidato del modulo Articoli?</p>
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
                const elem = findElementBySelector('.card-title', 'Articolo');
                return elem ? elem.closest('.card') : null;
            },
            popover: {
                title: 'Dati Articolo',
                description: `
                    <div class="tour-step">
                        <p>Definisci il codice, la descrizione e la categoria. Carica un'immagine e attiva l'articolo se necessario.</p>
                        <p><strong>Dettagli prodotto:</strong> Seleziona marca e modello, definisci sottocategoria, ubicazione, unità di misura, garanzia e i dati fisici (peso/volume).</p>
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
                const elem = findElementBySelector('.card-title', 'Acquisto');
                return elem ? elem.closest('.card') : null;
            },
            popover: {
                title: 'Acquisto',
                description: `
                    <div class="tour-step">
                        <p><strong>Fornitore:</strong></p>
                        <ul>
                            <li><strong>Fornitore predefinito:</strong> Fornitore predefinito tra quelli presenti nel plugin "Listino fornitori"</li>
                            <li><strong>Conto predefinito:</strong> Conto di acquisto predefinito</li>
                        </ul>
                        <p><strong>Prezzo:</strong></p>
                        <ul>
                            <li><strong>Prezzo di acquisto:</strong> Prezzo previsto per i fornitori</li>
                        </ul>
                        <p><em>Nota: I prezzi di acquisto vengono utilizzati dai fornitori per generare i listini di acquisto.</em></p>
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
                const elem = findElementBySelector('.card-title', 'Vendita');
                return elem ? elem.closest('.card') : null;
            },
            popover: {
                title: 'Vendita',
                description: `
                    <div class="tour-step">
                        <p><strong>Prezzo di vendita:</strong></p>
                        <ul>
                            <li><strong>Coefficiente di vendita:</strong> Moltiplicatore per calcolare automaticamente il prezzo di vendita</li>
                            <li><strong>Prezzo di vendita:</strong> Prezzo di vendita ${document.location.href.includes('prezzi_ivati') ? 'IVA inclusa' : 'esclusa IVA'}</li>
                            <li><strong>Iva di vendita:</strong> Aliquota IVA applicata (se non specificata usa l'IVA predefinita)</li>
                            <li><strong>Minimo di vendita:</strong> Quantità minima vendibile</li>
                        </ul>
                        <p><strong>Conto:</strong></p>
                        <ul>
                            <li><strong>Conto predefinito di vendita:</strong> Conto di vendita predefinito</li>
                        </ul>
                        <p><em>Nota: Premi il pulsante "Scorpora l'IVA" se vuoi calcolare il prezzo di vendita senza IVA.</em></p>
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
                return document.querySelector('#prezziacquisto')?.closest('.card');
            },
            popover: {
                title: 'Storico Prezzi',
                description: `
                    <div class="tour-step">
                        <p><strong>Ultimi 20 prezzi:</strong></p>
                        <ul>
                            <li><strong>Ultimi 20 prezzi di acquisto:</strong> Storico degli ultimi prezzi di acquisto da fornitori</li>
                            <li><strong>Ultimi 20 prezzi di vendita:</strong> Storico degli ultimi prezzi di vendita praticati</li>
                        </ul>
                        <p><em>Nota: Lo storico ti aiuta a monitorare i trend dei prezzi e a verificare la competitività.</em></p>
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

function startArticoliTour() {
    return initArticoliTour().then(function(tour) {
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
                    startArticoliTour();
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
