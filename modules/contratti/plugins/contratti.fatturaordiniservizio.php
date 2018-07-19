<?php

include_once __DIR__.'/../../../core.php';

include_once Modules::filepath('Fatture di vendita', 'modutil.php');

/*
    GESTIONE ORDINI DI SERVIZIO
*/
$mesi = [
    tr('Gennaio'),
    tr('Febbraio'),
    tr('Marzo'),
    tr('Aprile'),
    tr('Maggio'),
    tr('Giugno'),
    tr('Luglio'),
    tr('Agosto'),
    tr('Settembre'),
    tr('Ottobre'),
    tr('Novembre'),
    tr('Dicembre'),
];

// Pianificazione fatture
if (get('op') == 'add_fatturazione') {
    $prev_data = '';

    // Azzero la pianificazione zone se era già stata fatta, per poter sostituire la pianificazione,
    // mantenendo però le pianificazioni già fatturate
    foreach (post('zona') as $data_scadenza => $zone) {
        foreach ($zone as $n => $idzona) {
            $dbo->query('DELETE FROM co_ordiniservizio_pianificazionefatture WHERE idzona='.prepare($idzona).' AND iddocumento=0 AND idcontratto='.prepare($id_record));
        }
    }

    // Ciclo fra le voci in arrivo dal form
    foreach (post('zona') as $data_scadenza => $zone) {
        // Ogni data può avere più zone da pianificare
        foreach ($zone as $n => $idzona) {
            // Aggiunta pianificazione solo se la zona è spuntata
            if (in_array($idzona, post('idzona'))) {
                // Creazione pianificazione
                $dbo->query('INSERT INTO co_ordiniservizio_pianificazionefatture(idcontratto, data_scadenza, idzona, iddocumento) VALUES('.prepare($id_record).', '.prepare($data_scadenza).', '.prepare($idzona).', 0)');
            }

            $prev_data = $data_scadenza;
        }
    }

    flash()->info(tr('Pianificazione generata correttamente!'));
}

// Eliminazione pianificazione specifica
elseif (get('op') == 'del_pianificazione') {
    $idpianificazione = get('idpianificazione');

    $n = $dbo->fetchNum('SELECT id FROM co_ordiniservizio_pianificazionefatture WHERE id='.prepare($idpianificazione));

    if ($n == 1) {
        // Eliminazione ordine di servizio
        if ($dbo->query('DELETE FROM co_ordiniservizio_pianificazionefatture WHERE id='.prepare($idpianificazione))) {
            flash()->info(tr('Pianificazione eliminata correttamente!'));
        }
    }
}

// Creazione fattura pianificata
elseif (get('op') == 'addfattura') {
    $idpianificazione = get('idpianificazione');
    $descrizione = post('note');
    $data = post('data');
    $idtipodocumento = post('idtipodocumento');
    $note = post('note');

    // Lettura idanagrafica
    $rs = $dbo->fetchArray('SELECT idanagrafica FROM co_contratti WHERE id='.prepare($id_record));
    $idanagrafica = $rs[0]['idanagrafica'];

    $dir = 'entrata';
    $idconto = setting('Conto predefinito fatture di vendita');
    $numero = get_new_numerofattura($data);
    $id_segment = post('id_segment');
    $numero_esterno = get_new_numerosecondariofattura($data);

    // Tipo di pagamento + banca predefinite dall'anagrafica
    $query = 'SELECT id, (SELECT idbanca_vendite FROM an_anagrafiche WHERE idanagrafica = '.prepare($idanagrafica).') AS idbanca FROM co_pagamenti WHERE id = (SELECT idpagamento_vendite AS pagamento FROM an_anagrafiche WHERE idanagrafica='.prepare($idanagrafica).')';
    $rs = $dbo->fetchArray($query);
    $idpagamento = $rs[0]['id'];
    $idbanca = $rs[0]['idbanca'];

    // Se la fattura è di vendita e non è stato associato un pagamento predefinito al cliente leggo il pagamento dalle impostazioni
    if ($dir == 'entrata' && $idpagamento == '') {
        $idpagamento = setting('Tipo di pagamento predefinito');
    }

    // Se non è impostata la banca dell'anagrafica, uso quella del pagamento.
    if (empty($idbanca)) {
        // Banca predefinita del pagamento
        $query = 'SELECT id FROM co_banche WHERE id_pianodeiconti3 = (SELECT idconto_vendite FROM co_pagamenti WHERE id = '.prepare($idpagamento).')';
        $rs = $dbo->fetchArray($query);
        $idbanca = $rs[0]['id'];
    }

    $query = 'INSERT INTO co_documenti(numero, numero_esterno, idanagrafica, idtipodocumento, idpagamento, data, idstatodocumento, note, idsede, id_segment, idconto, idbanca) VALUES ('.prepare($numero).', '.prepare($numero_esterno).', '.prepare($idanagrafica).', '.prepare($idtipodocumento).', '.prepare($idpagamento).', '.prepare($data).", (SELECT `id` FROM `co_statidocumento` WHERE `descrizione`='Bozza'), ".prepare($note).', (SELECT idsede_fatturazione FROM an_anagrafiche WHERE idanagrafica='.prepare($idanagrafica).'), '.prepare($id_segment).', '.prepare($idconto).', '.prepare($idbanca).' )';
    $dbo->query($query);
    $iddocumento = $dbo->lastInsertedID();

    // Imposto l'iddocumento anche sulla pianificazione, giusto per tener traccia della fattura generata
    $dbo->query('UPDATE co_ordiniservizio_pianificazionefatture SET iddocumento='.prepare($iddocumento).' WHERE id='.prepare($idpianificazione));

    // Leggo quante rate si vogliono pagare per dividerle per mese
    $rs = $dbo->fetchArray('SELECT id FROM co_ordiniservizio_pianificazionefatture WHERE idcontratto='.prepare($id_record));

    // L'importo deve essere diviso per il numero di mesi
    $rs2 = $dbo->fetchArray('SELECT SUM(subtotale) AS totale FROM co_righe_contratti WHERE idcontratto='.prepare($id_record));
    $importo = $rs2[0]['totale'] / sizeof($rs);

    // Lettura iva del cliente o predefinita
    $rs2 = $dbo->fetchArray('SELECT idiva_vendite AS idiva FROM an_anagrafiche WHERE idanagrafica='.prepare($idanagrafica));
    $idiva = $rs2[0]['idiva'];

    if ($idiva != 0) {
        $rs2 = $dbo->fetchArray('SELECT * FROM co_iva WHERE id='.prepare($idiva));
    } else {
        $rs2 = $dbo->fetchArray('SELECT * FROM co_iva WHERE id='.prepare(setting('Iva predefinita')));
    }

    $desc_iva = $rs2[0]['descrizione'];

    $iva = $importo / 100 * $rs2[0]['percentuale'];
    $iva_indetraibile = $importo / 100 * $rs2[0]['indetraibile'];

    // Inserimento riga in fattura
    $dbo->query('INSERT INTO co_righe_documenti(iddocumento, descrizione, desc_iva, iva, iva_indetraibile, subtotale, um, qta, `order`) VALUES('.prepare($iddocumento).', '.prepare($descrizione).', '.prepare($desc_iva).', '.prepare($iva).', '.prepare($iva_indetraibile).', '.prepare($importo).", '-', 1, (SELECT IFNULL(MAX(`order`) + 1, 0) FROM co_righe_documenti AS t WHERE iddocumento=".prepare($iddocumento).'))');

    redirect($rootdir.'/editor.php?id_module='.Modules::get('Fatture di vendita')['id'].'&id_record='.$iddocumento.'&dir=entrata');
    exit();
}

echo '
<div class="box">
    <div class="box-header with-border">
        <h3 class="box-title">'.tr('Pianificazione fatturazione').'</h3>
    </div>
    <div class="box-body">';

echo '
<p>'.tr('Qui puoi programmare la fatturazione del contratto').'.</p>';

/*
    Fatture pianificate
*/
$rs = $dbo->fetchArray('SELECT *, (SELECT SUM(subtotale) FROM co_righe_contratti WHERE idcontratto='.prepare($id_record).') AS budget_contratto, (SELECT descrizione FROM an_zone WHERE id=idzona) AS zona FROM co_ordiniservizio_pianificazionefatture WHERE idcontratto='.prepare($id_record).' ORDER BY data_scadenza ASC');

if (empty($rs)) {
    echo '
<p>'.tr('Non sono ancora state pianificate fatture').'...</p>';
} else {
    $rs2 = $dbo->fetchArray('SELECT * FROM co_ordiniservizio_pianificazionefatture WHERE idcontratto='.prepare($id_record).' ORDER BY idzona');

    for ($i = 0; $i < sizeof($rs2); ++$i) {
        // Leggo quante rate sono pianificate per dividere l'importo delle sedi in modo corretto
        ++$n_rate[$rs2[$i]['idzona']];

        // Leggo il totale già fatturato per questa zona per toglierlo dalla divisione (totale/n_rate)
        $rs3 = $dbo->fetchArray('SELECT SUM(subtotale-sconto) AS totale FROM co_righe_documenti WHERE iddocumento IN (SELECT iddocumento FROM co_ordiniservizio_pianificazionefatture WHERE iddocumento='.prepare($rs2[$i]['iddocumento']).')');
        $gia_fatturato[$rs2[$i]['idzona']] += $rs3[0]['totale'];
    }

    echo '
<table class="table table-bordered table-striped table-hover table-condensed">
    <tr>
        <th width="10%">'.tr('Scadenza').'</th>
        <th width="15%">'.tr('Zona').'</th>
        <th width="15%">'.tr('Importo').'</th>
        <th>'.tr('Documento').'</th>
        <th width="20%">'.tr('Stato').'</th>
        <th width="12%"></th>
    </tr>';

    $prev_mese = '';
    $n_rata = 0;

    for ($i = 0; $i < sizeof($rs); ++$i) {
        // Lettura numero di sedi in cui si sono pianificati ordini di servizio per la zona corrente
        if (!empty($rs[$i]['idzona'])) {
            $n_sedi_pianificate = $dbo->fetchNum('SELECT DISTINCT(idsede) FROM my_impianti WHERE id IN (SELECT idimpianto FROM co_ordiniservizio WHERE idcontratto='.prepare($id_record).') AND idsede IN(SELECT id FROM an_sedi WHERE idzona='.prepare($rs[$i]['idzona']).')');

            // Verifico se ci sono impianti in questa zona legati alla sede legale
            $n_sedi_pianificate += $dbo->fetchNum('SELECT DISTINCT(idsede) FROM my_impianti WHERE id IN (SELECT idimpianto FROM co_ordiniservizio WHERE idcontratto='.prepare($id_record).') AND idsede=(SELECT idsede FROM an_anagrafiche WHERE idanagrafica=(SELECT idanagrafica FROM co_contratti WHERE id='.prepare($id_record).') AND idzona='.prepare($rs[$i]['idzona']).') AND idsede=0');
        }
        // Fix nel caso non siano previste sedi pianificate (l'eventuale 0 portava a problemi nel calcolo dell'importo)
        $n_sedi_pianificate = ($n_sedi_pianificate < 1) ? 1 : $n_sedi_pianificate;
        // else{
        // 	$n_sedi_pianificate = $dbo->fetchNum("SELECT (idsede) FROM my_impianti WHERE id IN (SELECT idimpianto FROM co_ordiniservizio WHERE idcontratto=\"".$id_record."\") AND idsede=0");
        // }

        echo '
    <tr>
        <td>';
        // Data scadenza
        if ($prev_mese != $rs[$i]['data_scadenza']) {
            ++$n_rata;
            echo '
            <b>'.$mesi[intval(date('m', strtotime($rs[$i]['data_scadenza']))) - 1].' '.date('Y', strtotime($rs[$i]['data_scadenza'])).'</b></td>';
        }
        echo '
        </td>';

        // Sede
        if ($rs[$i]['zona'] == '') {
            $zona = 'Altro';
        } else {
            $zona = $rs[$i]['zona'];
        }

        if ($n_sedi_pianificate == 1) {
            $n_sedi = tr('1 sede');
        } else {
            $n_sedi = tr('_NUM_ sedi', [
                '_NUM_' => $n_sedi_pianificate,
            ]);
        }

        echo '
        <td>'.$zona.' ('.$n_sedi.')</td>';

        /*
            Importo
        */
        // Se è stata emessa una fattura, bisogna utilizzare il totale della fattura da scalare al totale pianificato
        if ($rs[$i]['iddocumento'] != 0) {
            $rs2 = $dbo->fetchArray('SELECT SUM(subtotale-sconto) AS totale FROM co_righe_documenti WHERE iddocumento='.prepare($rs[$i]['iddocumento']));
            $importo = $rs2[0]['totale'];
        } else {
            // $importo = ($rs[$i]['budget_contratto'] * $n_sedi_pianificate / $n_rate[ $rs[$i]['idzona'] ]) - ($gia_fatturato[ $rs[$i]['idzona'] ] * $n_sedi_pianificate / sizeof($gia_fatturato[ $rs[$i]['idzona'] ]) );
            $importo = ($rs[$i]['budget_contratto'] * $n_sedi_pianificate / $n_rate[$rs[$i]['idzona']]);
        }

        echo '
        <td class="center">
            '.Translator::numberToLocale($importo).' &euro;<br>
            <small>'.Translator::numberToLocale($rs[$i]['budget_contratto']).' &euro; x '.$n_sedi_pianificate.' sedi / '.$n_rate[$rs[$i]['idzona']].' rate'.$extra.'</small>
        </td>';

        // Documento collegato (fattura)
        if ($rs[$i]['iddocumento'] != 0) {
            $rsf = $dbo->fetchArray('SELECT numero, numero_esterno, data, (SELECT SUM(subtotale) FROM co_righe_documenti WHERE iddocumento=co_documenti.id) AS imponibile, (SELECT icona FROM co_statidocumento WHERE id=co_documenti.idstatodocumento) AS icona, (SELECT descrizione FROM co_statidocumento WHERE id=co_documenti.idstatodocumento) AS stato FROM co_documenti WHERE id='.prepare($rs[$i]['iddocumento']));

            if ($rsf[0]['numero_esterno'] != '') {
                $numero_doc = $rsf[0]['numero_esterno'];
            } else {
                $numero_doc = $rsf[0]['numero'];
            }

            $documento = Modules::link('Fatture di vendita', $rs[$i]['iddocumento'], tr('Fattura num. _NUM_ del _DATE_', [
                '_NUM_' => $numero_doc,
                '_DATE_' => Translator::dateToLocale($rsf[0]['data']),
            ]));

            $stato = '<i class="'.$rsf[0]['icona'].'"></i> '.$rsf[0]['stato'];
        } else {
            $documento = '';
            $stato = '<i class="fa fa-clock-o"></i> '.tr('Non ancora fatturato');
        }

        // Link a fattura
        echo '
        <td>'.$documento.'</td>';

        // Stato
        echo '
        <td>'.$stato.'</td>';

        // Funzioni
        echo '
        <td>';
        if ($rs[$i]['iddocumento'] == 0) {
            // Creazione fattura
            echo "
            <button type='button' class='btn btn-primary btn-sm' onclick=\"launch_modal( 'Crea fattura', '".$rootdir.'/modules/contratti/plugins/addfattura.php?idcontratto='.$id_record.'&idpianificazione='.$rs[$i]['id'].'&importo='.$importo.'&n_rata='.$n_rata."', 1 );\">
                <i class='fa fa-euro'></i> ".tr('Crea fattura').'
            </button>';

            // Eliminazione pianificazione
            echo '
            <a class="btn btn-danger ask" data-backto="record-edit" data-method="get" data-op="del_pianificazione" data-idpianificazione="'.$rs[$i]['id'].'" data-msg="'.tr('Vuoi eliminare questa pianificazione?').'">
                <i class="fa fa-trash"></i>
            </a>';
        }

        echo '
        </td>
    </tr>';

        $prev_mese = $rs[$i]['data_scadenza'];
    }
    echo '
</table>';
}
echo '
<br><br>';

/*
    Schema per pianificare la fatturazione per zona
*/
$rs = $dbo->fetchArray('SELECT id, descrizione FROM an_zone WHERE ( id IN (SELECT idzona FROM an_sedi WHERE id IN (SELECT idsede FROM my_impianti WHERE id IN (SELECT idimpianto FROM co_ordiniservizio WHERE idcontratto='.prepare($id_record).'))) ) OR ( id=(SELECT idzona FROM an_anagrafiche WHERE idanagrafica=(SELECT idanagrafica FROM co_contratti WHERE id='.prepare($id_record).") AND idzona=an_zone.id) ) UNION SELECT 0, 'Altro'");

if (sizeof($rs) == 0) {
    echo '
<p>'.tr('Non sono ancora stati pianificati ordini di servizio').'...</p>';
}

// Elenco voci di servizio con mesi in cui eseguirle
else {
    // Calcolo mese iniziale e finale del contratto
    $rs2 = $dbo->fetchArray('SELECT data_accettazione, data_conclusione, TIMESTAMPDIFF( MONTH, data_accettazione, data_conclusione ) AS mesi FROM co_contratti WHERE id='.prepare($id_record));
    $n_mesi = $rs2[0]['mesi'] + 1;
    $mese_start = date('m', strtotime($rs2[0]['data_accettazione']));

    echo "
<button type='button' class='btn btn-primary' onclick=\"$(this).next().next().removeClass('hide'); $(this).remove();\">
    <i class='fa fa-calendar'></i> ".tr('Pianifica la fatturazione').'
</button>
<br>';

    echo "
<form action='".$rootdir.'/editor.php?id_module='.Modules::get('Contratti')['id'].'&id_record='.$id_record."&op=add_fatturazione' id='pianifica_form' method='post' class='hide'>
    <input type='hidden' name='backto' value='record-edit'>";

    // Indice zone fatturabili
    echo '
    <div class="row">
        <div class="col-md-6">
            {[ "type": "select", "label": "'.tr('Zone per le quali pianificare la fatturazione').'", "name": "idzona[]", "values": "query=SELECT id, descrizione FROM an_zone WHERE (id IN (SELECT idzona FROM an_sedi WHERE id IN (SELECT idsede FROM my_impianti WHERE id IN (SELECT idimpianto FROM co_ordiniservizio WHERE idcontratto='.prepare($id_record).')))) OR ( id=(SELECT idzona FROM an_anagrafiche WHERE idanagrafica=(SELECT idanagrafica FROM co_contratti WHERE id='.prepare($id_record).') AND idzona=an_zone.id) ) UNION SELECT 0, \'Altro\'", "multiple": 1, "extra": "onchange=\"$(this).find(\'option\').each( function(){ if( $(this).is(\':selected\') ){ $(\'#zona_\'+$(this).val()).removeClass(\'hide\'); }else{ $(\'#zona_\'+$(this).val()).addClass(\'hide\'); } });\"" ]}
        </div>
    </div>';

    // Zone
    for ($i = 0; $i < sizeof($rs); ++$i) {
        echo '
    <div class="hide" id="zona_'.$rs[$i]['id'].'">
        <big><b>'.$rs[$i]['descrizione'].'</b></big>
        <hr>
        <div class="row">';

        for ($j = 0; $j < $n_mesi; ++$j) {
            echo '
            <div class="col-md-3">
                <small><label for="m_'.date('Ym', strtotime($rs2[0]['data_accettazione'].' +'.$j.' month')).'_'.$rs[$i]['id'].'">
                    <input type="checkbox" id="m_'.date('Ym', strtotime($rs2[0]['data_accettazione'].' +'.$j.' month')).'_'.$rs[$i]['id'].'" name="zona['.date('Y-m-t', strtotime($rs2[0]['data_accettazione'].' +'.$j.' month')).'][]" value="'.$rs[$i]['id'].'" />'.$mesi[intval(date('m', strtotime($rs2[0]['data_accettazione'].' +'.$j.' month'))) - 1].' '.date('Y', strtotime($rs2[0]['data_accettazione'].' +'.$j.' month')).'
                </label></small>
            </div>';
        }

        echo '
        </div>
    </div>';
    }

    echo "

    <div class='clearfix'></div>
    <br>";

    // Pianificazione
    echo "
    <button type='button' class='btn btn-primary' onclick=\"if( $('input[type=checkbox]:checked').length>0 ){ if( confirm('Pianificare la fatturazione?') ){ $('#pianifica_form').submit(); } }\">
        <i class='fa fa-plus'></i> ".tr('Pianifica ora').'
    </button>';

    echo '
</form>';
}

echo '
    </div>
</div>';
