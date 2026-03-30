/**
 * Tour guidato del modulo Anagrafiche
 * Utilizza Shepherd.js per guidare l'utente attraverso le funzionalità principali
 */

// Variabile globale per il tour
let anagraficheTour = null;

/**
 * Inizializza il tour guidato delle anagrafiche
 */
function initAnagraficheTour() {
    // Verifica se Shepherd è disponibile
    if (typeof Shepherd === 'undefined') {
        console.error('Shepherd.js non è disponibile. Attendi il caricamento della libreria...');
        // Riprova dopo un breve ritardo
        setTimeout(function() {
            if (typeof Shepherd === 'undefined') {
                console.error('Shepherd.js non è disponibile dopo il ritardo. Il tour non può essere inizializzato.');
                return;
            } else {
                // Chiama la funzione di inizializzazione
                initAnagraficheTourInternal();
            }
        }, 500);
        return;
    }
    
    // Chiama la funzione di inizializzazione interna
    initAnagraficheTourInternal();
}

/**
 * Funzione interna per inizializzare il tour
 */
function initAnagraficheTourInternal() {
    // Crea una nuova istanza del tour
    anagraficheTour = new Shepherd.Tour({
        tourName: 'anagrafiche-tour',
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

    // Aggiungi i passaggi del tour
    addTourSteps();

    // Gestisci l'evento di completamento
    anagraficheTour.on('complete', function() {
        // Salva che il tour è stato completato
        localStorage.setItem('anagrafiche-tour-completed', 'true');
        showTourCompleteMessage();
    });

    // Gestisci l'evento di cancellazione
    anagraficheTour.on('cancel', function() {
        localStorage.setItem('anagrafiche-tour-completed', 'true');
    });
}

/**
 * Funzione helper per trovare un elemento usando jQuery e restituire l'elemento DOM
 * @param {string} selector - Selettore jQuery
 * @param {string} containsText - Testo da cercare nel titolo (opzionale)
 * @returns {HTMLElement|null} - Elemento DOM o null se non trovato
 */
function findElementBySelector(selector, containsText) {
    try {
        if (typeof jQuery === 'undefined' && typeof $ === 'undefined') {
            console.error('jQuery non è disponibile');
            return null;
        }

        const $ = typeof jQuery !== 'undefined' ? jQuery : window.$;
        
        if (containsText) {
            // Usa jQuery per trovare l'elemento con il testo specifico
            const $element = $(selector).filter(function() {
                return $(this).text().includes(containsText);
            });
            
            if ($element.length > 0) {
                return $element[0];
            }
            return null;
        } else {
            // Usa il selettore direttamente
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
    // Passo 1: Introduzione
    anagraficheTour.addStep({
        id: 'introduction',
        title: 'Benvenuto nel modulo Anagrafiche',
        text: `
            <div class="tour-step">
                <p>Vuoi iniziare il tour guidato del modulo Anagrafiche?</p>
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
                action: anagraficheTour.next,
                classes: 'shepherd-button-primary'
            }
        ]
    });

    // Passo 2: Dati anagrafici
    const datiAnagraficiElement = findElementBySelector('.card-primary .card-title', 'Dati anagrafici');
    anagraficheTour.addStep({
        id: 'dati-anagrafici',
        title: 'Dati Anagrafici',
        text: `
            <div class="tour-step">
                <ul>
                    <li><strong>Denominazione:</strong> Nome dell'azienda o identificativo principale</li>
                    <li><strong>Partita IVA:</strong> Codice fiscale/partita IVA per la fatturazione</li>
                    <li><strong>Tipologia:</strong> Azienda, Ente pubblico o Privato</li>
                    <li><strong>Codice anagrafica:</strong> Codice univoco per identificare l'anagrafica</li>
                </ul>
                <p><strong>Nota:</strong> Compila sempre questi campi con cura, poiché vengono utilizzati in tutta l'applicazione.</p>
            </div>
        `,
        attachTo: datiAnagraficiElement ? {
            element: datiAnagraficiElement.closest('.card'),
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
                action: anagraficheTour.back
            },
            {
                text: 'Avanti',
                action: anagraficheTour.next
            }
        ]
    });

    // Passo 3: Sede legale
    const sedeLegaleElement = findElementBySelector('.card-primary .card-title', 'Sede legale');
    anagraficheTour.addStep({
        id: 'sede-legale',
        title: 'Sede Legale',
        text: `
            <div class="tour-step">
                <ul>
                    <li><strong>Indirizzo e Civico:</strong> Via e numero civico</li>
                    <li><strong>C.A.P. e Città:</strong> Codice postale e località</li>
                    <li><strong>Provincia e Nazione:</strong> Provincia e paese</li>
                    <li><strong>Contatti:</strong> Telefono, cellulare, email e fax</li>
                </ul>
            </div>
        `,
        attachTo: sedeLegaleElement ? {
            element: sedeLegaleElement.closest('.card'),
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
                action: anagraficheTour.back
            },
            {
                text: 'Avanti',
                action: anagraficheTour.next
            }
        ]
    });

    // Passo 4: Informazioni per tipo
    const infoTipoElement = findElementBySelector('.card-primary .card-title', 'Informazioni per tipo di anagrafica');
    anagraficheTour.addStep({
        id: 'info-tipo',
        title: 'Informazioni per Tipo',
        text: `
            <div class="tour-step">
                <ul>
                    <li><strong>Cliente:</strong> Pagamenti, banche, listini, agente, ecc.</li>
                    <li><strong>Fornitore:</strong> Pagamenti, banche, iva predefinita, ecc.</li>
                    <li><strong>Tecnico:</strong> Colore per il calendario attività</li>
                </ul>
                <p><strong>Nota:</strong> I tab disponibili cambiano in base ai tipi di anagrafica selezionati.</p>
            </div>
        `,
        attachTo: infoTipoElement ? {
            element: infoTipoElement.closest('.card'),
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
                action: anagraficheTour.back
            },
            {
                text: 'Avanti',
                action: anagraficheTour.next
            }
        ]
    });

    // Passo 5: Informazioni aggiuntive
    const infoAggiuntiveElement = findElementBySelector('.card-primary .card-title', 'Informazioni aggiuntive');
    anagraficheTour.addStep({
        id: 'info-aggiuntive',
        title: 'Informazioni Aggiuntive',
        text: `
            <div class="tour-step">
                <ul>
                    <li><strong>Registro imprese:</strong> Numero iscrizione, codice REA</li>
                    <li><strong>Settore merceologico:</strong> Settore di attività</li>
                    <li><strong>Marche trattate:</strong> Brand commercializzati</li>
                    <li><strong>Note:</strong> Informazioni aggiuntive e commenti</li>
                </ul>
            </div>
        `,
        attachTo: infoAggiuntiveElement ? {
            element: infoAggiuntiveElement.closest('.card'),
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
                action: anagraficheTour.back
            },
            {
                text: 'Avanti',
                action: anagraficheTour.next
            }
        ]
    });

    // Passo 6: Pulsanti azione
    const pulsantiAzioneElement = findElementBySelector('.content-header .btn-group, .content-header .btn-primary');
    anagraficheTour.addStep({
        id: 'pulsanti-azione',
        title: 'Pulsanti di Azione',
        text: `
            <div class="tour-step">
                <ul>
                    <li><strong>Crea...:</strong> Aggiungi una nuova anagrafica</li>
                    <li><strong>Firma GDPR:</strong> Gestisci la firma per il trattamento dei dati personali</li>
                    <li><strong>Salva:</strong> Salva le modifiche apportate all'anagrafica</li>
                </ul>
                <p><strong>Nota:</strong> I pulsanti disponibili cambiano in base al tipo di anagrafica (cliente, fornitore, tecnico, ecc.).</p>
            </div>
        `,
        attachTo: pulsantiAzioneElement ? {
            element: pulsantiAzioneElement,
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
                action: anagraficheTour.back
            },
            {
                text: 'Avanti',
                action: anagraficheTour.next
            }
        ]
    });

    // Passo 7: Plugin
    const pluginElement = findElementBySelector('.control-sidebar-button');
    anagraficheTour.addStep({
        id: 'plugin',
        title: 'Plugin',
        text: `
            <div class="tour-step">
                <ul>
                    <li><strong>Sedi aggiuntive:</strong> Gestisci sedi secondarie dell'anagrafica</li>
                    <li><strong>Referenti:</strong> Aggiungi persone di riferimento</li>
                    <li><strong>Impianti del cliente:</strong> Visualizza gli impianti installati</li>
                    <li><strong>Contratti del cliente:</strong> Gestisci i contratti attivi</li>
                    <li><strong>Note interne:</strong> Aggiungi note private sull'anagrafica</li>
                    <li><strong>Checklist:</strong> Gestisci checklist e task</li>
                </ul>
                <p><strong>Nota:</strong> Clicca su un plugin per accedere alle funzionalità specifiche.</p>
            </div>
        `,
        attachTo: pluginElement ? {
            element: pluginElement,
            on: 'left'
        } : null,
        buttons: [
            {
                text: 'Fine tour',
                action: completeTourAndClose,
                classes: 'shepherd-button-secondary'
            },
            {
                text: 'Indietro',
                action: anagraficheTour.back
            },
            {
                text: 'Termina il tour',
                action: anagraficheTour.complete
            }
        ]
    });
}

/**
 * Avvia il tour guidato
 */
function startAnagraficheTour() {
    if (!anagraficheTour) {
        initAnagraficheTour();
    }
    
    if (anagraficheTour) {
        anagraficheTour.start();
    }
}

/**
 * Mostra un messaggio di completamento del tour
 */
function showTourCompleteMessage() {
    if (typeof swal !== 'undefined') {
        swal({
            title: 'Tour Completato',
            text: 'Hai completato il tour guidato del modulo Anagrafiche. Ora sei pronto per utilizzare tutte le funzionalità!',
            type: 'success',
            confirmButtonText: 'Perfetto',
            confirmButtonClass: 'btn-success'
        });
    } else {
        alert('Tour Completato. Hai completato il tour guidato del modulo Anagrafiche.');
    }
}

/**
 * Completa il tour e salva lo stato (usato per il pulsante "Fine tour")
 */
function completeTourAndClose() {
    // Salva che il tour è stato completato
    localStorage.setItem('anagrafiche-tour-completed', 'true');

    // Cancella il tour senza mostrare popup
    if (anagraficheTour) {
        anagraficheTour.cancel();
    }
}

/**
 * Cancella il tour e salva lo stato (usato per il pulsante "No")
 */
function cancelTourAndClose() {
    localStorage.setItem('anagrafiche-tour-completed', 'true');

    if (anagraficheTour) {
        anagraficheTour.cancel();
    }
}

/**
 * Verifica se il tour è già stato completato
 */
function isTourCompleted() {
    return localStorage.getItem('anagrafiche-tour-completed') === 'true';
}

/**
 * Mostra il pulsante per riavviare il tour
 */
function showRestartTourButton() {
    // Aggiungi un pulsante nella barra degli strumenti
    const restartButton = `
        <button type="button" class="btn btn-info btn-xs" onclick="startAnagraficheTour()" title="Riavvia il tour guidato">
            <i class="fa fa-question-circle"></i> Tour guidato
        </button>
    `;
    
    // Inserisci il pulsante dopo i pulsanti esistenti
    $('.content-header .btn-group').after(restartButton);
}

// Funzione per inizializzare il tour
function initTour() {
    // Verifica se siamo nella pagina di modifica delle anagrafiche
    if ($('#edit-form').length > 0) {
        // Mostra il pulsante per riavviare il tour
        showRestartTourButton();

        // Se il tour non è mai stato completato, avvialo automaticamente dopo un breve ritardo
        if (!isTourCompleted()) {
            setTimeout(function() {
                // Avvia il tour automaticamente
                startAnagraficheTour();
            }, 1000);
        }
    }
}

// Inizializza quando il documento è pronto
if (document.readyState === 'loading') {
    $(document).ready(initTour);
} else {
    // Il documento è già caricato, esegui direttamente
    initTour();
}
