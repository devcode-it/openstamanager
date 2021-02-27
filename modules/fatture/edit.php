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

use Modules\Anagrafiche\Anagrafica;
use Modules\Fatture\Gestori\Bollo;
use Modules\Iva\Aliquota;

include_once __DIR__.'/../../core.php';

$anagrafica_azienda = Anagrafica::find(setting('Azienda predefinita'));

$block_edit = !empty($note_accredito) || in_array($record['stato'], ['Emessa', 'Pagato', 'Parzialmente pagato']) || !$abilita_genera;

if ($dir == 'entrata') {
    $conto = 'vendite';
} else {
    $conto = 'acquisti';
}

// Informazioni sulla dichiarazione d'intento, visibili solo finchè la fattura è in bozza
if ($dir == 'entrata' && !empty($fattura->dichiarazione) && $fattura->stato->descrizione == 'Bozza') {
    $diff = $fattura->dichiarazione->massimale - $fattura->dichiarazione->totale;

    $id_iva = setting("Iva per lettere d'intento");
    $iva = Aliquota::find($id_iva);

    if (!empty($iva)) {
        if ($diff > 0) {
            echo '
        <div class="alert alert-info">
            <i class="fa fa-warning"></i> '.tr("La fattura è collegata a una dichiarazione d'intento con diponibilità di _MONEY_: per collegare una riga alla dichiarazione è sufficiente inserire come IVA _IVA_", [
                    '_MONEY_' => moneyFormat(abs($diff)),
                    '_IVA_' => '"'.$iva->descrizione.'"',
                ]).'.</b>
        </div>';
        } elseif ($diff == 0) {
            echo '
        <div class="alert alert-warning">
            <i class="fa fa-warning"></i> '.tr("La dichiarazione d'intento ha raggiunto il massimale previsto di _MONEY_: le nuove righe della fattura devono presentare IVA diversa da _IVA_", [
                    '_MONEY_' => moneyFormat(abs($fattura->dichiarazione->massimale)),
                    '_IVA_' => '"'.$iva->descrizione.'"',
                ]).'.</b>
        </div>';
        } else {
            echo '
        <div class="alert alert-danger">
            <i class="fa fa-warning"></i> '.tr("La dichiarazione d'intento ha superato il massimale previsto di _MONEY_: per rimuovere righe della fattura dalla dichiarazione è sufficiente modificare l'IVA in qualcosa di diverso da _IVA_", [
                '_MONEY_' => moneyFormat(abs($diff)),
                    '_IVA_' => '"'.$iva->descrizione.'"',
            ]).'.</b>
        </div>';
        }
    } else {
        //TODO link ad impostazioni con nuova ricerca rapida
        echo '
        <div class="alert alert-warning">
        <i class="fa fa-warning"></i> '.tr("Attenzione nessuna aliq. IVA definita per la dichiarazione d'intento. _SETTING_", [
            '_SETTING_' => Modules::link('Impostazioni', null, tr('Selezionala dalle impostazioni'), true, null, true, null, "&search=Iva per lettere d'intento"),
        ]).'
        </div>';
    }
}

// Verifica aggiuntive sulla sequenzialità dei numeri
if ($dir == 'entrata') {
    $numero_previsto = verifica_numero_fattura($fattura);
    if (!empty($numero_previsto)) {
        echo '
<div class="alert alert-warning">
    <i class="fa fa-warning"></i> '.tr("E' assente una fattura di vendita di numero _NUM_ in data precedente o corrispondente a _DATE_: si potrebbero verificare dei problemi con la numerazione corrente delle fatture", [
            '_DATE_' => dateFormat($fattura->data),
            '_NUM_' => '"'.$numero_previsto.'"',
        ]).'.</b>
</div>';
    }
}
?>
<form action="" method="post" id="edit-form">
	<input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="op" value="update">
	<input type="hidden" name="id_record" value="<?php echo $id_record; ?>">

	<!-- INTESTAZIONE -->
	<div class="panel panel-primary">
		<div class="panel-heading">
			<h3 class="panel-title"><?php echo tr('Intestazione'); ?></h3>
		</div>

		<div class="panel-body">

			<?php

                if ($dir == 'entrata') {
                    $rs2 = $dbo->fetchArray('SELECT piva, codice_fiscale, citta, indirizzo, cap, provincia, id_nazione, tipo FROM an_anagrafiche WHERE idanagrafica='.prepare($record['idanagrafica']));
                    $campi_mancanti = [];

                    //di default è un azienda e chiedo la partita iva
                    if (empty($rs2[0]['piva']) and (empty($rs2[0]['tipo']) or $rs2[0]['tipo'] == 'Azienda')) {
                        array_push($campi_mancanti, 'Partita IVA');
                    }

                    //se è un privato o un ente pubblico controllo il codice fiscale
                    if (($rs2[0]['tipo'] == 'Privato' or $rs2[0]['tipo'] == 'Ente pubblico') and empty($rs2[0]['codice_fiscale'])) {
                        array_push($campi_mancanti, 'Codice fiscale');
                    }

                    if ($rs2[0]['citta'] == '') {
                        array_push($campi_mancanti, 'Città');
                    }
                    if ($rs2[0]['indirizzo'] == '') {
                        array_push($campi_mancanti, 'Indirizzo');
                    }
                    if ($rs2[0]['cap'] == '') {
                        array_push($campi_mancanti, 'C.A.P.');
                    }
                    if (empty($rs2[0]['id_nazione'])) {
                        array_push($campi_mancanti, 'Nazione');
                    }

                    if (sizeof($campi_mancanti) > 0) {
                        echo "<div class='alert alert-warning'><i class='fa fa-warning'></i> Prima di procedere alla stampa completa i seguenti campi dell'anagrafica Cliente: <b>".implode(', ', $campi_mancanti).'</b><br/>
						'.Modules::link('Anagrafiche', $record['idanagrafica'], tr('Vai alla scheda anagrafica'), null).'</div>';
                    }
                }
            ?>

			<div class="row">
                <?php
                if ($dir == 'uscita') {
                    echo '
                <div class="col-md-2">
                    {[ "type": "text", "label": "'.tr('Numero fattura/protocollo').'", "required": 1, "name": "numero","class": "text-center alphanumeric-mask", "value": "$numero$" ]}
                </div>';
                    $label = tr('Numero fattura del fornitore');
                    $size = 2;
                } else {
                    $label = tr('Numero fattura');
                    $size = 4;
                }
                ?>

				<!-- id_segment -->
				{[ "type": "hidden", "label": "Segmento", "name": "id_segment", "class": "text-center", "value": "$id_segment$" ]}

				<div class="col-md-<?php echo $size; ?>">
					{[ "type": "text", "label": "<?php echo $label; ?>", "name": "numero_esterno", "class": "text-center", "value": "$numero_esterno$", "help": "<?php echo (empty($record['numero_esterno']) and $dir == 'entrata') ? tr('Il numero della fattura sarà generato automaticamente in fase di emissione.') : ''; ?>" ]}
				</div>

				<div class="col-md-2">
					{[ "type": "date", "label": "<?php echo tr('Data emissione'); ?>", "name": "data", "required": 1, "value": "$data$" ]}
				</div>

<?php

$query = 'SELECT * FROM co_statidocumento';
if (empty($record['is_fiscale'])) {
    $query .= " WHERE descrizione = 'Bozza'";

    $plugin = $dbo->fetchArray("SELECT id FROM zz_plugins WHERE name='Fatturazione Elettronica' AND idmodule_to = ".prepare($id_module));
    echo '<script>$("#link-tab_'.$plugin[0]['id'].'").addClass("disabled");</script>';
}
// Forzo il passaggio della fattura da Bozza ad Emessa per il corretto calcolo del numero.
elseif ($record['stato'] == 'Bozza') {
    $query .= " WHERE descrizione IN ('Emessa', 'Bozza')";
}

?>
				<?php if ($dir == 'entrata') {
    $readonly = '"readonly":1,';
}
    ?>

				<div class="col-md-2" <?php echo ($dir == 'entrata') ? 'hidden' : ''; ?>>
                    {[ "type": "date", "label": "<?php echo tr('Data registrazione'); ?>", <?php echo $readonly; ?> "name": "data_registrazione", "value": "$data_registrazione$", "help": "<?php echo tr('Data in cui si è effettuata la registrazione della fattura in contabilità'); ?>" ]}
				</div>

                <!-- TODO: da nascondere per le fatture di vendita in quanto questa data sarà sempre uguale alla data di emissione -->
                <div class="col-md-2" <?php echo ($is_fiscale) ? '' : 'hidden'; ?>>
                    {[ "type": "date", "class":"<?php echo (dateFormat($fattura->data_competenza) < dateFormat($fattura->data)) ? 'unblockable' : ''; ?>", "label": "<?php echo tr('Data competenza'); ?>", "name": "data_competenza", "required": 1, "value": "$data_competenza$", "min-date": "$data_registrazione$", "help": "<?php echo tr('Data nella quale considerare il movimento contabile, che può essere posticipato rispetto la data della fattura'); ?>" ]}
                </div>

					<?php
                    if ($dir == 'entrata') {
                        ?>

                <div class="col-md-2" <?php echo ($is_fiscale) ? '' : 'hidden'; ?> >
                    {[ "type": "select", "label": "<?php echo tr('Stato FE'); ?>", "name": "codice_stato_fe", "values": "query=SELECT codice as id, CONCAT_WS(' - ',codice,descrizione) as text FROM fe_stati_documento", "value": "$codice_stato_fe$", "disabled": <?php echo intval(API\Services::isEnabled() || ($record['stato'] == 'Bozza' && $abilita_genera)); ?>, "class": "unblockable", "help": "<?php echo (!empty($record['data_stato_fe'])) ? Translator::timestampToLocale($record['data_stato_fe']) : ''; ?>" ]}
                </div>

                        <?php
                    }

                echo '
                <div class="col-md-'.($is_fiscale ? 2 : 6).'">
                    <!-- TODO: Rimuovere possibilità di selezionare lo stato pagato obbligando l\'utente ad aggiungere il movimento in prima nota -->
                    {[ "type": "select", "label": "'.tr('Stato').'", "name": "idstatodocumento", "required": 1, "values": "query='.$query.'", "value": "$idstatodocumento$", "class": "'.(($record['stato'] != 'Bozza' && !$abilita_genera) ? '' : 'unblockable').'", "extra": "onchange=\"return cambiaStato()\"" ]}
                </div>
			</div>

			<div class="row">
				<div class="col-md-4">
				    '.Modules::link('Anagrafiche', $record['idanagrafica'], null, null, 'class="pull-right"');

                    if ($dir == 'entrata') {
                        ?>
						{[ "type": "select", "label": "<?php echo tr('Cliente'); ?>", "name": "idanagrafica", "required": 1, "ajax-source": "clienti", "help": "<?php echo tr("In caso di autofattura indicare l'azienda: ").stripslashes($database->fetchOne('SELECT ragione_sociale FROM an_anagrafiche WHERE idanagrafica = '.prepare(setting('Azienda predefinita')))['ragione_sociale']); ?>", "value": "$idanagrafica$" ]}
					<?php
                    } else {
                        ?>
						{[ "type": "select", "label": "<?php echo tr('Fornitore'); ?>", "name": "idanagrafica", "required": 1, "ajax-source": "fornitori", "value": "$idanagrafica$" ]}
					<?php
                    }

                    echo '
                </div>';

                if ($dir == 'entrata') {
                    echo '
				<div class="col-md-4">
					{[ "type": "select", "label": "'.tr('Agente di riferimento').'", "name": "idagente", "ajax-source": "agenti", "select-options": {"idanagrafica": '.$record['idanagrafica'].'}, "value": "$idagente_fattura$" ]}
				</div>';
                }

                echo '
                <div class="col-md-4">';
                    if (!empty($record['idreferente'])) {
                        echo Plugins::link('Referenti', $record['idanagrafica'], null, null, 'class="pull-right"');
                    }
                    echo '
                    {[ "type": "select", "label": "'.tr('Referente').'", "name": "idreferente", "value": "$idreferente$", "ajax-source": "referenti", "select-options": {"idanagrafica": '.$record['idanagrafica'].'} ]}
                </div>';

                // Conteggio numero articoli fatture
                $articolo = $dbo->fetchArray('SELECT mg_articoli.id FROM ((mg_articoli INNER JOIN co_righe_documenti ON mg_articoli.id=co_righe_documenti.idarticolo) INNER JOIN co_documenti ON co_documenti.id=co_righe_documenti.iddocumento) WHERE co_documenti.id='.prepare($id_record));
                    $id_modulo_anagrafiche = Modules::get('Anagrafiche')['id'];
                    $id_plugin_sedi = Plugins::get('Sedi')['id'];
                    if ($dir == 'entrata') {
                        echo '
                <div class="col-md-3">
                    {[ "type": "select", "label": "'.tr('Partenza merce').'", "name": "idsede_partenza", "ajax-source": "sedi_azienda", "value": "$idsede_partenza$", "readonly": "'.(sizeof($articolo) ? 1 : 0).'", "help": "'.tr("Sedi di partenza dell'azienda").'" ]}
                </div>

                <div class="col-md-3">
                    {[ "type": "select", "label": "'.tr('Destinazione merce').'", "name": "idsede_destinazione", "ajax-source": "sedi", "select-options": {"idanagrafica": '.$record['idanagrafica'].'}, "value": "$idsede_destinazione$", "help": "'.tr('Sedi del destinatario').'", "icon-after": "add|'.$id_modulo_anagrafiche.'|id_plugin='.$id_plugin_sedi.'&id_parent='.$record['idanagrafica'].'||'.(intval($block_edit) ? 'disabled' : '').'" ]}
                </div>';
                    } else {
                        echo '
                <div class="col-md-3">
                    {[ "type": "select", "label": "'.tr('Partenza merce').'", "name": "idsede_partenza", "ajax-source": "sedi", "select-options": {"idanagrafica": '.$record['idanagrafica'].'}, "value": "$idsede_partenza$", "help": "'.tr('Sedi del mittente').'", "icon-after": "add|'.$id_modulo_anagrafiche.'|id_plugin='.$id_plugin_sedi.'&id_parent='.$record['idanagrafica'].'||'.(intval($block_edit) ? 'disabled' : '').'" ]}
                </div>

                <div class="col-md-3">
                    {[ "type": "select", "label": "'.tr('Destinazione merce').'", "name": "idsede_destinazione", "ajax-source": "sedi_azienda", "value": "$idsede_destinazione$", "help": "'.tr("Sedi di arrivo dell'azienda").'" ]}
                </div>';
                    }
                ?>
			</div>
			<hr>


			<div class="row">
				<div class="col-md-3">
					<!-- Nella realtà la fattura accompagnatoria non può esistere per la fatturazione elettronica, in quanto la risposta dal SDI potrebbe non essere immediata e le merci in viaggio. Dunque si può emettere una documento di viaggio valido per le merci ed eventualmente una fattura pro-forma per l'incasso della stessa, emettendo infine la fattura elettronica differita. -->

					{[ "type": "select", "label": "<?php echo tr('Tipo documento'); ?>", "name": "idtipodocumento", "required": 1, "values": "query=SELECT id, CONCAT_WS(\" - \",codice_tipo_documento_fe, descrizione) AS descrizione FROM co_tipidocumento WHERE dir='<?php echo $dir; ?>' AND (reversed = 0 OR id = <?php echo $record['idtipodocumento']; ?>)", "value": "$idtipodocumento$", "readonly": <?php echo intval($record['stato'] != 'Bozza' && $record['stato'] != 'Annullata'); ?>, "help": "<?php echo ($database->fetchOne('SELECT tipo FROM an_anagrafiche WHERE idanagrafica = '.prepare($record['idanagrafica']))['tipo'] == 'Ente pubblico') ? 'FPA12 - fattura verso PA (Ente pubblico)' : 'FPR12 - fattura verso soggetti privati (Azienda o Privato)'; ?>" ]}
				</div>

				<div class="col-md-3">
					{[ "type": "select", "label": "<?php echo tr('Pagamento'); ?>", "name": "idpagamento", "required": 1, "ajax-source": "pagamenti", "value": "$idpagamento$", "extra": "onchange=\"$('#id_banca_azienda').val($(this).selectData().id_banca_<?php echo $conto; ?>).change(); \" " ]}
				</div>

				<div class="col-md-3">
					{[ "type": "select", "label": "<?php echo tr('Banca azienda'); ?>", "name": "id_banca_azienda", "ajax-source": "banche", "select-options": <?php echo json_encode(['id_anagrafica' => $anagrafica_azienda->id]); ?>, "value": "$id_banca_azienda$", "icon-after": "add|<?php echo Modules::get('Banche')['id']; ?>|id_anagrafica=<?php echo $anagrafica_azienda->id; ?>", "extra": " <?php echo (intval($block_edit)) ? 'disabled' : ''; ?> " ]}
				</div>

                <?php
                if ($record['stato'] != 'Bozza' && $record['stato'] != 'Annullata') {
                    $scadenze = $fattura->scadenze;

                    $ricalcola = true;
                    foreach ($scadenze as $scadenza) {
                        $ricalcola = empty(floatval($scadenza->pagato)) && $ricalcola;
                    }

                    echo '
                <div class="col-md-3">
                    <p class="pull-left"><strong>'.tr('Scadenze').'</strong></p>

                    <div class="btn-group pull-right">
                        '.Modules::link('Scadenzario', $scadenze[0]['id'], tr('<i class="fa fa-edit tip" title="'.tr('Modifica scadenze').'"></i>'), '', 'class="btn btn-xs btn-primary"');

                    // Ricalcola scadenze disponibile solo per fatture di acquisto
                    if ($fattura->isFE() && $ricalcola && $module['name'] == 'Fatture di acquisto') {
                        echo '
                    <button type="button" class="btn btn-info btn-xs pull-right tip" title="'.tr('Ricalcola le scadenze').'. '.tr('Per ricalcolare correttamente le scadenze, imposta la fattura di acquisto nello stato \'\'Bozza\'\' e correggi il documento come desiderato, poi re-imposta lo stato \'\'Emessa\'\' e utilizza questa funzione').'." id="ricalcola_scadenze">
                        <i class="fa fa-calculator" aria-hidden="true"></i>
                    </button>';
                    }
                    echo '
                    </div>
                    <div class="clearfix"></div>';

                    foreach ($scadenze as $scadenza) {
                        $pagamento_iniziato = !empty(floatval($scadenza->pagato)) || $scadenza->da_pagare == 0;

                        echo '
                    <p>'.dateFormat($scadenza['scadenza']).': ';

                        if ($pagamento_iniziato) {
                            echo '
                        <strike>';
                        }

                        echo (empty($scadenza->da_pagare) ? '<i class="fa fa-exclamation-triangle"></i>' : '').moneyFormat($scadenza->da_pagare);

                        if ($pagamento_iniziato) {
                            echo '
                        </strike>';
                        }

                        if ($pagamento_iniziato && $scadenza->pagato != $scadenza->da_pagare) {
                            echo ' ('.moneyFormat($scadenza->da_pagare - $scadenza->pagato).')';
                        }

                        echo '
                    </p>';
                    }

                    echo '
                </div>';
                }
                ?>
			</div>

            <div class="row">
				<div class="col-md-3">
					{[ "type": "checkbox", "label": "<?php echo tr('Split payment'); ?>", "name": "split_payment", "value": "$split_payment$", "help": "<?php echo tr('Abilita lo split payment per questo documento. Le aliquote iva con natura N6 (reverse charge) non saranno disponibili.'); ?>", "placeholder": "<?php echo tr('Split payment'); ?>" ]}
				</div>

				<?php
                // TODO: Fattura per conto del fornitore (es. cooperative agricole che emettono la fattura per conto dei propri soci produttori agricoli conferenti)
                if ($dir == 'entrata') {
                    ?>
					<div class="col-md-3">
						{[ "type": "checkbox", "label": "<?php echo tr('Fattura per conto terzi'); ?>", "name": "is_fattura_conto_terzi", "value": "$is_fattura_conto_terzi$", "help": "<?php echo tr('Nell\'xml della FE imposta il fornitore ('.stripslashes($database->fetchOne('SELECT ragione_sociale FROM an_anagrafiche WHERE idanagrafica = '.prepare(setting('Azienda predefinita')))['ragione_sociale']).') come cessionario e il cliente come cedente/prestatore.'); ?>", "placeholder": "<?php echo tr('Fattura per conto terzi'); ?>" ]}
					</div>

				<?php
                }
                ?>

                <div class="col-md-3">
                    {[ "type": "select", "label": "<?php echo tr('Ritenuta contributi'); ?>", "name": "id_ritenuta_contributi", "value": "$id_ritenuta_contributi$", "values": "query=SELECT *, CONCAT(descrizione,(IF(percentuale>0, CONCAT(\" - \", percentuale, \"% sul \", percentuale_imponibile, \"% imponibile\"), \"\"))) AS descrizione FROM co_ritenuta_contributi", "help": "<?php echo tr('Ritenuta contributi da applicare alle righe della fattura.'); ?>"  ]}
                </div>

                <?php
                if ($dir == 'uscita') {
                    echo '
                    <div class="col-md-3">
                        {[ "type": "checkbox", "label": "'.tr('Ritenuta pagata dal fornitore').'", "name": "is_ritenuta_pagata", "value": "$is_ritenuta_pagata$" ]}
                    </div>';
                }
                if ($dir == 'entrata') {
                    echo '
                    <div class="col-md-3">';

                    if (!empty($record['id_dichiarazione_intento'])) {
                        echo Plugins::link("Dichiarazioni d'Intento", $record['idanagrafica'], null, null, 'class="pull-right"');
                    }

                    echo '
                        {[ "type": "select", "label": "'.tr("Dichiarazione d'intento").'", "name": "id_dichiarazione_intento", "ajax-source": "dichiarazioni_intento", "select-options": {"idanagrafica": '.$record['idanagrafica'].', "data": "'.$record['data'].'"},"value": "$id_dichiarazione_intento$" ]}
                    </div>';
                }
            echo '
            </div>';

        if ($dir == 'entrata') {
            echo '
            <div class="row">
                <div class="col-md-3">
                    {[ "type": "checkbox", "label": "'.tr('Marca da bollo automatica').'", "name": "bollo_automatico", "value": "'.intval(!isset($record['bollo'])).'", "help": "'.tr("Seleziona per impostare automaticamente l'importo della marca da bollo").'. '.tr('Applicata solo se il totale della fattura è maggiore di _MONEY_', [
                            '_MONEY_' => moneyFormat(setting("Soglia minima per l'applicazione della marca da bollo")),
                        ]).'.", "placeholder": "'.tr('Bollo automatico').'" ]}
                </div>

                <div class="col-md-3 bollo">
                    {[ "type": "checkbox", "label": "'.tr('Addebita marca da bollo').'", "name": "addebita_bollo", "value": "$addebita_bollo$" ]}
                </div>

                <div class="col-md-3 bollo">
                    {[ "type": "number", "label": "'.tr('Importo marca da bollo').'", "name": "bollo", "value": "$bollo$"]}
                </div>

                <div class="col-md-3">
                    {[ "type": "number", "label": "'.tr('Sconto finale').'", "name": "sconto_finale", "value": "'.($fattura->sconto_finale_percentuale ?: $fattura->sconto_finale).'", "icon-after": "choice|untprc|'.(empty($fattura->sconto_finale) ? 'PRC' : 'UNT').'", "help": "'.tr('Sconto finale in fattura, utilizzabile per applicare sconti sul Netto a pagare del documento e le relative scadenze').'. '.tr('Per utilizzarlo in relazione a una riga della Fattura Elettronica, inserire il testo di descrizione in \'\'Attributi avanzati\'\' -> \'\'Altri Dati Gestionali\'\' -> \'\'Riferimento Testo\'\' della specifica riga').'. '.tr('Nota: lo sconto finale in fattura non influenza i movimenti contabili').'." ]}
                </div>
            </div>';

            $bollo = new Bollo($fattura);
        }
?>
			<div class="row">
				<div class="col-md-12">
					{[ "type": "textarea", "label": "<?php echo tr('Note'); ?>", "name": "note", "help": "<?php echo tr('Note visibili anche in fattura.'); ?>", "value": "$note$" ]}
				</div>
			</div>

			<div class="row">
				<div class="col-md-12">
					{[ "type": "textarea", "label": "<?php echo tr('Note interne'); ?>", "name": "note_aggiuntive", "help": "<?php echo tr('Note interne.'); ?>", "value": "$note_aggiuntive$", "class": "unblockable" ]}
				</div>
			</div>
		</div>
	</div>

<?php
if ($record['descrizione_tipo'] == 'Fattura accompagnatoria di vendita') {
    echo '
    <div class="box box-info">
        <div class="box-header with-border">
            <h3 class="box-title"><i class="fa fa-edit"></i> '.tr('Dati Fattura accompagnatoria').'</h3>
        </div>

        <div class="box-body">
            <div class="row">
                <div class="col-md-3">
                    {[ "type": "select", "label": "'.tr('Aspetto beni').'", "name": "idaspettobeni", "placeholder": "", "ajax-source": "aspetto-beni", "value": "$idaspettobeni$", "icon-after": "add|'.Modules::get('Aspetto beni')['id'].'||'.(($record['stato'] != 'Bozza') ? 'disabled' : '').'" ]}
                </div>

                <div class="col-md-3">
                    {[ "type": "select", "label": "'.tr('Causale trasporto').'", "name": "idcausalet", "placeholder": "", "ajax-source": "causali", "value": "$idcausalet$", "icon-after": "add|'.Modules::get('Causali')['id'].'||'.(($record['stato'] != 'Bozza') ? 'disabled' : '').'" ]}
                </div>

                <div class="col-md-3">
                    {[ "type": "select", "label": "'.tr('Porto').'", "name": "idporto", "placeholder": "", "values": "query=SELECT id, descrizione FROM dt_porto ORDER BY descrizione ASC", "value": "$idporto$" ]}
                </div>

                <div class="col-md-3">
                    {[ "type": "text", "label": "'.tr('Num. colli').'", "name": "n_colli", "value": "$n_colli$" ]}
                </div>
            </div>

            <div class="row">
                <div class="col-md-3">
                    {[ "type": "select", "label": "'.tr('Tipo di spedizione').'", "name": "idspedizione", "values": "query=SELECT id, descrizione FROM dt_spedizione ORDER BY descrizione ASC", "value": "$idspedizione$" ]}
                </div>

                <div class="col-md-3">
                    {[ "type": "select", "label": "'.tr('Vettore').'", "name": "idvettore", "ajax-source": "vettori", "value": "$idvettore$", "icon-after": "add|'.Modules::get('Anagrafiche')['id'].'|tipoanagrafica=Vettore|'.((($record['idspedizione'] != 3) and ($record['stato'] == 'Bozza')) ? '' : 'disabled').'", "disabled": '.intval($record['idspedizione'] == 3).', "required": '.intval($record['idspedizione'] != 3).' ]}
                </div>

                <script>
                    $("#idspedizione").change(function() {
                        if ($(this).val() == 3) {
                            $("#idvettore").attr("required", false);
                            $("#idvettore").attr("disabled", true);
                            $("label[for=idvettore]").text("'.tr('Vettore').'");
							$("#idvettore").selectReset(" '.tr("Seleziona un'opzione").'");
							$("#idvettore").next().next().find("button.bound:nth-child(1)").prop("disabled", true);
                        }else{
                            $("#idvettore").attr("required", true);
                            $("#idvettore").attr("disabled", false);
                            $("label[for=idvettore]").text("'.tr('Vettore').'*");
							$("#idvettore").next().next().find("button.bound:nth-child(1)").prop("disabled", false);
                        }
                    });

					$("#idcausalet").change(function() {
                        if ($(this).val() == 3) {
                            $("#tipo_resa").attr("disabled", false);
                        }else{
							$("#tipo_resa").attr("disabled", true);
                        }
                    });
                </script>';

    $tipo_resa = [
        [
            'id' => 'EXW',
            'text' => 'EXW',
        ],
        [
            'id' => 'FCA',
            'text' => 'FCA',
        ],
        [
            'id' => 'FAS',
            'text' => 'FAS',
        ],
        [
            'id' => 'FOB',
            'text' => 'FOB',
        ],
        [
            'id' => 'CFR',
            'text' => 'CFR',
        ],
        [
            'id' => 'CIF',
            'text' => 'CIF',
        ],
        [
            'id' => 'CPT',
            'text' => 'CPT',
        ],
        [
            'id' => 'CIP',
            'text' => 'CIP',
        ],
        [
            'id' => 'DAF',
            'text' => 'DAF',
        ],
        [
            'id' => 'DES',
            'text' => 'DES',
        ],
        [
            'id' => 'DEQ',
            'text' => 'DEQ',
        ],
        [
            'id' => 'DDU',
            'text' => 'DDU',
        ],
        [
            'id' => 'DDP',
            'text' => 'DDP',
        ],
    ];

    echo '
                <div class="col-md-3">
                    {[ "type": "select", "label": "'.tr('Tipo Resa').'", "name": "tipo_resa", "value": "$tipo_resa$", "values": '.json_encode($tipo_resa).', "readonly": '.intval($record['causale_desc'] != 'Reso').' ]}
                </div>
            </div>
        </div>';

    echo '
        <div class="row">
            <div class="col-md-3">
                {[ "type": "number", "label": "'.tr('Peso').'", "name": "peso", "value": "$peso$", "readonly": "'.intval(empty($record['peso_manuale'])).'", "help": "'.tr('Il valore del campo Peso viene calcolato in automatico sulla base degli articoli inseriti nel documento, a meno dell\'impostazione di un valore manuale in questo punto').'" ]}
                <input type="hidden" id="peso_calcolato" name="peso_calcolato" value="'.$fattura->peso_calcolato.'">
            </div>

            <div class="col-md-3">
                {[ "type": "checkbox", "label": "'.tr('Modifica peso').'", "name": "peso_manuale", "value":"$peso_manuale$", "help": "'.tr('Seleziona per modificare manualmente il campo Peso').'", "placeholder": "'.tr('Modifica peso').'" ]}
            </div>

            <div class="col-md-3">
                {[ "type": "number", "label": "'.tr('Volume').'", "name": "volume", "value": "$volume$", "readonly": "'.intval(empty($record['volume_manuale'])).'", "help": "'.tr('Il valore del campo Volume viene calcolato in automatico sulla base degli articoli inseriti nel documento, a meno dell\'impostazione di un valore manuale in questo punto').'" ]}
                <input type="hidden" id="volume_calcolato" name="volume_calcolato" value="'.$fattura->volume_calcolato.'">
            </div>

            <div class="col-md-3">
                {[ "type": "checkbox", "label": "'.tr('Modifica volume').'", "name": "volume_manuale", "value":"$volume_manuale$", "help": "'.tr('Seleziona per modificare manualmente il campo Volume').'", "placeholder": "'.tr('Modifica volume').'" ]}
            </div>
        </div>
    </div>';
}

echo '
</form>

<!-- RIGHE -->
<div class="panel panel-primary">
	<div class="panel-heading">
		<h3 class="panel-title">'.tr('Righe').'</h3>
	</div>

	<div class="panel-body">
		<div class="row">
			<div class="col-md-12">
				<div class="pull-left">';

if (!$block_edit) {
    if (empty($record['ref_documento'])) {
        if ($dir == 'entrata') {
            // Lettura interventi non rifiutati, non fatturati e non collegati a preventivi o contratti
            $int_query = 'SELECT COUNT(*) AS tot FROM in_interventi INNER JOIN in_statiintervento ON in_interventi.idstatointervento=in_statiintervento.idstatointervento WHERE idanagrafica='.prepare($record['idanagrafica']).' AND in_statiintervento.is_completato=1 AND in_statiintervento.is_fatturabile=1 AND in_interventi.id NOT IN (SELECT idintervento FROM co_righe_documenti WHERE idintervento IS NOT NULL) AND in_interventi.id_preventivo IS NULL AND in_interventi.id NOT IN (SELECT idintervento FROM co_promemoria WHERE idintervento IS NOT NULL)';
            $interventi = $dbo->fetchArray($int_query)[0]['tot'];

            // Se non trovo niente provo a vedere se ce ne sono per clienti terzi
            if (empty($interventi)) {
                // Lettura interventi non rifiutati, non fatturati e non collegati a preventivi o contratti (clienti terzi)
                $int_query = 'SELECT COUNT(*) AS tot FROM in_interventi INNER JOIN in_statiintervento ON in_interventi.idstatointervento=in_statiintervento.idstatointervento WHERE idclientefinale='.prepare($record['idanagrafica']).' AND in_statiintervento.is_completato=1 AND in_statiintervento.is_fatturabile=1 AND in_interventi.id NOT IN (SELECT idintervento FROM co_righe_documenti WHERE idintervento IS NOT NULL) AND in_interventi.id_preventivo IS NULL AND in_interventi.id NOT IN (SELECT idintervento FROM co_promemoria WHERE idintervento IS NOT NULL)';
                $interventi = $dbo->fetchArray($int_query)[0]['tot'];
            }

            echo '
                    <div class="tip" data-toggle="tooltip" title="'.tr('Attività completate non collegate a preventivi o contratti e che non siano già state fatturate.').'">
                        <a class="btn btn-sm btn-primary '.(!empty($interventi) ? '' : ' disabled').'" data-href="'.base_path().'/modules/fatture/add_intervento.php?id_module='.$id_module.'&id_record='.$id_record.'" data-title="'.tr('Aggiungi attività').'">
                            <i class="fa fa-plus"></i> '.tr('Attività').'
                        </a>
                    </div>';

            // Lettura preventivi accettati, in attesa di conferma o in lavorazione
            $prev_query = 'SELECT COUNT(*) AS tot FROM co_preventivi WHERE idanagrafica='.prepare($record['idanagrafica']).' AND idstato IN(SELECT id FROM co_statipreventivi WHERE is_fatturabile = 1) AND default_revision=1 AND co_preventivi.id IN (SELECT idpreventivo FROM co_righe_preventivi WHERE co_righe_preventivi.idpreventivo = co_preventivi.id AND (qta - qta_evasa) > 0)';
            $preventivi = $dbo->fetchArray($prev_query)[0]['tot'];
            echo '
                    <div class="tip">
                        <a class="btn btn-sm btn-primary '.(!empty($preventivi) ? '' : ' disabled').'" data-href="'.base_path().'/modules/fatture/add_preventivo.php?id_module='.$id_module.'&id_record='.$id_record.'" data-title="'.tr('Aggiungi preventivo').'" data-toggle="tooltip">
                            <i class="fa fa-plus"></i> '.tr('Preventivo').'
                        </a>
                    </div>';

            // Lettura contratti accettati, in attesa di conferma o in lavorazione
            $contr_query = 'SELECT COUNT(*) AS tot FROM co_contratti WHERE idanagrafica='.prepare($record['idanagrafica']).' AND idstato IN( SELECT id FROM co_staticontratti WHERE is_fatturabile = 1) AND co_contratti.id IN (SELECT idcontratto FROM co_righe_contratti WHERE co_righe_contratti.idcontratto = co_contratti.id AND (qta - qta_evasa) > 0)';
            $contratti = $dbo->fetchArray($contr_query)[0]['tot'];
            echo '
                    <div class="tip">
                        <a class="btn btn-sm btn-primary '.(!empty($contratti) ? '' : ' disabled').'"  data-href="'.base_path().'/modules/fatture/add_contratto.php?id_module='.$id_module.'&id_record='.$id_record.'" data-title="'.tr('Aggiungi contratto').'">
                            <i class="fa fa-plus"></i> '.tr('Contratto').'
                        </a>
                    </div>';
        }

        // Lettura ddt (entrata o uscita)
        $ddt_query = 'SELECT COUNT(*) AS tot FROM dt_ddt
            LEFT JOIN `dt_causalet` ON `dt_causalet`.`id` = `dt_ddt`.`idcausalet`
            LEFT JOIN `dt_statiddt` ON `dt_statiddt`.`id` = `dt_ddt`.`idstatoddt`
            LEFT JOIN `dt_tipiddt` ON `dt_tipiddt`.`id` = `dt_ddt`.`idtipoddt`
            WHERE idanagrafica='.prepare($record['idanagrafica']).'
                AND `dt_statiddt`.`descrizione` IN (\'Evaso\', \'Parzialmente evaso\', \'Parzialmente fatturato\')
                AND `dt_tipiddt`.`dir` = '.prepare($dir).'
                AND `dt_causalet`.`is_importabile` = 1
                AND dt_ddt.id IN (SELECT idddt FROM dt_righe_ddt WHERE dt_righe_ddt.idddt = dt_ddt.id AND (qta - qta_evasa) > 0)';
        $ddt = $dbo->fetchArray($ddt_query)[0]['tot'];
        echo '
					<a class="btn btn-sm btn-primary'.(!empty($ddt) ? '' : ' disabled').'" data-href="'.base_path().'/modules/fatture/add_ddt.php?id_module='.$id_module.'&id_record='.$id_record.'" data-toggle="tooltip" data-title="'.tr('Aggiungi ddt').'">
                        <i class="fa fa-plus"></i> '.tr('Ddt').'
					</a>';

        // Lettura ordini (cliente o fornitore)
        $ordini_query = 'SELECT COUNT(*) AS tot FROM or_ordini WHERE idanagrafica='.prepare($record['idanagrafica']).' AND idstatoordine IN (SELECT id FROM or_statiordine WHERE descrizione IN(\'Accettato\', \'Evaso\', \'Parzialmente evaso\', \'Parzialmente fatturato\')) AND idtipoordine=(SELECT id FROM or_tipiordine WHERE dir='.prepare($dir).') AND or_ordini.id IN (SELECT idordine FROM or_righe_ordini WHERE or_righe_ordini.idordine = or_ordini.id AND (qta - qta_evasa) > 0)';
        $ordini = $dbo->fetchArray($ordini_query)[0]['tot'];
        echo '
						<a class="btn btn-sm btn-primary'.(!empty($ordini) ? '' : ' disabled').'" data-href="'.base_path().'/modules/fatture/add_ordine.php?id_module='.$id_module.'&id_record='.$id_record.'" data-toggle="tooltip" data-title="'.tr('Aggiungi ordine').'">
							<i class="fa fa-plus"></i> '.tr('Ordine').'
                        </a>';
    }

    // Lettura articoli
    $art_query = 'SELECT id FROM mg_articoli WHERE attivo = 1 AND deleted_at IS NULL';
    if ($dir == 'entrata') {
        $art_query .= ' AND (qta > 0 OR servizio = 1)';
    } else {
        //Gli articoli possono essere creati al volo direttamente dal modale di aggiunta articolo
        $art_query .= ' OR 1=1';
    }

    $articoli = $dbo->fetchNum($art_query);
    echo '
                    <button class="btn btn-sm btn-primary tip'.(!empty($articoli) ? '' : ' disabled').'" title="'.tr('Aggiungi articolo').'" onclick="gestioneArticolo(this)">
                        <i class="fa fa-plus"></i> '.tr('Articolo').'
                    </button>';

    echo '
                    <button type="button" class="btn btn-sm btn-primary tip" title="'.tr('Aggiungi articoli tramite barcode').'" onclick="gestioneBarcode(this)">
                        <i class="fa fa-plus"></i> '.tr('Barcode').'
                    </button>';

    echo '
                    <button type="button" class="btn btn-sm btn-primary tip" title="'.tr('Aggiungi riga').'" onclick="gestioneRiga(this)">
                        <i class="fa fa-plus"></i> '.tr('Riga').'
                    </button>';

    echo '
                    <button type="button" class="btn btn-sm btn-primary tip" title="'.tr('Aggiungi descrizione').'" onclick="gestioneDescrizione(this)">
                        <i class="fa fa-plus"></i> '.tr('Descrizione').'
                    </button>';

    echo '
                    <button type="button" class="btn btn-sm btn-primary tip" title="'.tr('Aggiungi sconto/maggiorazione').'" onclick="gestioneSconto(this)">
                        <i class="fa fa-plus"></i> '.tr('Sconto/maggiorazione').'
                    </button>';
}
?>
				</div>

				<div class="pull-right">
					<!-- Stampe -->
<?php
//stampa solo per fatture di vendita
if ($dir == 'entrata') {
    if (sizeof($campi_mancanti) > 0) {
        //echo '{( "name": "button", "type": "print", "id_module": "'.$id_module.'", "id_record": "'.$id_record.'", "class": "btn-info disabled" )}';
    } else {
        //echo '{( "name": "button", "type": "print", "id_module": "'.$id_module.'", "id_record": "'.$id_record.'" )}';
    }
}
?>
				</div>
			</div>
		</div>
		<div class="clearfix"></div>
		<br>

        <div class="row">
			<div class="col-md-12" id="righe"></div>
		</div>
	</div>
</div>

<?php

if ($dir == 'uscita' && $fattura->isFE()) {
    echo '
<div class="alert alert-info text-center" id="controlla_totali"><i class="fa fa-spinner fa-spin"></i> '.tr('Controllo sui totali del documento e della fattura elettronica in corso').'...</div>

<script>
    $(document).ready(function() {
        $.ajax({
            url: globals.rootdir + "/actions.php",
            type: "post",
            data: {
                id_module: globals.id_module,
                id_record: globals.id_record,
                op: "controlla_totali",
            },
            success: function(data){
                data = JSON.parse(data);

                var div = $("#controlla_totali");
                div.removeClass("alert-info");

                if (data.stored == null) {
                    div.addClass("alert-info").html("'.tr("Il file XML non contiene il nodo ''ImportoTotaleDocumento'': impossibile controllare corrispondenza dei totali").'.")
                } else if (data.stored == data.calculated){
                    div.addClass("alert-success").html("'.tr('Il totale del file XML corrisponde a quello calcolato dal gestionale').'.")
                } else {
                    div.addClass("alert-warning").html("'.tr('Il totale del file XML non corrisponde a quello calcolato dal gestionale: previsto _XML_, calcolato _CALC_', [
                        '_XML_' => '" + data.stored + " " + globals.currency + "',
                        '_CALC_' => '" + data.calculated + " " + globals.currency + "',
                    ]).'.")
                }

            }
        });
    })
</script>';
}
?>

{( "name": "filelist_and_upload", "id_module": "$id_module$", "id_record": "$id_record$" )}

<?php
if ($dir == 'entrata') {
    echo '
<div class="alert alert-info text-center">'.tr('Per allegare un documento alla fattura elettronica caricare il file PDF specificando come categoria "Allegati Fattura Elettronica"').'.</div>';
}

echo '
<script type="text/javascript">
	$("#idanagrafica").change(function() {
        updateSelectOption("idanagrafica", $(this).val());
        session_set("superselect,idanagrafica", $(this).val(), 0);

        $("#id_dichiarazione_intento").selectReset();';

        if ($dir == 'entrata') {
            echo '$("#idsede_destinazione").selectReset();';
        } else {
            echo '$("#idsede_partenza").selectReset();';
        }

echo '
	});

    $("#ricalcola_scadenze").click(function() {
        swal({
            title: "'.tr('Desideri ricalcolare le scadenze?').'",
            type: "warning",
            showCancelButton: true,
            confirmButtonText: "'.tr('Sì').'"
        }).then(function (result) {
            redirect(globals.rootdir + "/editor.php", {
                id_module: globals.id_module,
                id_record: globals.id_record,
                op: "ricalcola_scadenze",
                backto: "record-edit",
            }, "post")
        })
    });
</script>';

if (!empty($note_accredito)) {
    echo '
<div class="alert alert-info text-center">'.tr('Note di credito collegate').':';
    foreach ($note_accredito as $nota) {
        $text = tr('Rif. fattura _NUM_ del _DATE_', [
            '_NUM_' => $nota['numero'],
            '_DATE_' => Translator::dateToLocale($nota['data']),
        ]);

        echo '
    <br>'.Modules::link('Fatture di vendita', $nota['id'], $text, $text);
    }
    echo '
</div>';
}

?>

{( "name": "log_email", "id_module": "$id_module$", "id_record": "$id_record$" )}

<?php
// Eliminazione ddt solo se ho accesso alla sede aziendale
$field_name = ($dir == 'entrata') ? 'idsede_partenza' : 'idsede_destinazione';
if (in_array($record[$field_name], $user->sedi)) {
    ?>
    <a class="btn btn-danger ask" data-backto="record-list">
        <i class="fa fa-trash"></i> <?php echo tr('Elimina'); ?>
    </a>
<?php
}

echo '
<script>
function gestioneArticolo(button) {
    gestioneRiga(button, "is_articolo");
}

function gestioneBarcode(button) {
    gestioneRiga(button, "is_barcode");
}

function gestioneSconto(button) {
    gestioneRiga(button, "is_sconto");
}

function gestioneDescrizione(button) {
    gestioneRiga(button, "is_descrizione");
}

async function gestioneRiga(button, options) {
    // Salvataggio via AJAX
    let valid = await salvaForm(button, $("#edit-form"));

    // Apertura modal
    if (valid) {
        // Lettura titolo e chiusura tooltip
        let title = $(button).tooltipster("content");
        $(button).tooltipster("close")

        // Apertura modal
        options = options ? options : "is_riga";
        openModal(title, "'.$structure->fileurl('row-add.php').'?id_module='.$id_module.'&id_record='.$id_record.'&" + options);
    }
}

/**
 * Funzione dedicata al caricamento dinamico via AJAX delle righe del documento.
 */
function caricaRighe() {
    let container = $("#righe");

    localLoading(container, true);
    return $.get("'.$structure->fileurl('row-list.php').'?id_module='.$id_module.'&id_record='.$id_record.'", function(data) {
        container.html(data);
        localLoading(container, false);
    });
}

$(document).ready(function () {
    caricaRighe();

    $("#data_registrazione").on("dp.change", function (e) {
        let data_competenza = $("#data_competenza");
        data_competenza.data("DateTimePicker").minDate(e.date);

        if(data_competenza.data("DateTimePicker").date() < e.date){
            data_competenza.data("DateTimePicker").date(e.date);
        }
    });

    $("#data").on("dp.change", function (e) {
        let data_competenza = $("#data_competenza");
        data_competenza.data("DateTimePicker").minDate(e.date);

        if(data_competenza.data("DateTimePicker").date() < e.date){
            data_competenza.data("DateTimePicker").date(e.date);
        }
    });
});

function cambiaStato() {
    let testo = $("#idstatodocumento option:selected").text();

    if (testo === "Pagato" || testo === "Parzialmente pagato") {
        if(confirm("'.tr('Sicuro di voler impostare manualmente la fattura come pagata senza aggiungere il movimento in prima nota?').'")) {
            return true;
        } else {
            $("#idstatodocumento").selectSet('.$record['idstatodocumento'].');
        }
    }
}';
if ($dir == 'entrata') {
    echo '
    function bolloAutomatico() {
        let bollo_automatico = input("bollo_automatico");
        let addebita_bollo = input("addebita_bollo");
        let has_bollo ='.($bollo->getBollo() > 0 ? 'true' : 'false').';
        if(bollo_automatico.get()==0){
            $(".bollo").show();
            input("bollo").enable();
        } else if(!has_bollo) {
            $(".bollo").hide();
        } else {
            $(".bollo").show();
            input("bollo").disable();
            $("#bollo").val('.setting('Importo marca da bollo').');
        }
    }
    $(document).ready(function() {
        if(!$("#volume_manuale").is(":checked")){
            input("volume").set($("#volume_calcolato").val());
        }
        $("#volume_manuale").click(function() {
            $("#volume").prop("readonly", !$("#volume_manuale").is(":checked"));
            if(!$("#volume_manuale").is(":checked")){
                input("volume").set($("#volume_calcolato").val());
            }
        });

        if(!$("#peso_manuale").is(":checked")){
            input("peso").set($("#peso_calcolato").val());
        }
        $("#peso_manuale").click(function() {
            $("#peso").prop("readonly", !$("#peso_manuale").is(":checked"));
            if(!$("#peso_manuale").is(":checked")){
                input("peso").set($("#peso_calcolato").val());
            }
        });

        bolloAutomatico();
    });
    input("bollo_automatico").change(function () {
        bolloAutomatico();
    });';
}
echo '
</script>';
