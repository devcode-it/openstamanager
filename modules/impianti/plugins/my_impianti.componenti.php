<?php
/*
 * OpenSTAManager: il software gestionale open source per l'assistenza tecnica e la fatturazione
 * Copyright (C) DevCode s.n.c.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

use Modules\Interventi\Intervento;
use Util\Ini;

include_once __DIR__.'/../../../core.php';

switch (filter('op')) {
    case 'modifica_componente':
        $idcomponente = get('id');
        $data = post('data_componente');

        // Ricavo il valore di contenuto leggendolo dal db
        $query = 'SELECT * FROM my_impianto_componenti WHERE idimpianto='.prepare($id_record).' AND id='.prepare($idcomponente);
        $rs = $dbo->fetchArray($query);
        $contenuto = $rs[0]['contenuto'];

        $contenuto = Ini::write($contenuto, $post);

        $query = 'UPDATE my_impianto_componenti SET data='.prepare($data).', contenuto='.prepare($contenuto).' WHERE idimpianto='.prepare($id_record).' AND id='.prepare($idcomponente);
        $dbo->query($query);

        flash()->info(tr('Informazioni componente aggiornate correttamente!'));

        $_SESSION['idcomponente'] = $idcomponente;
    break;

    case 'aggiunta_componente':
        $filename = get('filename');

        if (!empty($filename)) {
            $contenuto = file_get_contents(DOCROOT.'/files/impianti/'.$filename);
            $nome = Ini::getValue(Ini::readFile(DOCROOT.'/files/impianti/'.$filename), 'Nome');

            $query = 'INSERT INTO my_impianto_componenti(filename, idimpianto, contenuto, nome, data) VALUES('.prepare($filename).', '.prepare($id_record).', '.prepare($contenuto).', '.prepare($nome).', NOW())';
            $dbo->query($query);

            $idcomponente = $dbo->lastInsertedID();
            $_SESSION['idcomponente'] = $idcomponente;

            flash()->info(tr("Aggiunto un nuovo componente all'impianto!"));
        }
    break;

    case 'sostituzione_componente':
        $filename = get('filename');
        $id = get('id');

        $nome = Ini::getValue(Ini::readFile(DOCROOT.'/files/impianti/'.$filename), 'Nome');
        $contenuto = file_get_contents(DOCROOT.'/files/impianti/'.$filename);

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

            flash()->info(tr('Aggiunto un nuovo componente in sostituzione al precedente!'));
        } else {
            flash()->error(tr('Questo componente è già stato sostituito!').' '.('Nessuna modifica applicata'));
        }
    break;

    case 'unaggiunta_componente':
        $idcomponente = filter('id');

        $query = 'DELETE FROM my_impianto_componenti WHERE id='.prepare($idcomponente).' AND idimpianto='.prepare($id_record);
        $dbo->query($query);

        flash()->info(tr("Rimosso componente dall'impianto!"));
    break;
}

// Componenti non ancora collegati
if (empty($id_list)) {
    $id_list = '0';
}

echo '
<div class="box">
    <div class="box-header with-border">
        <h3 class="box-title">'.tr('Componenti installati').'</h3>
    </div>
    <div class="box-body">';

// Elenca i componenti disponibili
$componenti_disponibili = Ini::getList(DOCROOT.'/files/my_impianti/', $id_list);
echo '
        <div class="row">
            <div class="col-md-9">
                <select class="superselect" id="filename" name="filename">';

if (!empty($componenti_disponibili)) {
    echo '
                    <option value="0">- '.tr('Aggiungi un componente').' -</option>';
    foreach ($componenti_disponibili as $componente) {
        echo '
                    <option value="'.$componente[0].'">'.$componente[1].'</option>';
    }
} else {
    echo '
                    <option value="0">- '.tr('Hai già aggiunto tutti i componenti').' -</option>';
}

echo '
                </select>
            </div>

            <div class="col-md-3">
                <button type="button" class="btn btn-primary btn-block" onclick="aggiungiComponente()">
                    <i class="fa fa-plus"></i> '.tr('Aggiungi').'
                </button>
            </div>
        </div>

        <div class="clearfix"></div>
        <br>';

// Mostro tutti i componenti utilizzati elencando quelli attualmente installati per primi.
$q2 = 'SELECT * FROM my_impianto_componenti WHERE idimpianto = '.prepare($id_record).' ORDER by nome ASC, idsostituto DESC';
$componenti_installati = $dbo->fetchArray($q2);

if (!empty($componenti_installati)) {
    $prev_componente = '';

    echo '
        <div class="panel-group" id="accordion">';

    // Ciclo tra tutti i componenti
    foreach ($componenti_installati as $componente) {
        $contenuto = $componente['contenuto'];

        $nome_componente = $componente['nome'];
        $filename = $componente['filename'];

        if (empty($componente['data_sostituzione'])) {
            $stato_componente = tr('INSTALLATO in data _DATE_', [
                '_DATE_' => dateFormat($componente['data']),
            ]);
        } else {
            $stato_componente = tr('SOSTITUITO in data _DATE_', [
                '_DATE_' => dateFormat($componente['data_sostituzione']),
            ]);
        }

        // Per più "versioni" dello stesso componente mostro un riga meno evidente
        // per non confonderlo come componente in uso in questo momento
        $same = ($prev_componente == $nome_componente);

        if (get('id') == $componente['id']) {
            $collapsed = '';
            $icon = 'minus';
        } elseif ($_SESSION['idcomponente'] == $componente['id']) {
            unset($_SESSION['idcomponente']);
            $collapsed = '';
            $icon = 'minus';
        } else {
            $collapsed = 'collapsed-box';
            $icon = 'plus';
        }

        echo '
            <div class="box '.$collapsed.' box-'.($same ? 'default' : 'primary').'">
                <div class="box-header with-border'.($same ? ' mini' : '').'">
                    <h3 class="box-title'.($same ? ' mini' : '').'">'.
                        ($same ? '<small>' : '').$nome_componente.' ('.$stato_componente.')'.($same ? '</small>' : '').'
                    </h3>

                    <div class="box-tools pull-right">
                        <button type="button" class="btn btn-box-tool" data-widget="collapse">
                            <i class="fa fa-'.$icon.'"></i>
                        </button>
                    </div>
                </div>';

        echo '
                <div id="collapse_'.$j.'" class="box-body">
                    <div class="row">
                        <form method="post" action="'.ROOTDIR.'/editor.php?id_module='.$id_module.'&id_record='.$id_record.'&op=modifica_componente&id='.$componente['id'].'">
                            <input type="hidden" name="backto" value="record-edit">';

        // Nome
        echo '
                            <div class="col-md-6">
                                {[ "type": "span", "label": "'.tr('Nome').':", "name": "nome", "value": "'.$componente['nome'].'" ]}
                            </div>';

        // Data
        echo '
                            <div class="col-md-6">
                                {[ "type": "date", "label": "'.tr('Data').':", "name": "data_componente", "id": "data_componente'.$j.'", "value": "'.$componente['data'].'" ]}
                            </div>';

        // Campi previsti dal componente
        $fields = Ini::getFields($contenuto);
        array_shift($fields);
        foreach ($fields as $field) {
            echo '
                            <div class="col-md-6">
                                '.$field.'
                            </div>';
        }

        // Interventi collegati al componente
        $interventi_collegati = Intervento::join('my_componenti_interventi', 'my_componenti_interventi.id_intervento', '=', 'in_interventi.id')
            ->where('id_componente', $componente['id'])
            ->get();
        if (!$interventi_collegati->isEmpty()) {
            echo '
                            <div class="col-md-12">
                                <b>'.tr('Interventi collegati').':</b>
                                <table class="table table-condensed">
                                    <tr>
                                        <th>'.tr('Codice').'</th>
                                        <th>'.tr('Tipo').'</th>
                                        <th>'.tr('Stato').'</th>
                                        <th>'.tr('Data richiesta').'</th>
                                        <th>'.tr('Dettagli').'</th>
                                    </tr>';

            foreach ($interventi_collegati as $intervento) {
                echo '
                                    <tr bgcolor="'.$intervento->stato->colore.'">
                                        <td>'.$intervento->codice.'</td>
                                        <td>'.$intervento->tipo->descrizione.'</td>
                                        <td>'.$intervento->stato->descrizione.'</td>
                                        <td>'.dateFormat($intervento->data_richiesta).'</td>
                                        <td>'.Modules::link('Interventi', $intervento->id, null, '-').'</td>
                                    </tr>';
            }

            echo '
                                </table>
                            </div>';
        } else {
            echo '
                            <div class="clearfix"></div>
                            <div class="col-md-12">
                                <div class="alert alert-info">
                                    <i class="fa fa-info-circle"></i> '.tr('Nessuna attività collegato a questo componente!').'
                                </div>
                            </div>';
        }

        // Intervento di installazione del componente
        if (!empty($componente['idintervento'])) {
            $intervento_origine = Intervento::find($componente['idintervento']);

            echo '
                            '.Modules::link('Interventi', $componente['idintervento'], $intervento_origine->getReference()).'<br>';
        }

        echo '
                            <div class="clearfix"></div>
                            <br>';

        // Pulsante Salva/Elimina
        echo '
                            <div class="col-md-12">
								<a class="btn btn-danger ask" data-backto="record-edit" data-op="unaggiunta_componente" data-id="'.$componente['id'].'"><i class="fa fa-trash"></i> '.tr('Elimina').'</a>';

        // Sostituisci componente con un altro dello stesso tipo, posso sostituire solo i componenti installati
        if (empty($componente['data_sostituzione'])) {
            echo '
								<button type="button" class="btn btn-warning" onclick="sostituisciComponente()">
								    <i class="fa fa-refresh"></i> '.tr('Sostituisci questo componente').'
                                </button>';
        } else {
            echo '
								<button type="button" class="btn btn-warning disabled">
								    '.tr('Componente già sostituito').'
                                </button>';
        }

        echo '
								<button type="submit" class="btn btn-success pull-right"><i class="fa fa-check"></i> '.tr('Salva modifiche').'</button>';

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
        <div class=\'alert alert-info\' ><i class=\'fa fa-info-circle\'></i> '.tr('Nessun componente inserito').'.</div>';
}

echo '
    </div>
</div>

<script>
function aggiungiComponente() {
    if ($("#filename").val() != "0") {
        redirect("'.ROOTDIR.'/editor.php?id_module='.$id_module.'&id_record='.$id_record.'&op=aggiunta_componente&backto=record-edit&filename=" + $("#filename").val() + "&hash=tab_'.$id_plugin.'");
    } else {
        alert("'.tr('Seleziona prima un componente').'");
        $("#filename").focus();
    }
}

function sostituisciComponente() {
    if(confirm("'.tr('Vuoi sostituire questo componente con un altro dello stesso tipo?').'")){
        redirect("'.ROOTDIR.'/editor.php?id_module='.$id_module.'&id_record='.$id_record.'&op=sostituzione_componente&backto=record-edit&filename='.$filename.'&id='.$componente['id'].'");
    }
}

</script>';
