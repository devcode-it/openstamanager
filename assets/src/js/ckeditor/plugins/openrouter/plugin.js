CKEDITOR.plugins.add('openrouter', {
    icons: 'openrouter',
    init: function(editor) {
        // Registra il dialog
        CKEDITOR.dialog.add('openrouterDialog', function(editor) {
            return {
                title: 'Mistral AI',
                minWidth: 400,
                minHeight: 100,
                onShow: function() {
                    // Ottieni il testo selezionato ogni volta che il dialog viene mostrato
                    var selectedText = editor.getSelection().getSelectedText();

                    // Aggiorna l'elemento HTML con il testo selezionato
                    var selectedTextElement = this.getContentElement('tab1', 'selectedText');
                    if (selectedTextElement) {
                        if (selectedText) {
                            selectedTextElement.getElement().setHtml('<div style="margin-bottom: 10px;"><span style="font-style: italic;">Testo selezionato: </span><span style="font-style: italic; color: #666;">' + selectedText + '</span></div>');
                            selectedTextElement.getElement().show();
                        } else {
                            selectedTextElement.getElement().hide();
                        }
                    }
                },
                contents: [
                    {
                        id: 'tab1',
                        label: 'Richiesta',
                        elements: [
                            // Elemento per il testo selezionato (inizialmente vuoto)
                            {
                                type: 'html',
                                id: 'selectedText',
                                html: '<div style="margin-bottom: 10px; display: none;"></div>'
                            },
                            {
                                type: 'text',
                                id: 'prompt',
                                label: 'Genera testo con Mistral AI',
                                validate: CKEDITOR.dialog.validate.notEmpty('Il campo richiesta non può essere vuoto')
                            }
                        ]
                    }
                ],
                onOk: function() {
                    var dialog = this;
                    var prompt = dialog.getValueOf('tab1', 'prompt');

                    // Verifica se la variabile globals esiste e se contiene openRouterApiKey
                    if (typeof globals === 'undefined' || !globals.openRouterApiKey) {
                        if (typeof toastr !== 'undefined') {
                            toastr.error('OpenRouter API Key non configurata. Configurala nelle impostazioni.');
                        } else {
                            alert('OpenRouter API Key non configurata. Configurala nelle impostazioni.');
                        }
                        return false;
                    }

                    // Mostra una rotellina di caricamento direttamente nel dialog corrente
                    var dialog = this;
                    var dialogElement = dialog.getElement();

                    // Nascondi tutti gli elementi del dialog tranne i pulsanti
                    var dialogContents = dialogElement.findOne('.cke_dialog_contents');
                    if (dialogContents) {
                        dialogContents.setStyle('display', 'none');
                    }

                    // Crea un div con la rotellina di caricamento e lo stile CSS
                    var loadingDiv = document.createElement('div');
                    loadingDiv.className = 'loading-container';
                    loadingDiv.style.cssText = 'text-align: center; padding: 20px;';

                    // Aggiungi il contenuto HTML con lo stile CSS inline
                    loadingDiv.innerHTML = '<style>.loading-spinner { border: 5px solid #f3f3f3; border-top: 5px solid #3498db; border-radius: 50%; width: 50px; height: 50px; animation: spin 2s linear infinite; margin: 0 auto; } @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } } .loading-text { margin-top: 10px; font-weight: bold; }</style><div class="loading-spinner"></div><div class="loading-text">Elaborazione in corso...</div>';

                    // Cambia il titolo del dialog
                    var dialogTitle = dialogElement.findOne('.cke_dialog_title');
                    if (dialogTitle) {
                        dialogTitle.setHtml('Elaborazione in corso...');
                    }

                    // Aggiungi la rotellina al dialog
                    try {
                        // Trova il contenitore del body del dialog
                        var dialogBody = dialogElement.findOne('.cke_dialog_body');
                        if (dialogBody) {
                            dialogBody.appendChild(loadingDiv);
                        }
                    } catch (e) {
                        console.error('Impossibile aggiungere la rotellina al dialog:', e);
                    }

                    // Disabilita i pulsanti del dialog
                    var buttons = dialogElement.find('.cke_dialog_ui_button');
                    for (var i = 0; i < buttons.count(); i++) {
                        buttons.getItem(i).setAttribute('disabled', 'disabled');
                    }

                    // Log solo se debug è attivo
                    if (typeof globals !== 'undefined' && globals.debug) {
                        console.log('Prompt:', prompt);
                        console.log('Editor:', editor);
                    }

                    // Ottieni il testo selezionato (lo abbiamo già ottenuto all'apertura del dialog)
                    var selectedText = editor.getSelection().getSelectedText();
                    var content = selectedText; // Usa solo il testo selezionato, non l'intero contenuto

                    // Log per debug
                    if (typeof globals !== 'undefined' && globals.debug) {
                        console.log('Testo selezionato:', selectedText ? selectedText : 'Nessun testo selezionato');
                        console.log('Contenuto da inviare:', content);
                    }

                    // Chiamata API a OpenRouter
                    fetch('https://openrouter.ai/api/v1/chat/completions', {
                        method: 'POST',
                        headers: {
                            'Authorization': 'Bearer ' + globals.openRouterApiKey,
                            'Content-Type': 'application/json',
                            'HTTP-Referer': window.location.origin,
                            'X-Title': 'OpenSTAManager'
                        },
                        body: JSON.stringify({
                            model: 'mistralai/mistral-7b-instruct',
                            messages: [
                                {
                                    role: 'system',
                                    content: 'Sei un assistente esperto che aiuta a migliorare e modificare testi in italiano. Rispondi sempre in italiano.'
                                },
                                {
                                    role: 'user',
                                    content: selectedText ? (prompt + '\n\nTesto originale:\n' + content) : prompt
                                }
                            ],
                            temperature: 0.7,
                            max_tokens: 1000
                        })
                    })
                    .then(function(response) {
                        return response.json();
                    })
                    .then(function(data) {
                        // Non c'è più bisogno di chiudere il dialog di caricamento
                        if (typeof globals !== 'undefined' && globals.debug) {
                            console.log('Risposta ricevuta dall\'API');
                        }

                        if (!data.choices || !data.choices[0] || !data.choices[0].message) {
                            throw new Error('Risposta non valida da OpenRouter');
                        }

                        var aiResponse = data.choices[0].message.content;
                        if (typeof globals !== 'undefined' && globals.debug) {
                            console.log('Risposta ricevuta:', aiResponse);
                        }

                        // Inserisci direttamente la risposta nell'editor
                        if (typeof globals !== 'undefined' && globals.debug) {
                            console.log('Tentativo di inserire il testo nell\'editor');
                            console.log('Modalità editor:', editor.mode);
                            console.log('Editor status:', editor.status);
                        }

                        try {
                            // Metodo 1: Aggiungi il testo alla fine del contenuto esistente
                            if (typeof globals !== 'undefined' && globals.debug) {
                                console.log('Provo ad aggiungere il testo alla fine del contenuto esistente');
                            }

                            // Ottieni il contenuto attuale
                            var currentContent = editor.getData();

                            // Aggiungi la risposta alla fine del contenuto esistente
                            editor.setData(currentContent + '<p>' + aiResponse + '</p>');
                            editor.updateElement();
                            editor.fire('change');

                            if (typeof globals !== 'undefined' && globals.debug) {
                                console.log('Testo aggiunto con successo');
                            }

                            // Rimuovi la rotellina di caricamento e ripristina il dialog
                            try {
                                // Rimuovi il div di caricamento
                                var loadingContainer = dialogElement.findOne('.loading-container');
                                if (loadingContainer && loadingContainer.parentNode) {
                                    loadingContainer.parentNode.removeChild(loadingContainer);
                                }

                                // Mostra nuovamente i contenuti del dialog
                                var dialogContents = dialogElement.findOne('.cke_dialog_contents');
                                if (dialogContents) {
                                    dialogContents.setStyle('display', 'block');
                                }

                                // Ripristina il titolo originale
                                var dialogTitle = dialogElement.findOne('.cke_dialog_title');
                                if (dialogTitle) {
                                    dialogTitle.setHtml('Mistral AI');
                                }
                            } catch (e) {
                                console.error('Errore nella rimozione del container di caricamento:', e);
                            }

                            // Chiudi il dialog
                            dialog.hide();

                            // Mostra un messaggio toast di successo
                            setTimeout(function() {
                                if (typeof toastr !== 'undefined') {
                                    toastr.success('Testo aggiornato correttamente');
                                } else {
                                    alert('Testo aggiornato correttamente');
                                }
                            }, 100);
                        } catch (e) {
                            if (typeof globals !== 'undefined' && globals.debug) {
                                console.error("Errore nell'aggiornamento dell'editor:", e);
                            }

                            // Fallback: prova con insertHtml
                            try {
                                if (typeof globals !== 'undefined' && globals.debug) {
                                    console.log('Provo con insertHtml');
                                }

                                // Ottieni il contenuto attuale
                                var currentContent = editor.getData();

                                if (editor.mode !== 'wysiwyg') {
                                    editor.setMode('wysiwyg', function() {
                                        editor.insertHtml('<p>' + aiResponse + '</p>');
                                    });
                                } else {
                                    editor.insertHtml('<p>' + aiResponse + '</p>');
                                }

                                // Forza l'aggiornamento
                                editor.updateElement();
                                editor.fire('change');

                                if (typeof globals !== 'undefined' && globals.debug) {
                                    console.log('insertHtml eseguito');
                                }

                                // Rimuovi la rotellina di caricamento
                                try {
                                    var loadingOverlay = dialogElement.findOne('.loading-overlay');
                                    if (loadingOverlay) {
                                        try {
                                            loadingOverlay.remove();
                                        } catch (e) {
                                            // Se remove() non funziona, prova con removeChild
                                            if (loadingOverlay.parentNode) {
                                                loadingOverlay.parentNode.removeChild(loadingOverlay);
                                            }
                                        }
                                    }
                                } catch (e) {
                                    console.error('Errore nella rimozione dell\'overlay:', e);
                                }

                                // Chiudi il dialog
                                dialog.hide();

                                // Mostra un messaggio toast di successo
                                setTimeout(function() {
                                    if (typeof toastr !== 'undefined') {
                                        toastr.success('Testo aggiornato correttamente');
                                    } else {
                                        alert('Testo aggiornato correttamente');
                                    }
                                }, 100);
                            } catch (fallbackError) {
                                if (typeof globals !== 'undefined' && globals.debug) {
                                    console.error('Errore nel fallback:', fallbackError);
                                }
                                // Rimuovi la rotellina di caricamento e ripristina il dialog
                                try {
                                    // Rimuovi il div di caricamento
                                    var loadingContainer = dialogElement.findOne('.loading-container');
                                    if (loadingContainer && loadingContainer.parentNode) {
                                        loadingContainer.parentNode.removeChild(loadingContainer);
                                    }

                                    // Mostra nuovamente i contenuti del dialog
                                    var dialogContents = dialogElement.findOne('.cke_dialog_contents');
                                    if (dialogContents) {
                                        dialogContents.setStyle('display', 'block');
                                    }

                                    // Ripristina il titolo originale
                                    var dialogTitle = dialogElement.findOne('.cke_dialog_title');
                                    if (dialogTitle) {
                                        dialogTitle.setHtml('Mistral AI');
                                    }
                                } catch (e) {
                                    console.error('Errore nella rimozione del container di caricamento:', e);
                                }

                                // Riabilita i pulsanti del dialog
                                var buttons = dialogElement.find('.cke_dialog_ui_button');
                                for (var i = 0; i < buttons.count(); i++) {
                                    buttons.getItem(i).removeAttribute('disabled');
                                }

                                // Mostra un messaggio di errore
                                if (typeof toastr !== 'undefined') {
                                    toastr.error('Errore durante l\'aggiornamento del testo: ' + e.message);
                                } else {
                                    alert('Errore durante l\'aggiornamento del testo: ' + e.message);
                                }
                            }
                        }
                    })
                    .catch(function(error) {
                        // Log per debug
                        if (typeof globals !== 'undefined' && globals.debug) {
                            console.log('Errore nella richiesta API');
                            console.error('Errore nella richiesta API:', error);
                        }

                        // Rimuovi la rotellina di caricamento e ripristina il dialog
                        try {
                            // Rimuovi il div di caricamento
                            var loadingContainer = dialogElement.findOne('.loading-container');
                            if (loadingContainer && loadingContainer.parentNode) {
                                loadingContainer.parentNode.removeChild(loadingContainer);
                            }

                            // Mostra nuovamente i contenuti del dialog
                            var dialogContents = dialogElement.findOne('.cke_dialog_contents');
                            if (dialogContents) {
                                dialogContents.setStyle('display', 'block');
                            }

                            // Ripristina il titolo originale
                            var dialogTitle = dialogElement.findOne('.cke_dialog_title');
                            if (dialogTitle) {
                                dialogTitle.setHtml('Mistral AI');
                            }
                        } catch (e) {
                            console.error('Errore nella rimozione del container di caricamento:', e);
                        }

                        // Riabilita i pulsanti del dialog
                        var buttons = dialogElement.find('.cke_dialog_ui_button');
                        for (var i = 0; i < buttons.count(); i++) {
                            buttons.getItem(i).removeAttribute('disabled');
                        }

                        // Mostra un messaggio di errore
                        if (typeof toastr !== 'undefined') {
                            toastr.error('Errore: ' + (error.message || 'Si è verificato un errore durante la richiesta'));
                        } else {
                            alert('Errore: ' + (error.message || 'Si è verificato un errore durante la richiesta'));
                        }
                    });

                    return true; // Chiude il dialog di input
                }
            };
        });

        // Il dialog di caricamento è stato sostituito da un overlay direttamente nel dialog principale

        // Aggiungi il comando
        editor.addCommand('openrouter', new CKEDITOR.dialogCommand('openrouterDialog'));

        // Aggiungi il pulsante alla toolbar
        editor.ui.addButton('OpenRouter', {
            label: 'Genera testo con Mistral AI',
            command: 'openrouter',
            toolbar: 'insert'
        });

    }
});
