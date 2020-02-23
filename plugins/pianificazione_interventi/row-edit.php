<?php

use Plugins\PianificazioneInterventi\Promemoria;

include_once __DIR__.'/../../core.php';

$documento = Promemoria::find($id_record);

// Impostazioni per la gestione
$options = [
    'op' => 'manage_riga',
    'action' => 'edit',
    'dir' => $documento->direzione,
    'idanagrafica' => $documento['idanagrafica'],
    'totale_imponibile' => $documento->totale_imponibile,
    'id_plugin' => $id_plugin, // Modificato
];

// Dati della riga
$id_riga = get('idriga');
$type = get('type');
$riga = $documento->getRiga($type, $id_riga);

$result = $riga->toArray();
$result['prezzo'] = $riga->prezzo_unitario;

// Importazione della gestione dedicata
$file = 'riga';
if ($riga->isDescrizione()) {
    $file = 'descrizione';

    $options['op'] = 'manage_descrizione';
} elseif ($riga->isArticolo()) {
    $file = 'articolo';

    $options['op'] = 'manage_articolo';
} elseif ($riga->isSconto()) {
    $file = 'sconto';

    $options['op'] = 'manage_sconto';
}

// Modificato
echo '
<div id="riga-promemoria">';

echo App::load($file.'.php', $result, $options);

echo '
</div>
<script type="text/javascript">
    $(document).ready(function() {
        $("#riga-promemoria").ajaxForm({
            success: function(responseText, statusText, xhr, form){
                $(form).closest(".modal").modal("hide");

                refreshRighe('.$id_record.');
            }
        });
    });
</script>';
