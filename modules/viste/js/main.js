/**
 * Funzione per copiare la query negli appunti
 */
function copyQueryToClipboard() {
    var copyText = document.getElementById("query-to-copy");
    copyText.select();
    document.execCommand("copy");

    // Mostra un messaggio di conferma
    swal(globals.translations.copied, globals.translations.query_copied, "success");
}

/**
 * Funzione per formattare la query e rimuovere le barre di scorrimento
 */
function formatQuery() {
    var sqlFormattedDiv = $(".sql-formatted");

    // Alterna tra la visualizzazione con e senza barre di scorrimento
    if (sqlFormattedDiv.hasClass("formatted")) {
        // Ripristina la visualizzazione originale con barre di scorrimento
        sqlFormattedDiv.removeClass("formatted");

        // Ripristina lo stile originale
        sqlFormattedDiv.css({
            "max-height": "500px",
            "overflow": "auto"
        });

        // Ripristina lo stile per tutti gli elementi interni
        sqlFormattedDiv.find("*").css({
            "white-space": "pre",
            "word-wrap": "normal",
            "word-break": "normal"
        });

        // Cambia l'icona e il testo del pulsante
        $("#format-query-btn i").removeClass("fa-compress").addClass("fa-indent");
        $("#format-query-btn").attr("title", globals.translations.format_query);
        $("#format-query-btn").html('<i class="fa fa-indent"></i> ' + globals.translations.format);
    } else {
        // Rimuovi le barre di scorrimento e adatta l'altezza al contenuto
        sqlFormattedDiv.addClass("formatted");

        // Modifica lo stile del contenitore
        sqlFormattedDiv.css({
            "max-height": "none",
            "overflow": "visible"
        });

        // Modifica lo stile di tutti gli elementi interni per forzare il wrapping
        sqlFormattedDiv.find("*").css({
            "white-space": "pre-wrap",
            "word-wrap": "break-word",
            "word-break": "break-word"
        });

        // Cambia l'icona e il testo del pulsante
        $("#format-query-btn i").removeClass("fa-indent").addClass("fa-compress");
        $("#format-query-btn").attr("title", globals.translations.compress_query);
        $("#format-query-btn").html('<i class="fa fa-compress"></i> ' + globals.translations.compress);
    }
}

/**
 * Funzione per testare la query
 */
function testQuery() {
    $("#main_loading").fadeIn();

    $.ajax({
        url: globals.rootdir + "/actions.php?id_module=" + globals.id_module + "&id_record=" + globals.id_record + "&op=test",
        cache: false,
        type: "post",
        processData: false,
        contentType: false,
        dataType: "html",
        success: function(data) {
            $("#main_loading").fadeOut();

            if (data == "ok") {
                swal(globals.translations.working_query, globals.translations.query_works_correctly, "success");
            } else {
                swal(globals.translations.error, data, "error");
            }
        }
    });
}

/**
 * Aggiunge i pulsanti "Seleziona tutti" dinamicamente alle label dei gruppi
 */
function addSelectAllButtons() {
    // Cerca tutte le label che contengono "Gruppi con accesso"
    $("label:contains('Gruppi con accesso')").each(function() {
        var label = $(this);
        label.css("display", "block");

        // Crea il pulsante solo se non esiste già
        if (label.find(".btn-select-all-groups").length === 0) {
            var button = $('<div class="pull-right"><button type="button" class="btn btn-xs btn-default btn-select-all-groups" style="margin-left: 5px;"><i class="fa fa-check-square-o"></i> ' + globals.translations.select_all + '</button></div>');
            label.append(button);
        }
    });

    // Gestione del click sul pulsante "Seleziona tutti"
    $(document).off("click", ".btn-select-all-groups").on("click", ".btn-select-all-groups", function(e) {
        e.preventDefault();

        // Trova il select associato alla label
        var formGroup = $(this).closest(".form-group");
        var selectElement = formGroup.find("select");

        // Seleziona tutte le opzioni
        selectElement.find("option").prop("selected", true);

        // Aggiorna il plugin select2 se presente
        if ($.fn.select2) {
            selectElement.trigger("change");
        }
    });
}


/**
 * Funzione per importare un modulo
 */
function importModule() {
    // Apre il modal per l'importazione
    openModal(globals.translations.import_module, globals.rootdir + '/modules/viste/import_modal.php');
}

/**
 * Funzione per esportare un modulo
 */
function exportModule() {
    // Mostra il loader
    $("#main_loading").fadeIn();

    // Richiesta AJAX per esportare il modulo
    $.ajax({
        url: globals.rootdir + "/actions.php?id_module=" + globals.id_module + "&id_record=" + globals.id_record + "&op=export_module",
        cache: false,
        type: "post",
        processData: false,
        contentType: false,
        dataType: "json",
        success: function(response) {
            $("#main_loading").fadeOut();

            if (response.success) {
                // Crea un link per il download e lo clicca automaticamente
                var downloadLink = document.createElement("a");
                downloadLink.href = "data:application/json;charset=utf-8," + encodeURIComponent(JSON.stringify(response.data, null, 4));
                downloadLink.download = response.filename || "module_export.json";
                document.body.appendChild(downloadLink);
                downloadLink.click();
                document.body.removeChild(downloadLink);
            } else {
                swal(globals.translations.error, response.message, "error");
            }
        },
        error: function() {
            $("#main_loading").fadeOut();
            swal(globals.translations.error, "Si è verificato un errore durante l'esportazione", "error");
        }
    });
}

/**
 * Inizializzazione degli event listener quando il documento è pronto
 */
$(document).ready(function() {
    // Event listener per il pulsante di copia
    $("#copy-query-btn").click(function() {
        copyQueryToClipboard();
    });

    // Event listener per il pulsante di formattazione
    $("#format-query-btn").click(function() {
        formatQuery();
    });

    // Forza l'aggiornamento dell'autosize per le textarea delle query
    setTimeout(function() {
        $('textarea[name="options"], textarea[name="options2"]').each(function() {
            if (typeof autosize !== 'undefined') {
                autosize.update(this);
            }
        });
    }, 100);

    // Aggiungi il pulsante "Seleziona tutti" accanto alle label dei gruppi
    addSelectAllButtons();

    // Funzione per aggiungere la classe clickable-header alle card
    function makeHeadersClickable() {
        $(".card-header").each(function() {
            if ($(this).find('[data-card-widget="collapse"]').length > 0 && !$(this).hasClass("clickable-header")) {
                $(this).addClass("clickable-header");
            }
        });
    }

    // Esegui la funzione inizialmente
    makeHeadersClickable();

    // Quando viene aggiunto un nuovo campo, aggiungi anche il pulsante e rendi l'header cliccabile
    $(document).on("click", "#add", function() {
        // Attendiamo che il nuovo campo sia stato aggiunto e inizializzato
        setTimeout(function() {
            addSelectAllButtons();
            makeHeadersClickable();
        }, 500);
    });

    // Aggiungi stile CSS per rendere il cursore a puntatore sugli header cliccabili
    $("<style>")
        .prop("type", "text/css")
        .html(".clickable-header { cursor: pointer; }")
        .appendTo("head");

    // Rendi l'intera card header cliccabile per espandere/comprimere la card
    $(document).on("click", ".clickable-header", function(e) {
        // Verifica che il click non sia sul pulsante di collapse (per evitare doppi click)
        // e non sia su altri elementi interattivi come link o pulsanti
        if (!$(e.target).closest('[data-card-widget="collapse"]').length &&
            !$(e.target).closest('a').length &&
            !$(e.target).closest('button').length) {
            // Trova il pulsante di collapse all'interno dell'header e simulane il click
            $(this).find('[data-card-widget="collapse"]').click();
        }
    });


});
