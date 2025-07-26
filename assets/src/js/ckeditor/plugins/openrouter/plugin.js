// Translations - Default English translations instead of Italian
let TRANSLATIONS = {
    DIALOG_TITLE: 'AI Assistant (OpenRouter)',
    LOADING_TITLE: 'Processing...',
    LOADING_TEXT: 'Processing your request...',
    ERROR_ELEMENTS_NOT_FOUND: 'Dialog elements not found for toggleLoadingIndicator',
    ERROR_EDITOR_UPDATE: "Error updating the editor:",
    ERROR_FALLBACK: 'Error in setData fallback:',
    ERROR_INVALID_RESPONSE: 'Invalid response from OpenRouter',
    ERROR_API_RESPONSE: 'Invalid API response:',
    ERROR_API_KEY: 'OpenRouter API Key not configured. Configure it in settings.',
    ERROR_UPDATE_TEXT: "Error updating text in the editor.",
    ERROR_MAX_TOKENS: 'Max Tokens must be a positive integer.',
    SUCCESS_UPDATE: 'Text updated successfully',
    PLUGIN_LOADED: 'OpenRouter plugin loaded successfully.',
    LABEL_CONTEXT: 'Context:',
    LABEL_PROMPT: 'Your prompt:',
    LABEL_MODEL: 'AI Model:',
    LABEL_TEMPERATURE: 'Temperature:',
    LABEL_MAX_TOKENS: 'Max Tokens:',
    WARNING_TITLE: 'API Key Not Configured',
    WARNING_DESCRIPTION: 'Configure a valid API key in system settings to use this feature.',
    WARNING_BUTTON: 'I understand',
    INFO_TEXT: {
        SUGGESTION: 'If you have selected text in the editor, it will be automatically included in the prompt to be processed by the AI.',
        TEMPERATURE: 'Controls randomness. Lower values (e.g., 0.2) make the output more focused and deterministic, higher values (e.g., 1.0) make it more creative.',
        MAX_TOKENS: 'Limits the maximum length of the generated response.'
    },
    ERROR_PROMPT_EMPTY: "The prompt field cannot be empty."
};

CKEDITOR.plugins.add('openrouter', {
    icons: 'openrouter',
    init: function(editor) {
        // Carica il file CSS per lo stile del plugin
        var cssPath = this.path + 'styles/openrouter.css';
        CKEDITOR.document.appendStyleSheet(cssPath);
        
        // Determina la lingua da utilizzare per l'interfaccia
        // Prima controlla se è definita nelle impostazioni globali, altrimenti usa il browser, con fallback su inglese
        var userLang = 'it'; // Lingua predefinita: inglese
      
        
        var translationsPath = this.path + 'translations/translations_' + userLang + '.js';
        
        // Funzione per aggiornare le traduzioni
        // Esegue un merge intelligente mantenendo la struttura nidificata
        function updateTranslations(newTranslations) {
            if (newTranslations && typeof newTranslations === 'object') {
                // Merge delle traduzioni mantenendo la struttura nidificata
                for (var key in newTranslations) {
                    if (typeof newTranslations[key] === 'object' && !Array.isArray(newTranslations[key])) {
                        if (!TRANSLATIONS[key] || typeof TRANSLATIONS[key] !== 'object') {
                            TRANSLATIONS[key] = {};
                        }
                        for (var subKey in newTranslations[key]) {
                            TRANSLATIONS[key][subKey] = newTranslations[key][subKey];
                        }
                    } else {
                        TRANSLATIONS[key] = newTranslations[key];
                    }
                }
            }
        }
        
        // Prova a caricare il file di traduzioni
        CKEDITOR.scriptLoader.load(translationsPath, function(success) {
            if (success) {
                // Verifica il formato delle traduzioni caricate
                if (window.OPENROUTER_TRANSLATIONS) {
                    updateTranslations(window.OPENROUTER_TRANSLATIONS);
                } else {
                    // Prova il formato alternativo
                    if (window.TRANSLATIONS) {
                        updateTranslations(window.TRANSLATIONS);
                    }
                }
            }
        });

        // Recupera il modello AI predefinito dalle impostazioni globali
        // Se non è definito, utilizza mistral-7b-instruct come modello di fallback
        function getDefaultModel() {
            var defaultModel = 'mistralai/mistral-7b-instruct'; // Fallback predefinito
            if (typeof globals !== 'undefined' && globals.openRouterDefaultModel && globals.openRouterDefaultModel.trim() !== '') {
                defaultModel = globals.openRouterDefaultModel;
            }
            return defaultModel;
        }

        // Recupera il prompt di sistema predefinito dalle impostazioni globali
        // Se non è definito, utilizza un prompt generico come fallback
        function getSystemPrompt() {
            var defaultPrompt = 'Sei un assistente utile.'; // Fallback predefinito
            if (typeof globals !== 'undefined' && globals.AISystemPrompt && globals.AISystemPrompt.trim() !== '') {
                defaultPrompt = globals.AISystemPrompt;
            }
            return defaultPrompt;
        }

        // Gestisce la visualizzazione dell'indicatore di caricamento nel dialog
        // Mostra/nasconde l'animazione e gestisce gli stati dell'interfaccia utente
        function toggleLoadingIndicator(dialog, show) {
            var dialogElement = dialog.getElement();
            var dialogContents = dialogElement.findOne('.cke_dialog_contents_body');
            var loadingContainer = dialogElement.findOne('.loading-container');
            var dialogTitle = dialogElement.findOne('.cke_dialog_title');
            var closeButton = dialogElement.findOne('.cke_dialog_close_button');

            if (!dialogContents || !loadingContainer || !dialogTitle) {
                if (typeof globals !== 'undefined' && globals.debug) {
                    console.error(TRANSLATIONS.ERROR_ELEMENTS_NOT_FOUND);
                }
                return;
            }

            // Centra sempre il dialog
            dialogElement.setStyles({
                'position': 'fixed',
                'top': '50%',
                'left': '50%',
                'transform': 'translate(-50%, -50%)',
                'margin': '0'
            });

            if (show) {
                // Mantieni la larghezza corrente del dialog
                var currentWidth = dialogElement.getStyle('width');
                dialogContents.setStyle('display', 'none');
                loadingContainer.setHtml(`
                    <div class="loading-spinner"></div>
                    <div class="loading-text">${TRANSLATIONS.LOADING_TEXT}</div>
                `);
                loadingContainer.setStyle('display', 'block');
                dialogTitle.setHtml(TRANSLATIONS.LOADING_TITLE);
                
                // Nascondi il pulsante di chiusura durante il caricamento
                if (closeButton) closeButton.hide();
                
                var buttons = dialogElement.findOne('.cke_dialog_footer');
                if (buttons) buttons.hide();
            } else {
                // Mantieni il dialog centrato anche dopo il caricamento
                loadingContainer.setStyle('display', 'none');
                dialogContents.setStyle('display', 'block');
                dialogTitle.setHtml(TRANSLATIONS.DIALOG_TITLE);
                
                // Mostra sempre il pulsante di chiusura
                if (closeButton) closeButton.show();
                
                var buttons = dialogElement.findOne('.cke_dialog_footer');
                if (buttons) buttons.show();
            }
        }

        // Inserisce la risposta dell'AI nell'editor
        // Gestisce sia il caso di testo selezionato che di inserimento in una nuova posizione
        // Implementa un meccanismo di fallback in caso di errori
        function insertAiResponse(editor, aiResponse) {
            try {
                var selection = editor.getSelection();
                if (selection && selection.getSelectedText()) {
                    editor.insertHtml(aiResponse);
                } else {
                    editor.insertHtml('<p>' + aiResponse + '</p>');
                }
                editor.updateElement();
                editor.fire('change');
                return true;
            } catch (e) {
                if (typeof globals !== 'undefined' && globals.debug) {
                    // Rimuovo console.error
                }
                try {
                    var currentContent = editor.getData();
                    editor.setData(currentContent + '<p>' + aiResponse + '</p>');
                    editor.updateElement();
                    editor.fire('change');
                    return true;
                } catch (fallbackError) {
                    if (typeof globals !== 'undefined' && globals.debug) {
                        // Rimuovo console.error
                    }
                    return false;
                }
            }
        }

        // Elabora la risposta positiva dall'API
        // Verifica la validità della risposta e la inserisce nell'editor
        // Gestisce la chiusura del dialog e la notifica all'utente
        function handleApiResponse(dialog, editor, data) {
            toggleLoadingIndicator(dialog, false); // Nascondi indicatore di caricamento

            if (!data.choices || !data.choices[0] || !data.choices[0].message || !data.choices[0].message.content) {
                toastr.error(TRANSLATIONS.ERROR_INVALID_RESPONSE);
                return;
            }

            var aiResponse = data.choices[0].message.content.trim();

            if (insertAiResponse(editor, aiResponse)) {
                dialog.hide(); // Chiudi il dialog solo se l'inserimento ha successo
                // Mostra messaggio di successo con un leggero ritardo
                setTimeout(function() {
                    if (typeof toastr !== 'undefined') {
                        toastr.success(TRANSLATIONS.SUCCESS_UPDATE);
                    } else {
                        alert(TRANSLATIONS.SUCCESS_UPDATE);
                    }
                }, 100);
            } else {
                // Se l'inserimento fallisce, mostra errore e lascia il dialog aperto
                if (typeof toastr !== 'undefined') {
                    toastr.error(TRANSLATIONS.ERROR_UPDATE_TEXT);
                } else {
                    alert(TRANSLATIONS.ERROR_UPDATE_TEXT);
                }
            }
        }

        // Gestisce gli errori nelle chiamate API
        // Mostra messaggi di errore appropriati all'utente e nasconde l'indicatore di caricamento
        function handleApiError(dialog, error) {
            toggleLoadingIndicator(dialog, false); // Nascondi indicatore di caricamento

            // Mostra messaggio di errore all'utente
            if (typeof toastr !== 'undefined') {
                toastr.error('Errore: ' + (error.message || 'Si è verificato un errore durante la richiesta API'));
            } else {
                alert('Errore: ' + (error.message || 'Si è verificato un errore durante la richiesta API'));
            }
        }

        // Funzioni di utilità per la gestione dei cookie
        // Permettono di salvare e recuperare le preferenze dell'utente tra le sessioni
        function setCookie(name, value, days) {
            var expires = "";
            if (days) {
                var date = new Date();
                date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
                expires = "; expires=" + date.toUTCString();
            }
            // Aggiunto SameSite=Lax e Secure (se applicabile, ma CKEditor potrebbe essere locale)
            // Nota: Secure richiede HTTPS. Se si usa HTTP, rimuovere '; Secure'.
            var secureFlag = window.location.protocol === 'https:' ? '; Secure' : '';
            document.cookie = name + "=" + (value || "") + expires + "; path=/; SameSite=Lax" + secureFlag;
        }

        function getCookie(name) {
            var nameEQ = name + "=";
            var ca = document.cookie.split(';');
            for (var i = 0; i < ca.length; i++) {
                var c = ca[i];
                while (c.charAt(0) == ' ') c = c.substring(1, c.length);
                if (c.indexOf(nameEQ) == 0) {
                    return c.substring(nameEQ.length, c.length);
                }
            }
            return null;
        }

        // Effettua la chiamata API a OpenRouter
        // Gestisce l'invio del prompt, la configurazione della richiesta e il trattamento delle risposte
        function callOpenRouterApi(dialog, editor, systemPrompt, userPrompt, selectedText, selectedModel, temperature, maxTokens) {
            // Verifica API Key
            if (typeof globals === 'undefined' || !globals.openRouterApiKey) {
                if (typeof toastr !== 'undefined') {
                    toastr.error(TRANSLATIONS.ERROR_API_KEY);
                } else {
                    alert(TRANSLATIONS.ERROR_API_KEY);
                }
                return; // Interrompi se la chiave non è configurata
            }

            toggleLoadingIndicator(dialog, true); // Mostra caricamento

            var requestBody = {
                model: selectedModel,
                messages: [
                    { role: "system", content: systemPrompt }, // Usa il prompt di sistema dal dialog
                    { role: "user", content: userPrompt + (selectedText ? "\n\nRiscrivi o lavora su questo testo:\n" + selectedText : "") }
                ],
                temperature: parseFloat(temperature), // Ensure temperature is a float
                max_tokens: parseInt(maxTokens, 10) // Ensure max_tokens is an integer
            };

            // Esegui la chiamata API
            fetch('https://openrouter.ai/api/v1/chat/completions', {
                method: 'POST',
                headers: {
                    'Authorization': 'Bearer ' + globals.openRouterApiKey,
                    'Content-Type': 'application/json',
                    'HTTP-Referer': window.location.href, 
                    'X-Title': 'OpenSTAManager Integration'
                },
                body: JSON.stringify(requestBody)
            })
            .then(response => {
                if (!response.ok) {
                    // Se la risposta non è OK, leggi il corpo per dettagli sull'errore
                    return response.json().then(errData => {
                        var errorMsg = 'Errore API: ' + response.status + ' ' + response.statusText;
                        if (errData && errData.error && errData.error.message) {
                            errorMsg += ' - ' + errData.error.message;
                        } else if (typeof errData === 'string') {
                            errorMsg += ' - ' + errData;
                        }
                        throw new Error(errorMsg); // Lancia un errore per essere catturato dal blocco catch
                    }).catch(() => {
                        // Se il corpo JSON non può essere letto o è vuoto
                        throw new Error('Errore API: ' + response.status + ' ' + response.statusText + '. Impossibile ottenere dettagli aggiuntivi.');
                    });
                }
                return response.json(); // Se la risposta è OK, procedi con il parsing del JSON
            })
            .then(data => {
                handleApiResponse(dialog, editor, data); // Gestisci la risposta di successo
            })
            .catch(error => {
                handleApiError(dialog, error); // Gestisci l'errore della chiamata
            });
        }

        // Configurazione del dialog dell'editor
        // Definisce l'interfaccia utente, i campi di input e gestisce le interazioni dell'utente
        CKEDITOR.dialog.add('openrouterDialog', function(editor) {
            var defaultModel = getDefaultModel(); // Ottieni il modello predefinito
            var defaultSystemPrompt = getSystemPrompt(); // Ottieni il prompt di sistema predefinito
            var lastUsedModel = getCookie('ckeditorOpenRouterModel') || defaultModel; // Letto all'inizio, ma setup lo sovrascrive
            var lastTemperature = getCookie('ckeditorOpenRouterTemp') || '0.7'; // Default temperature
            var lastMaxTokens = getCookie('ckeditorOpenRouterTokens') || '1024'; // Default max tokens

            return {
                title: TRANSLATIONS.DIALOG_TITLE,
                minWidth: 400,
                minHeight: 400,
                width: Math.min(800, window.innerWidth * 0.8),
                height: Math.min(400, window.innerHeight * 0.8),
                resizable: CKEDITOR.DIALOG_RESIZE_BOTH,
                contents: [
                    {
                        id: 'tab-main',
                        label: 'Impostazioni Principali',
                        elements: [
                            {
                                type: 'html',
                                id: 'loading-indicator',
                                html: '<div class="loading-container" style="display: none; text-align: center; padding: 20px;"></div>'
                            },
                            {
                                type: 'html',
                                id: 'api-key-warning',
                                html: '<div id="api-key-warning-container" style="display: none; position: absolute; top: 0; left: 0; right: 0; bottom: 0; background-color: rgba(255, 255, 255, 0.9); z-index: 1000; display: flex; flex-direction: column; justify-content: center; align-items: center; text-align: center; padding: 20px;">' +
                                      '<div style="background-color: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 5px; margin-bottom: 15px; max-width: 80%;">' +
                                      '<h3 style="margin-top: 0;">' + TRANSLATIONS.WARNING_TITLE + '</h3>' +
                                      '<p>' + TRANSLATIONS.ERROR_API_KEY + '</p>' +
                                      '<p>' + TRANSLATIONS.WARNING_DESCRIPTION + '</p>' +
                                      '<button type="button" id="api-key-warning-close" style="margin-top: 15px; padding: 8px 16px; background-color: #dc3545; color: white; border: none; border-radius: 4px; cursor: pointer;" onclick="CKEDITOR.dialog.getCurrent().hide()">' + TRANSLATIONS.WARNING_BUTTON + '</button>' +
                                      '</div>' +
                                      '</div>'
                            },
                            {
                                type: 'textarea',
                                id: 'system_prompt',
                                label: TRANSLATIONS.LABEL_SYSTEM_PROMPT, // Nuova label
                                rows: 3,
                                'default': defaultSystemPrompt,
                                setup: function() {
                                    this.setValue(defaultSystemPrompt);
                                },
                                commit: function(data) {
                                    data.system_prompt = this.getValue();
                                }
                            },
                            {
                                type: 'textarea',
                                id: 'context',
                                label: TRANSLATIONS.LABEL_CONTEXT,
                                rows: 3,
                                setup: function() {
                                    var selection = editor.getSelection();
                                    var selectedText = selection ? selection.getSelectedText() : '';
                                    this.setValue(selectedText);
                                },
                                // Rimosso lo style per renderlo editabile
                                commit: function(data) {
                                    data.context = this.getValue(); // Recupera il contesto (potrebbe essere stato modificato)
                                }
                            },
                            {
                                type: 'textarea',
                                id: 'prompt',
                                label: TRANSLATIONS.LABEL_PROMPT,
                                rows: 5,
                                required: true,
                                validate: CKEDITOR.dialog.validate.notEmpty(TRANSLATIONS.ERROR_PROMPT_EMPTY),
                                setup: function() {
                                    // Potresti pre-popolare il prompt se necessario
                                },
                                commit: function(data) {
                                    data.user_prompt = this.getValue(); // Rinominato per chiarezza
                                }
                            },
                            {
                                type: 'select',
                                id: 'model',
                                label: TRANSLATIONS.LABEL_MODEL,
                                items: [ // Aggiungi qui i modelli che vuoi supportare
                                    ['Mistral 7B Instruct (Consigliato)', 'mistralai/mistral-7b-instruct'],
                                    ['Mixtral 8x7B Instruct', 'mistralai/mixtral-8x7b-instruct'],
                                    ['Google Gemini Pro 1.5', 'google/gemini-pro-1.5'],
                                    ['Google Gemini Pro', 'google/gemini-pro'],
                                    ['OpenAI GPT-4o', 'openai/gpt-4o'],
                                    ['OpenAI GPT-4 Turbo', 'openai/gpt-4-turbo'],
                                    ['OpenAI GPT-3.5 Turbo', 'openai/gpt-3.5-turbo'],
                                    ['Claude 3 Haiku', 'anthropic/claude-3-haiku-20240307'],
                                    ['Claude 3 Sonnet', 'anthropic/claude-3-sonnet-20240229'],
                                    ['Claude 3 Opus', 'anthropic/claude-3-opus-20240229'],
                                    ['Llama 3 70B Instruct (Meta)', 'meta-llama/llama-3-70b-instruct'],
                                    ['Llama 3 8B Instruct (Meta)', 'meta-llama/llama-3-8b-instruct']
                                    // Aggiungi altri modelli se necessario
                                ],
                                'default': lastUsedModel, // Valore predefinito iniziale
                                setup: function() {
                                    // Leggi il cookie *ogni volta* che il dialog viene aperto
                                    var currentLastUsedModel = getCookie('ckeditorOpenRouterModel') || defaultModel;
                                    // Imposta il valore del dropdown basato sul cookie corrente
                                    this.setValue(currentLastUsedModel);
                                },
                                commit: function(data) {
                                    // Salva il valore selezionato nel cookie quando si preme OK
                                    data.model = this.getValue();
                                    setCookie('ckeditorOpenRouterModel', data.model, 30);
                                }
                            },
                            {
                                type: 'hbox',
                                widths: ['50%', '50%'],
                                children: [
                                    {
                                        type: 'html',
                                        html: '<div style="padding: 5px;">' +
                                              '<label style="display: block; margin-bottom: 5px;">' + TRANSLATIONS.LABEL_TEMPERATURE + ' <span id="tempValue">' + lastTemperature + '</span></label>' +
                                              '<input type="range" id="temperatureRange" min="0.1" max="1.0" step="0.1" value="' + lastTemperature + '" ' +
                                              'style="width: 100%;" oninput="document.getElementById(\'tempValue\').textContent = this.value"/>' + // Completed oninput
                                              '</div>',
                                        setup: function() {
                                            var range = this.getElement().findOne('input');
                                            if (range) {
                                                // Leggi il cookie ogni volta che il dialog viene aperto
                                                var currentLastTemperature = getCookie('ckeditorOpenRouterTemp') || '0.7';
                                                range.$.value = currentLastTemperature;
                                                // Aggiorna il valore visualizzato
                                                var span = this.getElement().findOne('#tempValue');
                                                if (span) span.setText(currentLastTemperature);
                                            }
                                        },
                                        commit: function(data) {
                                            var range = this.getElement().findOne('input');
                                            if (range) {
                                                data.temperature = range.$.value;
                                                setCookie('ckeditorOpenRouterTemp', data.temperature, 30);
                                            }
                                        }
                                    },
                                    {
                                        type: 'text',
                                        id: 'max_tokens',
                                        label: TRANSLATIONS.LABEL_MAX_TOKENS,
                                        'default': lastMaxTokens,
                                        validate: function() {
                                            var value = parseInt(this.getValue(), 10);
                                            if (isNaN(value) || value <= 0) {
                                                alert(TRANSLATIONS.ERROR_MAX_TOKENS);
                                                return false;
                                            }
                                            return true;
                                        },
                                        setup: function() {
                                            // Leggi il cookie ogni volta che il dialog viene aperto
                                            var currentLastMaxTokens = getCookie('ckeditorOpenRouterTokens') || '1024';
                                            this.setValue(currentLastMaxTokens);
                                        },
                                        commit: function(data) {
                                            data.max_tokens = this.getValue();
                                            setCookie('ckeditorOpenRouterTokens', data.max_tokens, 30);
                                        }
                                    }
                                ]
                            },
                             {
                                type: 'html',
                                id: 'info_text',
                                html: '<div style="margin-top: 10px; font-size: 0.9em; color: #555;">' +
                                      '<strong>Suggerimento:</strong> ' + TRANSLATIONS.INFO_TEXT.SUGGESTION +
                                      '<br><strong>Temperatura:</strong> ' + TRANSLATIONS.INFO_TEXT.TEMPERATURE +
                                      '<br><strong>Max Tokens:</strong> ' + TRANSLATIONS.INFO_TEXT.MAX_TOKENS +
                                      '</div>'
                            }
                        ]
                    }
                ],
                onShow: function() {
                    var dialog = this;
                    var selection = editor.getSelection();
                    this.selectedText = selection ? selection.getSelectedText() : null;

                    // Forza il centramento del dialog
                    var dialogElement = dialog.getElement();
                    dialogElement.setStyles({
                        'position': 'fixed',
                        'top': '50%',
                        'left': '50%',
                        'transform': 'translate(-50%, -50%)',
                        'margin': '0'
                    });

                    // Aggiorna il campo contesto
                    var contextField = dialog.getContentElement('tab-main', 'context');
                    if (contextField) {
                        contextField.setValue(this.selectedText || '');
                    }

                    // Mostra/nascondi l'avviso API Key
                    var apiKeyWarningContainer = dialogElement.findOne('#api-key-warning-container');
                    var mainContents = dialogElement.findOne('.cke_dialog_contents_body'); // Selettore corretto
                    var footer = dialogElement.findOne('.cke_dialog_footer');

                    if (typeof globals === 'undefined' || !globals.openRouterApiKey) {
                        if (apiKeyWarningContainer) apiKeyWarningContainer.setStyle('display', 'flex');
                        if (mainContents) mainContents.setStyle('visibility', 'hidden'); // Nascondi contenuti principali
                        if (footer) footer.setStyle('visibility', 'hidden'); // Nascondi footer
                    } else {
                        if (apiKeyWarningContainer) apiKeyWarningContainer.setStyle('display', 'none');
                        if (mainContents) mainContents.setStyle('visibility', 'visible'); // Mostra contenuti principali
                        if (footer) footer.setStyle('visibility', 'visible'); // Mostra footer
                    }

                    toggleLoadingIndicator(this, false);
                },
                onOk: function() {
                    var dialog = this;
                    var data = {};
                    dialog.commitContent(data); // Raccoglie i dati dagli elementi del form

                    // Usa il contesto dal campo 'context' se presente, altrimenti il testo selezionato originale
                    var contextText = data.context || this.selectedText;

                    // Chiama la funzione API con il prompt di sistema
                    callOpenRouterApi(dialog, editor, data.system_prompt, data.user_prompt, contextText, data.model, data.temperature, data.max_tokens);

                    // Impedisci la chiusura automatica del dialog; la chiusura avverrà in handleApiResponse
                    return false;
                }
            };
        });

        // Configurazione del comando e del pulsante nella toolbar
        editor.addCommand('openrouterDialogCmd', new CKEDITOR.dialogCommand('openrouterDialog'));

        editor.ui.addButton('OpenRouter', {
            label: TRANSLATIONS.DIALOG_TITLE,
            command: 'openrouterDialogCmd',
            toolbar: 'insert',
            icon: this.path + 'icons/openrouter.png' // Assicurati che l'icona esista in questa posizione
        });

        // Log di debug per confermare il corretto caricamento del plugin
        if (typeof globals !== 'undefined' && globals.debug) {
            console.log(TRANSLATIONS.PLUGIN_LOADED);
        }
    }
});