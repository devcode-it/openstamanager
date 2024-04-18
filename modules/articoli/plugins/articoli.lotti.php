<?php
/*
 * OpenSTAManager: il software gestionale open source per l'assistenza tecnica e la fatturazione
 * Copyright (C) DevCode s.r.l.
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

$record['abilita_serial'] = ($record['serial'] > 0) ? 1 : $record['abilita_serial'];
if (empty($record['abilita_serial'])) {
    echo '
<script>$("#link-tab_'.$plugin['id'].'").addClass("disabled");</script>';
}

// Visualizzo, in base alle impostazioni scelte, se il magazzino verrà movimentato
$message = setting("Movimenta il magazzino durante l'inserimento o eliminazione dei lotti/serial number") ? tr("L'inserimento e la rimozione dei seriali modificherà la quantità dell'articolo!") : tr("L'inserimento e la rimozione dei seriali non movimenterà la quantità dell'articolo!");
echo '
<div class="alert alert-info">
    '.$message.'
</div>';

// Inserimento seriali
echo '
<div class="nav-tabs-custom">
    <ul class="nav nav-tabs nav-justified">
        <li class="active"><a href="#generazione" data-toggle="tab">'.tr('Generazione multipla').'</a></li>
        <li><a href="#inserimento" data-toggle="tab">'.tr('Inserimento singolo').'</a></li>
    </ul>

    <div class="tab-content">
        <form action="" method="post" role="form" class="tab-pane active" id="generazione">
            <input type="hidden" name="backto" value="record-edit">
            <input type="hidden" name="op" value="generate_serials">
            <input type="hidden" name="id_module" value="'.$id_module.'">
            <input type="hidden" name="id_record" value="'.$id_record.'">

            <div class="row">
                <div class="col-md-5">
                    {[ "type": "text", "label": "'.tr('Inizio').'", "name": "serial_start", "extra": "onkeyup=\"$(\'#serial_end\').val( $(this).val()); ricalcola_generazione();\"" ]}
                </div>

                <div class="col-md-2 text-center" style="padding-top: 20px;">
                    <i class="fa fa-arrow-circle-right fa-2x"></i>
                </div>

                <div class="col-md-5">
                    {[ "type": "text", "label": "'.tr('Fine').'", "name": "serial_end", "extra": "onkeyup=\"ricalcola_generazione();\"" ]}
                </div>
            </div>

            <div class="row">
                <div class="col-md-9">
                    <p class="text-danger">'.tr('Totale prodotti da inserire').': <span id="totale_generazione">0</span></p>
                </div>

                <div class="col-md-3 text-right">
                    <button type="button" class="btn btn-primary" onclick="addSerial(\'#generazione\', $(\'#totale_generazione\').text())"><i class="fa fa-plus"></i> '.tr('Aggiungi').'</button>
                </div>
            </div>
        </form>

        <form action="" method="post" role="form" class="tab-pane" id="inserimento">
            <input type="hidden" name="backto" value="record-edit">
            <input type="hidden" name="op" value="add_serials">
            <input type="hidden" name="id_module" value="'.$id_module.'">
            <input type="hidden" name="id_record" value="'.$id_record.'">

            <div class="row">
                <div class="col-md-12">
                    {[ "type": "select", "label": "'.tr('Nuovi seriali').'", "name": "serials[]", "extra": "onchange=\"ricalcola_inserimento();\"", "multiple": 1, "values": [] ]}
                </div>
            </div>

            <div class="row">
                <div class="col-md-9">
                    <p class="text-danger">'.tr('Totale prodotti da inserire').': <span id="totale_inserimento">0</span></p>
                </div>

                <div class="col-md-3 text-right">
                    <button type="button" class="btn btn-primary" onclick="addSerial(\'#inserimento\', $(\'#totale_inserimento\').text())"><i class="fa fa-plus"></i> '.tr('Aggiungi').'</button>
                </div>
            </div>
        </form>
    </div>
</div>';

// Elenco
if (empty(get('modal'))) {
    echo '
<div class="box">
    <div class="box-header with-border">
        <h3 class="box-title">'.tr('Elenco seriali').'</h3>
    </div>
    <div class="box-body">';

    // Conteggio totale prodotti
    $rs = $dbo->fetchArray('SELECT COUNT(id) AS tot FROM mg_prodotti WHERE id_articolo='.prepare($id_record));
    $tot_prodotti = $rs[0]['tot'];

    // Visualizzazione di tutti i prodotti
    $search_serial = get('search_serial');
    $query = 'SELECT id, serial, created_at FROM mg_prodotti WHERE serial IS NOT NULL AND id_articolo='.prepare($id_record).(!empty($search_serial) ? ' AND serial LIKE '.prepare('%'.$search_serial.'%') : '').' GROUP BY serial ORDER BY created_at DESC, serial DESC, lotto DESC, altro DESC';
    $rs2 = $dbo->fetchArray($query);

    echo '
    <table id="table-serials" class="table table-striped table-hover table-condensed table-bordered text-center datatables">
        <thead>
            <tr>
                <th>'.tr('Serial').'</th>
                <th>'.tr('Data di creazione').'</th>
                <th>'.tr('Documento di acquisto').'</th>
                <th>'.tr('Prezzo di acquisto').'</th>
                <th>'.tr('Documento di vendita').'</th>
                <th>'.tr('Prezzo di vendita').'</th>
                <th class="text-center">#</th>
            </tr>
        </thead>
        <tbody>';

    for ($i = 0; $i < count($rs2); ++$i) {
        echo '
        <tr>

            <td>'.$rs2[$i]['serial'].'</td>';

        echo '
            <td>'.Translator::timestampToLocale($rs2[$i]['created_at']).'</td>';

        // Ricerca acquisti
        $acquisti = $dbo->fetchArray('SELECT * FROM mg_prodotti WHERE dir=\'uscita\' AND id_articolo='.prepare($id_record).' AND (id_riga_documento IS NOT NULL OR id_riga_ordine IS NOT NULL OR id_riga_ddt IS NOT NULL) AND serial='.prepare($rs2[$i]['serial']));

        if (!empty($acquisti)) {
            echo '
            <td>';

            $totali = [];

            foreach ($acquisti as $acquisto) {
                // Acquistato su fatture
                if (!empty($acquisto['id_riga_documento'])) {
                    $module = 'Fatture di acquisto';

                    // Ricerca vendite su fatture
                    $query = 'SELECT *, `co_tipidocumento_lang`.`title` AS tipo_documento, `co_tipidocumento`.`dir`, `co_documenti`.`numero`, `co_documenti`.`numero_esterno`, `co_documenti`.`data` FROM `co_righe_documenti` INNER JOIN `co_documenti` ON `co_righe_documenti`.`iddocumento` = `co_documenti`.`id` INNER JOIN `co_tipidocumento` ON `co_documenti`.`idtipodocumento` = `co_tipidocumento`.`id` LEFT JOIN `co_tipidocumento_lang` ON (`co_tipidocumento`.`id` = `co_tipidocumento_lang`.`id_record` AND `co_tipidocumento_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).') WHERE `co_righe_documenti`.`id`='.prepare($acquisto['id_riga_documento']);
                    $data = $dbo->fetchArray($query);

                    $id = $data[0]['iddocumento'];
                }

                // Acquistato su ddt
                elseif (!empty($acquisto['id_riga_ddt'])) {
                    $module = 'Ddt di acquisto';

                    $query = 'SELECT 
                            *, 
                            `dt_tipiddt_lang`.`title` AS tipo_documento,
                            `dt_tipiddt`.`dir` AS `dir`,
                            `dt_ddt`.`numero` AS numero,
                            `dt_ddt`.`data` AS data,
                            `dt_ddt`.`numero_esterno` AS numero_esterno
                        FROM 
                            `dt_righe_ddt`
                            INNER JOIN `dt_ddt` ON `dt_righe_ddt`.`idddt` = `dt_ddt`.`id` 
                            INNER JOIN `dt_tipiddt` ON `dt_ddt`.`idtipoddt` = `dt_tipiddt`.`id`
                            LEFT JOIN `dt_tipiddt_lang` ON (`dt_tipiddt`.`id` = `dt_tipiddt_lang`.`id_record` AND `dt_tipiddt_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).')
                        WHERE 
                            `dt_righe_ddt`.`id`='.prepare($acquisto['id_riga_ddt']);
                    $data = $dbo->fetchArray($query);

                    $id = $data[0]['idddt'];
                }

                // Inserito su ordini
                elseif (!empty($acquisto['id_riga_ordine'])) {
                    $module = 'Ordini cliente';

                    // Ricerca inserimenti su ordini
                    $query = 'SELECT 
                            *, 
                            `or_tipiordine_lang`.`title` AS tipo_documento, 
                            `or_tipiordine`.`dir`,
                            `or_ordini`.`numero`,
                            `or_ordini`.`numero_esterno`,
                            `or_ordini`.`data`
                        FROM 
                            `or_righe_ordini` 
                            INNER JOIN `or_ordini` ON `or_righe_ordini`.`idordine`=`or_ordini`.`id`
                            INNER JOIN `or_tipiordine` ON `or_ordini`.`ididtipordine`=`or_tipiordine`.`id`
                            LEFT JOIN `or_tipiordine_lang` ON (`or_tipiordine`.`id` = `or_tipiordine_lang`.`id_record` AND `or_tipiordine_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).')
                        WHERE  
                            `or_righe_ordini`.`id`='.prepare($acquisto['id_riga_ordine']);
                    $data = $dbo->fetchArray($query);

                    $id = $data[0]['idordine'];
                }

                $totali[] = [$data[0]['prezzo_unitario'] - $data[0]['sconto_unitario'], $data[0]['iva_unitaria']];

                $numero = !empty($data[0]['numero_esterno']) ? $data[0]['numero_esterno'] : $data[0]['numero'];

                $text = tr('_DOC_ num. _NUM_ del _DATE_', [
                    '_DOC_' => $data[0]['tipo_documento'],
                    '_NUM_' => $numero,
                    '_DATE_' => Translator::dateToLocale($data[0]['data']),
                ]).(!empty($extra) ? ' '.$extra : '');

                echo Modules::link($module, $id, $text).'<br>';
            }

            echo '
            </td>

            <td class="text-center">';
            foreach ($totali as $value) {
                $subtotale = $value[0];
                $iva = $value[1];

                echo '
                <span>'.moneyFormat($subtotale + $iva).'</span>';
                if (!empty($subtotale) && !empty($iva)) {
                    echo '
                <small style="color:#555;">('.Translator::numberToLocale($subtotale).' + '.Translator::numberToLocale($iva).')</small>';
                }
                echo '
                <br>';
            }

            echo '
            </td>';
        }

        // Non venduto
        else {
            echo '
            <td></td>
            <td></td>';
        }

        // Ricerca vendite
        $vendite = $dbo->fetchArray('SELECT * FROM mg_prodotti WHERE dir=\'entrata\' AND id_articolo='.prepare($id_record).' AND serial='.prepare($rs2[$i]['serial']));

        if (!empty($vendite)) {
            echo '
            <td>';

            $totali = [];

            foreach ($vendite as $vendita) {
                // Venduto su fatture
                if (!empty($vendita['id_riga_documento'])) {
                    $module = 'Fatture di vendita';

                    // Ricerca vendite su fatture
                    $query = 'SELECT *, `co_tipidocumento_lang`.`title` AS tipo_documento, `co_tipidocumento`.`dir`, `co_documenti`.`numero`, `co_documenti`.`numero_esterno`,`co_documenti`.`data` FROM `co_righe_documenti` INNER JOIN `co_documenti` ON `co_righe_documenti`.`iddocumento`=`co_documenti`.`id` INNER JOIN `co_tipidocumento` ON `co_documenti`.`idtipodocumento`=`co_tipidocumento`.`id` LEFT JOIN `co_tipidocumento_lang` ON (`co_tipidocumento`.`id`=`co_tipidocumento_lang`.`id_record` AND `co_tipidocumento_lang`.`id_lang`='.prepare(Models\Locale::getDefault()->id).') WHERE `co_righe_documenti`.`id`='.prepare($vendita['id_riga_documento']);
                    $data = $dbo->fetchArray($query);

                    $id = $data[0]['iddocumento'];
                }

                // Venduto su ddt
                elseif (!empty($vendita['id_riga_ddt'])) {
                    $module = 'Ddt di vendita';

                    $query = 'SELECT 
                            *, 
                            `dt_tipiddt_lang`.`title` AS tipo_documento,
                            `dt_ddt`.`dir`,
                            `dt_ddt`.`numero`,
                            `dt_ddt`.`numero_esterno`,
                            `dt_ddt`.`data`
                        FROM 
                            `dt_righe_ddt`
                            INNER JOIN `dt_ddt` ON `dt_righe_ddt`.`idddt`=`dt_ddt`.`id`
                            INNER JOIN `dt_tipiddt` ON `dt_ddt`.`idtipoddt`=`dt_tipiddt`.`id`
                            LEFT JOIN `dt_tipiddt_lang` ON (`dt_tipiddt_lang`.`id_record`=`dt_tipiddt`.`id` AND `dt_tipiddt_lang`.`id_lang`='.prepare(Models\Locale::getDefault()->id).')
                        WHERE 
                            `dt_righe_ddt`.`id`='.prepare($vendita['id_riga_ddt']);
                    $data = $dbo->fetchArray($query);

                    $id = $data[0]['idddt'];
                }

                // Inserito su ordini
                elseif (!empty($vendita['id_riga_ordine'])) {
                    $module = 'Ordini cliente';

                    // Ricerca inserimenti su ordini
                    $query = 'SELECT 
                            *, 
                            `or_tipiordine_lang`.`title` AS tipo_ordine,
                            `or_ordini`.`dir`,
                            `or_ordini`.`numero`,
                            `or_ordini`.`numero_esterno`,
                            `or_ordini`.`data`
                        FROM 
                            `or_righe_ordini`
                            INNER JOIN `or_ordini` ON `or_righe_ordini`.`idordine`=`or_ordini`.`id`
                            INNER JOIN `or_tipiordine` ON `or_ordini`.`idtipoordine`=`or_tipiordine`.`id`
                            LEFT JOIN `or_tipiordine_lang` ON (`or_tipiordine_lang`.`id_record`=`or_tipiordine`.`id` AND `or_tipiordine_lang`.`id_lang`='.prepare(Models\Locale::getDefault()->id).')
                        WHERE  
                            `or_righe_ordini`.`id`='.prepare($vendita['id_riga_ordine']);
                    $data = $dbo->fetchArray($query);

                    $id = $data[0]['idordine'];
                }

                // Inserito su intervento
                elseif (!empty($vendita['id_riga_intervento'])) {
                    $module = 'Interventi';

                    // Ricerca inserimenti su interventi
                    $query = 'SELECT in_righe_interventi.*, in_interventi.codice, ( SELECT orario_inizio FROM in_interventi_tecnici WHERE idintervento=in_righe_interventi.idintervento LIMIT 0,1 ) AS data FROM in_righe_interventi JOIN in_interventi ON in_interventi.id = in_righe_interventi.idintervento WHERE in_righe_interventi.id='.prepare($vendita['id_riga_intervento']);
                    $data = $dbo->fetchArray($query);

                    $id = $data[0]['idintervento'];

                    $data[0]['tipo_documento'] = tr('Intervento').' '.$data[0]['codice'];
                }

                // Inserito su contratto
                elseif (!empty($vendita['id_riga_contratto'])) {
                    $module = 'Contratti';

                    // Ricerca vendite su contratti
                    $query = 'SELECT *, "Contratto" AS tipo_documento, ( SELECT data_bozza FROM co_contratti WHERE id=idcontratto ) AS data, ( SELECT numero FROM co_contratti WHERE id=idcontratto ) AS numero FROM co_righe_contratti WHERE co_righe_contratti.id='.prepare($vendita['id_riga_contratto']);
                    $data = $dbo->fetchArray($query);

                    $id = $data[0]['idcontratto'];
                }

                // Inserito su vendita banco
                elseif (!empty($vendita['id_riga_venditabanco'])) {
                    $module = 'Vendita al banco';

                    // Ricerca vendite su contratti
                    $query = 'SELECT *, "Vendita al banco" AS tipo_documento, ( SELECT data FROM vb_venditabanco WHERE id=idvendita ) AS data, ( SELECT numero FROM vb_venditabanco WHERE id=idvendita ) AS numero FROM vb_righe_venditabanco WHERE vb_righe_venditabanco.id='.prepare($vendita['id_riga_venditabanco']);
                    $data = $dbo->fetchArray($query);

                    $id = $data[0]['idvendita'];
                }

                $totali[] = [$data[0]['prezzo_unitario'] - $data[0]['sconto_unitario'], $data[0]['iva_unitaria']];

                $numero = !empty($data[0]['numero_esterno']) ? $data[0]['numero_esterno'] : $data[0]['numero'];

                $text = tr('_DOC_ num. _NUM_ del _DATE_', [
                    '_DOC_' => $data[0]['tipo_documento'],
                    '_NUM_' => $numero,
                    '_DATE_' => Translator::dateToLocale($data[0]['data']),
                ]);

                echo Modules::link($module, $id, $text).'<br>';
            }

            echo '
            </td>

            <td class="text-center">';
            foreach ($totali as $value) {
                $subtotale = $value[0];
                $iva = $value[1];

                echo '
                <span>'.moneyFormat($subtotale + $iva).'</span>';
                if (!empty($subtotale) && !empty($iva)) {
                    echo '
                <small style="color:#555;">('.Translator::numberToLocale($subtotale).' + '.Translator::numberToLocale($iva).')</small>';
                }
                echo '
                <br>';
            }

            echo '
            </td>

            <td></td>';
        }

        // Non venduto
        else {
            // Documento di vendita
            echo '
            <td></td>
            <td></td>

            <td class="text-center">
                <a class="btn btn-danger btn-sm ask" data-backto="record-edit" data-op="delprodotto" data-idprodotto="'.$rs2[$i]['id'].'">
                    <i class="fa fa-trash"></i>
                </a>
            </td>';
        }
        echo '
            </tr>';
    }
    echo '
            </tbody>
        </table>
    </div>
</div>';
}

echo '
<script type="text/javascript">
$(document).ready(function() {
    $("#table-serials").DataTable().draw();
    $("#serials").removeClass("superselect");
    $("#serials").select2().select2("destroy");

    $("#serials").select2({
        theme: "bootstrap",
        language: "it",
        allowClear: true,
        tags: true,
        tokenSeparators: [\',\']
    });
});

function addSerial(form_id, numero) {
    if (numero > 0){
        swal({
            title: "'.tr('Nuovi seriali').'",
            html: "'.tr("Confermi l'inserimento di _NUM_ nuovi seriali?", [
    '_NUM_' => '" + numero + "',
]).'",
            type: "success",
            showCancelButton: true,
            confirmButtonText: "'.tr('Continua').'"
        }).then(function (result) {
            if ($("div[id^=bs-popup").is(":visible")) { 
                salvaForm($(form_id), {
                }).then(function(response) {
                    $(form_id).closest("div[id^=bs-popup").modal("hide");
                });
            } else {
                $(form_id).submit();
            }
        })
    } else {
        swal("'.tr('Errore').'", "'.tr('Nessun seriale inserito').'", "error");
    }
}

function ricalcola_generazione(){
	if($("#serial_start").val() == undefined) return 0;

    var serial_start = get_last_numeric_part( $("#serial_start").val().toString() );
    var serial_end = get_last_numeric_part( $("#serial_end").val().toString() );
    var serial = Math.abs(parseInt(serial_end, 10) - parseInt(serial_start, 10))+1;

    // Se tutti i campi sono vuoti, il numero di prodotti è zero!
    if(isNaN(serial)) {
        serial = 0;
    }

    $("#totale_generazione").text(serial);
}

function ricalcola_inserimento(){
    $("#totale_inserimento").text($("#serials").select2("data").length);
}

/*
	Questa funzione restituisce la parte numerica di una stringa
*/
function get_last_numeric_part(str){
	var matches = str.match(/(.*?)([\d]*$)/);
	return matches[2];
}
</script>';
