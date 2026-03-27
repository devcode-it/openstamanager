/**
 * Tour guidato del modulo Interventi
 * Utilizza Shepherd.js per guidare l'utente attraverso le funzionalità principali
 */

// Variabile globale per il tour
let interventiTour = null;

/**
 * Inizializza il tour guidato degli interventi
 */
function initInterventiTour() {
    if (typeof Shepherd === 'undefined') {
        console.error('Shepherd.js non è disponibile. Attendi il caricamento della libreria...');
        setTimeout(function() {
            if (typeof Shepherd === 'undefined') {
                console.error('Shepherd.js non è disponibile dopo il ritardo. Il tour non può essere inizializzato.');
                return;
            } else {
                console.log('Shepherd.js è ora disponibile. Inizializzazione del tour...');
                initInterventiTourInternal();
            }
        }, 500);
        return;
    }
    
    initInterventiTourInternal();
}

/**
 * Funzione interna per inizializzare il tour
 */
function initInterventiTourInternal() {
    interventiTour = new Shepherd.Tour({
        tourName: 'interventi-tour',
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

    interventiTour.on('complete', function() {
        localStorage.setItem('interventi-tour-completed', 'true');
        showTourCompleteMessage();
    });

    interventiTour.on('cancel', function() {
        console.log('Tour interventi cancellato');
    });
}

/**
 * Funzione helper per trovare un elemento usando jQuery e restituire l'elemento DOM
 */
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

/**
 * Aggiunge i passaggi del tour
 */
function addTourSteps() {
    interventiTour.addStep({
        id: 'introduction',
        title: 'Benvenuto nel modulo Interventi',
        text: `
            <div class="tour-step">
                <p>Vuoi iniziare il tour guidato del modulo Interventi?</p>
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
                action: interventiTour.cancel,
                classes: 'shepherd-button-secondary'
            },
            {
                text: 'Inizia il tour',
                action: interventiTour.next,
                classes: 'shepherd-button-primary'
            }
        ]
    });

    const datiClienteElement = findElementBySelector('.card-title', 'Dati cliente');
    interventiTour.addStep({
        id: 'dati-cliente',
        title: 'Dati Cliente',
        text: `
            <div class="tour-step">
                <ul>
                    <li><strong>Cliente:</strong> Anagrafica del cliente a cui è riferito l'intervento</li>
                    <li><strong>Referente:</strong> Persona di contatto presso il cliente</li>
                    <li><strong>Sede:</strong> Indirizzo o sede specifica per l'intervento</li>
                </ul>
            <p>Per visualizzare questi dati è necessario espandere la sezione cliccando sul pulsante <i class="fa fa-plus"></i> in alto a destra.</p>
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
                action: interventiTour.back
            },
            {
                text: 'Avanti',
                action: interventiTour.next
            }
        ]
    });

    const datiInterventoElement = findElementBySelector('.card-title', 'Dati intervento');
    interventiTour.addStep({
        id: 'dati-intervento',
        title: 'Dati Intervento',
        text: `
            <div class="tour-step">
                <p>Qui puoi definire le informazioni essenziali dell'attività.</p>
                <p><strong>Dati temporali:</strong> Inserisci la data e ora di richiesta e scadenza, quindi assegna un tipo di attività (es. manutenzione, installazione) e definisci lo stato corrente (es. in programmazione, in corso, completato).</p>
                <p><strong>Tecnici:</strong> Seleziona i tecnici che dovranno lavorare all'intervento. Puoi assegnarne più di uno.</p>
                <p><strong>Collegamenti opzionali:</strong> Collega l'attività a un preventivo, un contratto o un ordine cliente per tenere traccia della provenienza del lavoro.</p>
            </div>
        `,
        attachTo: datiInterventoElement ? {
            element: datiInterventoElement.closest('.card'),
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
                action: interventiTour.back
            },
            {
                text: 'Avanti',
                action: interventiTour.next
            }
        ]
    });

    const sessioniElement = findElementBySelector('.card-title', 'Sessioni di lavoro');
    interventiTour.addStep({
        id: 'sessioni-lavoro',
        title: 'Sessioni di Lavoro',
        text: `
            <div class="tour-step">
                <ul>
                    <li><strong>Aggiungi sessione:</strong> Registra il lavoro svolto da ciascun tecnico</li>
                    <li><strong>Orari:</strong> Orario di inizio e fine di ogni sessione</li>
                    <li><strong>Note:</strong> Note sul lavoro svolto nella sessione</li>
                    <li><strong>Totale ore:</strong> Riepilogo delle ore lavorate</li>
                </ul>
            </div>
        `,
        attachTo: sessioniElement ? {
            element: sessioniElement.closest('.card'),
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
                action: interventiTour.back
            },
            {
                text: 'Avanti',
                action: interventiTour.next
            }
        ]
    });

    const righeElement = findElementBySelector('.card-title', 'Righe');
    interventiTour.addStep({
        id: 'righe',
        title: 'Righe',
        text: `
            <div class="tour-step">
                <ul>
                    <li><strong>Articoli:</strong> Articoli utilizzati nell'intervento</li>
                    <li><strong>Descrizione:</strong> Voci di spesa o descrizioni libere</li>
                    <li><strong>Quantità e Prezzo:</strong> Quantità e prezzo unitario di ogni riga</li>
                    <li><strong>IVA:</strong> Aliquota IVA applicata</li>
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
                action: interventiTour.back
            },
            {
                text: 'Avanti',
                action: interventiTour.next
            }
        ]
    });

    const costiElement = findElementBySelector('.card-title', 'Costi totali');
    interventiTour.addStep({
        id: 'costi-totali',
        title: 'Costi Totali',
        text: `
            <div class="tour-step">
                <ul>
                    <li><strong>Totale imponibile:</strong> Somma delle righe senza IVA</li>
                    <li><strong>Totale IVA:</strong> Somma delle imposte</li>
                    <li><strong>Totale:</strong> Importo totale dell'intervento</li>
                </ul>
            </div>
        `,
        attachTo: costiElement ? {
            element: costiElement.closest('.card'),
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
                action: interventiTour.back
            },
            {
                text: 'Avanti',
                action: interventiTour.next
            }
        ]
    });

    const docCollegatiElement = findElementBySelector('#documenti-collegati-title');
    interventiTour.addStep({
        id: 'documenti-collegati',
        title: 'Documenti Collegati',
        text: `
            <div class="tour-step">
                <ul>
                    <li><strong>Preventivi:</strong> Preventivi collegati all'intervento</li>
                    <li><strong>Contratti:</strong> Contratti da cui deriva l'intervento</li>
                    <li><strong>DDT:</strong> Documenti di trasporto associati</li>
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
                action: interventiTour.back
            },
            {
                text: 'Termina il tour',
                action: interventiTour.complete
            }
        ]
    });
}

/**
 * Avvia il tour guidato
 */
function startInterventiTour() {
    if (!interventiTour) {
        initInterventiTour();
    }
    
    if (interventiTour) {
        interventiTour.start();
    }
}

/**
 * Mostra un messaggio di completamento del tour
 */
function showTourCompleteMessage() {
    if (typeof swal !== 'undefined') {
        swal({
            title: 'Tour Completato',
            text: 'Hai completato il tour guidato del modulo Interventi. Ora sei pronto per utilizzare tutte le funzionalità!',
            type: 'success',
            confirmButtonText: 'Perfetto',
            confirmButtonClass: 'btn-success'
        });
    } else {
        alert('Tour Completato. Hai completato il tour guidato del modulo Interventi.');
    }
}

/**
 * Completa il tour e salva lo stato
 */
function completeTourAndClose() {
    localStorage.setItem('interventi-tour-completed', 'true');

    if (interventiTour) {
        interventiTour.cancel();
    }
}

/**
 * Verifica se il tour è già stato completato
 */
function isTourCompleted() {
    return localStorage.getItem('interventi-tour-completed') === 'true';
}

/**
 * Mostra il pulsante per riavviare il tour
 */
function showRestartTourButton() {
    const restartButton = `
        <button type="button" class="btn btn-info btn-xs" onclick="startInterventiTour()" title="Riavvia il tour guidato">
            <i class="fa fa-question-circle"></i> Tour guidato
        </button>
    `;
    
    $('.content-header .btn-group').after(restartButton);
}

/**
 * Inizializza il tour
 */
function initTour() {
    if ($('#edit-form').length > 0) {
        showRestartTourButton();

        if (!isTourCompleted()) {
            setTimeout(function() {
                startInterventiTour();
            }, 1000);
        }
    }
}

if (document.readyState === 'loading') {
    $(document).ready(initTour);
} else {
    initTour();
}
