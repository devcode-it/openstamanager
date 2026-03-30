/**
 * Tour guidato del modulo Ordini
 * Utilizza Shepherd.js per guidare l'utente attraverso le funzionalità principali
 */

let ordiniTour = null;

function initOrdiniTour() {
    if (typeof Shepherd === 'undefined') {
        console.error('Shepherd.js non è disponibile. Attendi il caricamento della libreria...');
        setTimeout(function() {
            if (typeof Shepherd === 'undefined') {
                console.error('Shepherd.js non è disponibile dopo il ritardo. Il tour non può essere inizializzato.');
                return;
            } else {
                initOrdiniTourInternal();
            }
        }, 500);
        return;
    }
    
    initOrdiniTourInternal();
}

function initOrdiniTourInternal() {
    ordiniTour = new Shepherd.Tour({
        tourName: 'ordini-tour',
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

    ordiniTour.on('complete', function() {
        localStorage.setItem('ordini-tour-completed', 'true');
        showTourCompleteMessage();
    });

    ordiniTour.on('cancel', function() {
        localStorage.setItem('ordini-tour-completed', 'true');
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
    ordiniTour.addStep({
        id: 'introduction',
        title: 'Benvenuto nel modulo Ordini',
        text: `
            <div class="tour-step">
                <p>Vuoi iniziare il tour guidato del modulo Ordini?</p>
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
                action: ordiniTour.next,
                classes: 'shepherd-button-primary'
            }
        ]
    });

    const datiClienteElement = findElementBySelector('.card-title', 'Dati cliente');
    const datiFornitoreElement = findElementBySelector('.card-title', 'Dati fornitore');
    const datiDestElement = datiClienteElement || datiFornitoreElement;

    ordiniTour.addStep({
        id: 'dati-soggetto',
        title: datiClienteElement ? 'Dati Cliente' : 'Dati Fornitore',
        text: `
            <div class="tour-step">
                <ul>
                    <li><strong>Anagrafica:</strong> Cliente o fornitore a cui è riferito l'ordine</li>
                    <li><strong>Referente:</strong> Persona di contatto</li>
                    <li><strong>Sede:</strong> Indirizzo di spedizione o sede specifica</li>
                </ul>
            </div>
        `,
        attachTo: datiDestElement ? {
            element: datiDestElement.closest('.card'),
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
                action: ordiniTour.back
            },
            {
                text: 'Avanti',
                action: ordiniTour.next
            }
        ]
    });

    const intestazioneElement = findElementBySelector('.card-title', 'Intestazione');
    const isVendita = datiClienteElement !== null;
    ordiniTour.addStep({
        id: 'intestazione',
        title: 'Intestazione',
        text: `
            <div class="tour-step">
                <p>Inserisci la data e definisci le sedi di partenza e destinazione della merce.</p>
                <p><strong>Spedizione:</strong> Scegli il tipo di spedizione, il vettore e il porto (condizione del trasporto). Aggiungi eventuali condizioni generali e note.</p>
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
                action: ordiniTour.back
            },
            {
                text: 'Avanti',
                action: ordiniTour.next
            }
        ]
    });

    const righeElement = findElementBySelector('.card-title', 'Righe');
    ordiniTour.addStep({
        id: 'righe',
        title: 'Righe',
        text: `
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
                action: ordiniTour.back
            },
            {
                text: 'Avanti',
                action: ordiniTour.next
            }
        ]
    });

    const docCollegatiElement = findElementBySelector('#documenti-collegati-title');
    ordiniTour.addStep({
        id: 'documenti-collegati',
        title: 'Documenti Collegati',
        text: `
            <div class="tour-step">
                <ul>
                    <li><strong>Preventivi:</strong> Preventivi collegati all'ordine</li>
                    <li><strong>DDT:</strong> Documenti di trasporto associati</li>
                    <li><strong>Fatture:</strong> Fatture emesse basate sull'ordine</li>
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
                action: ordiniTour.back
            },
            {
                text: 'Termina il tour',
                action: ordiniTour.complete
            }
        ]
    });
}

function startOrdiniTour() {
    if (!ordiniTour) {
        initOrdiniTour();
    }
    
    if (ordiniTour) {
        ordiniTour.start();
    }
}

function showTourCompleteMessage() {
    if (typeof swal !== 'undefined') {
        swal({
            title: 'Tour Completato',
            text: 'Hai completato il tour guidato del modulo Ordini. Ora sei pronto per utilizzare tutte le funzionalità!',
            type: 'success',
            confirmButtonText: 'Perfetto',
            confirmButtonClass: 'btn-success'
        });
    } else {
        alert('Tour Completato. Hai completato il tour guidato del modulo Ordini.');
    }
}

function completeTourAndClose() {
    localStorage.setItem('ordini-tour-completed', 'true');

    if (ordiniTour) {
        ordiniTour.cancel();
    }
}

function cancelTourAndClose() {
    localStorage.setItem('ordini-tour-completed', 'true');

    if (ordiniTour) {
        ordiniTour.cancel();
    }
}

function isTourCompleted() {
    return localStorage.getItem('ordini-tour-completed') === 'true';
}

function showRestartTourButton() {
    const restartButton = `
        <button type="button" class="btn btn-info btn-xs" onclick="startOrdiniTour()" title="Riavvia il tour guidato">
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
                startOrdiniTour();
            }, 1000);
        }
    }
}

if (document.readyState === 'loading') {
    $(document).ready(initTour);
} else {
    initTour();
}
