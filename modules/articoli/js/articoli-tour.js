/**
 * Tour guidato del modulo Articoli
 * Utilizza Shepherd.js per guidare l'utente attraverso le funzionalità principali
 */

let articoliTour = null;

function initArticoliTour() {
    if (typeof Shepherd === 'undefined') {
        console.error('Shepherd.js non è disponibile. Attendi il caricamento della libreria...');
        setTimeout(function() {
            if (typeof Shepherd === 'undefined') {
                console.error('Shepherd.js non è disponibile dopo il ritardo. Il tour non può essere inizializzato.');
                return;
            } else {
                initArticoliTourInternal();
            }
        }, 500);
        return;
    }
    
    initArticoliTourInternal();
}

function initArticoliTourInternal() {
    articoliTour = new Shepherd.Tour({
        tourName: 'articoli-tour',
        useModalOverlay: true,
        defaultStepOptions: {
            classes: 'shadow-md bg-purple-dark',
            scrollTo: { behavior: 'smooth', block: 'center' },
            cancelIcon: {
                enabled: true,
                label: 'Chiudi'
            },
            arrow: true,
            modalOverlayOpeningPadding: 10,
            modalOverlayOpeningRadius: 10,
        },
    });

    addTourSteps();

    articoliTour.on('complete', function() {
        localStorage.setItem('articoli-tour-completed', 'true');
        showTourCompleteMessage();
    });

    articoliTour.on('cancel', function() {
        localStorage.setItem('articoli-tour-completed', 'true');
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

function addTourSteps() {
    articoliTour.addStep({
        id: 'introduction',
        title: 'Benvenuto nel modulo Articoli',
        text: `
            <div class="tour-step">
                <p>Vuoi iniziare il tour guidato del modulo Articoli?</p>
                <p>Il tour ti guiderà attraverso le sezioni principali del modulo.</p>
            </div>
        `,
        attachTo: {
            element: document.body,
            on: 'top'
        },
        buttons: [
            {
                text: 'No',
                action: cancelTourAndClose,
                classes: 'shepherd-button-secondary'
            },
            {
                text: 'Inizia il tour',
                action: articoliTour.next,
                classes: 'shepherd-button-primary'
            }
        ]
    });

    const articoloElement = findElementBySelector('.card-title', 'Articolo');
    articoliTour.addStep({
        id: 'articolo',
        title: 'Dati Articolo',
        text: `
            <div class="tour-step">
                <p>Definisci il codice, la descrizione e la categoria. Carica un'immagine e attiva l'articolo se necessario.</p>
                <p><strong>Dettagli prodotto:</strong> Seleziona marca e modello, definisci sottocategoria, ubicazione, unità di misura, garanzia e i dati fisici (peso/volume).</p>
            </div>
        `,
        attachTo: articoloElement ? {
            element: articoloElement.closest('.card'),
            on: 'bottom'
        } : null,
        buttons: [
            {
                text: 'Fine tour',
                action: completeTourAndClose,
                classes: 'shepherd-button-secondary'
            },
            {
                text: 'Indietro',
                action: articoliTour.back
            },
            {
                text: 'Avanti',
                action: articoliTour.next
            }
        ]
    });

    const acquistoElement = findElementBySelector('.card-title', 'Acquisto');
    articoliTour.addStep({
        id: 'acquisto',
        title: 'Acquisto',
        text: `
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
        attachTo: acquistoElement ? {
            element: acquistoElement.closest('.card'),
            on: 'bottom'
        } : null,
        buttons: [
            {
                text: 'Fine tour',
                action: completeTourAndClose,
                classes: 'shepherd-button-secondary'
            },
            {
                text: 'Indietro',
                action: articoliTour.back
            },
            {
                text: 'Avanti',
                action: articoliTour.next
            }
        ]
    });

    const venditaElement = findElementBySelector('.card-title', 'Vendita');
    articoliTour.addStep({
        id: 'vendita',
        title: 'Vendita',
        text: `
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
        attachTo: venditaElement ? {
            element: venditaElement.closest('.card'),
            on: 'bottom'
        } : null,
        buttons: [
            {
                text: 'Fine tour',
                action: completeTourAndClose,
                classes: 'shepherd-button-secondary'
            },
            {
                text: 'Indietro',
                action: articoliTour.back
            },
            {
                text: 'Avanti',
                action: articoliTour.next
            }
        ]
    });

    articoliTour.addStep({
        id: 'storico',
        title: 'Storico Prezzi',
        text: `
            <div class="tour-step">
                <p><strong>Ultimi 20 prezzi:</strong></p>
                <ul>
                    <li><strong>Ultimi 20 prezzi di acquisto:</strong> Storico degli ultimi prezzi di acquisto da fornitori</li>
                    <li><strong>Ultimi 20 prezzi di vendita:</strong> Storico degli ultimi prezzi di vendita praticati</li>
                </ul>
                <p><em>Nota: Lo storico ti aiuta a monitorare i trend dei prezzi e a verificare la competitività.</em></p>
            </div>
        `,
        attachTo: {
            element: document.querySelector('#prezziacquisto')?.closest('.card'),
            on: 'bottom'
        },
        buttons: [
            {
                text: 'Fine tour',
                action: completeTourAndClose,
                classes: 'shepherd-button-secondary'
            },
            {
                text: 'Indietro',
                action: articoliTour.back
            },
            {
                text: 'Termina il tour',
                action: articoliTour.complete
            }
        ]
    });
}

function startArticoliTour() {
    if (!articoliTour) {
        initArticoliTour();
    }
    
    if (articoliTour) {
        articoliTour.start();
    }
}

function showTourCompleteMessage() {
    if (typeof swal !== 'undefined') {
        swal({
            title: 'Tour Completato',
            text: 'Hai completato il tour guidato del modulo Articoli. Ora sei pronto per utilizzare tutte le funzionalità!',
            type: 'success',
            confirmButtonText: 'Perfetto',
            confirmButtonClass: 'btn-success'
        });
    } else {
        alert('Tour Completato. Hai completato il tour guidato del modulo Articoli.');
    }
}

function completeTourAndClose() {
    localStorage.setItem('articoli-tour-completed', 'true');

    if (articoliTour) {
        articoliTour.cancel();
    }
}

function cancelTourAndClose() {
    localStorage.setItem('articoli-tour-completed', 'true');

    if (articoliTour) {
        articoliTour.cancel();
    }
}

function isTourCompleted() {
    return localStorage.getItem('articoli-tour-completed') === 'true';
}

function showRestartTourButton() {
    const restartButton = `
        <button type="button" class="btn btn-info btn-xs" onclick="startArticoliTour()" title="Riavvia il tour guidato">
            <i class="fa fa-question-circle"></i> Tour guidato
        </button>
    `;
    
    $('.content-header .btn-group').after(restartButton);
}

function initTour() {
    if ($('#edit-form').length > 0) {
        showRestartTourButton();

        if (!isTourCompleted()) {
            setTimeout(function() {
                startArticoliTour();
            }, 1000);
        }
    }
}

if (document.readyState === 'loading') {
    $(document).ready(initTour);
} else {
    initTour();
}
