<?php

include_once __DIR__.'/../../../core.php';

$matricole = (array) post('matricole');

// Salvo gli impianti selezionati
if (filter('op') == 'link_myimpianti') {
    $matricole_old = $dbo->fetchArray('SELECT * FROM my_impianti_interventi WHERE idintervento='.prepare($id_record));
    $matricole_old = array_column($matricole_old, 'idimpianto');

    // Individuazione delle matricole mancanti
    foreach ($matricole_old as $matricola) {
        if (!in_array($matricola, $matricole)) {
            $dbo->query('DELETE FROM my_impianti_interventi WHERE idintervento='.prepare($id_record).' AND idimpianto = '.prepare($matricola));

            $components = $dbo->fetchArray('SELECT * FROM my_impianto_componenti WHERE idimpianto = '.prepare($matricola));
            if (!empty($components)) {
                foreach ($components as $component) {
                    $dbo->query('DELETE FROM my_componenti_interventi WHERE id_componente = '.prepare($component['id']).' AND id_intervento = '.prepare($id_record));
                }
            }
        }
    }

    foreach ($matricole as $matricola) {
        if (!in_array($matricola, $matricole_old)) {
            $dbo->query('INSERT INTO my_impianti_interventi(idimpianto, idintervento) VALUES('.prepare($matricola).', '.prepare($id_record).')');
        }
    }

    flash()->info(tr('Informazioni impianti salvate!'));
} elseif (filter('op') == 'link_componenti') {
    $components = (array) post('componenti');

    $list = (!empty(post('list'))) ? explode(',', post('list')) : [];
    foreach ($list as $delete) {
        if (!in_array($delete, $components)) {
            $dbo->query('DELETE FROM my_componenti_interventi WHERE id_componente = '.prepare($delete).' AND id_intervento = '.prepare($id_record));
        }
    }

    foreach ($components as $component) {
        if (!in_array($component, $list)) {
            $dbo->query('INSERT INTO my_componenti_interventi(id_componente, id_intervento) VALUES('.prepare($component).', '.prepare($id_record).')');
        }
    }

    flash()->info(tr('Informazioni componenti salvate!'));
}

//Blocco della modifica impianti se l'intervento è completato
$rss = $dbo->fetchArray('SELECT in_statiintervento.completato FROM in_statiintervento INNER JOIN in_interventi ON in_statiintervento.idstatointervento=in_interventi.idstatointervento WHERE in_interventi.id='.prepare($id_record));
$flg_completato = $rss[0]['completato'];

if ($flg_completato) {
    $readonly = 'readonly';
    $disabled = 'disabled';
} else {
    $readonly = '';
    $disabled = '';
}

// IMPIANTI
echo '
<div class="box">
    <div class="box-header with-border">
        <h3 class="box-title">'.tr("Impianti dell'intervento").'</h3>
    </div>
    <div class="box-body">
        <p>'.tr("Impianti su cui è stato effettuato l'intervento").'</p>';

$query = 'SELECT * FROM my_impianti_interventi INNER JOIN my_impianti ON my_impianti_interventi.idimpianto=my_impianti.id WHERE idintervento='.prepare($id_record);
$rs = $dbo->fetchArray($query);

echo '
        <div class="row">';

foreach ($rs as $r) {
    echo '
            <div class="col-md-3">
                <table class="table table-hover table-condensed table-striped">';

    // MATRICOLA
    echo '
                    <tr>
                        <td align="right">'.tr('Matricola').':</td>
                        <td valign="top">'.$r['matricola'].'</td>
                    </tr>';

    // NOME
    echo '
                    <tr>
                        <td align="right">'.tr('Nome').':</td>
                        <td valign="top">
                            '.Modules::link('MyImpianti', $r['id'], $r['nome']).'
                        </td>
                    </tr>';

    // DATA
    echo '
                    <tr>
                        <td align="right">'.tr('Data').':</td>
                        <td valign="top">'.Translator::dateToLocale($r['data']).'</td>
                    </tr>';

    // DESCRIZIONE
    echo '
                    <tr>
                        <td align="right">'.tr('Descrizione').':</td>
                        <td valign="top">'.$r['descrizione'].'</td>
                    </tr>';

    echo '
                    <tr>
                        <td valign="top" align="right">'.tr("Componenti soggetti all'intervento").'</td>
                        <td valign="top">
                            <form action="'.$rootdir.'/editor.php?id_module='.$id_module.'&id_record='.$id_record.'&op=link_componenti&matricola='.$r['id'].'" method="post">
                                <input type="hidden" name="backto" value="record-edit">

				                <select class="superselect" name="componenti[]" multiple '.$readonly.' '.$disabled.'>';
    $inseriti = $dbo->fetchArray('SELECT * FROM my_componenti_interventi WHERE id_intervento='.prepare($id_record));
    $inseriti = !empty($inseriti) ? array_column($inseriti, 'id_componente') : [];
    $list = [];

    $componenti = $dbo->fetchArray('SELECT * FROM my_impianto_componenti WHERE idimpianto='.prepare($r['id']).' ORDER BY id');
    if (!empty($componenti)) {
        foreach ($componenti as $componente) {
            $nome = '';
            echo '
                                    <option value="'.$componente['id'].'"';
            if (in_array($componente['id'], $inseriti)) {
                echo ' selected="selected"';
                $list[] = $componente['id'];
            }
            if (str_contains($componente['contenuto'], '[Matricola]')) {
                $ini_array = parse_ini_string($componente['contenuto'], true);
                foreach ($ini_array as $sezione => $array_impostazioni) {
                    if ($sezione == 'Matricola') {
                        $nome .= $ini_array[$sezione]['valore'].' - ';
                    }
                }
            }
            $nome .= $componente['nome'];
            echo ' title="'.addslashes($componente['nome']).'">'.$nome.'</option>';
        }
    }
    echo '
                                </select><br><br>
                                <input type="hidden" name="list" value="'.implode(',', $list).'">

                                <button type="submit" class="btn btn-success" '.$disabled.'><i class="fa fa-check"></i> '.tr('Salva componenti').'</button>
                            </form>
                        </td>
                    </tr>
                </table>
            </div>';
}

echo '
        </div>';

/*
    Aggiunta impianti all'intervento
*/
// Elenco impianti collegati all'intervento
$impianti = $dbo->fetchArray('SELECT idimpianto FROM my_impianti_interventi WHERE idintervento='.prepare($id_record));
$impianti = !empty($impianti) ? array_column($impianti, 'idimpianto') : [];

// Elenco sedi
$sedi = $dbo->fetchArray('SELECT id, nomesede, citta FROM an_sedi WHERE idanagrafica='.prepare($record['idanagrafica'])." UNION SELECT 0, 'Sede legale', '' ORDER BY id");

echo '
        <p><strong>'.tr('Impianti disponibili').'</strong></p>
        <form action="'.$rootdir.'/editor.php?id_module='.$id_module.'&id_record='.$id_record.'&op=link_myimpianti" method="post">
            <input type="hidden" name="backto" value="record-edit">
            <div class="row">
                <div class="col-xs-12 col-md-6">
                    {[ "type": "select", "name": "matricole[]", "multiple": 1, "value": "'.implode(',', $impianti).'", "values": "query=SELECT my_impianti.id, CONCAT(matricola, \' - \', nome) AS descrizione, CONCAT(nomesede, IF(citta IS NULL OR citta = \'\', \'\', CONCAT(\' (\', citta, \')\'))) AS optgroup FROM my_impianti JOIN (SELECT id, nomesede, citta FROM an_sedi UNION SELECT 0 AS id, \'Sede legale\' AS nomesede, \'\' AS citta) AS t ON t.id = my_impianti.idsede WHERE idanagrafica='.prepare($record['idanagrafica']).' ORDER BY idsede ASC, matricola ASC", "extra": "'.$readonly.'" ]}
                </div>
            </div>
            <br><br>

            <button type="submit" class="btn btn-success" '.$disabled.'><i class="fa fa-check"></i> '.tr('Salva impianti').'</button></a>
        </form>';

echo '
    </div>
</div>';
