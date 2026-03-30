/**
 * Tour guidato del modulo Fatture
 * Utilizza Shepherd.js per guidare l'utente attraverso le funzionalità principali
 */

let fattureTour = null;

function initFattureTour() {
    if (typeof Shepherd === 'undefined') {
        console.error('Shepherd.js non è disponibile. Attendi il caricamento della libreria...');
        setTimeout(function() {
            if (typeof Shepherd === 'undefined') {
                console.error('Shepherd.js non è disponibile dopo il ritardo. Il tour non può essere inizializzato.');
                return;
            } else {
                initFattureTourInternal();
            }
        }, 500);
        return;
    }
    
    initFattureTourInternal();
}

function initFattureTourInternal() {
    fattureTour = new Shepherd.Tour({
        tourName: 'fatture-tour',
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

    fattureTour.on('complete', function() {
        localStorage.setItem('fatture-tour-completed', 'true');
        showTourCompleteMessage();
    });

    fattureTour.on('cancel', function() {
        localStorage.setItem('fatture-tour-completed', 'true');
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
    fattureTour.addStep({
        id: 'introduction',
        title: 'Benvenuto nel modulo Fatture',
        text: `
            <div class="tour-step">
                <p>Vuoi iniziare il tour guidato del modulo Fatture?</p>
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
                action: fattureTour.next,
                classes: 'shepherd-button-primary'
            }
        ]
    });

    const datiClienteElement = findElementBySelector('.card-title', 'Dati cliente');
    const datiFornitoreElement = findElementBySelector('.card-title', 'Dati fornitore');
    const datiDestElement = datiClienteElement || datiFornitoreElement;

    fattureTour.addStep({
        id: 'dati-soggetto',
        title: datiClienteElement ? 'Dati Cliente' : 'Dati Fornitore',
        text: `
            <div class="tour-step">
                <p>Seleziona l'anagrafica ${datiClienteElement ? 'cliente' : 'fornitore'} a cui è riferita la fattura${datiClienteElement ? '. Puoi indicare anche un agente di riferimento.' : ''}</p>
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
                action: fattureTour.back
            },
            {
                text: 'Avanti',
                action: fattureTour.next
            }
        ]
    });

    const intestazioneElement = findElementBySelector('.card-title', 'Intestazione');
    const isVendita = datiClienteElement !== null;
    fattureTour.addStep({
        id: 'intestazione',
        title: 'Intestazione',
        text: `
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
                action: fattureTour.back
            },
            {
                text: 'Avanti',
                action: fattureTour.next
            }
        ]
    });

    const fattAccElement = findElementBySelector('.card-title', 'Dati Fattura accompagnatoria');
    if (fattAccElement) {
        fattureTour.addStep({
            id: 'fattura-accompagnatoria',
            title: 'Dati Fattura Accompagnatoria',
            text: `
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
            attachTo: {
                element: fattAccElement.closest('.card'),
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
                    action: fattureTour.back
                },
                {
                    text: 'Avanti',
                    action: fattureTour.next
                }
            ]
        });
    }

    const righeElement = findElementBySelector('.card-title', 'Righe');
    fattureTour.addStep({
        id: 'righe',
        title: 'Righe',
        text: `
            <div class="tour-step">
                <p>Aggiungi articoli dal magazzino, righe descrizione o collegati da altri documenti. Per ogni riga definisci quantità, prezzo, IVA e sconto.</p>
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
                action: fattureTour.back
            },
            {
                text: 'Avanti',
                action: fattureTour.next
            }
        ]
    });

    const docCollegatiElement = findElementBySelector('#documenti-collegati-title');
    fattureTour.addStep({
        id: 'documenti-collegati',
        title: 'Documenti Collegati',
        text: `
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
                action: fattureTour.back
            },
            {
                text: 'Termina il tour',
                action: fattureTour.complete
            }
        ]
    });
}

function startFattureTour() {
    if (!fattureTour) {
        initFattureTour();
    }
    
    if (fattureTour) {
        fattureTour.start();
    }
}

function showTourCompleteMessage() {
    if (typeof swal !== 'undefined') {
        swal({
            title: 'Tour Completato',
            text: 'Hai completato il tour guidato del modulo Fatture. Ora sei pronto per utilizzare tutte le funzionalità!',
            type: 'success',
            confirmButtonText: 'Perfetto',
            confirmButtonClass: 'btn-success'
        });
    } else {
        alert('Tour Completato. Hai completato il tour guidato del modulo Fatture.');
    }
}

function completeTourAndClose() {
    localStorage.setItem('fatture-tour-completed', 'true');

    if (fattureTour) {
        fattureTour.cancel();
    }
}

function cancelTourAndClose() {
    localStorage.setItem('fatture-tour-completed', 'true');

    if (fattureTour) {
        fattureTour.cancel();
    }
}

function isTourCompleted() {
    return localStorage.getItem('fatture-tour-completed') === 'true';
}

function showRestartTourButton() {
    const restartButton = `
        <button type="button" class="btn btn-info btn-xs" onclick="startFattureTour()" title="Riavvia il tour guidato">
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
                startFattureTour();
            }, 1000);
        }
    }
}

if (document.readyState === 'loading') {
    $(document).ready(initTour);
} else {
    initTour();
}
