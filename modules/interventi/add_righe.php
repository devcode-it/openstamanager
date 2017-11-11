<?php

include_once __DIR__.'/../../core.php';

$idriga = filter('idriga');

//Lettura idanagrafica cliente e percentuale di sconto/rincaro in base al listino
$rs = $dbo->fetchArray('SELECT idanagrafica, (SELECT prc_guadagno FROM mg_listini WHERE id=(SELECT idlistino_vendite FROM an_anagrafiche WHERE idanagrafica=.in_interventi.idanagrafica)) AS prc_sconto FROM in_interventi WHERE id='.prepare($id_record));
$idanagrafica = $rs[0]['idanagrafica'];
$prc_sconto = $rs[0]['prc_sconto'];

if (empty($idriga)) {
    $op = 'addriga';
    $button = '<i class="fa fa-plus"></i> '.tr('Aggiungi');

    // valori default
    $descrizione = '';
    $qta = 1;
    $um = '';
    $prezzo_vendita = '0';
    $prezzo_acquisto = '0';

    if (!empty($rs[0]['prc_guadagno'])) {
        $sconto = $rs[0]['prc_guadagno'];
        $tipo_sconto = 'PRC';
    }
} else {
    $op = 'editriga';
    $button = '<i class="fa fa-edit"></i> '.tr('Modifica');

    // carico record da modificare
    $q = 'SELECT * FROM in_righe_interventi WHERE id='.prepare($idriga);
    $rsr = $dbo->fetchArray($q);

    $descrizione = $rsr[0]['descrizione'];
    $qta = $rsr[0]['qta'];
    $um = $rsr[0]['um'];
    $prezzo_vendita = $rsr[0]['prezzo_vendita'];
    $prezzo_acquisto = $rsr[0]['prezzo_acquisto'];

    $sconto_unitario = $rsr[0]['sconto_unitario'];
    $tipo_sconto = $rsr[0]['tipo_sconto'];
}

/*
    Form di inserimento
*/
echo '
<form id="add-righe" action="'.$rootdir.'/editor.php?id_module='.$id_module.'&id_record='.$id_record.'" method="post">
    <input type="hidden" name="op" value="'.$op.'">
    <input type="hidden" name="idriga" value="'.$idriga.'">';

// Descrizione
echo '
    <div class="row">
        <div class="col-md-12">
            {[ "type": "textarea", "label": "'.tr('Descrizione').'", "id": "descrizione_riga", "name": "descrizione", "required": 1, "value": '.json_encode($descrizione).' ]}
        </div>
    </div>
    <br>';

// Quantità
echo '
    <div class="row">
        <div class="col-md-4">
            {[ "type": "number", "label": "'.tr('Q.tà').'", "name": "qta", "required": 1, "value": "'.$qta.'", "decimals": "qta" ]}
        </div>';

// Unità di misura
echo '
        <div class="col-md-4">
            {[ "type": "select", "label": "'.tr('Unità di misura').'", "icon-after": "add|'.Modules::get('Unità di misura')['id'].'", "name": "um", "value": "'.$um.'", "ajax-source": "misure" ]}
        </div>
    </div>';

// Prezzo di acquisto
echo '
    <div class="row">
        <div class="col-md-4">
            {[ "type": "number", "label": "'.tr('Prezzo di acquisto (un.)').'", "name": "prezzo_acquisto", "required": 1, "value": "'.$prezzo_acquisto.'", "icon-after": "&euro;" ]}
        </div>';

// Prezzo di vendita
echo '
        <div class="col-md-4">
            {[ "type": "number", "label": "'.tr('Prezzo di vendita (un.)').'", "name": "prezzo_vendita", "required": 1, "value": "'.$prezzo_vendita.'", "icon-after": "&euro;" ]}
        </div>';

// Sconto unitario
echo '
        <div class="col-md-4">
            {[ "type": "number", "label": "'.tr('Sconto unitario').'", "name": "sconto", "icon-after": "choice|untprc|'.$tipo_sconto.'", "value": "'.$sconto_unitario.'" ]}
        </div>
    </div>';

echo '
    <!-- PULSANTI -->
    <div class="row">
        <div class="col-md-12 text-right">
            <button type="submit" class="btn btn-primary pull-right">'.$button.'</button>
        </div>
    </div>
</form>';

echo '
<script src="'.$rootdir.'/lib/init.js"></script>';

?>

<script type="text/javascript">
    $(document).ready(function() {
        $('#add-righe').ajaxForm({
            success: function(){
                $('#bs-popup').modal('hide');

                // Ricarico le righe
                $('#righe').load(globals.rootdir + '/modules/interventi/ajax_righe.php?id_module=<?php echo $id_module ?>&id_record=<?php echo $id_record ?>');

                // Ricarico la tabella dei costi
                $('#costi').load(globals.rootdir + '/modules/interventi/ajax_costi.php?id_module=<?php echo $id_module ?>&id_record=<?php echo $id_record ?>');
            }
        });
    });
</script>
