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
 * Inizializzazione degli event listener quando il documento è pronto
 */
$(document).ready(function() {
    // Event listener per il pulsante di copia
    $("#copy-query-btn").click(function() {
        copyQueryToClipboard();
    });
});
