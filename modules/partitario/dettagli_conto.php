<?php

include_once __DIR__.'/../../core.php';

$id_conto = get('id_conto');

// Calcolo totale conto da elenco movimenti di questo conto
$query = 'SELECT co_movimenti.*, dir FROM co_movimenti
                LEFT OUTER JOIN co_documenti ON co_movimenti.iddocumento = co_documenti.id
                LEFT OUTER JOIN co_tipidocumento ON co_documenti.idtipodocumento = co_tipidocumento.id
            WHERE co_movimenti.idconto='.prepare($id_conto).' AND co_movimenti.data >= '.prepare($_SESSION['period_start']).' AND co_movimenti.data <= '.prepare($_SESSION['period_end']).' ORDER BY co_movimenti.data DESC';
$movimenti = $dbo->fetchArray($query);

if (!empty($movimenti)) {
    echo '
<table class="table table-bordered table-hover table-condensed table-striped">
    <tr>
        <th>'.tr('Causale').'</th>
        <th width="100">'.tr('Data').'</th>
        <th width="100">'.tr('Dare').'</th>
        <th width="100">'.tr('Avere').'</th>
    </tr>';

    // Elenco righe del partitario
    foreach ($movimenti as $movimento) {
        echo '
    <tr>
        <td>';

        if (!empty($movimento['iddocumento'])) {
            $modulo_fattura = ($movimento['dir'] == 'entrata') ? Modules::get('Fatture di vendita')['id'] : Modules::get('Fatture di acquisto')['id'];

            echo '
            <a class="clickable" onclick="open_movimento('.$movimento['id'].', '.$modulo_fattura.')">'.$movimento['descrizione'].'</a>';
        } else {
            echo '
            <span>'.$movimento['descrizione'].'</span>';
        }

        echo '
        </td>';

        // Data
        echo '
        <td>
            '.dateFormat($movimento['data']).'
        </td>';

        // Dare
        if ($movimento['totale'] > 0) {
            echo '
        <td class="text-right">
            '.moneyFormat(abs($movimento['totale']), 2).'
        </td>
        <td></td>';
        }

        // Avere
        else {
            echo '
        <td></td>
        <td class="text-right">
            '.moneyFormat(abs($movimento['totale']), 2).'
        </td>';
        }

        echo '
    </tr>';
    }

    echo '
</table>

<script>
function open_movimento(id_movimento, id_module){
    launch_modal("'.tr('Dettagli movimento').'", "'.$structure->fileurl('dettagli_movimento.php').'?id_movimento=" + id_movimento + "&id_module=" + id_module);
}
</script>';
}
