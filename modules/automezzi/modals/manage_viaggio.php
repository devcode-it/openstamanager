<?php

include_once __DIR__.'/../../../core.php';

$idviaggio = get('idviaggio');
$idautomezzo = get('id_record');

// Se è presente idviaggio, recupero i dati per la modifica
if (!empty($idviaggio)) {
    $viaggio = $dbo->fetchOne('SELECT * FROM an_automezzi_viaggi WHERE id='.prepare($idviaggio));
    $op = 'editviaggio';
    $button_icon = 'fa-edit';
    $button_text = tr('Modifica');
} else {
    // Valori di default per nuovo viaggio
    $viaggio = [
        'idtecnico' => '',
        'data_inizio' => '-now-',
        'data_fine' => '-now-',
        'km_inizio' => '',
        'km_fine' => '',
        'destinazione' => '',
        'motivazione' => '',
    ];
    $op = 'addviaggio';
    $button_icon = 'fa-plus';
    $button_text = tr('Aggiungi');
}

/*
    Form di inserimento/modifica viaggio
*/
echo '
<form id="viaggio_form" action="" method="post">
    <input type="hidden" name="op" value="'.$op.'">
    <input type="hidden" name="backto" value="record-edit">
    <input type="hidden" name="id_module" value="'.$id_module.'">
    <input type="hidden" name="id_record" value="'.$id_record.'">';

if (!empty($idviaggio)) {
    echo '
    <input type="hidden" name="idviaggio" value="'.$idviaggio.'">';
}

echo '
    <div class="row">';

// Tecnico
echo '
        <div class="col-md-12">
            {[ "type": "select", "label": "'.tr('Tecnico').'", "name": "idtecnico", "required": 1, "ajax-source": "tecnici_automezzo", "select-options": {"idautomezzo": '.$idautomezzo.'}, "value": "'.$viaggio['idtecnico'].'" ]}
        </div>';

// Data
echo '
        <div class="col-md-6">
            {[ "type": "timestamp", "label": "'.tr('Data inizio viaggio').'", "name": "data_inizio", "required": 1, "value": "'.$viaggio['data_inizio'].'" ]}
        </div>

        <div class="col-md-6">
            {[ "type": "timestamp", "label": "'.tr('Data fine viaggio').'", "name": "data_fine", "value": "'.$viaggio['data_fine'].'" ]}
        </div>';

// KM Inizio
echo '
        <div class="col-md-6">
            {[ "type": "number", "label": "'.tr('KM Inizio').'", "name": "km_inizio", "required": 1, "decimals": "qta", "value": "'.$viaggio['km_inizio'].'", "decimals": 0 ]}
        </div>';

// KM Fine
echo '
        <div class="col-md-6">
            {[ "type": "number", "label": "'.tr('KM Fine').'", "name": "km_fine", "decimals": "qta", "value": "'.$viaggio['km_fine'].'", "decimals": 0 ]}
        </div>';

echo '
    </div>

    <div class="row">';

// Destinazione
echo '
        <div class="col-md-6">
            {[ "type": "text", "label": "'.tr('Destinazione').'", "name": "destinazione", "required": 1, "value": "'.$viaggio['destinazione'].'" ]}
        </div>';

// Motivazione
echo '
        <div class="col-md-6">
            {[ "type": "text", "label": "'.tr('Motivazione').'", "name": "motivazione", "value": "'.$viaggio['motivazione'].'", "required": 1 ]}
        </div>';

echo '
    </div>';

echo '
    <!-- PULSANTI -->
    <div class="modal-footer">
        <div class="col-md-12 text-right">
            <button type="submit" class="btn btn-primary pull-right"><i class="fa '.$button_icon.'"></i> '.$button_text.'</button>
        </div>
    </div>
</form>';

if ($viaggio['id']) {
    echo '
    <hr>
    {( "name": "filelist_and_upload", "id_module": "$id_module$", "id_record": "$id_record$", "key": "an_automezzi_viaggi:'.$viaggio['id'].'" )}';
}

// Recupero elenco motivazioni già utilizzate per l'autocompletamento
$motivazioni = $dbo->fetchArray('SELECT DISTINCT(BINARY `motivazione`) AS `motivazione` FROM `an_automezzi_viaggi` WHERE `motivazione` IS NOT NULL AND `motivazione` != "" ORDER BY `motivazione`');
$motivazioni_list = array_clean(array_column($motivazioni, 'motivazione'));

echo '
<script>
var motivazioni = '.json_encode($motivazioni_list).';

// Auto-completamento motivazione
$(document).ready(function () {
    const input = $("input[name=\'motivazione\']")[0];

    if (input) {
        autocomplete({
            minLength: 0,
            showOnFocus: true,
            input: input,
            emptyMsg: globals.translations.noResults,
            fetch: function (text, update) {
                text = text.toLowerCase();
                const suggestions = motivazioni.filter(n => n.toLowerCase().startsWith(text));

                // Trasformazione risultati in formato leggibile
                const results = suggestions.map(function (result) {
                    return {
                        label: result,
                        value: result
                    }
                });

                update(results);
            },
            onSelect: function (item) {
                input.value = item.label;
            },
        });
    }

    init();
});
</script>';

