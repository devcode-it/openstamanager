<?php

include_once __DIR__.'/../../../core.php';

switch (filter('op')) {
    case 'updatecomponente':
        $idcomponente = get('id');
        $data = post('data_componente');

        // Ricavo il valore di contenuto leggendolo dal db
        $query = 'SELECT * FROM my_impianto_componenti WHERE idimpianto='.prepare($id_record).' AND id='.prepare($idcomponente);
        $rs = $dbo->fetchArray($query);
        $contenuto = $rs[0]['contenuto'];

        $contenuto = \Util\Ini::write($contenuto, $post);

        $query = 'UPDATE my_impianto_componenti SET data='.prepare($data).', contenuto='.prepare($contenuto).' WHERE idimpianto='.prepare($id_record).' AND id='.prepare($idcomponente);
        $dbo->query($query);

        $_SESSION['infos'][] = _('Informazioni componente aggiornate correttamente!');

        $_SESSION['idcomponente'] = $idcomponente;
    break;

    case 'linkcomponente':
        $filename = get('filename');

        if (!empty($filename)) {
            $contenuto = file_get_contents($docroot.'/files/my_impianti/'.$filename);
            $nome = \Util\Ini::getValue(\Util\Ini::readFile($docroot.'/files/my_impianti/'.$filename), 'Nome');

            $query = 'INSERT INTO my_impianto_componenti(filename, idimpianto, contenuto, nome, data) VALUES('.prepare($filename).', '.prepare($id_record).', '.prepare($contenuto).', '.prepare($nome).', NOW())';
            $dbo->query($query);

            $idcomponente = $dbo->lastInsertedID();
            $_SESSION['idcomponente'] = $idcomponente;

            $_SESSION['infos'][] = _("Aggiunto un nuovo componente all'impianto!");
        }
    break;

    case 'sostituiscicomponente':
        $filename = get('filename');
        $id = get('id');

        $nome = \Util\Ini::getValue(\Util\Ini::readFile($docroot.'/files/my_impianti/'.$filename), 'Nome');
        $contenuto = file_get_contents($docroot.'/files/my_impianti/'.$filename);

        // Verifico che questo componente non sia già stato sostituito
        $query = 'SELECT * FROM my_impianto_componenti WHERE idsostituto = '.prepare($id);
        $rs = $dbo->fetchArray($query);

        if (empty($rs)) {
            // Inserisco il nuovo componente in sostituzione
            $query = 'INSERT INTO my_impianto_componenti(idsostituto, filename, idimpianto, contenuto, nome, data) VALUES('.prepare($id).', '.prepare($filename).', '.prepare($id_record).', '.prepare($contenuto).', '.prepare($nome).', NOW())';
            $dbo->query($query);

            $idcomponente = $dbo->lastInsertedID();
            $_SESSION['idcomponente'] = $idcomponente;

            // Aggiorno la data di sostituzione del componente precedente
            $query = 'UPDATE my_impianto_componenti SET data_sostituzione = NOW() WHERE idimpianto = '.prepare($id_record).' AND id = '.prepare($id);
            $dbo->query($query);

            $_SESSION['infos'][] = _('Aggiunto un nuovo componente in sostituzione al precedente!');
        } else {
            $_SESSION['errors'][] = _('Questo componente è già stato sostituito!').' '.('Nessuna modifica applicata');
        }
    break;

    case 'unlinkcomponente':
        $idcomponente = filter('id');

        $query = 'DELETE FROM my_impianto_componenti WHERE id='.prepare($idcomponente).' AND idimpianto='.prepare($id_record);
        $dbo->query($query);

        $_SESSION['infos'][] = _("Rimosso componente dall'impianto!");
    break;
}

// Componenti non ancora collegati
if ($id_list == '') {
    $id_list = '0';
}

echo '
<div class="box">
    <div class="box-header with-border">
        <h3 class="box-title">'._('Componenti installati').'</h3>
    </div>
    <div class="box-body">';

// Elenca i componenti disponibili
$cmp = \Util\Ini::getList($docroot.'/files/my_impianti/', $id_list);

echo '
        <div class="row">
            <div class="col-md-4 col-md-push-6">
                <select class="superselect" id="filename" name="filename">';

if (count($cmp) > 0) {
    echo '
                    <option value="0">- '._('Aggiungi un componente').' -</option>';
    for ($c = 0; $c < count($cmp); ++$c) {
        echo '
                    <option value="'.$cmp[$c][0].'">'.$cmp[$c][1].'</option>';
    }
} else {
    echo '
                    <option value="0">- '._('Hai già aggiunto tutti i componenti').' -</option>';
}

echo '
                </select>
            </div>

            <div class="col-md-2 col-md-push-6">';
echo "
                <a class=\"btn btn-primary btn-block\" id=\"addta\" href=\"javascript:;\" onclick=\"if ( $('#filename').val()!='0' ){ redirect('".$rootdir.'/editor.php?id_module='.$id_module.'&id_record='.$id_record."&op=linkcomponente&backto=record-edit&filename='+$('#filename').val());}else{ alert('"._('Seleziona prima un componente')."'); $('#filename').focus(); }\"><i class='fa fa-plus'></i> "._('Aggiungi').'</a>';
echo '
            </div>
        </div>

        <div class="clearfix"></div>
        <br>';

// Mostro tutti i componenti utilizzati elencando quelli attualmente installati per primi.
$q2 = 'SELECT *, (SELECT MIN(orario_inizio) FROM in_interventi_tecnici INNER JOIN in_interventi ON in_interventi_tecnici.idintervento=in_interventi.id WHERE in_interventi.id=my_impianto_componenti.idintervento) AS data_intervento FROM my_impianto_componenti WHERE idimpianto = '.prepare($id_record).' ORDER by nome ASC, data_intervento DESC, idsostituto DESC';
$rs2 = $dbo->fetchArray($q2);
$n2 = count($rs2);

if (!empty($rs2)) {
    $prev_componente = '';

    echo '
        <div class="panel-group" id="accordion">';

    // Ciclo tra tutti i componenti
    for ($j = 0; $j < $n2; ++$j) {
        $contenuto = $rs2[$j]['contenuto'];

        $nome_componente = $rs2[$j]['nome'];
        $filename = $rs2[$j]['filename'];

        if (empty($rs2[$j]['data_sostituzione'])) {
            $statocomponente = str_replace('_DATE_', Translator::dateToLocale($rs2[$j]['data']), _('INSTALLATO in data _DATE_'));
        } else {
            $statocomponente = str_replace('_DATE_', Translator::dateToLocale($rs2[$j]['data_sostituzione']), _('SOSTITUITO in data _DATE_'));
        }

        // Per più "versioni" dello stesso componente mostro un riga meno evidente
        // per non confonderlo come componente in uso in questo momento
        $same = ($prev_componente == $nome_componente);
        echo '
            <div class="panel panel-'.($same ? 'default' : 'primary').'">
                <div class="panel-heading'.($same ? ' mini' : '').'">
                    <h4 class="panel-title'.($same ? ' mini' : '').'">
                        <a data-toggle="collapse" data-parent="#accordion" href="#collapse_'.$j.'">'.($same ? '<small>' : '').''.$nome_componente.' ('.$statocomponente.')'.($same ? '</small>' : '').'</a>
                    </h4>
                </div>';

        if (get('id') == $rs2[$j]['id']) {
            $in = 'in';
        } elseif ($_SESSION['idcomponente'] == $rs2[$j]['id']) {
            unset($_SESSION['idcomponente']);
            $in = 'in';
        } else {
            $in = '';
        }

        echo '
                <div id="collapse_'.$j.'" class="panel-collapse collapse '.$in.'">
                    <div class="panel-body">';
        // FORM COMPONENTE
        echo '
                        <form method="post" action="'.$rootdir.'/editor.php?id_module='.$id_module.'&id_record='.$id_record.'&op=updatecomponente&id='.$rs2[$j]['id'].'">
                            <input type="hidden" name="backto" value="record-edit">';

        // Nome
        echo '
                            <div class="col-md-6">
                                {[ "type": "span", "label": "'._('Nome').':", "name": "nome", "value": "'.$rs2[$j]['nome'].'" ]}
                            </div>';

        // Data
        echo '
                            <div class="col-md-6">
                                {[ "type": "date", "label": "'._('Data').':", "name": "data_componente", "id": "data_componente'.$j.'", "value": "'.$rs2[$j]['data'].'" ]}
                            </div>';

        $fields = \Util\Ini::getFields($contenuto);

        array_shift($fields);
        foreach ($fields as $field) {
            echo '
                            <div class="col-md-6">
                                '.$field.'
                            </div>';
        }

        $interventi = $dbo->fetchArray('SELECT *, (SELECT descrizione FROM in_tipiintervento WHERE idtipointervento=in_interventi.idtipointervento) AS tipo, (SELECT descrizione FROM in_statiintervento WHERE idstatointervento=in_interventi.idstatointervento) AS stato, (SELECT colore FROM in_statiintervento WHERE idstatointervento=in_interventi.idstatointervento) AS colore FROM in_interventi INNER JOIN my_componenti_interventi ON my_componenti_interventi.id_intervento=in_interventi.id WHERE id_componente='.prepare($rs2[$j]['id']).' ORDER BY id_intervento');
        if ($interventi != null) {
            // Collegamento a intervento se c'è
            echo '
                            <div class="col-md-12">
                                <b>'._('Interventi collegati').':</b>
                                <table class="table table-condensed">
                                    <tr>
                                        <th>'._('Codice').'</th>
                                        <th>'._('Tipo').'</th>
                                        <th>'._('Stato').'</th>
                                        <th>'._('Data richiesta').'</th>
                                        <th>'._('Dettagli').'</th>
                                    </tr>';

            foreach ($interventi as $intervento) {
                echo '
                                    <tr bgcolor="'.$intervento['colore'].'">
                                        <td>'.$intervento['id_intervento'].'</td>
                                        <td>'.$intervento['tipo'].'</td>
                                        <td>'.$intervento['stato'].'</td>
                                        <td>'.$intervento['data_richiesta'].'</td>
                                        <td>'.Modules::link('Interventi', $intervento['id_intervento'], null, '-').'</td>
                                    </tr>';
            }

            echo '
                                </table>
                            </div>';
        } else {
            echo '
                            <div class="clearfix"></div>
                            <div class="col-md-12">
                                <div class="alert alert-info">'._('Nessun intervento collegato a questo componente!').'</div>
                            </div>';
        }

        if (!empty($rs2[$j]['idintervento'])) {
            echo '
                            '.Modules::link('Interventi', $rs2[$j]['id'], str_replace(['_NUM_', '_DATE_'], [$rs2[$j]['codice'], Translator::dateToLocale($rs2[$j]['data_intervento'])], 'Intervento _NUM_ del _DATE_')).'<br>';
        }

        echo '
                            <div class="clearfix"></div>
                            <br>';

        // Pulsante Salva/Elimina
        echo '
                            <div class="col-md-12">
                                <button type="submit" class="btn btn-success"><i class="fa fa-check"></i> '._('Salva modifiche').'</button>

                                <a class="btn btn-danger ask" data-backto="record-edit" data-op="unlinkcomponente" data-id="'.$rs2[$j]['id'].'">
                                    <i class="fa fa-trash"></i> '._('Elimina').'
                                </a>
                            </div>';

        // Sostituisci componente con un altro dello stesso tipo, posso sostituire solo i componenti installati
        echo '
                            <div class="col-md-12">';
        if (empty($rs2[$j]['data_sostituzione'])) {
            echo "
                                <a href=\"javascript:;\" class=\"text-warning\" onclick=\"if( confirm('"._('Vuoi sostituire questo componente con un altro dello stesso tipo?')."') ){ location.href='".$rootdir.'/editor.php?id_module='.$id_module.'&id_record='.$id_record.'&op=sostituiscicomponente&backto=record-edit&filename='.$filename.'&id='.$rs2[$j]['id']."'; }\"><i class='fa fa-refresh'></i> "._('Sostituisci questo componente').'</a><br><br>';
        } else {
            echo '
                                <p class="text-danger">'._('Componente già sostituito').'</p>';
        }

        echo '
                            </div>

                        </form>
                    </div>
                </div>
            </div>';
        $prev_componente = $nome_componente;
    }
    echo '
        </div>';
} else {
    echo '
        <p>'._('Nessun componente inserito').'.</p>';
}

echo '
    </div>
</div>';
