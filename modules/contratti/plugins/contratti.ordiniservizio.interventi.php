<?php

include_once __DIR__.'/../../../core.php';

/*
    Salvataggio voci di servizio
*/
if (filter('op') == 'save_ordineservizio') {
    $n_errors = 0;

    if (post('eseguito') !== null) {
        foreach (post('eseguito') as $idvoceservizio => $eseguito) {
            $presenza = post('presenza')[$idvoceservizio];
            $esito = post('esito')[$idvoceservizio];
            $priorita = post('priorita')[$idvoceservizio];

            if (!$dbo->query('UPDATE co_ordiniservizio_vociservizio SET eseguito='.prepare($eseguito).', presenza='.prepare($presenza).', esito='.prepare($esito).', priorita='.prepare($priorita).', note='.prepare(post('note_ods')[$idvoceservizio]).' WHERE id='.prepare($idvoceservizio))) {
                ++$n_errors;
            }
        }
    }

    if ($n_errors == 0) {
        flash()->info(tr('Voci di servizio salvate correttamente!'));
    } else {
        flash()->error(tr('Errore durante il salvataggio delle voci di servizio!'));
    }

    // Aggiornamento 4 spunte
    $dbo->query('UPDATE co_ordiniservizio SET copia_centrale='.prepare(post('copia_centrale')).', copia_cliente='.prepare(post('copia_cliente')).', copia_amministratore='.prepare(post('copia_amministratore')).'", funzionamento_in_sicurezza='.prepare(post('funzionamento_in_sicurezza')).' WHERE idintervento='.prepare($id_record));
}

/*
    Visualizzazione voci di servizio collegate a questo intervento
*/
// Info principali
$rs = $dbo->fetchArray('SELECT * FROM co_ordiniservizio WHERE idintervento='.prepare($idintervento));
$check_copia_centrale = $rs[0]['copia_centrale'];
$check_copia_cliente = $rs[0]['copia_cliente'];
$check_copia_amministratore = $rs[0]['copia_amministratore'];
$check_funzionamento_in_sicurezza = $rs[0]['funzionamento_in_sicurezza'];

if (sizeof($rs) == 0) {
    echo '
<p>'.tr('Nessun collegamento a ordini di servizio')."...</p>\n";
} else {
    echo '
<p>'.tr('Ordine di servizio numero _NUM_ (termine massimo _DATE_)', [
    '_NUM_' => '<b>'.$rs[0]['id'].'</b>',
    '_DATE_' => Translator::dateToLocale($rs[0]['data_scadenza']),
]).':</p>';

    $rs = $dbo->fetchArray('SELECT * FROM co_ordiniservizio_vociservizio WHERE idordineservizio=(SELECT id FROM co_ordiniservizio WHERE idintervento='.prepare($idintervento).' LIMIT 0,1) ORDER BY categoria ASC');

    echo '
<form action="'.$rootdir.'/editor.php?id_module='.Modules::get('Interventi')['id'].'&id_record='.$id_record.'&idordineservizio='.$rs[0]['idordineservizio'].'&op=save_ordineservizio" method="post" id="form-ordineservizio">
    <div class="row">
        <div class="col-md-9">
    		<table class="table table-hover table-striped">
                <tr>
                    <th width="30%">'.tr('Voce di servizio').'</th>
                    <th>'.tr('Presenza').'</th>
                    <th>'.tr('Eseguito').'</th>
                    <th>'.tr('Esito').'</th>
                    <th>'.tr('Priorità').'</th>
                    <th width="30%">'.tr('Note').'</th>
                </tr>';

    $prev_cat = '';

    for ($i = 0; $i < sizeof($rs); ++$i) {
        if ($prev_cat != $rs[$i]['categoria']) {
            echo '
                <tr>
                    <th colspan="6">'.$rs[$i]['categoria'].'</th>
                </tr>';
        }

        echo '
                <tr>
                    <td>'.$rs[$i]['voce'].'</td>';

        // Presenza SI
        if ($rs[$i]['presenza'] == '1') {
            $attr_si = 'checked="true"';
            $attr_no = '';
        }

        // Presenza NO
        elseif ($rs[$i]['presenza'] == '-1') {
            $attr_si = '';
            $attr_no = 'checked="true"';
        }

        // Nessuna spunta
        else {
            $attr_si = '';
            $attr_no = '';
        }

        echo '
                    <td>
                        <div>
                            <input type="radio" name="presenza['.$rs[$i]['id'].']" value="1" '.$attr_si.'> '.tr('Sì').'<br>
                            <input type="radio" name="presenza['.$rs[$i]['id'].']" value="-1" '.$attr_no.'> '.tr('No').'
                        </div>
                    </td>';

        // Eseguito SI
        if ($rs[$i]['eseguito'] == '1') {
            $attr_si = 'checked="true"';
            $attr_no = '';
        }

        // Eseguito NO
        elseif ($rs[$i]['eseguito'] == '-1') {
            $attr_si = '';
            $attr_no = 'checked="true"';
        }

        // Nessuna spunta
        else {
            $attr_si = '';
            $attr_no = '';
        }

        echo '
                    <td>
                        <div>
                            <input type="radio" name="eseguito['.$rs[$i]['id'].']" value="1" '.$attr_si.'> '.tr('Sì').'<br>
                            <input type="radio" name="eseguito['.$rs[$i]['id'].']" value="-1" '.$attr_no.'> '.tr('No').'
                        </div>
                    </td>';

        // Esito SI
        if ($rs[$i]['esito'] == '1') {
            $attr_si = 'checked="true"';
            $attr_no = '';
        }

        // Esito NO
        elseif ($rs[$i]['esito'] == '-1') {
            $attr_si = '';
            $attr_no = 'checked="true"';
        }

        // Nessuna spunta
        else {
            $attr_si = '';
            $attr_no = '';
        }

        echo '
                    <td>
                        <div>
                            <input type="radio" name="esito['.$rs[$i]['id'].']" value="1" '.$attr_si.'> '.tr('Pos.').'<br>
                            <input type="radio" name="esito['.$rs[$i]['id'].']" value="-1" '.$attr_no.'> '.tr('Neg.').'
                        </div>
                    </td>';

        // Priorità 1
        if ($rs[$i]['priorita'] == '1') {
            $attr_1 = 'checked="true"';
            $attr_2 = '';
            $attr_3 = '';
        }

        // Priorità 2
        elseif ($rs[$i]['priorita'] == '2') {
            $attr_1 = '';
            $attr_2 = 'checked="true"';
            $attr_3 = '';
        }

        // Priorità 3
        elseif ($rs[$i]['priorita'] == '3') {
            $attr_1 = '';
            $attr_2 = '';
            $attr_3 = 'checked="true"';
        }

        // Nessuna priorità
        else {
            $attr_1 = '';
            $attr_2 = '';
            $attr_3 = '';
        }

        echo '
                    <td>
                        <div>
                            <input type="radio" name="priorita['.$rs[$i]['id'].']" value="3" '.$attr_si.'> '.tr('A').'<br>
                            <input type="radio" name="priorita['.$rs[$i]['id'].']" value="2" '.$attr_no.'> '.tr('M').'
                            <input type="radio" name="priorita['.$rs[$i]['id'].']" value="-1" '.$attr_no.'> '.tr('B').'
                        </div>
                    </td>';

        echo '
                    <td>
                        {[ "type": "text", "name": "note_ods['.$rs[$i]['id'].']", "value": "'.$rs[$i]['note'].'" ]}
                    </td>';

        $prev_cat = $rs[$i]['categoria'];
    }

    echo '
                </tr>
            </table>
        </div>';

    // Parte destra
    echo '
        <div class="col-md-3">';

    echo '
            {[ "type": "checkbox", "label": "'.tr('Consegnata copia in centrale').'", "name": "copia_centrale", "value": "'.$check_copia_centrale.'" ]}';

    echo '
            {[ "type": "checkbox", "label": "'.tr('Consegnata copia al cliente').'", "name": "copia_cliente", "value": "'.$check_copia_cliente.'" ]}';

    echo '
            {[ "type": "checkbox", "label": "'.tr("Consegnata copia all'amministratore").'", "name": "copia_amministratore", "value": "'.$check_copia_amministratore.'" ]}';

    if ($check_funzionamento_in_sicurezza == '1') {
        $attr = 'checked="true"';
    } else {
        $attr = '';
    }
    echo '
            {[ "type": "checkbox", "label": "'.tr("L'impianto può funzionare in sicurezza").'", "name": "funzionamento_in_sicurezza", "value": "'.$check_funzionamento_in_sicurezza.'" ]}';

    echo '
        </div>
    </div>

    <div class="clearfix"></div>

    <button type="button" class="btn btn-success" onclick="if( confirm(\'Salvere le modifiche?\') ){ $(\'#form-ordineservizio\').submit(); }">
        <i class="fa fa-check"></i> '.tr('Salva modifiche').'
    </button>

</form>';

    /*
        Stampa intervento con voci di servizio
    */
    echo '
<div class="text-center">
    '.Prints::getLink('Ordine di servizio', $id_record, 'btn-primary', tr('Stampa ordine di servizio')).'
</div>';
}
