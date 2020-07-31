<?php

$result['idarticolo'] = isset($result['idarticolo']) ? $result['idarticolo'] : null;

$_SESSION['superselect']['dir'] = $options['dir'];
$_SESSION['superselect']['idanagrafica'] = $options['idanagrafica'];
$_SESSION['superselect']['idarticolo'] = $options['idarticolo'];

$qta_minima = 0;

// Articolo
if (empty($result['idarticolo'])) {
    echo '
    <div class="row">
        <div class="col-md-12">
            {[ "type": "select", "label": "'.tr('Articolo').'", "name": "idarticolo", "required": 1, "value": "'.$result['idarticolo'].'", "ajax-source": "articoli", "icon-after": "add|'.Modules::get('Articoli')['id'].'" ]}
        </div>
    </div>

    <input type="hidden" name="id_dettaglio_fornitore" id="id_dettaglio_fornitore" value="">';
} else {
    $database = database();
    $articolo = $database->fetchOne('SELECT mg_articoli.id,
        mg_fornitore_articolo.id AS id_dettaglio_fornitore,
        IFNULL(mg_fornitore_articolo.codice_fornitore, mg_articoli.codice) AS codice,
        IFNULL(mg_fornitore_articolo.descrizione, mg_articoli.descrizione) AS descrizione,
        IFNULL(mg_fornitore_articolo.qta_minima, 0) AS qta_minima
    FROM mg_articoli
        LEFT JOIN mg_fornitore_articolo ON mg_fornitore_articolo.id_articolo = mg_articoli.id AND mg_fornitore_articolo.id = '.prepare($result['id_dettaglio_fornitore']).'
    WHERE mg_articoli.id = '.prepare($result['idarticolo']));

    $qta_minima = $articolo['qta_minima'];

    echo '
    <p><strong>'.tr('Articolo').':</strong> '.$articolo['codice'].' - '.$articolo['descrizione'].'.</p>
    <input type="hidden" name="idarticolo" id="idarticolo" value="'.$articolo['id'].'">';
}

echo '
    <input type="hidden" name="qta_minima" id="qta_minima" value="'.$qta_minima.'">';

// Selezione impianto per gli Interventi
if ($module['name'] == 'Interventi') {
    echo '
<div class="row">
    <div class="col-md-12">
        {[ "type": "select", "label": "'.tr('Impianto su cui installare').'", "name": "idimpianto", "value": "'.$idimpianto.'", "ajax-source": "impianti-intervento" ]}
    </div>
</div>';
}

echo App::internalLoad('riga.php', $result, $options);

// Informazioni aggiuntive
if ($module['name'] != 'Contratti' && $module['name'] != 'Preventivi') {
    $disabled = empty($result['idarticolo']);

    echo '
<div class="row '.(!empty($options['nascondi_prezzi']) ? 'hidden' : '').'" id="prezzi_articolo">
    <div class="col-md-4 text-center">
        <button type="button" class="btn btn-sm btn-info btn-block '.($disabled ? 'disabled' : '').'" '.($disabled ? 'disabled' : '').' onclick="$(\'#prezziacquisto\').toggleClass(\'hide\'); $(\'#prezziacquisto\').load(\''.ROOTDIR."/ajax_complete.php?module=Articoli&op=getprezziacquisto&idarticolo=' + ( $('#idarticolo option:selected').val() || $('#idarticolo').val()) + '&idanagrafica=".$options['idanagrafica'].'\');">
            <i class="fa fa-search"></i> '.tr('Ultimi prezzi di acquisto').'
        </button>
        <div id="prezziacquisto" class="hide"></div>
    </div>

    <div class="col-md-4 text-center">
        <button type="button" class="btn btn-sm btn-info btn-block '.($disabled ? 'disabled' : '').'" '.($disabled ? 'disabled' : '').' onclick="$(\'#prezzi\').toggleClass(\'hide\'); $(\'#prezzi\').load(\''.ROOTDIR."/ajax_complete.php?module=Articoli&op=getprezzi&idarticolo=' + ( $('#idarticolo option:selected').val() || $('#idarticolo').val()) + '&idanagrafica=".$options['idanagrafica'].'\');">
            <i class="fa fa-search"></i> '.tr('Ultimi prezzi al cliente').'
        </button>
        <div id="prezzi" class="hide"></div>
    </div>

    <div class="col-md-4 text-center">
        <button type="button" class="btn btn-sm btn-info btn-block '.($disabled ? 'disabled' : '').'" '.($disabled ? 'disabled' : '').' onclick="$(\'#prezzivendita\').toggleClass(\'hide\'); $(\'#prezzivendita\').load(\''.ROOTDIR."/ajax_complete.php?module=Articoli&op=getprezzivendita&idarticolo=' + ( $('#idarticolo option:selected').val() || $('#idarticolo').val()) + '&idanagrafica=".$options['idanagrafica'].'\');">
            <i class="fa fa-search"></i> '.tr('Ultimi prezzi di vendita').'
        </button>
        <div id="prezzivendita" class="hide"></div>
    </div>
</div>
<br>';
}

echo '
<script>
$(document).ready(function () {
    $("#idarticolo").on("change", function() {
        // Autoimpostazione dei valori relativi
        if ($(this).val()) {
            session_set("superselect,idarticolo", $(this).val(), 0);
            session_set("superselect,idanagrafica", "'.$options['idanagrafica'].'", 0);
            session_set("superselect,dir", "'.$options['dir'].'", 0);

            $data = $(this).selectData();

            $("#prezzo_unitario").val($data.prezzo_'.($options['dir'] == 'entrata' ? 'vendita' : 'acquisto').');
            $("#costo_unitario").val($data.prezzo_acquisto);
            $("#descrizione_riga").val($data.descrizione);';

if ($options['dir'] == 'entrata') {
    echo '
            if($data.idiva_vendita) {
                $("#idiva").selectSetNew($data.idiva_vendita, $data.iva_vendita);
            }';
} else {
    echo '
            $("#id_dettaglio_fornitore").val($data.id_dettaglio_fornitore);
            $("#qta_minima").val($data.qta_minima);
            aggiorna_qta_minima();';
}

echo '

            var id_conto = $data.idconto_'.($options['dir'] == 'entrata' ? 'vendita' : 'acquisto').';
            if(id_conto) {
                $("#idconto").selectSetNew(id_conto, $data.idconto_'.($options['dir'] == 'entrata' ? 'vendita' : 'acquisto').'_title);
            }

            $("#um").selectSetNew($data.um, $data.um);
            // Aggiornamento automatico di guadagno e margine
            aggiorna_guadagno();
        }';

if ($module['name'] != 'Contratti' && $module['name'] != 'Preventivi') {
    echo '

        // Operazioni sui prezzi in fondo alla pagina
        $("#prezzi_articolo button").attr("disabled", !$(this).val());

        if ($(this).val()) {
            $("#prezzi_articolo button").removeClass("disabled");
        } else {
            $("#prezzi_articolo button").addClass("disabled");
        }

        $("#prezzi").html("");
        $("#prezzivendita").html("");
        $("#prezziacquisto").html("");';
}

echo '
    });';

if ($options['dir'] == 'uscita') {
    echo '

    aggiorna_qta_minima();
    $("#qta").keyup(aggiorna_qta_minima);';
}

echo '
});';

if ($options['dir'] == 'uscita') {
    echo '
// Funzione per l\'aggiornamento in tempo reale del guadagno
function aggiorna_qta_minima() {
    var qta_minima = parseFloat($("#qta_minima").val());
    var qta = $("#qta").val().toEnglish();

    if (qta_minima == 0) {
        return;
    }

    var parent = $("#qta").closest("div").parent();
    var div = parent.find("div[id*=\"errors\"]");

    div.html("<small>'.tr('Quantità minima').': " + qta_minima.toLocale() + "</small>");
    if (qta < qta_minima) {
        parent.addClass("has-error");
        div.addClass("text-danger").removeClass("text-success");
    } else {
        parent.removeClass("has-error");
        div.removeClass("text-danger").addClass("text-success");
    }
}';
}
echo '
</script>';
