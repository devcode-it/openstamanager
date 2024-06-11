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

include_once __DIR__.'/../../core.php';
use Models\Module;
use Modules\Pagamenti\Pagamento;

$dir = $documento->direzione;
$numero = $documento->numero_esterno ?: $documento->numero;
$id_modulo_banche = Module::where('name', 'Banche')->first()->id;
$id_modulo_prima_nota = Module::where('name', 'Prima nota')->first()->id;

echo '
<form action="" method="post" id="edit-form">
	<input type="hidden" name="op" value="update">
	<input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="id_record" value="'.$id_record.'">

	<input type="hidden" name="tipo" value="'.$record['tipo'].'">
	<input type="hidden" name="descrizione" value="'.$record['descrizione'].'">
	<input type="hidden" name="iddocumento" value="'.$record['iddocumento'].'">
    <input type="hidden" name="idanagrafica" value="'.$record['idanagrafica'].'">

	<div class="card card-primary">
		<div class="card-header">
			<h3 class="card-title">
			    '.tr('Dettagli scadenza').'
                </button>
            </h3>
		</div>

		<div class="card-body">
			<div class="row">

				<!-- Info scadenza -->
				<div class="col-md-6">
					<table class="table table-striped table-hover table-condensed table-bordered">
                        <tr>
                            <th width="125">'.($dir == 'entrata' ? tr('Cliente') : ($dir == 'uscita' ? tr('Fornitore') : tr('Anagrafica'))).':</th>
                            <td>
                                '.Modules::link('Anagrafiche', $record['idanagrafica'], $record['ragione_sociale']).'
                            </td>
                        </tr>';
if (!empty($documento)) {
    echo '
                        <tr>
                            <th>'.tr('Documento').':</th>
                            <td>'.$documento->tipo->getTranslation('title').'</td>
                        </tr>

                        <tr>
                            <th>'.tr('Numero').':</th>
                            <td>'.$numero.'</td>
                        </tr>
                    </table>    

                    <table class="table table-striped table-hover table-condensed table-bordered">
                        <tr>
                            <th>'.tr('Data').':</th>
                            <td>'.Translator::dateToLocale($documento->data).'</td>
                        </tr>

                        <tr>
                            <th>'.tr('Netto a pagare').':</th>
                            <td>'.moneyFormat($documento->netto).'</td>
                        </tr>
                        <tr>
                            <th>'.tr('Info distinta').' <span class="tip" title="'.tr('Informazioni/Note sulla distinta associata alla scadenza (es. numero)').'" ><i class="fa fa-question-circle-o" ></i></span>:</th>
                            <td>
                                {[ "type": "text", "name": "distinta", "value": "'.$record['distinta'].'" ]}
                            </td>
                        </tr>
                    </table>

                    '.Modules::link($documento->module, $record['iddocumento'], '<i class="fa fa-folder-open"></i> '.tr('Apri documento'), null, 'class="btn btn-primary"').'
                </div>';
} else {
    $scadenza = $dbo->fetchOne('SELECT * FROM co_scadenziario WHERE id = '.prepare($id_record));
    echo '          
                    <table class="table table-striped table-hover table-condensed table-bordered">
                        <tr>
                            <td>';
    echo input([
        'type' => 'ckeditor',
        'label' => tr('descrizione'),
        'name' => 'descrizione',
        'required' => 1,
        'extra' => 'rows="2"',
        'value' => $record['descrizione'],
    ]);
    echo '
                            </td>
                        </tr>
                    </table>
                </div>';
}

echo '
                <div class="col-md-6">
                    <table class="table table-striped table-hover table-condensed table-bordered">
                        <tr>
                            <td>';
echo input([
    'type' => 'ckeditor',
    'label' => tr('Note'),
    'name' => 'note',
    'extra' => 'rows="2"',
    'value' => $record['note'],
]);
echo '
                            </td>
                        </tr>';

if (!empty($record['presentazioni_exported_at'])) {
    $export_riba = '<i class="fa fa-check text-success"></i> '.tr('Esportata il _DATA_', [
        '_DATA_' => Translator::timestampToLocale($record['presentazioni_exported_at']),
    ]).'';
} else {
    $export_riba = '<i class="fa fa-clock-o text-warning"></i> '.tr('Non ancora esportata');
}
echo '
                    </table>';

echo '
				</div>
            </div>
        </div>
    </div>
    <div class="card card-primary">
        <div class="card-header">
            <h3 class="card-title">
                '.tr('Rate scadenza').'
                <button type="button" class="btn btn-xs btn-info pull-right tip" id="add-scadenza" '.(empty($documento) ? 'disabled' : '').' title="'.tr('È possibile aggiungere scadenze solo se è presente il collegamento a un documento, in caso contrario è consigliato creare più scadenze con la stessa descrizione').'">
                    <i class="fa fa-plus"></i> '.tr('Aggiungi scadenza').'
                </button>
            </h3>
        </div>

        <div class="card-body">
            <div class="row">
                <div class="col-md-12">
                    <table class="table table-hover table-condensed table-bordered text-center">
                        <thead>
                            <tr>
                                <th style="width:17%;">'.tr('Banca accredito').'</th> 
                                <th style="width:16%;">'.tr('Banca addebito').'</th> 
                                <th style="width:20%;">'.tr('Metodo di pagamento').'</th>
                                <th style="width:10%;">'.tr('Data').'</th>
                                <th style="width:10%;">'.tr('Data concordata').'</th>
                                <th style="width:10%;">'.tr('Importo').'</th>
                                <th style="width:10%;">'.tr('Pagato').'</th>
                                <th style="width:7%;">'.tr('Rata').'</th>
                            </tr>
                        </thead>

                        <tbody id="scadenze">';

foreach ($scadenze as $i => $scadenza) {
    $scadenza = (array) $scadenza;
    if ($scadenza['da_pagare'] === $scadenza['pagato'] && $scadenza['da_pagare'] > 0) {
        $class = 'success';
    } elseif (abs($scadenza['pagato']) === 0.000000) {
        $class = 'danger';
    } elseif (abs($scadenza['pagato']) <= abs($scadenza['da_pagare'])) {
        $class = 'warning';
    } else {
        $class = 'danger';
    }

    $id_pagamento = Pagamento::find($scadenza['id_pagamento']);

    $pagamento = $dbo->fetchOne('SELECT `co_pagamenti`.`id` FROM `fe_modalita_pagamento` INNER JOIN `fe_modalita_pagamento_lang` ON (`fe_modalita_pagamento_lang`.`id_record` = `fe_modalita_pagamento`.`codice` AND `fe_modalita_pagamento_lang`.`id_lang` = '.Models\Locale::getDefault()->id.') INNER JOIN `co_pagamenti` ON `fe_modalita_pagamento`.`codice` = `co_pagamenti`.`codice_modalita_pagamento_fe` WHERE `fe_modalita_pagamento`.`codice` LIKE '.prepare($id_pagamento->codice_modalita_pagamento_fe).'')['id'];
    echo '
                            <tr class="'.$class.'">
                                <input type="hidden" name="id_scadenza['.$i.']" value="'.$scadenza['id'].'">
                                <td align="center">
                                    '.($dir == 'entrata' ?
                                '{[ "type": "select", "name": "id_banca_azienda['.$i.']", "ajax-source": "banche", "select-options": '.json_encode(['id_anagrafica' => $anagrafica_azienda->id]).', "value": "'.$scadenza['id_banca_azienda'].'", "icon-after": "add|'.$id_modulo_banche.'|id_anagrafica='.$anagrafica_azienda->id.'" ]}'
                                :
                                '{[ "type": "select", "name": "id_banca_controparte['.$i.']", "ajax-source": "banche", "select-options":'.json_encode(['id_anagrafica' => $scadenza['idanagrafica']]).', "value": "'.$scadenza['id_banca_controparte'].'", "icon-after": "add|'.$id_modulo_banche.'|idanagrafica='.$record['idanagrafica'].'"]}
                                    ').'
                                </td>

                                <td align="center">
                                    '.($dir == 'entrata' ?
                                '{[ "type": "select", "name": "id_banca_controparte['.$i.']", "ajax-source": "banche", "select-options":'.json_encode(['id_anagrafica' => $scadenza['idanagrafica']]).', "value": "'.$scadenza['id_banca_controparte'].'", "icon-after": "add|'.$id_modulo_banche.'|idanagrafica='.$record['idanagrafica'].'"]}'
                                :
                                '{[ "type": "select", "name": "id_banca_azienda['.$i.']", "ajax-source": "banche", "select-options": '.json_encode(['id_anagrafica' => $anagrafica_azienda->id]).', "value": "'.$scadenza['id_banca_azienda'].'", "icon-after": "add|'.$id_modulo_banche.'|id_anagrafica='.$anagrafica_azienda->id.'" ]}'
                                ).'
                                </td>

                                <td>
                                    {[ "type": "select", "name": "id_pagamento['.$i.']", "values": "query=SELECT `co_pagamenti`.`id`, `fe_modalita_pagamento_lang`.`title` as descrizione FROM `fe_modalita_pagamento` LEFT JOIN `fe_modalita_pagamento_lang` ON (`fe_modalita_pagamento_lang`.`id_record` = `fe_modalita_pagamento`.`codice` AND `fe_modalita_pagamento_lang`.`id_lang` = '.Models\Locale::getDefault()->id.') INNER JOIN `co_pagamenti` ON `fe_modalita_pagamento`.`codice` = `co_pagamenti`.`codice_modalita_pagamento_fe` GROUP BY title", "value": "'.$pagamento.'" ]}
                                </td>

                                <td align="center">
                                    {[ "type": "date", "name": "scadenza['.$i.']", "value": "'.$scadenza['scadenza'].'", "readonly": 1 ]}
                                </td>

                                <td align="center">
                                    {[ "type": "date", "name": "data_concordata['.$i.']", "value": "'.$scadenza['data_concordata'].'" ]}
                                </td>

                                <td class="text-right">
                                    {[ "type": "number", "name": "da_pagare['.$i.']", "decimals": 2, "value": "'.numberFormat($scadenza['da_pagare'], 2).'", "onchange": "controlloTotale()" ]}
                                </td>

                                <td class="text-right">
                                    {[ "type": "number", "name": "pagato['.$i.']", "decimals": 2, "value": "'.numberFormat($scadenza['pagato']).'"  ]}
                                </td>

                                <td align="center">
                                    <a onclick="launch_modal(\''.tr('Registra contabile pagamento').'\', \''.base_path().'/add.php?id_module='.$id_modulo_prima_nota.'&id_scadenze='.$scadenza['id'].'\');" class="btn btn-sm btn-primary">
                                        <i class="fa fa-euro"></i> '.($dir == 'entrata' ? tr('Incassa') : tr('Paga')).'
                                    </a>
                                </td>
                            </tr>';
}

echo '
                        </tbody>
                        <tfoot>
                            <tr>
                                <td class="text-right"></td>
                                <td class="text-right"></td>
                                <td class="text-right"></td>
                                <td class="text-right"></td>
                                <td class="text-right"><b>'.tr('Totale').'</b></td>
                                <td class="text-right" id="totale_utente">'.numberFormat($totale_da_pagare).'</td>
                                <td class="text-right"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>';
?>

            </div>
            <div class="alert alert-warning hide" id="totale"><?php echo tr('Il totale da pagare non corrisponde con il totale della fattura che è pari a _MONEY_', [
                '_MONEY_' => '<b>'.moneyFormat($totale_da_pagare).'</b>',
            ]); ?>.<br><?php echo tr('Differenza di _TOT_ _CURRENCY_', [
                '_TOT_' => '<span id="diff"></span>',
                '_CURRENCY_' => currency(),
            ]); ?>.
            </div>

            <input type="hidden" id="totale_da_pagare" value="<?php echo round($totale_da_pagare, 2); ?>">
        </div>
    </div>
</form>

{( "name": "filelist_and_upload", "id_module": "$id_module$", "id_record": "<?php echo $id_record; ?>" )}

{( "name": "log_email", "id_module": "$id_module$", "id_record": "$id_record$" )}

<?php
if (empty($documento)) {
    echo '
<a class="btn btn-danger ask" data-backto="record-list">
    <i class="fa fa-trash"></i> '.tr('Elimina').'
</a>';
}

echo '
<table class="hide">
    <tbody id="scadenza-template">
        <tr class="danger">
            <input type="hidden" name="id_scadenza[-id-]" value="">
                <td align="center">
                    '.($dir == 'entrata' ?
'{[ "type": "select", "name": "id_banca_azienda[-id-]", "ajax-source": "banche", "select-options": '.json_encode(['id_anagrafica' => $anagrafica_azienda->id]).', "icon-after": "add|'.$id_modulo_banche.'|id_anagrafica='.$anagrafica_azienda->id.'" ]}'
:
'{[ "type": "select", "name": "id_banca_controparte[-id-]", "ajax-source": "banche", "select-options":'.json_encode(['id_anagrafica' => $scadenza['idanagrafica']]).', "icon-after": "add|'.$id_modulo_banche.'|idanagrafica='.$record['idanagrafica'].'"]}
                    ').'
                </td>
                <td align="center">
                    '.($dir == 'entrata' ?
'{[ "type": "select", "name": "id_banca_controparte[-id-]", "ajax-source": "banche", "select-options":'.json_encode(['id_anagrafica' => $scadenza['idanagrafica']]).',"icon-after": "add|'.$id_modulo_banche.'|idanagrafica='.$record['idanagrafica'].'"]}'
:
'{[ "type": "select", "name": "id_banca_azienda[-id-]", "ajax-source": "banche", "select-options": '.json_encode(['id_anagrafica' => $anagrafica_azienda->id]).', "icon-after": "add|'.$id_modulo_banche.'|id_anagrafica='.$anagrafica_azienda->id.'" ]}'
).'
                </td>

                <td>
                    {[ "type": "select", "name": "id_pagamento[-id-]", "values": "query=SELECT `co_pagamenti`.`id`, `fe_modalita_pagamento_lang`.`title` as descrizione FROM `fe_modalita_pagamento` LEFT JOIN `fe_modalita_pagamento_lang` ON (`fe_modalita_pagamento_lang`.`id_record` = `fe_modalita_pagamento`.`codice` AND `fe_modalita_pagamento_lang`.`id_lang` = '.Models\Locale::getDefault()->id.') INNER JOIN `co_pagamenti` ON `fe_modalita_pagamento`.`codice` = `co_pagamenti`.`codice_modalita_pagamento_fe` GROUP BY title"]}
                </td>

                <td align="center">
                    {[ "type": "date", "name": "scadenza[-id-]" ]}
                </td>

                <td align="center">
                    {[ "type": "date", "name": "data_concordata[-id-]" ]}
                </td>

                <td class="text-right">
                    {[ "type": "number", "name": "da_pagare[-id-]", "decimals": 2, "onchange": "controlloTotale()" ]}
                </td>

                <td class="text-right">
                    {[ "type": "number", "name": "pagato[-id-]", "decimals": 2 ]}
                </td>

                <td align="center">
                    <a onclick="launch_modal(\''.tr('Registra contabile pagamento').'\', \''.base_path().'/add.php?id_module='.$id_modulo_prima_nota.'&id_scadenze=-id-\');" class="btn btn-sm btn-primary">
                        <i class="fa fa-euro"></i> '.($dir == 'entrata' ? tr('Incassa') : tr('Paga')).'
                    </a>
                </td>      
            </input>    
        </tr>
    </tbody>
</table>

<script>
    
	$(document).on("click", "#add-scadenza", function() {
        var i = '.$i.';
        cleanup_inputs();

        i++;
		var text = replaceAll($("#scadenza-template").html(), "-id-", "" + i);

		$("#scadenze").append(text);

        $.ajax({
            url: globals.rootdir + "/actions.php",
            method: "POST",
            dataType: "json",
            data: {
                id_module: globals.id_module,
                id_record: globals.id_record,
                op: "add",
                idanagrafica: '.$record['idanagrafica'].',
                iddocumento: '.$documento['id'].',
                data_emissione: "'.$documento['data_emissione'].'",
            },
            success: function(response) {
                restart_inputs();
            },
            error: function(xhr, status, error) {
                console.error(error);
            }
        });

		restart_inputs();
	});
</script>';

// Abilitazione dei controlli solo per Scadenze collegate a documenti
if (!empty($documento)) {
    echo '
<script>
    globals.cifre_decimali = 2;

	$(document).ready(function() {
        controlloTotale();';

    if ($dir == 'uscita') {
        echo '
        $("#email-button").remove();
        $("#allega-fattura").remove();';
    }
    echo '
	});

    function controlloTotale() {
        let totale_da_pagare = parseFloat($("#totale_da_pagare").val());
        let totale_utente = 0;

        $("input[name*=da_pagare]").each(function() {
            totale_utente += input(this).get();
        });

        if (isNaN(totale_utente)) {
            totale_utente = 0;
        }

        totale_utente = Math.round(totale_utente * 100) / 100;
        totale_da_pagare = Math.round(totale_da_pagare * 100) / 100;

        let diff = Math.abs(totale_da_pagare) - Math.abs(totale_utente);

        if (diff == 0) {
            $("#totale").addClass("hide");
        } else {
            $("#totale").removeClass("hide");
        }

        $("#diff").html(diff.toLocale());
        $("#totale_utente").html(totale_utente.toLocale());
    }
</script>';
}
