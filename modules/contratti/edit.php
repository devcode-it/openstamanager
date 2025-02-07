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
use Modules\Contratti\Stato;

$block_edit = $record['is_completato'];
$data_accettazione = $record['data_accettazione'] ? strtotime((string) $record['data_accettazione']) : '';
$data_conclusione = $record['data_conclusione'] ? strtotime((string) $record['data_conclusione']) : '';

if ($data_conclusione < $data_accettazione && !empty($data_accettazione) && !empty($data_conclusione)) {
    echo '
    <div class="alert alert-warning"><a class="clickable" onclick="$(\'.alert\').hide();"><i class="fa fa-times"></i></a> '.tr('Attenzione! La data di accettazione supera la data di conclusione del contratto. Verificare le informazioni inserite.').'</div>';
}

echo '
<form action="" method="post" id="edit-form">
	<input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="op" value="update">
	<input type="hidden" name="id_record" value="'.$id_record.'">

    <div class="row">
        <div class="col-md-2 offset-md-10">';
if (setting('Cambia automaticamente stato contratti fatturati')) {
    $id_stato_fatt = Stato::where('name', 'Fatturato')->first()->id;
    $id_stato_parz_fatt = Stato::where('name', 'Parzialmente fatturato')->first()->id;

    if ($contratto->stato->id == $id_stato_fatt || $contratto->stato->id == $id_stato_parz_fatt) {
        echo '
                    {[ "type": "select", "label": "'.tr('Stato').'", "name": "idstato", "required": 1, "values": "query=SELECT `co_staticontratti`.`id`, `title` as `descrizione`, `colore` AS _bgcolor_ FROM `co_staticontratti` LEFT JOIN `co_staticontratti_lang` ON (`co_staticontratti`.`id` = `co_staticontratti_lang`.`id_record` AND `co_staticontratti_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).') ORDER BY `title`", "value": "$idstato$", "class": "unblockable" ]}';
    } else {
        echo '
                    {[ "type": "select", "label": "'.tr('Stato').'", "name": "idstato", "required": 1, "values": "query=SELECT `co_staticontratti`.`id`, `title` as `descrizione`, `colore` AS _bgcolor_ FROM `co_staticontratti` LEFT JOIN `co_staticontratti_lang` ON (`co_staticontratti`.`id` = `co_staticontratti_lang`.`id_record` AND `co_staticontratti_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).') WHERE `co_staticontratti`.`id` NOT IN ('.implode(',', [$id_stato_fatt, $id_stato_parz_fatt]).') ORDER BY `title`", "value": "$idstato$", "class": "unblockable" ]}';
    }
} else {
    echo '
            {[ "type": "select", "label": "'.tr('Stato').'", "name": "idstato", "required": 1, "values": "query=SELECT `co_staticontratti`.`id`, `title` as `descrizione`, `colore` AS _bgcolor_ FROM `co_staticontratti` LEFT JOIN `co_staticontratti_lang` ON (`co_staticontratti`.`id` = `co_staticontratti_lang`.`id_record` AND `co_staticontratti_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).') ORDER BY `title`", "value": "$idstato$", "class": "unblockable" ]}
        </div>';
}
echo '
        </div>
    </div>

	<!-- DATI INTESTAZIONE -->
    <div class="card card-primary collapsable">
        <div class="card-header with-border">
            <h3 class="card-title">'.tr('Dati cliente').'</h3>
            <div class="card-tools pull-right">
                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                    <i class="fa fa-minus"></i>
                </button>
            </div>
        </div>


        <div class="card-body">
            <!-- RIGA 1 -->
            <div class="row">
                <div class="col-md-4">
                '.Modules::link('Anagrafiche', $record['idanagrafica'], null, null, 'class="pull-right"').'
                    {[ "type": "select", "label": "'.tr('Cliente').'", "name": "idanagrafica", "id": "idanagrafica_c", "required": 1, "value": "$idanagrafica$", "ajax-source": "clienti" ]}
                </div>

                <div class="col-md-4">
                    {[ "type": "select", "label": "'.tr('Sede partenza').'", "name": "idsede_partenza", "ajax-source": "sedi_azienda", "value": "$idsede_partenza$", "select-options": '.json_encode(['idsede_partenza' => $record['idsede_partenza']]).', "help": "'.tr("Sedi di partenza dell'azienda").'" ]}
                </div>

                <div class="col-md-4">
                    {[ "type": "select", "label": "'.tr('Sede destinazione').'", "name": "idsede_destinazione", "value": "$idsede_destinazione$", "ajax-source": "sedi", "select-options": {"idanagrafica": '.$record['idanagrafica'].'}, "placeholder": "Sede legale" ]}
                </div>
            </div>

            <div class="row">
                <div class="col-md-4">
                    '.Plugins::link('Referenti', $record['idanagrafica'], null, null, 'class="pull-right"').'
                    {[ "type": "select", "label": "'.tr('Referente').'", "name": "idreferente", "value": "$idreferente$", "ajax-source": "referenti", "select-options": {"idanagrafica": '.$record['idanagrafica'].',"idsede_destinazione": '.$record['idsede_destinazione'].'} ]}
                </div>

                <div class="col-md-4">';
if ($record['idagente'] != 0) {
    echo Modules::link('Anagrafiche', $record['idagente'], null, null, 'class="pull-right"');
}
echo '
                    {[ "type": "select", "label": "'.tr('Agente').'", "name": "idagente", "ajax-source": "agenti", "select-options": {"idanagrafica": '.$record['idanagrafica'].'}, "value": "$idagente$" ]}
                </div>
            </div>
        </div>
    </div>';
?>
	<div class="card card-primary">
		<div class="card-header">
			<h3 class="card-title"><?php echo tr('Intestazione'); ?></h3>
		</div>

		<div class="card-body">
			<div class="row">
				<div class="col-md-2">
					{[ "type": "text", "label": "<?php echo tr('Numero'); ?>", "name": "numero", "required": 1, "class": "text-center", "value": "$numero$", "icon-after": "<?php echo $numero_contratto_originale; ?>" ]}
				</div>

                <div class="col-md-2">
                    {[ "type": "date", "label": "<?php echo tr('Data bozza'); ?>", "name": "data_bozza", "required": 1, "value": "$data_bozza$" ]}
                </div>

                <div class="col-md-2">
                    {[ "type": "date", "label": "<?php echo tr('Data accettazione'); ?>", "name": "data_accettazione", "value": "$data_accettazione$", "max-date": "$data_conclusione$" ]}
                </div>

                <div class="col-md-2">
                    {[ "type": "date", "label": "<?php echo tr('Data conclusione'); ?>", "name": "data_conclusione", "value": "$data_conclusione$", "disabled": "<?php echo $contratto ? ($contratto->isDataConclusioneAutomatica() ? '1", "help": "'.tr('La Data di conclusione è calcolata in automatico in base al valore del campo Validità contratto, se definita') : '0') : ''; ?>" ]}
                </div>

                <div class="col-md-2">
                    {[ "type": "date", "label": "<?php echo tr('Data rifiuto'); ?>", "name": "data_rifiuto", "value": "$data_rifiuto$" ]}
                </div>

                <div class="col-md-2">
					{[ "type": "number", "label": "<?php echo tr('Validità contratto'); ?>", "name": "validita", "decimals": "0", "value": "$validita$", "icon-after": "choice|period|<?php echo $record['tipo_validita']; ?>", "help": "<?php echo tr('Il campo Validità contratto viene utilizzato per il calcolo della Data di conclusione del contratto'); ?>" ]}
				</div>
            </div>

            <div class="row">
                <div class="col-md-4">
                    {[ "type": "text", "label": "<?php echo tr('Nome contratto'); ?>", "name": "nome", "required": 1, "value": "$nome$" ]}
                </div>

                <div class="col-md-6">
					{[ "type": "select", "multiple": "1", "label": "<?php echo tr('Impianti'); ?>", "name": "matricolaimpianto[]", "values": "query=SELECT idanagrafica, id AS id, IF(nome = '', matricola, CONCAT(matricola, ' - ', nome)) AS descrizione FROM my_impianti WHERE idanagrafica='$idanagrafica$' ORDER BY descrizione", "value": "$idimpianti$", "icon-after": "add|<?php echo Module::where('name', 'Impianti')->first()->id; ?>|<?php echo 'id_anagrafica='.$record['idanagrafica']; ?>||<?php echo (empty($block_edit)) ? '' : 'disabled'; ?>" ]}
				</div>

                <div class="col-md-2">
                    {[ "type": "number", "label": "<?php echo 'Sconto in fattura'; ?>", "name": "sconto_finale", "value": "<?php echo $contratto->sconto_finale_percentuale ?: $contratto->sconto_finale; ?>", "icon-after": "choice|untprc|<?php echo empty($contratto->sconto_finale) ? 'PRC' : 'UNT'; ?>", "help": "<?php echo tr('Sconto in fattura, utilizzabile per applicare sconti sul netto a pagare del documento'); ?>." ]}
				</div>
            </div>

            <div class="row">
                <div class="col-md-3">
                    {[ "type": "select", "label": "<?php echo tr('Pagamento'); ?>", "name": "idpagamento", "values": "query=SELECT `co_pagamenti`.`id`, `co_pagamenti_lang`.`title` AS `descrizione` FROM `co_pagamenti` LEFT JOIN `co_pagamenti_lang` ON (`co_pagamenti`.`id` = `co_pagamenti_lang`.`id_record` AND `co_pagamenti_lang`.`id_lang` = <?php echo prepare(Models\Locale::getDefault()->id); ?>) GROUP BY `descrizione` ORDER BY `descrizione`", "value": "$idpagamento$" ]}
                </div>

                <div class="col-md-3">
                    {[ "type": "select", "label": "<?php echo tr('Tipo attività predefinita'); ?>", "name": "idtipointervento", "ajax-source": "tipiintervento", "value": "$idtipointervento$" ]}
                </div>

                <div class="col-md-3">
                    <?php echo (!empty($record['id_categoria'])) ? Modules::link('Categorie contratti', $record['id_categoria'], null, null, 'class="pull-right"') : ''; ?>
                    {[ "type": "select", "label": "<?php echo tr('Categoria'); ?>", "name": "id_categoria", "required": 0, "value": "$id_categoria$", "ajax-source": "categorie_contratti", "icon-after": "add|<?php echo Module::where('name', 'Categorie contratti')->first()->id; ?>" ]}
                </div>

                <div class="col-md-3">
                    <?php echo !empty($record['id_sottocategoria']) ? Modules::link('Categorie contratti', $record['id_categoria'], null, null, 'class="pull-right"') : ''; ?>{[ "type": "select", "label": "<?php echo tr('Sottocategoria'); ?>", "name": "id_sottocategoria", "value": "$id_sottocategoria$", "ajax-source": "sottocategorie_contratti", "select-options": <?php echo json_encode(['id_categoria' => $record['id_categoria']]); ?>, "icon-after": "add|<?php echo Module::where('name', 'Categorie contratti')->first()->id; ?>|id_original=<?php echo $record['id_categoria']; ?>" ]}
                </div>
            </div>

			<div class="row">
				<div class="col-md-4">
					{[ "type": "textarea", "label": "<?php echo tr('Esclusioni'); ?>", "name": "esclusioni", "class": "autosize", "value": "$esclusioni$", "extra": "rows='5'" ]}
				</div>
				<div class="col-md-4">
					{[ "type": "textarea", "label": "<?php echo tr('Descrizione'); ?>", "name": "descrizione", "class": "autosize", "value": "$descrizione$", "extra": "rows='5'" ]}
				</div>
                <?php
            // Nascondo le note interne ai clienti
            if ($user->gruppo != 'Clienti') {
                echo '

                <div class="col-md-4">
                    {[ "type": "textarea", "label": "'.tr('Note interne').'", "name": "informazioniaggiuntive", "class": "autosize", "value": "$informazioniaggiuntive$", "extra": "rows=\'5\'" ]}
                </div>';
            }
?>
			</div>

            <div class="row">
				<div class="col-md-12">
                    <?php echo input([
                        'type' => 'ckeditor',
                        'use_full_ckeditor' => 0,
                        'label' => tr('Condizioni generali di fornitura'),
                        'name' => 'condizioni_fornitura',
                        'value' => $record['condizioni_fornitura'],
                    ]);
?>
				</div>
			</div>
		</div>
	</div>

    <?php
        if (!empty($record['id_documento_fe']) || !empty($record['num_item']) || !empty($record['codice_cig']) || !empty($record['codice_cup'])) {
            $collapsed = '';
        } else {
            $collapsed = ' collapsed-card';
        }
?>

    <!-- Fatturazione Elettronica PA-->

    <div class="card card-primary collapsable  <?php echo ($record['tipo_anagrafica'] == 'Ente pubblico' || $record['tipo_anagrafica'] == 'Azienda') ? 'show' : 'hide'; ?> <?php echo $collapsed; ?>">
        <div class=" card-header">
            <h4 class=" card-title">
                
                <?php echo tr('Dati appalto'); ?></h4>

                <div class="card-tools pull-right">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                    <i class="fa fa-plus"></i>
                    </button>
                </div>
            
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    {[ "type": "text", "label": "<?php echo tr('Identificatore Documento'); ?>", "name": "id_documento_fe", "required": 0, "help": "<?php echo tr('<span>Obbligatorio per valorizzare CIG/CUP. &Egrave; possible inserire: </span><ul><li>N. determina</li><li>RDO</li><li>Ordine MEPA</li></ul>'); ?>", "value": "$id_documento_fe$", "maxlength": 20 ]}
                </div>

                <div class="col-md-6">
                    {[ "type": "text", "label": "<?php echo tr('Numero Riga'); ?>", "name": "num_item", "required": 0, "value": "$num_item$", "maxlength": 15 ]}
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    {[ "type": "text", "label": "<?php echo tr('Codice CIG'); ?>", "name": "codice_cig", "required": 0, "value": "$codice_cig$", "maxlength": 15 ]}
                </div>

                <div class="col-md-6">
                    {[ "type": "text", "label": "<?php echo tr('Codice CUP'); ?>", "name": "codice_cup", "required": 0, "value": "$codice_cup$", "maxlength": 15 ]}
                </div>
            </div>
        </div>
    </div>
  

	<!-- COSTI -->
	<div class="card card-primary">
		<div class="card-header">
			<h3 class="card-title"><?php echo tr('Costi unitari'); ?></h3>
		</div>

		<div class="card-body">
			<div class="row">
				<div class="col-md-12 col-lg-12">
<?php

$idtipiintervento = ['-1'];

// Loop fra i tipi di attività e i relativi costi del tipo intervento
$rs = $dbo->fetchArray('SELECT `co_contratti_tipiintervento`.*, `in_tipiintervento_lang`.`title` FROM `co_contratti_tipiintervento` INNER JOIN `in_tipiintervento` ON `in_tipiintervento`.`id` = `co_contratti_tipiintervento`.`idtipointervento` LEFT JOIN `in_tipiintervento_lang` ON `in_tipiintervento_lang`.`id_record` = `in_tipiintervento`.`id` AND `in_tipiintervento_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).' WHERE `idcontratto`='.prepare($id_record).' AND (`co_contratti_tipiintervento`.`costo_ore` != `in_tipiintervento`.`costo_orario` OR `co_contratti_tipiintervento`.`costo_km` != `in_tipiintervento`.`costo_km` OR `co_contratti_tipiintervento`.`costo_dirittochiamata` != `in_tipiintervento`.`costo_diritto_chiamata`) ORDER BY `in_tipiintervento_lang`.`title`');

if (!empty($rs)) {
    echo '
                    <table class="table table-striped table-sm table-bordered">
                        <tr>
                            <th width="300">'.tr('Tipo attività').'</th>

                            <th>'.tr('Addebito orario').' <span class="tip" title="'.tr('Addebito al cliente').'"><i class="fa fa-question-circle-o"></i></span></th>
                            <th>'.tr('Addebito km').' <span class="tip" title="'.tr('Addebito al cliente').'"><i class="fa fa-question-circle-o"></i></span></th>
                            <th>'.tr('Addebito diritto ch.').' <span class="tip" title="'.tr('Addebito al cliente').'"><i class="fa fa-question-circle-o"></i></span></th>

                            <th width="40"></th>
                        </tr>';

    for ($i = 0; $i < sizeof($rs); ++$i) {
        echo '
                            <tr>
                                <td>'.$rs[$i]['title'].'</td>

                                <td>
                                    {[ "type": "number", "name": "costo_ore['.$rs[$i]['idtipointervento'].']", "value": "'.$rs[$i]['costo_ore'].'" ]}
                                </td>

                                <td>
                                    {[ "type": "number", "name": "costo_km['.$rs[$i]['idtipointervento'].']", "value": "'.$rs[$i]['costo_km'].'" ]}
                                </td>

                                <td>
                                    {[ "type": "number", "name": "costo_dirittochiamata['.$rs[$i]['idtipointervento'].']", "value": "'.$rs[$i]['costo_dirittochiamata'].'" ]}
                                </td>

                                <td>
                                    <button type="button" class="btn btn-warning" data-card-widget="tooltip" title="Importa valori da tariffe standard" onclick="if( confirm(\'Importare i valori dalle tariffe standard?\') ){ $.post( \''.base_path().'/modules/contratti/actions.php\', { op: \'import\', idcontratto: \''.$id_record.'\', idtipointervento: \''.$rs[$i]['idtipointervento'].'\' }, function(data){ location.href=\''.base_path().'/editor.php?id_module='.$id_module.'&id_record='.$id_record.'\'; } ); }">
                                    <i class="fa fa-download"></i>
                                    </button>
                                </td>

                            </tr>';

        $idtipiintervento[] = prepare($rs[$i]['idtipointervento']);
    }
    echo '
                    </table>';
}

echo '
                    <button type="button" onclick="$(this).next().toggleClass(\'hide\');" class="btn btn-info btn-sm"><i class="fa fa-th-list"></i> '.tr('Mostra tipi di attività non modificati').'</button>
					<div class="hide">';

// Loop fra i tipi di attività e i relativi costi del tipo intervento (quelli a 0)
$rs = $dbo->fetchArray('SELECT * FROM `co_contratti_tipiintervento` INNER JOIN `in_tipiintervento` ON `in_tipiintervento`.`id` = `co_contratti_tipiintervento`.`idtipointervento` LEFT JOIN `in_tipiintervento_lang` ON (`in_tipiintervento`.`id`=`in_tipiintervento_lang`.`id_record` AND `in_tipiintervento_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).') WHERE `co_contratti_tipiintervento`.`idtipointervento` NOT IN('.implode(',', $idtipiintervento).') AND `idcontratto`='.prepare($id_record).' ORDER BY `title`');

if (!empty($rs)) {
    echo '
                        <div class="clearfix">&nbsp;</div>
						<table class="table table-striped table-sm table-bordered">
							<tr>
								<th width="300">'.tr('Tipo attività').'</th>

								<th>'.tr('Addebito orario').' <span class="tip" title="'.tr('Addebito al cliente').'"><i class="fa fa-question-circle-o"></i></span></th>
								<th>'.tr('Addebito km').' <span class="tip" title="'.tr('Addebito al cliente').'"><i class="fa fa-question-circle-o"></i></span></th>
								<th>'.tr('Addebito diritto ch.').' <span class="tip" title="'.tr('Addebito al cliente').'"><i class="fa fa-question-circle-o"></i></span></th>

                                <th width="40"></th>
							</tr>';

    for ($i = 0; $i < sizeof($rs); ++$i) {
        echo '
                            <tr>
                                <td>'.$rs[$i]['title'].'</td>

                                <td>
                                    {[ "type": "number", "name": "costo_ore['.$rs[$i]['idtipointervento'].']", "value": "'.$rs[$i]['costo_orario'].'", "icon-after": "<i class=\'fa fa-euro\'></i>" ]}
                                </td>

                                <td>
                                    {[ "type": "number", "name": "costo_km['.$rs[$i]['idtipointervento'].']", "value": "'.$rs[$i]['costo_km'].'", "icon-after": "<i class=\'fa fa-euro\'></i>" ]}
                                </td>

                                <td>
                                    {[ "type": "number", "name": "costo_dirittochiamata['.$rs[$i]['idtipointervento'].']", "value": "'.$rs[$i]['costo_diritto_chiamata'].'" , "icon-after": "<i class=\'fa fa-euro\'></i>" ]}
                                </td>

                                <td>
                                <button type="button" class="btn btn-warning" data-card-widget="tooltip" title="Importa valori da tariffe standard" onclick="if( confirm(\'Importare i valori dalle tariffe standard?\') ){ $.post( \''.base_path().'/modules/contratti/actions.php\', { op: \'import\', idcontratto: \''.$id_record.'\', idtipointervento: \''.$rs[$i]['idtipointervento'].'\' }, function(data){ location.href=\''.base_path().'/editor.php?id_module='.$id_module.'&id_record='.$id_record.'\'; } ); }">
                                    <i class="fa fa-download"></i>
                                </button>
                                </td>

                            </tr>';
    }
    echo '
                        </table>';
}
echo '

					</div>
				</div>
			</div>
		</div>
	</div>
</form>

<!-- RIGHE -->
<div class="card card-primary">
    <div class="card-header">
        <h3 class="card-title">'.tr('Righe').'</h3>
    </div>

    <div class="card-body">';

if (!$block_edit) {
    // Form di inserimento riga documento
    echo '
        <form id="link_form" action="" method="post">
            <input type="hidden" name="op" value="add_articolo">
            <input type="hidden" name="backto" value="record-edit">

            <div class="row">
                <div class="col-md-3">
                    {[ "type": "text", "label": "'.tr('Aggiungi un articolo tramite barcode').'", "name": "barcode", "extra": "autocomplete=\"off\"", "icon-before": "<i class=\"fa fa-barcode\"></i>", "required": 0 ]}
                </div>

                <div class="col-md-4">
                    {[ "type": "select", "label": "'.tr('Articolo').'", "name": "id_articolo", "value": "", "ajax-source": "articoli", "select-options": {"permetti_movimento_a_zero": 1}, "icon-after": "add|'.Module::where('name', 'Articoli')->first()->id.'" ]}
                </div>

                <div class="col-md-3" style="margin-top: 25px">
                    <button title="'.tr('Aggiungi articolo alla vendita').'" class="btn btn-primary tip" type="button" onclick="salvaArticolo()">
                        <i class="fa fa-plus"></i> '.tr('Aggiungi').'
                    </button>
                    
                    <a class="btn btn-primary" onclick="gestioneRiga(this)" data-title="'.tr('Aggiungi riga').'">
                        <i class="fa fa-plus"></i> '.tr('Riga').'
                    </a>
                    
                    <div class="btn-group tip" data-card-widget="tooltip">
                        <button type="button" class="btn btn-primary dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
                            <i class="fa fa-list"></i> '.tr('Altro').'
                            <span class="caret"></span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-right">

                            <a class="dropdown-item" style="cursor:pointer" onclick="gestioneDescrizione(this)" data-title="'.tr('Aggiungi descrizione').'">
                                <i class="fa fa-plus"></i> '.tr('Descrizione').'
                            </a>

                            <a class="dropdown-item" style="cursor:pointer" onclick="gestioneSconto(this)" data-title="'.tr('Aggiungi sconto/maggiorazione').'">
                                <i class="fa fa-plus"></i> '.tr('Sconto/maggiorazione').'
                            </a>
                        </ul>
                    </div>
                </div>

                <div class="col-md-2">
                    {[ "type": "select", "label": "'.tr('Ordinamento').'", "name": "ordinamento", "class": "no-search", "value": "'.($_SESSION['module_'.$id_module]['order_row_desc'] ? 'desc' : 'manuale').'", "values": "list=\"desc\": \"'.tr('Ultima riga inserita').'\", \"manuale\": \"'.tr('Manuale').'\"" ]}
                </div>
            </div>
        </form>';
}

echo '
        <div class="clearfix"></div>
        <br>

        <div class="row">
			<div class="col-md-12" id="righe"></div>
		</div>
    </div>
</div>

{( "name": "filelist_and_upload", "id_module": "$id_module$", "id_record": "$id_record$" )}

{( "name": "log_email", "id_module": "$id_module$", "id_record": "$id_record$" )}

<script type="text/javascript">
function gestioneSconto(button) {
    gestioneRiga(button, "is_sconto=1");
}

function gestioneDescrizione(button) {
    gestioneRiga(button, "is_descrizione=1");
}

async function gestioneRiga(button, options) {
    // Salvataggio via AJAX
    await salvaForm("#edit-form", {}, button);

    // Lettura titolo e chiusura tooltip
    let title = $(button).attr("data-title");

    // Apertura modal
    options = options ? options : "is_riga=1";
    openModal(title, "'.$structure->fileurl('row-add.php').'?id_module='.$id_module.'&id_record='.$id_record.'&" + options);
}

/**
 * Funzione dedicata al caricamento dinamico via AJAX delle righe del documento.
 */
function caricaRighe(id_riga) {
    let container = $("#righe");

    localLoading(container, true);
    return $.get("'.$structure->fileurl('row-list.php').'?id_module='.$id_module.'&id_record='.$id_record.'", function(data) {
        container.html(data);
        localLoading(container, false);
        if (id_riga != null) {
            $("tr[data-id="+ id_riga +"]").effect("highlight",1000);
        }
    });
}

$(document).ready(function() {
    caricaRighe(null);
    
    $("#data_accettazione").on("dp.change", function() {
        if($(this).val()){
            $("#data_rifiuto").attr("disabled", true);
        }else{
            $("#data_rifiuto").attr("disabled", false);
        }
    });

    $("#data_rifiuto").on("dp.change", function() {
        if($(this).val()){
            $("#data_accettazione").attr("disabled", true);
        }else{
            $("#data_accettazione").attr("disabled", false);
        }
    });

    $("#data_accettazione").trigger("dp.change");
    $("#data_rifiuto").trigger("dp.change");

    $("#id_articolo").on("change", function(e) {
        if ($(this).val()) {
            var data = $(this).selectData();

            if (data.barcode) {
                $("#barcode").val(data.barcode);
            } else {
                $("#barcode").val("");
            }
        }

        e.preventDefault();

        setTimeout(function(){
            $("#barcode").focus();
        }, 100);
    });

    $("#barcode").focus();

    caricaRighe(null);
    content_was_modified = false;
});

$("#idanagrafica_c").change(function() {
    updateSelectOption("idanagrafica", $(this).val());
    session_set("superselect,idanagrafica", $(this).val(), 0);

    $("#idsede_destinazione").selectReset();
    $("#matricolaimpianto").selectReset();
    $("#idpagamento").selectReset();

    let data = $(this).selectData();
    if (data) {
        // Impostazione del tipo di pagamento da anagrafica
        if (data.id_pagamento) {
            input("idpagamento").getElement()
                .selectSetNew(data.id_pagamento, data.desc_pagamento);
        }
    }
});

$("#codice_cig, #codice_cup").bind("keyup change", function(e) {

    if ($("#codice_cig").val() == "" && $("#codice_cup").val() == "" ){
        $("#id_documento_fe").prop("required", false);
    }else{
        $("#id_documento_fe").prop("required", true);
    }
});


async function salvaArticolo() {
    // Salvataggio via AJAX
    await salvaForm("#edit-form");
    
    $("#link_form").ajaxSubmit({
        url: globals.rootdir + "/actions.php",
        data: {
            id_module: globals.id_module,
            id_record: globals.id_record,
            ajax: true,
        },
        type: "post",
        beforeSubmit: function(arr, $form, options) {
            return $form.parsley().validate();
        },
        success: function(response){
            renderMessages();
            if(response.length > 0){
                response = JSON.parse(response);
                swal({
                    type: "error",
                    title: "'.tr('Errore').'",
                    text: response.error,
                });
            }

            $("#barcode").val("");
            $("#id_articolo").selectReset();
            content_was_modified = false;
            caricaRighe(null);
        }
    });
}

$("#link_form").bind("keypress", function(e) {
    if (e.keyCode == 13) {
        e.preventDefault();
        salvaArticolo();
        return false;
    }
});
</script>';

// Collegamenti diretti
// Fatture o interventi collegati a questo contratto
if (!empty($elementi)) {
    echo '
<div class="card card-warning collapsable collapsed-card">
    <div class="card-header with-border">
        <h3 class="card-title"><i class="fa fa-warning"></i> '.tr('Documenti collegati: _NUM_', [
        '_NUM_' => count($elementi),
    ]).'</h3>
        <div class="card-tools pull-right">
            <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fa fa-plus"></i></button>
        </div>
    </div>
    <div class="card-body">
        <ul>';

    // Elenco attività o contratti collegati
    foreach ($elementi as $elemento) {
        $descrizione = tr('_DOC_ num. _NUM_ del _DATE_', [
            '_DOC_' => $elemento['tipo_documento'],
            '_NUM_' => !empty($elemento['numero_esterno']) ? $elemento['numero_esterno'] : $elemento['numero'],
            '_DATE_' => Translator::dateToLocale($elemento['data']),
        ]);

        echo '
            <li>'.Modules::link($elemento['modulo'], $elemento['id'], $descrizione).'</li>';
    }

    echo '
        </ul>
    </div>
</div>';
}

if (!empty($elementi)) {
    echo '
<div class="alert alert-danger">
    '.tr('Eliminando questo documento si potrebbero verificare problemi nelle altre sezioni del gestionale').'.
</div>';
} else {
    ?>

<a class="btn btn-danger ask" data-backto="record-list">
    <i id ="elimina" class="fa fa-trash"></i> <?php echo tr('Elimina'); ?>
</a>

<?php
}

echo '
<script type="text/javascript">
$(document).ready(function() {
    $("#data_conclusione").on("dp.change", function (e) {
        let data_accettazione = $("#data_accettazione");
        data_accettazione.data("DateTimePicker").maxDate(e.date);

        if(data_accettazione.data("DateTimePicker").date() > e.date){
            data_accettazione.data("DateTimePicker").date(e.date);
        }
    });

    $("#idsede_destinazione").change(function(){
        updateSelectOption("idsede_destinazione", $(this).val());
        $("#idreferente").selectReset();
    });
});

input("ordinamento").on("change", function(){
    if (input(this).get() == "desc") {
        session_set("module_'.$id_module.',order_row_desc", 1, "").then(function () {
            caricaRighe(null);
        });
    } else {
        session_set("module_'.$id_module.',order_row_desc").then(function () {
            caricaRighe(null);
        });
    }
});

$("#id_categoria").change(function() {
    updateSelectOption("id_categoria", $(this).val());

    $("#id_sottocategoria").val(null).trigger("change");
});
</script>';
