/**
 * Tour guidato del modulo Preventivi
 * Utilizza Shepherd.js per guidare l'utente attraverso le funzionalità principali
 */

let preventiviTour = null;

function initPreventiviTour() {
    if (typeof Shepherd === 'undefined') {
        console.error('Shepherd.js non è disponibile. Attendi il caricamento della libreria...');
        setTimeout(function() {
            if (typeof Shepherd === 'undefined') {
                console.error('Shepherd.js non è disponibile dopo il ritardo. Il tour non può essere inizializzato.');
                return;
            } else {
                initPreventiviTourInternal();
            }
        }, 500);
        return;
    }
    
    initPreventiviTourInternal();
}

function initPreventiviTourInternal() {
    preventiviTour = new Shepherd.Tour({
        tourName: 'preventivi-tour',
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

    preventiviTour.on('complete', function() {
        localStorage.setItem('preventivi-tour-completed', 'true');
        showTourCompleteMessage();
    });

    preventiviTour.on('cancel', function() {
        localStorage.setItem('preventivi-tour-completed', 'true');
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
    preventiviTour.addStep({
        id: 'introduction',
        title: 'Benvenuto nel modulo Preventivi',
        text: `
            <div class="tour-step">
                <p>Vuoi iniziare il tour guidato del modulo Preventivi?</p>
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
                action: preventiviTour.next,
                classes: 'shepherd-button-primary'
            }
        ]
    });

    const datiClienteElement = findElementBySelector('.card-title', 'Dati cliente');
    preventiviTour.addStep({
        id: 'dati-cliente',
        title: 'Dati Cliente',
        text: `
            <div class="tour-step">
                <ul>
                    <li><strong>Cliente:</strong> Anagrafica del cliente a cui è riferito il preventivo</li>
                    <li><strong>Referente:</strong> Persona di contatto presso il cliente</li>
                    <li><strong>Sede:</strong> Indirizzo o sede specifica per il preventivo</li>
                </ul>
            </div>
        `,
        attachTo: datiClienteElement ? {
            element: datiClienteElement.closest('.card'),
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
                action: preventiviTour.back
            },
            {
                text: 'Avanti',
                action: preventiviTour.next
            }
        ]
    });

    const intestazioneElement = findElementBySelector('.card-title', 'Intestazione');
    preventiviTour.addStep({
        id: 'intestazione',
        title: 'Intestazione',
        text: `
            <div class="tour-step">
                <p>Inserisci il numero, la data e il nome del preventivo. Definisci la validità e lo stato (bozza, accettato, rifiuto, ecc.)</p>
                <p><strong>Condizioni:</strong> Scegli il tipo di attività, le condizioni di pagamento, le banche e applica un eventuale sconto in fattura.</p>
                <p><strong>Dettagli:</strong> Aggiungi descrizione, esclusioni, tempi di consegna, garanzia e condizioni generali.</p>
            </div>
        `,
        attachTo: intestazioneElement ? {
            element: intestazioneElement.closest('.card'),
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
                action: preventiviTour.back
            },
            {
                text: 'Avanti',
                action: preventiviTour.next
            }
        ]
    });

    const righeElement = findElementBySelector('.card-title', 'Righe');
    preventiviTour.addStep({
        id: 'righe',
        title: 'Righe',
        text: `
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
        attachTo: righeElement ? {
            element: righeElement.closest('.card'),
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
                action: preventiviTour.back
            },
            {
                text: 'Termina il tour',
                action: preventiviTour.complete
            }
        ]
    });
}

function startPreventiviTour() {
    if (!preventiviTour) {
        initPreventiviTour();
    }
    
    if (preventiviTour) {
        preventiviTour.start();
    }
}

function showTourCompleteMessage() {
    if (typeof swal !== 'undefined') {
        swal({
            title: 'Tour Completato',
            text: 'Hai completato il tour guidato del modulo Preventivi. Ora sei pronto per utilizzare tutte le funzionalità!',
            type: 'success',
            confirmButtonText: 'Perfetto',
            confirmButtonClass: 'btn-success'
        });
    } else {
        alert('Tour Completato. Hai completato il tour guidato del modulo Preventivi.');
    }
}

function completeTourAndClose() {
    localStorage.setItem('preventivi-tour-completed', 'true');

    if (preventiviTour) {
        preventiviTour.cancel();
    }
}

function cancelTourAndClose() {
    localStorage.setItem('preventivi-tour-completed', 'true');

    if (preventiviTour) {
        preventiviTour.cancel();
    }
}

function isTourCompleted() {
    return localStorage.getItem('preventivi-tour-completed') === 'true';
}

function showRestartTourButton() {
    const restartButton = `
        <button type="button" class="btn btn-info btn-xs" onclick="startPreventiviTour()" title="Riavvia il tour guidato">
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
                startPreventiviTour();
            }, 1000);
        }
    }
}

if (document.readyState === 'loading') {
    $(document).ready(initTour);
} else {
    initTour();
}
