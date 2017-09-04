<?php

include_once __DIR__.'/../../../core.php';

// Rimuove il collegamento dell'impianto dall'anagrafica
if (filter('op') == 'unlink_my_impianti') {
    $matricola = filter('matricola');
    $dbo->query('DELETE FROM my_impianti WHERE idanagrafica='.prepare($id_record).' AND id='.prepare($matricola));

    $_SESSION['infos'][] = tr('Impianto rimosso!');
}

// IMPIANTI
echo '
<div class="box">
    <div class="box-header with-border">
        <h3 class="box-title">'.tr('Impianti del cliente').'</h3>
    </div>
    <div class="box-body">';

// Verifico se l'anagrafica è un cliente
$rs = $dbo->fetchNum('SELECT idtipoanagrafica FROM an_tipianagrafiche_anagrafiche WHERE idanagrafica = '.prepare($id_record)." AND idtipoanagrafica = (SELECT idtipoanagrafica FROM an_tipianagrafiche WHERE descrizione='Cliente')");

if (!empty($rs)) {
    $rs = $dbo->fetchArray('SELECT * FROM my_impianti WHERE idanagrafica='.prepare($id_record));

    if (!empty($rs)) {
        foreach ($rs as $r) {
            echo '
        <div class="col-md-3">
            <table class="table table-striped table-condensed table-hover">';

            // MATRICOLA
            echo '
                <tr>
                    <td>'.tr('Matricola').':</td>
                    <td>
                        '.Modules::link('MyImpianti', $r['id'], '<strong>'.$r['matricola'].'</strong>').'

                        <a class="btn btn-sm btn-danger ask pull-right" data-backto="record-edit" data-op="unlink_my_impianti" data-matricola="'.$r['id'].'">
                            <i class="fa fa-trash"></i>
                        </a>
                    </td>
                </tr>';

            // NOME
            echo '
                <tr>
                    <td>'.tr('Nome').':</td>
                    <td>'.$r['nome'].'</td>
                </tr>';

            // DATA
            echo '
                <tr>
                    <td>'.tr('Data').':</td>
                    <td>'.Translator::dateToLocale($r['data']).'</td>
                </tr>';

            // DESCRIZIONE
            echo '
                <tr>
                    <td>'.tr('Descrizione').':</td>
                    <td>'.$r['descrizione'].'</td>
                </tr>
            </table>
        </div>';
        }
    } else {
        echo '
        <p>'.tr('Questa anagrafica non ha impianti').'...</p>';
    }
} else {
    echo '
        <p>'.tr("L'anagrafica corrente non è di tipo 'Cliente'").'.</p>';
}

echo '
    </div>
</div>';
