<?php

use Plugins\PianificazioneFatturazione\Pianificazione;

include_once __DIR__.'/../../../core.php';

$pianificazioni = Pianificazione::doesntHave('fattura')->get();
if ($pianificazioni->isEmpty()) {
    echo '
<p>'.tr('Non ci sono fatture da emettere').'.</p>';

    return;
}

$raggruppamenti = $pianificazioni->groupBy(function ($item) {
    return ucfirst($item->data_scadenza->formatLocalized('%B %Y'));
});

$counter = 0;
foreach ($raggruppamenti as $mese => $raggruppamento) {
    ++$counter;

    // Se cambia il mese ricreo l'intestazione della tabella
    if ($counter == 1) {
        $attr = '';
        $class = 'fa-minus-circle';
    } else {
        $attr = 'style="display:none;"';
        $class = 'fa-plus-circle';
    }

    echo "
<h4>
    <a class='clickable' onclick=\"if( $('#rate_pianificare_".$counter."').css('display') == 'none' ){ $(this).children('i').removeClass('fa-plus-circle'); $(this).children('i').addClass('fa-minus-circle'); }else{ $(this).children('i').addClass('fa-plus-circle'); $(this).children('i').removeClass('fa-minus-circle'); } $('#rate_pianificare_".$counter."').slideToggle();\">
        <i class='fa ".$class."'></i> ".$mese.'
    </a>
</h4>';

    echo '
<div id="rate_pianificare_'.$counter.'" '.$attr.'>
    <table class="table table-hover table-striped">
        <thead>
            <tr>
                <th width="25%">'.tr('Entro il').'</th>
                <th width="35%">'.tr('Ragione sociale').'</th>
                <th width="20%">'.tr('Importo').'</th>
                <th width="10%"></th>
            </tr>
        </thead>

        <tbody>';

    // Elenco fatture da emettere
    foreach ($pianificazioni as $pianificazione) {
        $contratto = $pianificazione->contratto;
        $anagrafica = $contratto->anagrafica;

        echo '
            <tr>
                <td>
                    '.dateFormat($pianificazione->data_scadenza).'
                    <br><small>'.reference($contratto).'</small>
                </td>

                <td>
                    '.Modules::link('Anagrafiche', $anagrafica->id, nl2br($anagrafica->ragione_sociale)).'
                </td>

                <td>
                    '.moneyFormat($pianificazione->totale).'<br>
                    <small>'.tr('_TOT_ / _NUM_ rate', [
                        '_TOT_' => moneyFormat($contratto->totale),
                        '_NUM_' => numberFormat($contratto->pianificazioni()->count(), 0),
                    ]).'</small>
                </td>';

        // Pulsanti
        echo '
                <td class="text-center">
                    <button type="button" class="btn btn-primary btn-sm" onclick="crea_fattura('.$contratto->id.', '.$pianificazione->id.')">
                        <i class="fa fa-euro"></i> '.tr('Crea fattura').'
                    </button>
                </td>
            </tr>';
    }

    echo '
        </tbody>
    </table>
</div>';
}

$modulo_pianificazione = Modules::get('Contratti');
$plugin_pianificazione = Plugins::get('Pianificazione fatturazione');
echo '
<script>
function crea_fattura(contratto, rata){
    openModal("Crea fattura", "'.$plugin_pianificazione->fileurl('crea_fattura.php').'?id_module='.$modulo_pianificazione->id.'&id_plugin='.$plugin_pianificazione->id.'&id_record=" + contratto + "&rata=" + rata);
}
</script>';
