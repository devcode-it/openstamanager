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
 * Inizializzazione degli event listener quando il documento è pronto
 */
$(document).ready(function() {
    // Event listener per il pulsante di copia
    $("#copy-query-btn").click(function() {
        copyQueryToClipboard();
    });

    // Aggiungi il pulsante "Seleziona tutti" accanto alle label dei gruppi
    addSelectAllButtons();

    // Quando viene aggiunto un nuovo campo, aggiungi anche il pulsante
    $(document).on("click", "#add", function() {
        // Attendiamo che il nuovo campo sia stato aggiunto e inizializzato
        setTimeout(function() {
            addSelectAllButtons();
        }, 500);
    });
});
