/**
 * Calcola la differenza in ore fra start ed end, e le scrive nel campo delle ore della riga specificata
 */
function calcola_ore(idriga, start, end) {
    end = moment(end, globals.timestampFormat);
    start = moment(start, globals.timestampFormat);

    totale_ore = end.diff(start, 'minutes') / 60;
    totale_ore = totale_ore.toFixed(2);

    if (!isNaN(totale_ore)) {
        $('#ore' + idriga).val(totale_ore);
    }
}

/*
	Aggiunge una nuova riga per la sessione di lavoro in base al tecnico selezionato
*/
function add_tecnici(idintervento, idtecnico) {
    $('#tecnici').load(globals.rootdir + '/modules/interventi/ajax_tecnici.php?id_module=' + globals.id_module +'&id_record=' + idintervento + '&op=add_sessione&idtecnico=' + idtecnico);

    $('#costi').load(globals.rootdir + '/modules/interventi/ajax_costi.php?id_module=' + globals.id_module +'&id_record=' + idintervento);
}

function elimina_sessione(idriga, idintervento, idzona) {
    if (confirm('Eliminare sessione di lavoro?')) {
        $('#tecnici').load(globals.rootdir + '/modules/interventi/ajax_tecnici.php?id_module=' + globals.id_module +'&id_record=' + idintervento + '&op=del_sessione&id=' + idriga);

        $('#costi').load(globals.rootdir + '/modules/interventi/ajax_costi.php?id_module=' + globals.id_module +'&id_record=' + idintervento);
    }
}
