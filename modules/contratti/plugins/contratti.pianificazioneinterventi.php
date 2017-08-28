<?php

include_once __DIR__.'/../../../core.php';

// Pianificazione intervento
switch (filter('op')) {
    case 'pianifica':
        $data_richiesta = filter('data_richiesta');
        $idtipointervento = filter('idtipointervento');
        $richiesta = filter('richiesta');
        $idsede = filter('idsede_c');

        $query = 'INSERT INTO `co_righe_contratti`(`idcontratto`, `idtipointervento`, `data_richiesta`, `richiesta`, `idsede`) VALUES('.prepare($id_record).', '.prepare($idtipointervento).', '.prepare($data_richiesta).', '.prepare($richiesta).', '.prepare($idsede).')';

        if (isset($id_record)) {
            if ($dbo->query($query)) {
                $_SESSION['infos'][] = _('Intervento pianificato!');
            } else {
                $_SESSION['errors'][] = _("Errore durante l'aggiunta dell'intervento!");
            }
        }
        break;

    // Eliminazione intervento
    case 'depianifica':
        $id = filter('id');

        $dbo->query('DELETE FROM `co_righe_contratti` WHERE id='.prepare($id));
        $_SESSION['infos'][] = _('Pianificazione eliminata!');

        redirect($rootdir.'/editor.php?id_module='.$id_module.'&id_record='.$id_record.'#tab_'.$id_plugin);

        break;
}

// Righe già inserite
$qp = 'SELECT *, (SELECT descrizione FROM in_tipiintervento WHERE idtipointervento=co_righe_contratti.idtipointervento) AS tipointervento FROM co_righe_contratti WHERE idcontratto='.prepare($id_record).' ORDER BY data_richiesta ASC';
$rsp = $dbo->fetchArray($qp);

echo '
<div class="box">
    <div class="box-header with-border">
        <h3 class="box-title">'._('Pianificazione interventi').'</h3>
    </div>
    <div class="box-body">
        <p>'._('Puoi <b>pianificare dei "promemoria"</b> degli interventi da effettuare entro determinate scadenze').'</p>
        <p>'._('Questi promemoria serviranno per semplificare la pianificazione del giorno esatto di intervento nel caso, ad esempio, di interventi mensili e verranno visualizzati nella dashboard').'.</p>';
// Nessun intervento pianificato
if (count($rsp) != 0) {
    echo '
        <table class="table table-condensed table-striped table-hover">
            <thead>
                <tr>
                    <th>'._('Entro il').'</th>
                    <th>'._('Tipo intervento').'</th>
                    <th>'._('Descrizione').'</th>
                    <th>'._('Intervento collegato').'</th>
                    <th>'._('Sede').'</th>
                    <th>'._('Opzioni').'</th>
                </tr>
            </thead>
            <tbody>';

    // Elenco interventi
    for ($i = 0; $i < sizeof($rsp); ++$i) {
        //  Sede
        if ($rsp[$i]['idsede'] == '-1') {
            echo '- '.('Nessuna').' -';
        } elseif (empty($rsp[$i]['idsede'])) {
            $info_sede = _('Sede legale');
        } else {
            $rsp2 = $dbo->fetchArray("SELECT id, CONCAT( CONCAT_WS( ' (', CONCAT_WS(', ', nomesede, citta), indirizzo ), ')') AS descrizione FROM an_sedi WHERE id=".prepare($rsp[$i]['idsede']));

            $info_sede = $rsp2[0]['descrizione'];
        }

        // Intervento svolto
        if (!empty($rsp[$i]['idintervento'])) {
            $rsp2 = $dbo->fetchArray('SELECT id, codice, (SELECT MIN(orario_inizio) FROM in_interventi_tecnici WHERE idintervento=in_interventi.id) AS data FROM in_interventi WHERE id='.prepare($rsp[$i]['idintervento']));

            $info_intervento = Modules::link('Interventi', $rsp2[0]['id'], str_replace(['_NUM_', '_DATE_'], [$rsp2[0]['codice'], Translator::dateToLocale($rsp2[0]['data'])], _('Intervento _NUM_ del _DATE_')));
        } else {
            $info_intervento = '- '.('Nessuno').' -';
        }
        echo '
                <tr>
                    <td>'.Translator::dateToLocale($rsp[$i]['data_richiesta']).'</td>
                    <td>'.$rsp[$i]['tipointervento'].'</td>
                    <td>'.nl2br($rsp[$i]['richiesta']).'</td>
                    <td>'.$info_intervento.'</td>
                    <td>'.$info_sede.'</td>
                    <td align="right">';

        if (empty($rsp[$i]['idintervento'])) {
            echo '
                        <button type="button" class="btn btn-primary btn-sm" title="Pianifica ora..." data-toggle="tooltip" onclick="launch_modal(\'Pianifica intervento\', \''.$rootdir.'/add.php?id_module='.Modules::getModule('Interventi')['id'].'&ref=interventi_contratti&idcontratto='.$id_record.'&idcontratto_riga='.$rsp[$i]['id'].'\');"><i class="fa fa-calendar"></i></button>
                        <button type="button" class="btn btn-danger btn-sm ask" data-op="depianifica" data-id="'.$rsp[$i]['id'].'">
                            <i class="fa fa-trash"></i>
                        </button>';
        }
        echo '
                    </td>
                </tr>';
    }
    echo '
            </tbody>
        </table>';
}

/*
    Nuovo intervento
*/
echo '
        <p>'._('Pianifica promemoria per un nuovo intervento').':</p>
        <form action="" method="post">
            <input type="hidden" name="backto" value="record-edit">
            <input type="hidden" name="op" value="pianifica">

            <table class="table table-condensed table-striped table-hover">
                <thead>
                    <tr>
                        <th>'._('Entro il').'</th>
                        <th>'._('Tipo intervento').'</th>
                        <th>'._('Descrizione').'</th>
                        <th>'._('Sede').'</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            {[ "type": "date", "placeholder": "'._('Entro il').'", "name": "data_richiesta", "required": 1, "value": "" ]}
                        </td>
                        <td>
                            {[ "type": "select", "placeholder": "'._('Tipo intervento').'", "name": "idtipointervento", "values": "query=SELECT idtipointervento AS id, descrizione FROM in_tipiintervento ORDER BY descrizione ASC", "value": "'.$rsp[0]['idtipointervento'].'" ]}
                        </td>
                        <td>
                            {[ "type": "textarea", "placeholder": "'._('Descrizione').'", "name": "richiesta" ]}
                        </td>
                        <td>
                            {[ "type": "select", "placeholder": "'._('Sede').'", "name": "idsede_c", "values": "query=SELECT 0 AS id, \'Sede legale\' AS descrizione UNION SELECT id, CONCAT( CONCAT_WS( \' (\', CONCAT_WS(\', \', `nomesede`, `citta`), `indirizzo` ), \')\') AS descrizione FROM an_sedi WHERE idanagrafica='.$records[0]['idanagrafica'].'", "value": "0" ]}
                        </td>
                    </tr>
                </tbody>
            </table>

            <div class="pull-right">
                <button type="submit" class="btn btn-primary"><i class="fa fa-plus"></i> '._('Aggiungi').'</button>
            </div>
            <div class="clearfix"></div>
        </form>
    </div>
</div>';
