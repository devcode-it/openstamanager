<?php

use Modules\DDT\DDT;
use Modules\Ordini\Ordine;

include_once __DIR__.'/../../core.php';
include_once __DIR__.'/init.php';

$direzione = 'uscita';
$id_riga = get('id_riga');
$qta = get('qta');

$id_documento = get('id_documento');
$tipo_documento = get('tipo_documento');
if ($tipo_documento == 'ordine') {
    $documento = Ordine::find($id_documento);
    $righe_utilizzate = get('righe_ordini');
} else {
    $documento = DDT::find($id_documento);
    $righe_utilizzate = get('righe_ddt');
}

echo '
<table class="table table-striped table-hover table-condensed table-bordered">
    <tr>
        <th>'.tr('Descrizione').'</th>
        <th width="120">'.tr('Q.tà').' <i title="'.tr('da evadere').' / '.tr('totale').'" class="tip fa fa-question-circle-o"></i></th>
        <th class="text-center" width="60">#</th>
    </tr>

    <tbody>';

$id_riferimento = get('id_riferimento');
$righe = $documento->getRighe();
foreach ($righe as $riga) {
    $qta_rimanente = $riga->qta_rimanente - $righe_utilizzate[$riga->id];

    $dettagli = [
        'tipo' => get_class($riga),
        'id' => $riga->id,
        'qta' => $riga->qta,
        'prezzo_unitario' => $riga->prezzo_unitario,
        'id_iva' => $riga->id_iva,
        'iva_percentuale' => $riga->aliquota->percentuale,
    ];

    echo '
        <tr '.($id_riferimento == $riga->id ? 'class="success"' : '').' data-dettagli='.json_encode($dettagli).'>
            <td>'.$riga->descrizione.'</td>
            <td>'.numberFormat($qta_rimanente, 'qta').' / '.numberFormat($riga->qta, 'qta').'</td>
            <td class="text-center">';

    if ($qta_rimanente >= $qta) {
        echo '
                <button type="button" class="btn btn-info btn-xs" onclick="selezionaRiga(this)">
                    <i class="fa fa-check"></i>
                </button>';
    }

    echo '
            </td>
        </tr>';
}

echo '
    </tbody>
</table>

<script>
var documento_importazione = {
    tipo: "'.$tipo_documento.'",
    id: "'.$id_documento.'",
    descrizione: "Rif. '.$tipo_documento.' num. '.$documento->numero.'",
};

function selezionaRiga(button) {
    let riga = $(button).closest("tr");

    let dettagli_riga = riga.data("dettagli");
    impostaRiferimento("'.$id_riga.'", documento_importazione, dettagli_riga);

    $(button).closest(".modal").modal("hide");
}
</script>';
