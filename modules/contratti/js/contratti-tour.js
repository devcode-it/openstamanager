/**
 * Tour guidato del modulo Contratti
 * Utilizza Shepherd.js per guidare l'utente attraverso le funzionalità principali
 */

let contrattiTour = null;

function initContrattiTour() {
    if (typeof Shepherd === 'undefined') {
        console.error('Shepherd.js non è disponibile. Attendi il caricamento della libreria...');
        setTimeout(function() {
            if (typeof Shepherd === 'undefined') {
                console.error('Shepherd.js non è disponibile dopo il ritardo. Il tour non può essere inizializzato.');
                return;
            } else {
                console.log('Shepherd.js è ora disponibile. Inizializzazione del tour...');
                initContrattiTourInternal();
            }
        }, 500);
        return;
    }
    
    initContrattiTourInternal();
}

function initContrattiTourInternal() {
    contrattiTour = new Shepherd.Tour({
        tourName: 'contratti-tour',
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

    contrattiTour.on('complete', function() {
        localStorage.setItem('contratti-tour-completed', 'true');
        showTourCompleteMessage();
    });

    contrattiTour.on('cancel', function() {
        console.log('Tour contratti cancellato');
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
    contrattiTour.addStep({
        id: 'introduction',
        title: 'Benvenuto nel modulo Contratti',
        text: `
            <div class="tour-step">
                <p>Vuoi iniziare il tour guidato del modulo Contratti?</p>
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
                action: contrattiTour.cancel,
                classes: 'shepherd-button-secondary'
            },
            {
                text: 'Inizia il tour',
                action: contrattiTour.next,
                classes: 'shepherd-button-primary'
            }
        ]
    });

    const datiClienteElement = findElementBySelector('.card-title', 'Dati cliente');
    contrattiTour.addStep({
        id: 'dati-cliente',
        title: 'Dati Cliente',
        text: `
            <div class="tour-step">
                <ul>
                    <li><strong>Cliente:</strong> Anagrafica del cliente a cui è riferito il contratto</li>
                    <li><strong>Referente:</strong> Persona di contatto presso il cliente</li>
                    <li><strong>Sede:</strong> Indirizzo o sede specifica per il contratto</li>
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
                action: contrattiTour.back
            },
            {
                text: 'Avanti',
                action: contrattiTour.next
            }
        ]
    });

    const intestazioneElement = findElementBySelector('.card-title', 'Intestazione');
    contrattiTour.addStep({
        id: 'intestazione',
        title: 'Intestazione',
        text: `
            <div class="tour-step">
                <p>Inserisci numero, data e definisci la validità e lo stato del contratto.</p>
                <p><strong>Categorizzazione:</strong> Scegli le categorie, il tipo di attività predefinito e le condizioni di pagamento.</p>
                <p><strong>Rinnovo:</strong> Se il contratto è rinnovabile, puoi impostare il rinnovo automatico, i giorni di preavviso e le ore residue.</p>
                <p><strong>Dettagli:</strong> Aggiungi esclusioni, descrizione, note interne e condizioni generali.</p>
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
                action: contrattiTour.back
            },
            {
                text: 'Avanti',
                action: contrattiTour.next
            }
        ]
    });

    const costiElement = findElementBySelector('.card-title', 'Costi unitari');
    contrattiTour.addStep({
        id: 'costi-unitari',
        title: 'Costi Unitari',
        text: `
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
                action: contrattiTour.back
            },
            {
                text: 'Avanti',
                action: contrattiTour.next
            }
        ]
    });

    const righeElement = findElementBySelector('.card-title', 'Righe');
    contrattiTour.addStep({
        id: 'righe',
        title: 'Righe',
        text: `
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
                action: contrattiTour.back
            },
            {
                text: 'Avanti',
                action: contrattiTour.next
            }
        ]
    });

    const docCollegatiElement = findElementBySelector('#documenti-collegati-title');
    contrattiTour.addStep({
        id: 'documenti-collegati',
        title: 'Documenti Collegati',
        text: `
            <div class="tour-step">
                <ul>
                    <li><strong>Interventi:</strong> Interventi collegati al contratto</li>
                    <li><strong>DDT:</strong> Documenti di trasporto associati</li>
                    <li><strong>Preventivi:</strong> Preventivi da cui deriva il contratto</li>
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
                action: contrattiTour.back
            },
            {
                text: 'Termina il tour',
                action: contrattiTour.complete
            }
        ]
    });
}

function startContrattiTour() {
    if (!contrattiTour) {
        initContrattiTour();
    }
    
    if (contrattiTour) {
        contrattiTour.start();
    }
}

function showTourCompleteMessage() {
    if (typeof swal !== 'undefined') {
        swal({
            title: 'Tour Completato',
            text: 'Hai completato il tour guidato del modulo Contratti. Ora sei pronto per utilizzare tutte le funzionalità!',
            type: 'success',
            confirmButtonText: 'Perfetto',
            confirmButtonClass: 'btn-success'
        });
    } else {
        alert('Tour Completato. Hai completato il tour guidato del modulo Contratti.');
    }
}

function completeTourAndClose() {
    localStorage.setItem('contratti-tour-completed', 'true');

    if (contrattiTour) {
        contrattiTour.cancel();
    }
}

function isTourCompleted() {
    return localStorage.getItem('contratti-tour-completed') === 'true';
}

function showRestartTourButton() {
    const restartButton = `
        <button type="button" class="btn btn-info btn-xs" onclick="startContrattiTour()" title="Riavvia il tour guidato">
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
                startContrattiTour();
            }, 1000);
        }
    }
}

if (document.readyState === 'loading') {
    $(document).ready(initTour);
} else {
    initTour();
}
