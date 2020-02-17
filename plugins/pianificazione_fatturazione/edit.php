<?php

include_once __DIR__.'/../../../core.php';

use Modules\Contratti\Contratto;
use Modules\Contratti\Stato;

$contratto = Contratto::find($id_record);
$is_pianificabile = $contratto->stato->is_pianificabile && !empty($contratto['data_accettazione']); // Contratto permette la pianificazione

$stati_pianificabili = Stato::where('is_pianificabile', 1)->get();
$elenco_stati = $stati_pianificabili->implode('descrizione', ', ');

echo '
<p>'.tr('Qui puoi pianificare la suddivisione del budget del contratto in rate uguali fatturabili in modo separato').'. '.tr('Questa procedura può essere effettuata solo una volta, e sovrascriverà in modo irreversibile tutte le righe del contratto').'.</p>
<p>'.tr('Per poter procedere, il contratto deve avere <b>data accettazione</b> e <b>data conclusione</b> definita ed essere in uno dei seguenti stati: _LINK_', [
    '_LINK_' => '<b>'.$elenco_stati.'</b>',
]).'.</p>

<div class="alert alert-warning">
    <i class="fa fa-warning"></i> '.tr("Tutte le righe del contratto vengono convertite in righe generiche, rendendo impossibile risalire ad eventuali articoli utilizzati all'interno del contratto e pertanto non movimentando il magazzino").'.
</div>';

$pianificazioni = $contratto->pianificazioni;
if (!$pianificazioni->isEmpty()) {
    echo '
<hr>
<table class="table table-bordered table-striped table-hover table-condensed">
    <thead>
        <tr>
            <th width="10%">'.tr('Scadenza').'</th>
            <th width="15%">'.tr('Importo').'</th>
            <th>'.tr('Documento').'</th>
            <th width="12%">#</th>
        </tr>
    </thead>
    <tbody>';

    $previous = null;
    foreach ($pianificazioni as $rata => $pianificazione){
        echo '
        <tr>
            <td>';

        // Data scadenza
        if (!$pianificazione->data_scadenza->equalTo($previous)) {
            $previous = $pianificazione->data_scadenza;
            echo '
                <b>'.$pianificazione->data_scadenza->formatLocalized('%B %Y').'</b>';
        }

        echo '
            </td>

            <td class="center">
                '.moneyFormat($pianificazione->totale).'
            </td>';

        // Documento collegato
        echo '
            <td>';
        $fattura = $pianificazione->fattura;
        if (!empty($fattura)) {
            echo '
                '.Modules::link('Fatture di vendita', $fattura->id, tr('Fattura num. _NUM_ del _DATE_', [
                '_NUM_' => $fattura->numero,
                '_DATE_' => dateFormat($fattura->data),
            ])).' (<i class="'.$fattura->stato->icona.'"></i> '.$fattura->stato->descrizione.')';
        } else {
            echo '
                <i class="fa fa-clock-o"></i> '.tr('Non ancora fatturato');
        }
        echo '
            </td>';

        // Creazione fattura
        echo '
            <td>
                <button type="button" class="btn btn-primary btn-sm '.(!empty($fattura) ? 'disabled' : '').'" '.(!empty($fattura) ? 'disabled' : '').' onclick="crea_fattura('.$rata.')">
                    <i class="fa fa-euro"></i> '.tr('Crea fattura').'
                </button>
            </td>
        </tr>';
    }

    echo '
    </tbody>
</table>';
} else {
    echo '
<div class="alert alert-info">
    <i class="fa fa-warning"></i> '.tr('Pianificazione non ancora effettuata per il contratto corrente').'.
</div>

<button type="button" '.(!empty($is_pianificabile) ? '' : 'disabled').' title="'.tr('Aggiungi una nuova pianificazione').'" data-toggle="tooltip" class="btn btn-primary pull-right tip" id="pianifica">
    <i class="fa fa-plus"></i> '.tr('Pianifica').'
</button>
<div class="clearfix"></div>';
}

echo '
<script type="text/javascript">
	$("#pianifica").click(function() {
        openModal("Nuova pianificazione", "'.$structure->fileurl('add_pianificazione.php').'?id_module='.$id_module.'&id_plugin='.$id_plugin.'&id_record='.$id_record.'");
	});

	function crea_fattura(rata){
        openModal("Crea fattura", "'.$structure->fileurl('crea_fattura.php').'?id_module='.$id_module.'&id_plugin='.$id_plugin.'&id_record='.$id_record.'&rata=" + rata);
	}
</script>';
