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

use Carbon\Carbon;
use Models\Module;
use Modules\Fatture\Fattura;
use Modules\Pagamenti\Pagamento;
use Plugins\ImportFE\FatturaElettronica;
use Util\XML;

include_once __DIR__.'/../../core.php';

echo '
<script>
$(document).ready(function() {
    $("#save-buttons").hide();

    // Visualizza input seriali se abilita serial dell\'articolo selezionato è attivo
    let articoli = $("select[name^=articoli]");
    articoli.each(function() {
        verificaSerial($(this));
    });

    // Disabilita input seriali se flag crea serialzi è disattivato
    if (!$("#flag_crea_seriali").is(":checked")) {
        $("[id^=\'serial\']").attr("disabled", true);
    }
});
</script>';

$skip_link = $has_next && post('sequence') ? base_path().'/editor.php?id_module='.$id_module.'&id_plugin='.$id_plugin.'&id_record='.($id_record + 1).'&sequence='.get('sequence') : base_path().'/editor.php?id_module='.$id_module;

if (empty($fattura)) {
    if (!empty($error)) {
        echo '
<p>'.tr("Errore durante l'apertura della fattura elettronica _NAME_", [
            '_NAME_' => $record['name'],
        ]).'.</p>';
    } elseif (!empty($imported)) {
        echo '
<p>'.tr('La fattura elettronica _NAME_ è già stata importata in passato', [
            '_NAME_' => $record['name'],
        ]).'.</p>';
    }

    echo '
<div class="row">
    <div class="col-md-12 text-right">';

    if (!empty($imported)) {
        echo '
        <button type="button" class="btn btn-danger" onclick="cleanup()">
            <i class="fa fa-trash-o"></i> '.tr('Processa e rimuovi').'
        </button>';
    }

    echo '
        <button type="button" class="btn btn-warning" onclick="skip()">
            <i class="fa fa-ban "></i> '.tr('Salta fattura').'
        </button>
    </div>
</div>

<script>
function skip() {
    redirect("'.$skip_link.'");
}

function cleanup(){
    $.ajax({
        url: globals.rootdir + "/actions.php",
        type: "get",
        data: {
            id_module: "'.$id_module.'",
            id_plugin: "'.$id_plugin.'",
            op: "delete",
            name: "'.$record['name'].'",
        }
    });

    $.ajax({
        url: globals.rootdir + "/actions.php",
        type: "get",
        data: {
            id_module: "'.$id_module.'",
            id_plugin: "'.$id_plugin.'",
            op: "process",
            name: "'.$record['name'].'",
        }
    });

    skip();
}
</script>';

    return;
}

// Cliente
$cliente = $fattura->getAnagrafe('Cliente');

$ragione_sociale = $cliente['ragione_sociale'] ?: $cliente['cognome'].' '.$cliente['nome'];
$codice_fiscale = $cliente['codice_fiscale'];
$partita_iva = $cliente['partita_iva'];

$sede = $cliente['sede'];

$cap = $sede['cap'];
$citta = $sede['comune'];
$provincia = $sede['provincia'];

// Dati generali
$fattura_body = $fattura->getBody();
$dati_generali = $fattura_body['DatiGenerali']['DatiGeneraliDocumento'];

$tipo_documento = $database->fetchOne('SELECT CONCAT("(", `codice`, ") ", `title`) AS descrizione FROM `fe_tipi_documento` LEFT JOIN `fe_tipi_documento_lang` ON (`fe_tipi_documento_lang`.`id_record` = `fe_tipi_documento`.`codice` AND `fe_tipi_documento_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).') WHERE codice = '.prepare($dati_generali['TipoDocumento']))['descrizione'];

// Gestione per fattura elettroniche senza pagamento definito
$pagamenti = [];
if (isset($fattura_body['DatiPagamento'])) {
    $pagamenti = $fattura_body['DatiPagamento'];
    $pagamenti = isset($pagamenti[0]) ? $pagamenti : [$pagamenti];
}

$is_autofattura = false;
if (in_array($dati_generali['TipoDocumento'], ['TD16', 'TD17', 'TD18', 'TD19', 'TD20', 'TD21', 'TD28'])) {
    $is_autofattura = true;
}

// Individuazione metodo di pagamento di base
$metodi = $pagamenti[0]['DettaglioPagamento'] ?? [];
$metodi = isset($metodi[0]) ? $metodi : [$metodi];

$codice_modalita_pagamento = $metodi[0]['ModalitaPagamento'];

echo '
<form action="" method="post">
    <input type="hidden" name="filename" value="'.$record['name'].'">
    <input type="hidden" name="op" value="generate">

    <div class="row">
		<div class="col-md-3">
			<h4>
			    '.$ragione_sociale.'

			    '.(empty($anagrafica) ? '<span class="badge bg-warning">'.tr('Nuova anagrafica').'</span>' : '<small>'.Modules::link('Anagrafiche', $anagrafica->id, '', null, '').'</small>').'<br>

				<small>
					'.(!empty($codice_fiscale) ? (tr('Codice Fiscale').': '.$codice_fiscale.'<br>') : '').'
					'.(!empty($partita_iva) ? (tr('Partita IVA').': '.$partita_iva.'<br>') : '').'
					'.$cap.' '.$citta.' ('.$provincia.')<br>
				</small>
			</h4>
		</div>

		<div class="col-md-3">
			<h4>
			    '.$dati_generali['Numero'].'

				<a href="'.$structure->fileurl('view.php').'?filename='.$record['name'].'" class="btn btn-info btn-xs" target="_blank" >
					<i class="fa fa-eye"></i> '.tr('Visualizza').'
				</a>

				<br><small>
					'.$tipo_documento.'
					<br>'.Translator::dateToLocale($dati_generali['Data']).'
					<br>'.$dati_generali['Divisa'].'
				</small>
			</h4>
		</div>';

// Blocco DatiPagamento è valorizzato (opzionale)
if (!empty($pagamenti)) {
    echo '
		<div class="col-md-6">
            <h4>'.tr('Pagamento').'</h4>

            <p>'.tr('La fattura importata presenta _NUM_ rat_E_ di pagamento con le seguenti scadenze', [
        '_NUM_' => count($metodi),
        '_E_' => ((count($metodi) > 1) ? 'e' : 'a'),
    ]).':</p>
            <ol>';

    foreach ($pagamenti as $pagamento) {
        $rate = $pagamento['DettaglioPagamento'];
        $rate = isset($rate[0]) ? $rate : [$rate];

        // Scadenze di pagamento
        foreach ($rate as $rata) {
            $descrizione = !empty($rata['ModalitaPagamento']) ? $database->fetchOne('SELECT `title` FROM `fe_modalita_pagamento` LEFT JOIN `fe_modalita_pagamento_lang` ON (`fe_modalita_pagamento_lang`.`id_record`=`fe_modalita_pagamento`.`codice` AND `fe_modalita_pagamento_lang`.`id_lang`='.prepare(Models\Locale::getDefault()->id).') WHERE `codice` = '.prepare($rata['ModalitaPagamento']))['descrizione'] : '';
            $data = !empty($rata['DataScadenzaPagamento']) ? FatturaElettronica::parseDate($rata['DataScadenzaPagamento']) : '';

            echo '
				<li>
				    '.dateFormat($data).'
				    '.moneyFormat($rata['ImportoPagamento']).'
                    ('.$descrizione.')
                </li>';
        }
    }

    echo '
            </ol>
        </div>';
}

echo '
	</div>';

// Tipo del documento
$query = "SELECT `co_tipidocumento`.`id`, CONCAT('(', `codice_tipo_documento_fe`, ') ', `title`) AS descrizione FROM `co_tipidocumento` LEFT JOIN `co_tipidocumento_lang` ON (`co_tipidocumento_lang`.`id_record` = `co_tipidocumento`.`id` AND `co_tipidocumento_lang`.`id_lang` = ".prepare(Models\Locale::getDefault()->id).") WHERE `dir` = 'entrata'";
$query_tipo = $query.' AND `codice_tipo_documento_fe` = '.prepare($dati_generali['TipoDocumento']);
$numero_tipo = $database->fetchNum($query_tipo);
if (!empty($numero_tipo)) {
    $query = $query_tipo;
}

$id_tipodocumento = $database->fetchOne($query_tipo)['id'];

echo '
    <div class="row">
        <div class="col-md-3">
            {[ "type": "select", "label": "'.tr('Tipo fattura').'", "name": "id_tipo", "required": 1, "values": "query='.$query.'", "value": "'.($numero_tipo != 1 ? $id_tipodocumento : '').'" ]}
        </div>';

// Sezionale
$id_segment = $database->table('co_tipidocumento')->where('id', '=', $id_tipodocumento)->value('id_segment');

echo '
        <div class="col-md-3">
            {[ "type": "select", "label": "'.tr('Sezionale').'", "name": "id_segment", "required": 1, "ajax-source": "segmenti", "select-options": '.json_encode(['id_module' => $id_module, 'is_fiscale' => 1, 'is_sezionale' => 1, 'for_fe' => 1]).', "value": "'.$id_segment.'" ]}
        </div>';

// Data di registrazione
$data_registrazione = get('data_registrazione');
$data_registrazione = new Carbon($data_registrazione);
echo '
        <div class="col-md-3">
            {[ "type": "date", "label": "'.tr('Data di registrazione').'", "name": "data_registrazione", "required": 1, "value": "'.($data_registrazione ?: $dati_generali['Data']).'", "max-date": "-now-", "min-date": "'.$dati_generali['Data'].'" ]}
        </div>';

if (!empty($anagrafica)) {
    $query = "SELECT
            `co_documenti`.`id`,
            CONCAT('Fattura num. ', `co_documenti`.`numero_esterno`, ' del ', DATE_FORMAT(`co_documenti`.`data`, '%d/%m/%Y')) AS descrizione
        FROM `co_documenti`
            INNER JOIN `co_tipidocumento` ON `co_tipidocumento`.`id` = `co_documenti`.`idtipodocumento`
        WHERE
            `co_tipidocumento`.`dir` = 'entrata' AND
            (`co_documenti`.`data` BETWEEN NOW() - INTERVAL 1 YEAR AND NOW()) AND
            `co_documenti`.`idstatodocumento` IN (SELECT `id_record` FROM `co_statidocumento_lang` WHERE `title` != 'Bozza') AND
            `co_documenti`.`idanagrafica` = ".prepare($anagrafica->id);

    // Riferimenti ad altre fatture
    if (in_array($dati_generali['TipoDocumento'], ['TD04', 'TD05'])) {
        echo '
        <div class="col-md-3">
            {[ "type": "select", "label": "'.tr('Fattura collegata').'", "name": "ref_fattura", "required": 0, "values": "query='.$query.'" ]}
        </div>';
    } elseif ($dati_generali['TipoDocumento'] == 'TD06') {
        $query .= 'AND `co_documenti`.`id_segment` = (SELECT `zz_segments`.`id` FROM `zz_segments` LEFT JOIN `zz_segments_lang` ON (`zz_segments_lang`.`id_record` = `zz_segments`.`id` AND `zz_segments_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).") WHERE `title` = 'Fatture pro-forma' AND `id_module` = ".prepare($id_module).')';

        echo '
        <div class="col-md-3">
            {[ "type": "select", "label": "'.tr('Collega a fattura pro-forma').'", "name": "ref_fattura", "values": "query='.$query.'" ]}
        </div>';
    } elseif ($is_autofattura) {
        $query = "SELECT
            `co_documenti`.`id`,
            CONCAT('Fattura num. ', `co_documenti`.`numero_esterno`, ' del ', DATE_FORMAT(`co_documenti`.`data`, '%d/%m/%Y')) AS descrizione
        FROM `co_documenti`
            INNER JOIN `co_tipidocumento` ON `co_tipidocumento`.`id` = `co_documenti`.`idtipodocumento`
        WHERE
            `co_tipidocumento`.`dir` = 'entrata' AND
            `co_tipidocumento`.`codice_tipo_documento_fe` IN('TD16', 'TD17', 'TD18', 'TD19', 'TD20', 'TD21', 'TD28') AND
            (`co_documenti`.`data` BETWEEN NOW() - INTERVAL 1 YEAR AND NOW()) AND
            `co_documenti`.`idstatodocumento` IN (SELECT `id_record` FROM `co_statidocumento_lang` WHERE `title` != 'Bozza') AND
            `co_documenti`.`idanagrafica` = ".prepare($anagrafica->id);

        $autofattura_collegata = Fattura::where('progressivo_invio', '=', $fattura->getHeader()['DatiTrasmissione']['ProgressivoInvio'])->first();

        echo '
        <div class="col-md-3">
            {[ "type": "select", "label": "'.tr('Autofattura collegata').'", "name": "autofattura", "values": "query='.$query.'", "value": "'.$autofattura_collegata->id.'" ]}
        </div>';
    }
}

echo '
    </div>';

// Pagamento
$pagamento = Pagamento::where('codice_modalita_pagamento_fe', $codice_modalita_pagamento)->where('predefined', '1')->first();
echo '
    <div class="row" >
		<div class="col-md-3">
		    <button type="button" class="btn btn-info btn-xs pull-right" onclick="updateSelectOption(\'codice_modalita_pagamento_fe\', \'\')">
		        <i class="fa fa-refresh"></i> '.tr('Visualizza tutte le modalità').'
            </button>

            {[ "type": "select", "label": "'.tr('Pagamento').'", "name": "pagamento", "required": 1, "ajax-source": "pagamenti", "select-options": '.json_encode(['codice_modalita_pagamento_fe' => $codice_modalita_pagamento]).', "value": "'.$pagamento->id.'" ]}
        </div>';

// Movimentazioni
echo '
        <div class="col-md-3">
            {[ "type": "checkbox", "label": "'.tr('Movimenta gli articoli').'", "name": "movimentazione", "value": "'.setting('Movimenta magazzino da fatture di acquisto').'" ]}
        </div>

        <div class="col-md-3">
            {[ "type": "checkbox", "label": "'.tr('Creazione automatica articoli').'", "name": "flag_crea_articoli", "value": 0, "help": "'.tr('Nel caso di righe con almeno un nodo \'CodiceArticolo\', il gestionale procede alla creazione dell\'articolo se la riga non risulta assegnata manualmente').'." ]}
        </div>
        
        <div class="col-md-3">
            {[ "type": "checkbox", "label": "'.tr('Creazione seriali').'", "name": "flag_crea_seriali", "value": "'.setting('Creazione seriali in import FE').'", "help": "'.tr('Nel caso di righe contenenti serial number, il gestionale procede alla loro registrazione. Controllare che l\'XML della fattura di acquisto contenga il nodo \'CodiceTipo\' valorizzato con \'serial\' o \'Serial\' ').'." ]}
        </div>';

$ritenuta = $dati_generali['DatiRitenuta'];

if (!empty($ritenuta)) {
    echo '
            <div class="col-md-3">
                {[ "type": "checkbox", "label": "'.tr('Ritenuta pagata dal cliente').'", "name": "is_ritenuta_pagata", "value": 0, "help": "'.tr('Attivare se la ritenuta è stata pagata dal cliente').'" ]}
            </div>';
}
echo '
    </div>';

// Righe
if (setting('Aggiorna info di acquisto') == 'Non aggiornare') {
    $update_info = 'update_not';
} elseif (setting('Aggiorna info di acquisto') == 'Aggiorna prezzo di listino') {
    $update_info = 'update_price';
} else {
    $update_info = 'update_all';
}

$righe = $fattura->getRighe();
if (!empty($righe)) {
    echo '
    <h4>
        '.tr('Righe').'
        <button type="button" class="btn btn-info btn-sm pull-right" onclick="copia()" style="margin-left:5px;"><i class="fa fa-copy"></i> '.tr('Copia dati contabili dalla prima riga valorizzata').'</button>
        <button type="button" class="btn btn-info btn-sm pull-right" onclick="copy_rif()"><i class="fa fa-copy"></i> '.tr('Copia il riferimento vendita dalla prima riga valorizzata').'</button>
        <div class="clearfix"></div>
    </h4>

    <div class="table-responsive">
        <table class="table table-striped table-hover table-sm table-bordered">
            <thead>
                <tr>
                    <th>'.tr('Descrizione').'</th>
                    <th class="text-center" width="10%">'.tr('Quantità').'</th>
                    <th class="text-center" width="10%">'.tr('Prezzo unitario').'</th>
                    <th class="text-center" width="10%">'.tr('Aliquota').'</th>
                </tr>
            </thead>

            <tbody>';

    // Dati ordini
    $DatiOrdini = XML::forceArray($fattura->getBody()['DatiGenerali']['DatiOrdineAcquisto']);
    $DatiDDT = XML::forceArray($fattura->getBody()['DatiGenerali']['DatiDDT']);

    // Riorganizzazione dati ordini per numero di riga
    $dati_ordini = [];
    foreach ($DatiOrdini as $dato) {
        foreach ($dato['RiferimentoNumeroLinea'] as $dati => $linea) {
            $dati_ordini[(int) $linea] = [
                'numero' => $dato['IdDocumento'],
                'data' => (new Carbon($dato['Data']))->format('d/m/Y'),
            ];
        }
    }

    // Riorganizzazione dati ordini per numero di riga
    $dati_ddt = [];
    foreach ($DatiDDT as $dato) {
        foreach ($dato['RiferimentoNumeroLinea'] as $dati => $linea) {
            $dati_ddt[(int) $linea] = [
                'numero' => $dato['NumeroDDT'],
                'data' => (new Carbon($dato['DataDDT']))->format('d/m/Y'),
            ];
        }
    }

    foreach ($righe as $key => $riga) {
        $query = "SELECT `co_iva`.`id`, IF(`codice` IS NULL, `title`, CONCAT(`codice`, ' - ', `title`)) AS descrizione FROM `co_iva` LEFT JOIN `co_iva_lang` ON (`co_iva`.`id` = `co_iva_lang`.`id_record` AND `co_iva_lang`.`id_lang` = ".prepare(Models\Locale::getDefault()->id).') WHERE `deleted_at` IS NULL AND `percentuale` = '.prepare($riga['AliquotaIVA']);
        $start_query = $query;

        if (!empty($riga['Natura'])) {
            $query .= ' AND `codice_natura_fe` = '.prepare($riga['Natura']);

            // Fallback per natura iva mancante
            if (empty($dbo->fetchArray($query))) {
                $query = $start_query;
            }
        }

        $query .= ' ORDER BY `descrizione` ASC';

        // Visualizzazione codici articoli
        $codici = $riga['CodiceArticolo'] ?: [];
        $codici = !empty($codici) && !isset($codici[0]) ? [$codici] : $codici;

        $codici_articoli = [];
        $serial = [];
        $i = 0;
        foreach ($codici as $codice) {
            $codici_articoli[] = (($i == 0) ? '<b>' : '').$codice['CodiceValore'].' ('.$codice['CodiceTipo'].')'.(($i == 0) ? '</b>' : '');
            if (str_contains((string) $codice['CodiceTipo'], 'serial') || str_contains((string) $codice['CodiceTipo'], 'Serial')) {
                $serial[] = $codice['CodiceValore'];
            }
            ++$i;
        }

        // Individuazione articolo con codice relativo
        $id_articolo = null;
        // Prendo il codice articolo dal primo nodo CodiceValore che trovo
        $codice_principale = $codici[0]['CodiceValore'];
        if (!empty($codice_principale)) {
            if (empty($id_articolo)) {
                $id_articolo = $database->fetchOne('SELECT `id` FROM `mg_articoli` WHERE `codice` = '.prepare($codice_principale))['id'];
                if (empty($id_articolo)) {
                    $id_articolo = $database->fetchOne('SELECT `id` FROM `mg_articoli` WHERE REPLACE(`codice`, " ", "") = '.prepare($codice_principale))['id'];
                }
            }

            $idconto_acquisto = $database->fetchOne('SELECT `idconto_acquisto` FROM `mg_articoli` WHERE `id` = '.prepare($id_articolo))['idconto_acquisto'];
        }

        $idconto_acquisto = $is_autofattura ? setting('Conto per autofattura') : $idconto_acquisto;
        $qta = $riga['Quantita'];
        $um = $riga['UnitaMisura'];
        $prezzo_unitario = $riga['PrezzoUnitario'] ?: $riga['Importo'];
        $is_descrizione = empty((float) $riga['Quantita']) && empty((float) $prezzo_unitario);

        $sconto_unitario = 0;
        $sconti = $riga['ScontoMaggiorazione'] ?: 0;
        if (!empty($sconti)) {
            $tot_sconto_calcolato = 0;
            $sconto_unitario = 0;
            $sconti = $sconti[0] ? $sconti : [$sconti];

            // Determina il tipo di sconto in caso di sconti misti UNT e PRC
            foreach ($sconti as $sconto) {
                $tipo_sconto = !empty($sconto['Importo']) ? '€' : '%';
                if (!empty($tipo) && $tipo_sconto != $tipo) {
                    $tipo = '€';
                } else {
                    $tipo = $tipo_sconto;
                }
            }

            foreach ($sconti as $sconto) {
                $unitario = $sconto['Importo'] ?: $sconto['Percentuale'];

                // Sconto o Maggiorazione
                $sconto_riga = ($sconto['Tipo'] == 'SC') ? $unitario : -$unitario;

                $tipo_sconto = !empty($sconto['Importo']) ? '€' : '%';
                if ($tipo_sconto == '%') {
                    $sconto_calcolato = calcola_sconto([
                        'sconto' => $sconto_riga,
                        'prezzo' => $sconto_unitario ? $prezzo_unitario - ($tot_sconto_calcolato / ($qta ?: 1)) : $prezzo_unitario,
                        'tipo' => 'PRC',
                        'qta' => $qta,
                    ]);

                    if ($tipo == '%') {
                        $tot_sconto = ($prezzo_unitario * $qta != 0 ? $sconto_calcolato * 100 / ($prezzo_unitario * $qta) : 0);
                    } else {
                        $tot_sconto = $sconto_calcolato;
                    }
                } else {
                    $tot_sconto = $sconto_riga;
                }

                $tot_sconto_calcolato += $sconto_calcolato;
                $sconto_unitario += $tot_sconto;
            }
        }

        $riferimento_fe = '';

        if ($dati_ddt[(int) $riga['NumeroLinea']]) {
            $riferimento_fe = tr('DDT _NUMERO_ del _DATA_',
                [
                    '_NUMERO_' => $dati_ddt[(int) $riga['NumeroLinea']]['numero'],
                    '_DATA_' => $dati_ddt[(int) $riga['NumeroLinea']]['data'],
                ]);
        }

        echo '
        <tr data-id="'.$key.'" data-qta="'.$qta.'" data-descrizione="'.$riga['Descrizione'].'" data-prezzo_unitario="'.$prezzo_unitario.'" data-iva_percentuale="'.$riga['AliquotaIVA'].'">
            <td>
                '.(empty($codice_principale) ? '<div style="padding:7px;" class="badge badge-warning pull-right text-muted articolo-warning hidden">'.tr('Creazione automatica articolo non disponibile').'</div>' : '<label class="badge badge-success pull-right text-muted articolo-warning hidden"><input class="check" type="checkbox" name="crea_articoli['.$key.']"/> <span style="position:relative;top:-2px;" >'.tr('Crea automaticamente questo articolo').'</span></label>').'
                <small class="pull-right text-muted" id="riferimento_'.$key.'"></small><br>
                <small class="pull-right text-muted">'.$riferimento_fe.'</small>


                '.$riga['Descrizione'].'<br>

				'.(!empty($codici_articoli) ? '<small>'.implode(', ', $codici_articoli).'</small><br>' : '').'

                <b id="riferimento_'.$key.'_descrizione"></b>
            </td>

            <td class="text-center">
                '.numberFormat($qta, 'qta').' '.$um.'
                <span id="riferimento_'.$key.'_qta"></span>
            </td>

            <td class="text-right">
                '.moneyFormat($prezzo_unitario);
        if (abs($sconto_unitario) > 0) {
            $text = ($prezzo_unitario >= 0 && $sconto_unitario > 0) || ($prezzo_unitario < 0 && $sconto_unitario < 0) ? tr('sconto _TOT_ _TYPE_', ['_TOT_' => Translator::numberToLocale(abs($sconto_unitario)), '_TYPE_' => $tipo]) : tr('maggiorazione _TOT__TYPE_', ['_TOT_' => Translator::numberToLocale(abs($sconto_unitario)), '_TYPE_' => $tipo]);
            echo '
                        <br> <span class="right badge badge-danger">'.$text.'</small>';
        }
        echo '
                <span id="riferimento_'.$key.'_prezzo"></span>
            </td>

            <td class="text-right">
                '.replace('_VALUE_ _DESC_', [
            '_VALUE_' => empty($riga['Natura']) ? numberFormat($riga['AliquotaIVA'], 0).'%' : $riga['Natura'],
            '_DESC_' => $riga['RiferimentoNormativo'] ? ' - '.$riga['RiferimentoNormativo'] : '',
        ]).'
                <span id="riferimento_'.$key.'_iva"></span>
            </td>
        </tr>';

        if (!$is_descrizione) {
            echo '
        <tr id="dati_'.$key.'">
            <td class="row">
                <span class="hide" id="aliquota['.$key.']">'.$riga['AliquotaIVA'].'</span>
                <input type="hidden" name="qta_riferimento['.$key.']" id="qta_riferimento_'.$key.'" value="'.$riga['Quantita'].'">

                <input type="hidden" name="tipo_riferimento['.$key.']" id="tipo_riferimento_'.$key.'" value="">
                <input type="hidden" name="id_riferimento['.$key.']" id="id_riferimento_'.$key.'" value="">
                <input type="hidden" name="id_riga_riferimento['.$key.']" id="id_riga_riferimento_'.$key.'" value="">
                <input type="hidden" name="tipo_riga_riferimento['.$key.']" id="tipo_riga_riferimento_'.$key.'" value="">

                <input type="hidden" name="tipo_riferimento_vendita['.$key.']" id="tipo_riferimento_vendita_'.$key.'" value="">
                <input type="hidden" name="id_riferimento_vendita['.$key.']" id="id_riferimento_vendita_'.$key.'" value="">
                <input type="hidden" name="id_riga_riferimento_vendita['.$key.']" id="id_riga_riferimento_vendita_'.$key.'" value="">
                <input type="hidden" name="tipo_riga_riferimento_vendita['.$key.']" id="tipo_riga_riferimento_vendita_'.$key.'" value="">

                <div class="card collapsed-card card-lg" style="background:#eeeeee;">
                    <div class="card-header">
                        <div class="row">
                            <div class="col-md-5">
                                {["type": "select", "name": "articoli['.$key.']", "ajax-source": "articoli", "select-options": '.json_encode(['permetti_movimento_a_zero' => 1, 'dir' => 'entrata', 'idanagrafica' => $anagrafica ? $anagrafica->id : '']).', "icon-after": "add|'.Module::where('name', 'Articoli')->first()->id.'|codice='.($codice_principale ? urlencode((string) $codice_principale) : '').'&descrizione='.($riga['Descrizione'] ? urlencode((string) $riga['Descrizione']) : '').'&prezzo_acquisto='.($riga['PrezzoUnitario'] ? urlencode((string) $riga['PrezzoUnitario']) : '').'", "value": "'.$id_articolo.'", "label": "'.tr('Articolo').'","extra": "data-id=\''.$key.'\'" ]}
                            </div>

                            <div class="col-md-3">
                                {[ "type": "select", "name": "conto['.$key.']", "id": "conto-'.$key.'", "ajax-source": "conti-acquisti", "required": 1, "label": "'.tr('Conto acquisti').'", "value": "'.$idconto_acquisto.'" ]}
                            </div>

                            <div class="col-md-3">
                                {[ "type": "select", "name": "iva['.$key.']", "values": '.json_encode('query='.$query).', "required": 1, "label": "'.tr('Aliquota IVA').'" ]}
                            </div>

                            <div class="col-md-1 card-tools">
                            <br>
                                <button type="button" class="btn btn-lg" data-card-widget="collapse" onclick="$(this).find(\'i\').toggleClass(\'fa-plus\').toggleClass(\'fa-minus\');">
                                <i class="fa fa-plus"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                {[ "type": "select", "name": "selezione_riferimento['.$key.']", "ajax-source": "riferimenti-fe", "select-options": '.json_encode(['id_anagrafica' => $anagrafica ? $anagrafica->id : '']).', "label": "'.tr('Riferimento acquisto').'", "icon-after": '.json_encode('<button type="button" onclick="rimuoviRiferimento(this)" class="btn btn-danger disabled" id="rimuovi_riferimento_'.$key.'"><i class="fa fa-close"></i></button>').', "help": "'.tr('Articoli contenuti in Ordini o DDT del cliente').'" ]}
                            </div>

                            <div class="col-md-3">
                                {[ "type": "select", "name": "selezione_riferimento_vendita['.$key.']", "ajax-source": "riferimenti-vendita-fe", "select-options": '.json_encode(['id_articolo' => $id_articolo]).', "label": "'.tr('Riferimento vendita').'", "icon-after": '.json_encode('<button type="button" onclick="rimuoviRiferimentoVendita(this)" class="btn btn-danger disabled" id="rimuovi_riferimento_vendita_'.$key.'"><i class="fa fa-close"></i></button>').', "help": "'.tr('Articoli contenuti in Ordini Cliente').'" ]}
                            </div>

                            <div class="col-md-6">
                                {[ "type": "select", "name": "update_info['.$key.']", "values": "list=\"update_not\":\"Nessuna operazione\", \"update_price\":\"Crea listino del cliente (se non presente) e aggiorna il prezzo di acquisto\", \"update_all\":\"Crea listino del cliente (se non presente) aggiorna prezzo di acquisto e imposta cliente come predefinito\"", "label": "'.tr('Aggiorna informazioni di acquisto').'", "value": "'.$update_info.'", "help": "'.tr('Creazione automatica articolo deve essere attiva o l\'articolo deve essere selezionato affinché questa impostazione abbia effetto').'.", "readonly": "'.(empty($codice_principale) ? 1 : 0).'" ]}
                            </div>
                        </div>

                        <div class="row">';
            if (setting('Creazione seriali in import FE') && $serial) {
                for ($i = 0; $i < $qta; ++$i) {
                    echo '
                                            <div class="col-md-3">
                                                {[ "type": "text", "label": "'.tr('Serial').'", "name": "serial['.$key.'][]", "value": "'.$serial[$i].'" ]}
                                            </div>';
                }
            }
            echo '
                        </div>
                    </div> 
                </div>
            </td>
        </tr>';
        } else {
            echo '
                <input type="hidden" name="qta_riferimento['.$key.']" id="qta_riferimento_'.$key.'" value="'.$riga['Quantita'].'">

                <input type="hidden" name="tipo_riferimento['.$key.']" id="tipo_riferimento_'.$key.'" value="">
                <input type="hidden" name="id_riferimento['.$key.']" id="id_riferimento_'.$key.'" value="">
                <input type="hidden" name="id_riga_riferimento['.$key.']" id="id_riga_riferimento_'.$key.'" value="">
                <input type="hidden" name="tipo_riga_riferimento['.$key.']" id="tipo_riga_riferimento_'.$key.'" value="">

                <input type="hidden" name="tipo_riferimento_vendita['.$key.']" id="tipo_riferimento_vendita_'.$key.'" value="">
                <input type="hidden" name="id_riferimento_vendita['.$key.']" id="id_riferimento_vendita_'.$key.'" value="">
                <input type="hidden" name="id_riga_riferimento_vendita['.$key.']" id="id_riga_riferimento_vendita_'.$key.'" value="">
                <input type="hidden" name="tipo_riga_riferimento_vendita['.$key.']" id="tipo_riga_riferimento_vendita_'.$key.'" value="">
                
                <input type="hidden" name="conto['.$key.']" value="">
                <input type="hidden" name="iva['.$key.']" value="">
                <input type="hidden" name="update_info['.$key.']" value="">';
        }
    }

    echo '
            </tbody>
        </table>
    </div>';

    echo '
    <script>
    function copia() {
        let aliquote = $("select[name^=iva]");
        let conti = $("select[name^=conto]");

        // Individuazione della prima IVA selezionata
        let iva_selezionata = null;
        for (const aliquota of aliquote) {
            const data = $(aliquota).selectData();
            if (data && data.id) {
                iva_selezionata = data;
                break;
            }
        }

        // Individuazione del primo conto selezionato
        let conto_selezionato = null;
        for (const conto of conti) {
            const data = $(conto).selectData();
            if (data && data.id) {
                conto_selezionato = data;
                break;
            }
        }

        // Selezione generale per l\'IVA
        if (iva_selezionata) {
            aliquote.each(function() {
                $(this).selectSet(iva_selezionata.id);
            });
        }

        // Selezione generale per il conto
        if (conto_selezionato) {
            conti.each(function() {
                $(this).selectSetNew(conto_selezionato.id, conto_selezionato.text, conto_selezionato);
            });
        }
    }
    </script>';
} else {
    echo '
    <p>'.tr('Non ci sono righe nella fattura').'.</p>';
}

echo '
    <div class="row">
        <div class="col-md-12 text-right">
            <a href="'.$skip_link.'" class="btn btn-warning">
                <i class="fa fa-ban "></i> '.tr('Salta fattura').'
            </a>

            <button type="submit" class="btn btn-primary">
                <i class="fa fa-arrow-right"></i> '.tr('Continua').'...
            </button>
        </div>
    </div>
</form>

<script>
input("flag_crea_articoli").on("change", function (){
    if (input("flag_crea_articoli").get()) {
        $(".articolo-warning").removeClass("hidden");
        $(".check").each(function(){
            if( !$(this).is(":checked") ){
                $(this).trigger("click");
            }
        });
    } else {
        $(".articolo-warning").addClass("hidden");
        $(".check").each(function(){
            if( $(this).is(":checked") ){
                $(this).trigger("click");
            }
        });
    }
});

$("select[name^=selezione_riferimento]").change(function() {
    if (!$(this).hasClass("already-loaded")) {
        let $this = $(this);
        let data = $this.selectData();

        if (data) {
            let riga = $this.closest("tr").prev();
            selezionaRiferimento(riga, data.tipo, data.id, data.dir);
        }
    }
});

function rimuoviRiferimento(button) {
    let riga = $(button).closest("tr").prev();
    let id_riga = riga.data("id");

    impostaRiferimento(id_riga, {}, {});

    input("selezione_riferimento[" + id_riga + "]").enable()
        .getElement().selectReset();
    $(button).addClass("disabled");
    riga.removeClass("success").removeClass("warning");
}

function selezionaRiferimento(riga, tipo_documento, id_documento, dir) {
    let id_riga = riga.data("id");
    let qta = riga.data("qta");
    let descrizione = riga.data("descrizione");
    let prezzo_unitario = riga.data("prezzo_unitario");
    
    let riferimenti = getRiferimenti();
    let query = {
        id_module: "'.$id_module.'",
        id_record: "'.$id_record.'",
        qta: qta,
        descrizione: descrizione,
        prezzo_unitario: prezzo_unitario,
        id_riga: id_riga,
        id_documento: id_documento,
        tipo_documento: tipo_documento,
        righe_ddt: riferimenti.ddt,
        righe_ordini: riferimenti.ordini,
        dir: dir,
    };

    let url = "'.$structure->fileurl('riferimento.php').'?" + $.param(query);

    openModal("'.tr('Selezione riferimento').'", url);
}

function getRiferimenti() {
    let righe_ordini = {};
    let righe_ddt = {};

    $("[id^=tipo_riferimento_]").each(function(index, item) {
        let tipo = $(item).val();
        let riga = $(item).closest("tr");

        let qta = parseFloat(riga.find("[id^=qta_riferimento_]").val());
        let id_riga = riga.find("[id^=id_riga_riferimento_]").val();
        if (tipo === "ordine") {
            righe_ordini[id_riga] = righe_ordini[id_riga] ? righe_ordini[id_riga] : 0;
            righe_ordini[id_riga] += qta;
        } else if (tipo === "ddt") {
            righe_ddt[id_riga] = righe_ddt[id_riga] ? righe_ddt[id_riga] : 0;
            righe_ddt[id_riga] += qta;
        }
    });

    return {
        ordini: righe_ordini,
        ddt: righe_ddt,
    };
}

/**
*
* @param id_riga
* @param documento = {tipo, id, descrizione }
* @param riga = {tipo, id, descrizione, qta, prezzo_unitario}
*/
function impostaRiferimento(id_riga, documento, riga) {
    // Informazioni interne per il riferimento
    $("#tipo_riferimento_" + id_riga).val(documento.tipo);
    $("#id_riferimento_" + id_riga).val(documento.id);
    $("#tipo_riga_riferimento_" + id_riga).val(riga.tipo);
    $("#id_riga_riferimento_" + id_riga).val(riga.id);

    // Gestione della selezione
    input("selezione_riferimento[" + id_riga + "]").disable();
    $("#rimuovi_riferimento_" + id_riga).removeClass("disabled");

    let riga_fe = $("#id_riga_riferimento_" + id_riga).closest("tr").prev();

    // Informazioni visibili sulla quantità
    impostaContenuto(riga_fe.data("qta"), riga.qta, (riga.um ? " " + riga.um : ""), "#riferimento_" + id_riga + "_qta", true);

    // Informazioni visibili sul prezzo unitario
    impostaContenuto(riga_fe.data("prezzo_unitario"), riga.prezzo_unitario, " " + globals.currency, "#riferimento_" + id_riga + "_prezzo", true);

    // Informazioni visibili sull\'aliquota IVA
    impostaContenuto(riga_fe.data("iva_percentuale"), parseInt(riga.iva_percentuale), "%", "#riferimento_" + id_riga + "_iva", false);

    $("#riferimento_" + id_riga).html(documento.descrizione ? documento.descrizione : "");

    var descrizione = riga.descrizione;
    if(typeof descrizione !== "undefined"){
        descrizione = descrizione.replace(/_/g, " ").replace(/\n/g, "<br>");
    }

    // Dettagli del documento trovato
    icona_documento = documento.match_documento_da_fe ? "fa-check-circle text-success" : "fa-question-circle text-orange";
    tooltip_icona = documento.match_documento_da_fe ? "La corrispondenza trovata è avvenuta in base a quanto ha specificato il cliente nella fattura elettronica" : "Nessuna corrispondenza con quanto ha specificato il cliente nella fattura elettronica, il riferimento potrebbe non essere corretto";

    $("#riferimento_" + id_riga + "_descrizione").html("<br><b class=\"tip\" title=\"" + tooltip_icona + "\"><i class=\"fa " + icona_documento + "\"></i> " + documento.opzione + "</b><br>");

    // Dettagli della riga trovata
    $("#riferimento_" + id_riga + "_descrizione").append(descrizione ? descrizione : "");

    // Colorazione dell\'intera riga
    let warnings = riga_fe.find(".text-warning");
    if (warnings.length === 0) {
        riga_fe.addClass("success").removeClass("warning");
    } else {
        riga_fe.removeClass("success").addClass("warning");
    }

    if (riga.id_articolo) {
        input("articoli["+id_riga+"]").getElement().selectSetNew(riga.id_articolo, riga.desc_articolo.replace(/_/g, " ").replace(/\n/g, "<br>"));
        if (riga.id_conto) {
            input("conto["+id_riga+"]").getElement().selectSetNew(riga.id_conto, riga.desc_conto.replace(/_/g, " ").replace(/\n/g, "<br>"));
        }
    }
}

// Informazioni visibili sull\'aliquota IVA
function impostaContenuto(valore_riga, valore_riferimento, contenuto_successivo, id_elemento, parse_riferimento) {
    let elemento = $(id_elemento);
    if (valore_riferimento === undefined) {
        elemento.html("");
        return;
    }

    valore_riga = parseFloat(valore_riga);
    valore_riferimento = parseFloat(valore_riferimento);

    let contenuto = (parse_riferimento ? valore_riferimento.toLocale() + contenuto_successivo : valore_riferimento + contenuto_successivo);
    if (valore_riferimento === valore_riga) {
        contenuto = `<i class="fa fa-check"></i> ` + contenuto;
        elemento.addClass("text-success").removeClass("text-warning");
    } else {
        contenuto = `<i class="fa fa-warning"></i> ` + contenuto;
        elemento.removeClass("text-success").addClass("text-warning");
    }

    elemento.html("<br>" + contenuto);
}

function impostaRiferimentoVendita(id_riga, documento, riga) {
    // Informazioni interne per il riferimento
    $("#tipo_riferimento_vendita_" + id_riga).val(documento.tipo);
    $("#id_riferimento_vendita_" + id_riga).val(documento.id);
    $("#tipo_riga_riferimento_vendita_" + id_riga).val(riga.tipo);
    $("#id_riga_riferimento_vendita_" + id_riga).val(riga.id);

    // Gestione della selezione
    input("selezione_riferimento_vendita[" + id_riga + "]").disable();
    $("#rimuovi_riferimento_vendita_" + id_riga).removeClass("disabled");
}

function rimuoviRiferimentoVendita(button) {
    let riga = $(button).closest("tr").prev();
    let id_riga = riga.data("id");

    impostaRiferimentoVendita(id_riga, {}, {});

    input("selezione_riferimento_vendita[" + id_riga + "]").enable()
        .getElement().selectReset();
    $(button).addClass("disabled");
    riga.removeClass("success").removeClass("warning");
}

$("[id^=\'articoli\']").change(function() {
    $("#conto-"+$(this).data("id")).selectReset();
    updateSelectOption("id_articolo", $(this).val());
    let data = $(this).selectData();
    if(data!==undefined){
        $("#conto-"+$(this).data("id")).selectSetNew(data.idconto_acquisto, data.idconto_acquisto_title);
    }

    verificaSerial($(this));

    
    if($(this).val()){
       $("#update_info"+$(this).data("id")).prop(\'disabled\', false);
    }else{
        $("#update_info"+$(this).data("id")).prop(\'disabled\', true);
    }

     
});

function copy_rif() {
    let rif_vendite = $("select[name^=selezione_riferimento_vendita]");

    // Individuazione della prima IVA selezionata
    let iva_selezionata = null;
    for (const rif_vendita of rif_vendite) {
        const data = $(rif_vendita).selectData();
        if (data && data.id) {
            rif_vendita_selezionata = data;
            break;
        }
    }

    // Selezione generale per il conto
    if (rif_vendita_selezionata) {
        rif_vendite.each(function() {
            $(this).selectSetNew(rif_vendita_selezionata.id, rif_vendita_selezionata.text, rif_vendita_selezionata);

            id = $(this).attr("id").toString();
            var matches = id.match(/(\d+)/);
            id_riga = matches[0];

            $("#tipo_riferimento_vendita_" + id_riga).val("ordine");
            $("#id_riferimento_vendita_" + id_riga).val(rif_vendita_selezionata.id);
            $("#id_riga_riferimento_vendita_" + id_riga).val("new-ordine-"+rif_vendita_selezionata.id);

            $("#rimuovi_riferimento_vendita_" + id_riga).removeClass("disabled");
            $(this).prop("disabled", true);
        });
    }
}

// Visualizza input seriali se abilita serial dell\'articolo selezionato è attivo
function verificaSerial(riga) {
    if (riga.val()) {
        let data = riga.selectData();
        if (data.abilita_serial) {
            $("#serial"+riga.data("id")).parent().parent().parent().removeClass("hidden");
        } else {
            $("#serial"+riga.data("id")).parent().parent().parent().addClass("hidden");
        }
    } else {
        $("#serial"+riga.data("id")).parent().parent().parent().addClass("hidden");
    }
}

// Disabilita input seriali se flag crea seriali è disattivato
$("#flag_crea_seriali").on("change", function() {
    if ($(this).is(":checked")) {
        $("[id^=\'serial\']").attr("disabled", false);
    } else {
        $("[id^=\'serial\']").attr("disabled", true);
    }
});
</script>';
