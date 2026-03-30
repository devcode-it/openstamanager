/**
 * Tour guidato del modulo Impianti
 * Utilizza Shepherd.js per guidare l'utente attraverso le funzionalità principali
 */

let impiantiTour = null;

function initImpiantiTour() {
    if (typeof Shepherd === 'undefined') {
        console.error('Shepherd.js non è disponibile. Attendi il caricamento della libreria...');
        setTimeout(function() {
            if (typeof Shepherd === 'undefined') {
                console.error('Shepherd.js non è disponibile dopo il ritardo. Il tour non può essere inizializzato.');
                return;
            } else {
                initImpiantiTourInternal();
            }
        }, 500);
        return;
    }
    
    initImpiantiTourInternal();
}

function initImpiantiTourInternal() {
    impiantiTour = new Shepherd.Tour({
        tourName: 'impianti-tour',
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

    impiantiTour.on('complete', function() {
        localStorage.setItem('impianti-tour-completed', 'true');
        showTourCompleteMessage();
    });

    impiantiTour.on('cancel', function() {
        localStorage.setItem('impianti-tour-completed', 'true');
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
    impiantiTour.addStep({
        id: 'introduction',
        title: 'Benvenuto nel modulo Impianti',
        text: `
            <div class="tour-step">
                <p>Vuoi iniziare il tour guidato del modulo Impianti?</p>
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
                action: impiantiTour.next,
                classes: 'shepherd-button-primary'
            }
        ]
    });

    const datiImpiantoElement = findElementBySelector('.card-title', 'Dati impianto');
    impiantiTour.addStep({
        id: 'dati-impianto',
        title: 'Dati Impianto',
        text: `
            <div class="tour-step">
                <p>Carica una foto dell'impianto e inserisci matricola, nome e data di installazione. Collega il cliente e specifica sede e tecnico.</p>
                <p><strong>Prodotto:</strong> Definisci marca, modello, proprietario e stato. Seleziona o aggiungi una categoria.</p>
            </div>
        `,
        attachTo: datiImpiantoElement ? {
            element: datiImpiantoElement.closest('.card'),
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
                action: impiantiTour.back
            },
            {
                text: 'Avanti',
                action: impiantiTour.next
            }
        ]
    });

    impiantiTour.addStep({
        id: 'dettagli-impianto',
        title: 'Dettagli Impianto',
        text: `
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
        attachTo: datiImpiantoElement ? {
            element: datiImpiantoElement.closest('.card'),
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
                action: impiantiTour.back
            },
            {
                text: 'Avanti',
                action: impiantiTour.next
            }
        ]
    });
}

function startImpiantiTour() {
    if (!impiantiTour) {
        initImpiantiTour();
    }
    
    if (impiantiTour) {
        impiantiTour.start();
    }
}

function showTourCompleteMessage() {
    if (typeof swal !== 'undefined') {
        swal({
            title: 'Tour Completato',
            text: 'Hai completato il tour guidato del modulo Impianti. Ora sei pronto per utilizzare tutte le funzionalità!',
            type: 'success',
            confirmButtonText: 'Perfetto',
            confirmButtonClass: 'btn-success'
        });
    } else {
        alert('Tour Completato. Hai completato il tour guidato del modulo Impianti.');
    }
}

function completeTourAndClose() {
    localStorage.setItem('impianti-tour-completed', 'true');

    if (impiantiTour) {
        impiantiTour.cancel();
    }
}

function cancelTourAndClose() {
    localStorage.setItem('impianti-tour-completed', 'true');

    if (impiantiTour) {
        impiantiTour.cancel();
    }
}

function isTourCompleted() {
    return localStorage.getItem('impianti-tour-completed') === 'true';
}

function showRestartTourButton() {
    const restartButton = `
        <button type="button" class="btn btn-info btn-xs" onclick="startImpiantiTour()" title="Riavvia il tour guidato">
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
                startImpiantiTour();
            }, 1000);
        }
    }
}

if (document.readyState === 'loading') {
    $(document).ready(initTour);
} else {
    initTour();
}
