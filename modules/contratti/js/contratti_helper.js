/*
	Legge la matricola data e ricrea le spunte e selezioni della matricola data nella nuova pianificazione,
	in modo da poter creare un'altra pianificazione simile (o modificando quella appena copiata)
*/
function copia_pianificazione_os(idcontratto, matricola_src) {
    $.get(globals.rootdir + '/modules/contratti/ajax.php?op=get_pianificazione_os&idcontratto=' + idcontratto + '&matricola_src=' + matricola_src, function (data, response) {
        if (response == 'success') {
            //Nascondo tutte le voci pianificate
            $('div[id*=voce_]').addClass('hide');

            //Tolgo tutte le spunte
            $('div[id*=voce_] input[id*=m_]').removeAttr('checked');

            //Deseleziono tutte le voci della lista <select> multipla
            $('select[name*=voce] option').removeAttr('selected');


            /*
            	La risposta sarà strutturata così:
            	201301:1,201301:2,201301:3,201302:1,201302:3

            	e cioè:
            	Ym `data_scadenza` : idvoce
            */
            os = data.split(',');

            for (i = 0; i < os.length; i++) {
                v = os[i].split(':');

                mese = v[0];
                idvoce = v[1];

                //Seleziono la voce del select multiplo
                $('select[name*=voce] option[value=' + idvoce + ']').attr('selected', 'true');

                //Mostro il riquadro della voce con i mesi
                $('#voce_' + idvoce).removeClass('hide');

                //Spunto la coppia mese-anno
                $('#m_' + mese + '_' + idvoce).removeAttr('checked').click();
            }
        }
    });
}
