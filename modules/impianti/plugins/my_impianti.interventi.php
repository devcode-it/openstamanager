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

include_once __DIR__.'/../../../core.php';

$matricole = (array) post('matricole');

// Salvo gli impianti selezionati
if (filter('op') == 'link_impianti') {
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
    $id_impianto = post('id_impianto');

    $dbo->query('DELETE FROM my_componenti_interventi WHERE id_componente IN (SELECT id FROM my_impianto_componenti WHERE idimpianto = '.prepare($id_impianto).') AND id_intervento = '.prepare($id_record));

    foreach ($components as $component) {
        $dbo->query('INSERT INTO my_componenti_interventi(id_componente, id_intervento) VALUES ('.prepare($component).', '.prepare($id_record).')');
    }

    flash()->info(tr('Informazioni componenti salvate!'));
}

// Blocco della modifica impianti se l'intervento è completato
$dati_intervento = $dbo->fetchArray('SELECT in_statiintervento.is_completato FROM in_statiintervento INNER JOIN in_interventi ON in_statiintervento.idstatointervento = in_interventi.idstatointervento WHERE in_interventi.id='.prepare($id_record));
$is_completato = $dati_intervento[0]['is_completato'];

if ($is_completato) {
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

$impianti_collegati = $dbo->fetchArray('SELECT * FROM my_impianti_interventi INNER JOIN my_impianti ON my_impianti_interventi.idimpianto = my_impianti.id WHERE idintervento = '.prepare($id_record));

echo '
        <div class="row">';
foreach ($impianti_collegati as $impianto) {
    echo '
            <div class="col-md-3">
                <table class="table table-hover table-condensed table-striped">';

    // MATRICOLA
    echo '
                    <tr>
                        <td class="text-right">'.tr('Matricola').':</td>
                        <td valign="top">'.$impianto['matricola'].'</td>
                    </tr>';

    // NOME
    echo '
                    <tr>
                        <td class="text-right">'.tr('Nome').':</td>
                        <td valign="top">
                            '.Modules::link('Impianti', $impianto['id'], $impianto['nome']).'
                        </td>
                    </tr>';

    // DATA
    echo '
                    <tr>
                        <td class="text-right">'.tr('Data').':</td>
                        <td valign="top">'.dateFormat($impianto['data']).'</td>
                    </tr>';

    // DESCRIZIONE
    echo '
                    <tr>
                        <td class="text-right">'.tr('Descrizione').':</td>
                        <td valign="top">'.$impianto['descrizione'].'</td>
                    </tr>';

    echo '
                    <tr>
                        <td valign="top" class="text-right">'.tr("Componenti soggetti all'intervento").'</td>
                        <td valign="top">
                            <form action="'.ROOTDIR.'/editor.php?id_module='.$id_module.'&id_record='.$id_record.'&op=link_componenti&matricola='.$impianto['id'].'" method="post">
                                <input type="hidden" name="backto" value="record-edit">
                                <input type="hidden" name="id_impianto" value="'.$impianto['id'].'">';

    $inseriti = $dbo->fetchArray('SELECT * FROM my_componenti_interventi WHERE id_intervento = '.prepare($id_record));
    $ids = array_column($inseriti, 'id_componente');

    echo '

                                {[ "type": "select", "label": "'.tr('Componenti').'", "multiple": 1, "name": "componenti[]", "id": "componenti_'.$impianto['id'].'", "ajax-source": "componenti", "select-options": {"matricola": '.$impianto['id'].'}, "value": "'.implode(',', $ids).'", "readonly": "'.!empty($readonly).'", "disabled": "'.!empty($disabled).'" ]}

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
        <form action="'.$rootdir.'/editor.php?id_module='.$id_module.'&id_record='.$id_record.'&op=link_impianti" method="post">
            <input type="hidden" name="backto" value="record-edit">
            <div class="row">
                <div class="col-xs-12 col-md-6">
                    {[ "type": "select", "name": "matricole[]", "multiple": 1, "value": "'.implode(',', $impianti).'", "ajax-source": "impianti-cliente", "select-options": {"idanagrafica": '.$record['idanagrafica'].'}, "extra": "'.$readonly.'" ]}
                </div>
            </div>
            <br><br>
            <button type="submit" class="btn btn-success" '.$disabled.'><i class="fa fa-check"></i> '.tr('Salva impianti').'</button>

            <button type="button" class="btn btn-primary" data-toggle="modal" data-title="'.tr('Aggiungi impianto').'" data-href="'.$rootdir.'/add.php?id_module='.Modules::get('Impianti')['id'].'&source=Attività&select=idimpianti&id_anagrafica='.$record['idanagrafica'].'&ajax=yes"><i class="fa fa-plus"></i> '.tr('Aggiungi impianto').'</button>

        </form>';

echo '
    </div>
</div>';
