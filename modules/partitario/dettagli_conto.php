<?php

include_once __DIR__.'/../../core.php';

$prima_nota = Modules::get('Prima nota');

$id_conto = get('id_conto');

// Calcolo totale conto da elenco movimenti di questo conto
$query = 'SELECT co_movimenti.*,
    SUM(totale) AS totale,
    dir FROM co_movimenti
LEFT OUTER JOIN co_documenti ON co_movimenti.iddocumento = co_documenti.id
LEFT OUTER JOIN co_tipidocumento ON co_documenti.idtipodocumento = co_tipidocumento.id
WHERE co_movimenti.idconto='.prepare($id_conto).' AND
    co_movimenti.data >= '.prepare($_SESSION['period_start']).' AND
    co_movimenti.data <= '.prepare($_SESSION['period_end']).'
GROUP BY co_movimenti.idmastrino
ORDER BY co_movimenti.data DESC';
$movimenti = $dbo->fetchArray($query);

// Se il conto fa parte dello stato patrimoniale, sommo i movimenti del periodo precedente
$primo_livello = $dbo->fetchOne('SELECT co_pianodeiconti1.descrizione FROM co_pianodeiconti1 INNER JOIN co_pianodeiconti2 ON co_pianodeiconti1.id=co_pianodeiconti2.idpianodeiconti1 INNER JOIN co_pianodeiconti3 ON co_pianodeiconti2.id=co_pianodeiconti3.idpianodeiconti2 WHERE co_pianodeiconti3.id='.prepare($id_conto))['descrizione'];

if ($primo_livello == 'Patrimoniale') {
    $saldo_precedente = $dbo->fetchOne('SELECT SUM(totale) AS totale FROM co_movimenti WHERE idconto='.prepare($id_conto).' AND data < '.prepare($_SESSION['period_start']).' GROUP BY idconto')['totale'];

    $movimenti_precedenti[] =
        [
           'id' => null,
           'idmastrino' => null,
           'data' => $_SESSION['period_start'],
           'data_documento' => null,
           'iddocumento' => 0,
           'id_scadenza' => null,
           'is_insoluto' => 0,
           'idanagrafica' => 0,
           'descrizione' => 'Apertura conti',
           'idconto' => $idconto,
           'totale' => $saldo_precedente,
           'primanota' => 0,
        ];
    $movimenti = array_merge($movimenti_precedenti, $movimenti);
}

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

        if (!empty($movimento['primanota'])) {
            $modulo_fattura = ($movimento['dir'] == 'entrata') ? Modules::get('Fatture di vendita')['id'] : Modules::get('Fatture di acquisto')['id'];

            echo Modules::link($prima_nota->id, $movimento['idmastrino'], $movimento['descrizione']);
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
function open_movimento(id_mastrino, id_module){
    launch_modal("'.tr('Dettagli movimento').'", "'.$structure->fileurl('dettagli_movimento.php').'?id_mastrino=" + id_mastrino + "&id_module=" + id_module);
}
</script>';
} else {
    echo '
<p>'.tr('Nessun movimento presente').'</p>';
}
