<?php

include_once __DIR__ . '/../../core.php';

// Generazione della tabella per l'aggiunta dei riferimenti
$id_documento = filter('id_documento');
$tipo_documento = filter('tipo_documento');
if (empty($tipo_documento) || empty($id_documento)) {
    return;
}
$documento = $tipo_documento::find($id_documento);

// Informazioni sulla riga
$source_type = filter('source_type');
$source_id = filter('source_id');
$riferimenti = filter('riferimenti');

echo '
<table class="table table-striped table-hover table-condensed table-bordered">
    <tr>
        <th>'.tr('Descrizione').'</th>
        <th>'.tr('Q.tà').' <i title="'.tr('da evadere').' / '.tr('totale').'" class="tip fa fa-question-circle-o"></i></th>
        <th class="text-center">#</th>
    </tr>

    <tbody>';

    $righe = $documento->getRighe();
    foreach ($righe as $riga) {
        $riga_class = get_class($riga);

        $riferimento_locale =  $riga_class .'|'.$riga->id;
        $presente = in_array($riferimento_locale, $riferimenti);

    echo '
    <tr data-id="'.$riga->id.'" data-type="'.$riga_class.'">
        <td>'.$riga->descrizione.'</td>
        <td>'.numberFormat($riga->qta_rimanente, 'qta').' / '.numberFormat($riga->qta, 'qta').'</td>
        <td class="text-center">
            <button type="button" class="btn btn-'.($presente ? 'success' : 'info').' btn-xs" '.($presente ? '':'onclick="salvaRiferimento(this, \''.addslashes($source_type).'\',\''.$source_id.'\')"' ).'>
                <i class="fa fa-check"></i>
            </button>
        </td>
    </tr>';
    }

    echo '
    </tbody>
</table>';
