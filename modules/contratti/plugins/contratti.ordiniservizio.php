<?php

include_once __DIR__.'/../../../core.php';

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

// Generazione ordini di servizio
if (get('op') == 'add_ordineservizio') {
    $prev_data = '';

    // Ciclo fra le voci in arrivo dal form
    foreach (post('voce') as $data_scadenza => $ordiniservizio) {
        $data_scadenza = date_create_from_format('Ym', $data_scadenza)->format(Intl\Formatter::getStandardFormats()['date']);

        // Ogni data può avere più voci di servizio da salvare
        foreach ($ordiniservizio as $n => $idvoce) {
            // Aggiunta ordine di servizio solo se la voce è spuntata
            if (in_array($idvoce, post('idvoce'))) {
                // Creazione ordine di servizio per data di scadenza
                if ($prev_data != $data_scadenza) {
                    $dbo->query('INSERT INTO co_ordiniservizio(idcontratto, data_scadenza, idimpianto, stato) VALUES('.prepare($id_record).', '.prepare($data_scadenza).', '.prepare(post('matricola')).", 'aperto')");
                    $idordineservizio = $dbo->lastInsertedID();
                }

                $dbo->query('INSERT INTO co_ordiniservizio_vociservizio(idordineservizio, voce, categoria, eseguito) VALUES('.prepare($idordineservizio).', (SELECT descrizione FROM in_vociservizio WHERE id='.prepare($idvoce).'), (SELECT categoria FROM in_vociservizio WHERE id='.prepare($idvoce).'), 0 )');
            }

            $prev_data = $data_scadenza;
        }
    }

    flash()->info(tr('Ordini di servizio generati correttamente!'));
}

// Eliminazione pianificazione specifica
elseif (get('op') == 'del_ordineservizio') {
    $idordineservizio = get('idordineservizio');

    $n = $dbo->fetchNum('SELECT id FROM co_ordiniservizio WHERE id='.prepare($idordineservizio)." AND stato='aperto'");

    if ($n == 1) {
        // Eliminazione ordine di servizio
        if ($dbo->query('DELETE FROM co_ordiniservizio WHERE id='.prepare($idordineservizio))) {
            // Eliminazione voci di servizio collegate
            $dbo->query('DELETE FROM co_ordiniservizio_vociservizio WHERE idordineservizio='.prepare($idordineservizio));

            flash()->info(tr('Ordine di servizio eliminato correttamente!'));
        }
    }

    // Non si può eliminare l'ordine di servizio perché è chiuso
    else {
        flash()->info(tr('Ordine di servizio già chiuso, impossibile eliminarlo!'));
    }
}

echo '
<div class="box">
    <div class="box-header with-border">
        <h3 class="box-title">'.tr('Pianificazione ordini di servizio').'</h3>
    </div>
    <div class="box-body">';

echo '
<p>'.tr('Qui puoi programmare gli ordini di servizio del contratto').'.</p>';

/*
    Ordini di servizio pianificati
*/

// (SELECT idsede FROM my_impianti WHERE idimpianto=co_ordiniservizio.idimpianto)

$rs = $dbo->fetchArray("SELECT *, (SELECT CONCAT_WS(' ', nomesede, citta) FROM an_sedi WHERE id=(SELECT idsede FROM my_impianti WHERE idimpianto=co_ordiniservizio.idimpianto LIMIT 0,1)) AS sede, (SELECT CONCAT_WS(' - ', matricola, nome) FROM my_impianti WHERE id=co_ordiniservizio.idimpianto) AS impianto, (SELECT MIN(orario_inizio) FROM in_interventi_tecnici WHERE idintervento=co_ordiniservizio.idintervento) AS data_intervento FROM co_ordiniservizio WHERE idcontratto=".prepare($id_record).' ORDER BY data_scadenza ASC');

if (empty($rs)) {
    echo '
<p>'.tr('Non sono ancora stati pianificati ordini di servizio').'...</p>';
} else {
    echo '
<table class="table table-striped table-hover table-bordered table-condensed">
    <tr>
        <th width="10%">'.tr('Entro').'</th>
        <th>'.tr('Sede').'</th>
        <th width="20%">'.tr('Impianto').'</th>
        <th width="10%">'.tr('Voci di servizio').'</th>
        <th width="10%">'.tr('Stato').'</th>
        <th width="3%"></th>
    </tr>';

    $prev_mese = '';

    foreach ($rs as $r) {
        echo '
    <tr>
        <td>';
        // Data scadenza
        if ($prev_mese != $r['data_scadenza']) {
            echo '
            <b>'.$mesi[intval(date('m', strtotime($r['data_scadenza']))) - 1].' '.date('Y', strtotime($r['data_scadenza'])).'</b>
        </td>';
        } else {
            echo '
            <small><em>'.$mesi[intval(date('m', strtotime($r['data_scadenza']))) - 1].' '.date('Y', strtotime($r['data_scadenza'])).'</em></small>';
        }

        // Sede
        if ($r['sede'] == '') {
            $sede = 'Sede legale';
        } else {
            $sede = $r['sede'];
        }

        echo '
        <td>'.$sede.'</td>';

        // Impianto
        echo '
        <td>
            '.Modules::link('MyImpianti', $r['idimpianto'], $r['impianto']).'
        </td>';

        // Voci di servizio
        $rs2 = $dbo->fetchArray('SELECT * FROM co_ordiniservizio_vociservizio WHERE idordineservizio='.prepare($r['id']).' ORDER BY categoria ASC');

        echo '
        <td class="text-center">
            <button type="button" class="btn btn-primary btn-sm" onclick="launch_modal(\'Pianifica intervento\', \'#voci_'.$r['id'].'\' );">
                <i class="fa fa-list"></i> '.tr('Visualizza').'... ('.sizeof($rs2).')
            </button>';

        // Popup voci di servizio
        echo '
            <div class="hide">
                <div id="voci_'.$r['id'].'">';

        if (empty($rs2)) {
            echo '
                    <p>'.tr('Non sono state pianificate voci di servizio').'...</p>';
        } else {
            echo '
                    <table class="table table-bordered table-condensed table-hover table-striped">
                        <tr>
                            <th>'.tr('Voci di servizio').'</th>
                            <th width="40%">'.tr('Eseguito').'</th>
                        </tr>';

            $prev_cat = '';

            for ($v = 0; $v < sizeof($rs2); ++$v) {
                if ($rs2[$v]['categoria'] != $prev_cat) {
                    echo '
                        <tr>
                            <th colspan="2">'.$rs2[$v]['categoria'].'</th>
                        </tr>';
                }

                echo '
                        <tr>
                            <td>'.$rs2[$v]['voce'].'</td>

                            <td>';

                // intervento non ancora eseguito
                if (empty($r['idintervento'])) {
                    echo '
                                <span class="text-warning">
                                    <i class="fa fa-clock-o"></i> '.tr('non ancora eseguito').'
                                </span>';
                } else {
                    $res = $dbo->fetchArray('SELECT codice FROM in_interventi WHERE id='.prepare($rsp[$i]['idintervento']));

                    echo '
                                <span class="text-success">
                                    <i class="fa fa-check"></i>
                                    '.Modules::link('Interventi', $r['idintervento'], tr('Intervento num. _NUM_ del _DATE_', [
                                        '_NUM_' => $res['codice'],
                                        '_DATE_' => Translator::dateToLocale($r[0]['data_intervento']),
                                    ])).'
                                </span>';
                }
                echo '
                            </td>
                        </tr>';

                $prev_cat = $rs2[$v]['categoria'];
            }

            echo '
                    </table>';
        }
        echo '
                </div>
            </div>
        </td>';

        // Stato
        echo '
        <td class="text-center">';
        if (empty($r['idintervento'])) {
            echo '
            <span class="text-warning"><i class="fa fa-clock-o"></i> '.tr('aperto').'</span>';
        } else {
            echo '
            <span class="text-success"><i class="fa fa-check"></i> '.tr('chiuso').'/span>';
        }
        echo '
        </td>';

        // Funzioni
        echo '
        <td>';
        if (empty($r['idintervento'])) {
            echo '
            <a class="btn btn-danger ask" data-backto="record-edit" data-method="get" data-op="del_ordineservizio" data-idordineservizio="'.$r['id'].'" data-msg="'.tr('Vuoi eliminare questa pianificazione?').'">
                <i class="fa fa-trash"></i>
            </a>';
        }

        echo '
        </td>
    </tr>';

        $prev_mese = $r['data_scadenza'];
    }
    echo '
</table>';
}

echo '
<br><br>';

/*
    Schema per aggiungere ordini di servizio
*/
$rs = $dbo->fetchArray('SELECT * FROM in_vociservizio ORDER BY categoria ASC');

if (empty($rs)) {
    echo '
<p>
    '.tr('Non sono ancora state inserite voci di servizio').'.
    <a href="'.$rootdir.'/controller.php?id_module='.Modules::get('Voci di servizio')['id'].'">'.tr('Inizia ora').'...</a>
</p>';
}

// Elenco voci di servizio con mesi in cui eseguirle
else {
    // Calcolo mese iniziale e finale del contratto
    $rs2 = $dbo->fetchArray('SELECT data_accettazione, data_conclusione, TIMESTAMPDIFF(MONTH, data_accettazione, data_conclusione) AS mesi FROM co_contratti WHERE id='.prepare($id_record));

    if (!empty($rs2[0]['data_accettazione']) && !empty($rs2[0]['data_conclusione'])) {
        $n_mesi = $rs2[0]['mesi'] + 1;
        $mese_start = date('m', strtotime($rs2[0]['data_accettazione']));

        echo '
<button type="button" class="btn btn-primary" onclick="$(this).next().removeClass(\'hide\'); $(this).remove();">
    <i class="fa fa-calendar"></i> '.tr('Pianifica nuovi ordini di servizio').'
</button>

<form action="'.$rootdir.'/editor.php?id_module='.Modules::get('Contratti')['id'].'&id_record='.$id_record.'&op=add_ordineservizio" id="plan_form" method="post" class="no-check hide">
    <input type="hidden" name="backto" value="record-edit">';

        // Selezione impianto
        echo '
    <div class="row">
        <div class="col-md-6">
            {[ "type": "select", "label": "'.tr('Impianto').'", "name": "matricola", "values": "query=SELECT my_impianti.id, CONCAT(my_impianti.matricola, \" - \", my_impianti.nome) AS descrizione, an_sedi.optgroup FROM my_impianti INNER JOIN (SELECT id, CONCAT(an_sedi.nomesede, \"(\", an_sedi.citta, \")\") AS optgroup FROM an_sedi WHERE idanagrafica='.prepare($record['idanagrafica']).' UNION SELECT 0, \'Sede legale\') AS an_sedi ON my_impianti.idsede = an_sedi.id WHERE my_impianti.idanagrafica='.prepare($record['idanagrafica']).' AND my_impianti.id NOT IN(SELECT idimpianto FROM co_ordiniservizio WHERE idcontratto='.prepare($id_record).') ORDER BY idsede ASC, matricola ASC" ]}
        </div>';

        // Indice voci di servizio
        echo '
        <div class="col-md-6">
            {[ "type": "select", "label": "'.tr('Voci di servizio da pianificare').'", "name": "idvoce[]", "values": "query=SELECT id, descrizione, categoria AS optgroup FROM in_vociservizio ORDER BY categoria ASC", "multiple": 1, "extra": "onchange=\"$(this).find(\'option\').each( function(){ if( $(this).is(\':selected\') ){ $(\'#voce_\'+$(this).val()).removeClass(\'hide\'); }else{ $(\'#voce_\'+$(this).val()).addClass(\'hide\'); } });\"" ]}
        </div>
    </div>';

        // voci di servizio
        foreach ($rs as $r) {
            echo '
    <div class="col-md-3 hide" id="voce_'.$r['id'].'">
        <big><b>'.$r['id'].' - '.$r['descrizione'].'</b></big>
        <hr>';

            for ($j = 0; $j < $n_mesi; ++$j) {
                $id_mese = date('Ym', strtotime($rs2[0]['data_accettazione'].' +'.$j.' month'));
                $nome_mese = $mesi[intval(date('m', strtotime($rs2[0]['data_accettazione'].' +'.$j.' month'))) - 1].' '.date('Y', strtotime($rs2[0]['data_accettazione'].' +'.$j.' month'));
                echo '
        <small>
            <label for="m_'.$id_mese.'_'.$r['id'].'">
                <input type="checkbox" id="m_'.$id_mese.'_'.$r['id'].'" name="voce['.$id_mese.'][]" value="'.$r['id'].'">
                '.$nome_mese.'
            </label>
        </small><br>';
            }

            echo '
    </div>';
        }

        echo '
    <div class="clearfix"></div><br>';

        echo '
    <button type="button" class="btn btn-primary" onclick="if($(\'#matricola\').val() && $(\'#idvoce\').val() ){ if( confirm(\'Pianificare questo ordine di servizio?\') ){ $(\'#plan_form\').submit(); } } else { if ( !$(\'#matricola\').val()) {alert (\'Seleziona un impianto.\'); $(\'#matricola\').focus();}else { alert (\'Seleziona le voci di servizio da pianificare.\'); $(\'#idvoce\').focus(); } }">
        <i class="fa fa-plus"></i> '.tr('Aggiungi').'
    </button>';

        /*
            Copia pianificazione da una già fatta per un impianto ad un'altra
        */
        // Opzione di copia pianificazione solo se ci sono ancora impianti non pianificati
        $query2 = 'SELECT * FROM my_impianti WHERE idanagrafica='.prepare($record['idanagrafica']).' AND id IN (SELECT idimpianto FROM co_ordiniservizio WHERE idcontratto='.prepare($id_record).')';
        $cont = $dbo->fetchNum($query2);

        if ($cont > 0) {
            // Elenco impianti già pianificati
            echo '
    <hr>

    <div class="row">
        <div class="col-md-6">
            {[ "type": "select", "label": "'.tr('Copiare la pianificazione da un altro impianto').'", "name": "matricola_src", "values": "query=SELECT my_impianti.id, CONCAT(my_impianti.matricola, \" - \", my_impianti.nome) AS descrizione, an_sedi.optgroup FROM my_impianti INNER JOIN (SELECT id, CONCAT(an_sedi.nomesede, \"(\", an_sedi.citta, \")\") AS optgroup FROM an_sedi WHERE idanagrafica='.prepare($record['idanagrafica']).' UNION SELECT 0, \'Sede legale\') AS an_sedi ON my_impianti.idsede = an_sedi.id WHERE my_impianti.idanagrafica='.prepare($record['idanagrafica']).' AND my_impianti.id IN(SELECT idimpianto FROM co_ordiniservizio WHERE idcontratto='.prepare($id_record).') ORDER BY idsede ASC, matricola ASC" ]}
        </div>
    </div>';

            echo '
    <div class="clearfix"></div><br>';

            echo '
    <button type="button" class="btn btn-primary" onclick="copia_pianificazione_os( \''.$id_record.'\', $(\'#matricola_src option:selected\').val() );">
        <i class="fa fa-upload"></i>'.tr('Carica questa pianificazione').'
    </button>';
        }

        echo '
</form>';
    } else {
        echo '
<p>'.tr('Le date di accettazione e conclusione del contratto non sono ancora state impostate').'</p>';
    }
}

echo '
    </div>
</div>';
