/**
 * Tour guidato del modulo DDT
 * Utilizza Shepherd.js per guidare l'utente attraverso le funzionalità principali
 */

let ddtTour = null;

function initDdtTour() {
    if (typeof Shepherd === 'undefined') {
        console.error('Shepherd.js non è disponibile. Attendi il caricamento della libreria...');
        setTimeout(function() {
            if (typeof Shepherd === 'undefined') {
                console.error('Shepherd.js non è disponibile dopo il ritardo. Il tour non può essere inizializzato.');
                return;
            } else {
                initDdtTourInternal();
            }
        }, 500);
        return;
    }
    
    initDdtTourInternal();
}

function initDdtTourInternal() {
    ddtTour = new Shepherd.Tour({
        tourName: 'ddt-tour',
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

    ddtTour.on('complete', function() {
        localStorage.setItem('ddt-tour-completed', 'true');
        showTourCompleteMessage();
    });

    ddtTour.on('cancel', function() {
        localStorage.setItem('ddt-tour-completed', 'true');
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
    ddtTour.addStep({
        id: 'introduction',
        title: 'Benvenuto nel modulo DDT',
        text: `
            <div class="tour-step">
                <p>Vuoi iniziare il tour guidato del modulo DDT?</p>
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
                action: ddtTour.next,
                classes: 'shepherd-button-primary'
            }
        ]
    });

    const datiDestElement = findElementBySelector('.card-title', 'Dati destinatario');
    const datiMittElement = findElementBySelector('.card-title', 'Dati mittente');
    const datiSoggettoElement = datiDestElement || datiMittElement;

    ddtTour.addStep({
        id: 'dati-soggetto',
        title: datiDestElement ? 'Dati Destinatario' : 'Dati Mittente',
        text: `
            <div class="tour-step">
                <ul>
                    <li><strong>Anagrafica:</strong> Destinatario o mittente a cui è riferito il DDT</li>
                    <li><strong>Referente:</strong> Persona di contatto</li>
                    <li><strong>Sede:</strong> Indirizzo di spedizione o provenienza</li>
                </ul>
            </div>
        `,
        attachTo: datiSoggettoElement ? {
            element: datiSoggettoElement.closest('.card'),
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
                action: ddtTour.back
            },
            {
                text: 'Avanti',
                action: ddtTour.next
            }
        ]
    });

    const intestazioneElement = findElementBySelector('.card-title', 'Intestazione');
    ddtTour.addStep({
        id: 'intestazione',
        title: 'Intestazione',
        text: `
            <div class="tour-step">
                <p>Inserisci i numeri (primario e secondario) e la data del documento. Definisci le sedi di partenza e destinazione.</p>
                <p><strong>Condizioni:</strong> Scegli le condizioni di pagamento e applica un eventuale sconto in fattura.</p>
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
                action: ddtTour.back
            },
            {
                text: 'Avanti',
                action: ddtTour.next
            }
        ]
    });

    const datiDdtElement = findElementBySelector('.card-title', 'Dati ddt');
    ddtTour.addStep({
        id: 'dati-ddt',
        title: 'Dati DDT',
        text: `
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
        attachTo: datiDdtElement ? {
            element: datiDdtElement.closest('.card'),
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
                action: ddtTour.back
            },
            {
                text: 'Avanti',
                action: ddtTour.next
            }
        ]
    });

    const righeElement = findElementBySelector('.card-title', 'Righe');
    ddtTour.addStep({
        id: 'righe',
        title: 'Righe',
        text: `
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
                action: ddtTour.back
            },
            {
                text: 'Avanti',
                action: ddtTour.next
            }
        ]
    });

    const docCollegatiElement = findElementBySelector('#documenti-collegati-title');
    ddtTour.addStep({
        id: 'documenti-collegati',
        title: 'Documenti Collegati',
        text: `
            <div class="tour-step">
                <ul>
                    <li><strong>Ordini:</strong> Ordini a cui è associato il DDT</li>
                    <li><strong>Fatture:</strong> Fatture collegate al DDT</li>
                    <li><strong>Preventivi:</strong> Preventivi associati</li>
                </ul>
            </div>
        `,
        attachTo: docCollegatiElement ? {
            element: docCollegatiElement.closest('.card'),
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
                action: ddtTour.back
            },
            {
                text: 'Termina il tour',
                action: ddtTour.complete
            }
        ]
    });
}

function startDdtTour() {
    if (!ddtTour) {
        initDdtTour();
    }
    
    if (ddtTour) {
        ddtTour.start();
    }
}

function showTourCompleteMessage() {
    if (typeof swal !== 'undefined') {
        swal({
            title: 'Tour Completato',
            text: 'Hai completato il tour guidato del modulo DDT. Ora sei pronto per utilizzare tutte le funzionalità!',
            type: 'success',
            confirmButtonText: 'Perfetto',
            confirmButtonClass: 'btn-success'
        });
    } else {
        alert('Tour Completato. Hai completato il tour guidato del modulo DDT.');
    }
}

function completeTourAndClose() {
    localStorage.setItem('ddt-tour-completed', 'true');

    if (ddtTour) {
        ddtTour.cancel();
    }
}

function cancelTourAndClose() {
    localStorage.setItem('ddt-tour-completed', 'true');

    if (ddtTour) {
        ddtTour.cancel();
    }
}

function isTourCompleted() {
    return localStorage.getItem('ddt-tour-completed') === 'true';
}

function showRestartTourButton() {
    const restartButton = `
        <button type="button" class="btn btn-info btn-xs" onclick="startDdtTour()" title="Riavvia il tour guidato">
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
                startDdtTour();
            }, 1000);
        }
    }
}

if (document.readyState === 'loading') {
    $(document).ready(initTour);
} else {
    initTour();
}
