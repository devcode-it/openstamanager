function calcolaSubtotale(tipo) {
    let total = 0;
    let currency = '€';
    let prevRow = 0;
    let descrizione = '';
    let error = null;

    $('#righe').find('.check:checked').each(function () {
        // salva il valore della seconda colonna della riga corrente
        let currentRow = parseInt($(this).closest('tr').find('td:nth-child(2)').text());

        let number = 0;
        let $td = $(this).closest('tr').find('td').eq(-2);
        let text = $td.text();

        // se esiste una riga precedente e il suo valore di "data-row" non è
        // consecutivo a quello della riga corrente, mostra un messaggio di errore
        if (prevRow && (currentRow !== prevRow + 1)) {
            error = 'Attenzione: le righe selezionate non sono consecutive';
        }

        // sostituisci il punto con nulla
        text = text.replace('.', '');

        // sostituisci la virgola con il punto
        text = text.replace(',', '.');

        // converte la stringa in numero
        number = parseFloat(text);

        // aggiungi il numero al totale
        total += number;

        prevRow = currentRow;
    });

    // formatta il totale con virgola, due cifre decimali e separatore di migliaia
    total = total.toLocaleString('it-IT', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });

    descrizione = 'Subtotale = ' + total + ' ' + currency;

    // effettua una chiamata post per creare una nuova riga descrizione
    if (error !== null) {
        toastr.error(error);
        return;
    }

    $.post('/actions.php', {
        op: 'manage_subtotale',
        id_module: globals.id_module,
        id_record: globals.id_record,
        descrizione: descrizione,
        tipo: tipo,
        dir: 'entrata',
    }, function (data) {
        data = JSON.parse(data);
        newId = data.id;

        // prende l'id di ogni $('#righe'), separati da virgola
        ids = $('#righe').find('tr').map(function () {
            return $(this).attr('data-id');
        }).get();

        // prende l'id dell'ultima riga .check:checked
        let lastCheckedId = $('#righe').find('.check:checked').last().closest('tr').attr('data-id');

        // inserisce newId dopo lastCheckedId, nell'array ids
        ids.splice(ids.indexOf(lastCheckedId) + 1, 0, newId);

        // chiamata ajax per il riordinamento delle righe
        $.post('/actions.php', {
            op: 'update_position',
            id_module: globals.id_module,
            id_record: globals.id_record,
            order: ids.join(','),
        }, function () {
            location.reload();
        });
    });
}
