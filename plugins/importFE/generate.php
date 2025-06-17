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
use Modules\Fatture\Fattura;
use Modules\Pagamenti\Pagamento;
use Plugins\ImportFE\FatturaElettronica;
use Util\XML;

include_once __DIR__.'/../../core.php';

echo '
<style>
.riga-fattura td {
    height: 60px;
    vertical-align: middle;
}

.table td {
    padding: 0.5rem;
}
</style>

<script>
$(document).ready(function() {
    $("#save-buttons").hide();

    // Visualizza input seriali se abilita serial dell\'articolo selezionato è attivo
    let articoli = $("select[name^=articoli]");
    articoli.each(function() {
        verificaSerial($(this));
    });

    // Disabilita input seriali se flag crea seriali è disattivato
    if (!$("#flag_crea_seriali").is(":checked")) {
        $("[id^=\'serial\']").attr("disabled", true);
    }
});

function copy_rif() {
    let riferimenti = $("select[name^=selezione_riferimento_vendita]");

    // Individuazione del primo riferimento selezionato
    let riferimento_selezionato = null;
    for (const riferimento of riferimenti) {
        const data = $(riferimento).selectData();
        if (data && data.id) {
            riferimento_selezionato = data;
            break;
        }
    }

    // Selezione generale per il riferimento
    if (riferimento_selezionato) {
        riferimenti.each(function() {
            $(this).selectSetNew(riferimento_selezionato.id, riferimento_selezionato.text, riferimento_selezionato);
        });
    }
}
</script>';

$skip_link = base_path().'/controller.php?id_module='.$id_module.'&id_plugin='.$id_plugin;

if (empty($fattura_pa)) {
    if (!empty($error)) {
        echo '
<div class="alert alert-danger">
    <i class="fa fa-exclamation-triangle mr-2"></i>'.tr("Errore durante l'apertura della fattura elettronica _NAME_", [
            '_NAME_' => $record['name'],
        ]).'
</div>';
    } elseif (!empty($imported)) {
        echo '
<div class="alert alert-info">
    <i class="fa fa-info-circle mr-2"></i>'.tr('La fattura elettronica _NAME_ è già stata importata in passato', [
            '_NAME_' => $record['name'],
        ]).'
</div>';
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
// Fornitore
$fornitore = $fattura_pa->getAnagrafe();

$ragione_sociale = $fornitore['ragione_sociale'] ?: $fornitore['cognome'].' '.$fornitore['nome'];
$codice_fiscale = $fornitore['codice_fiscale'];
$partita_iva = $fornitore['partita_iva'];

$sede = $fornitore['sede'];

$cap = $sede['cap'];
$citta = $sede['comune'];
$provincia = $sede['provincia'];

// Dati generali
$fattura_body = $fattura_pa->getBody();
$dati_generali = $fattura_body['DatiGenerali']['DatiGeneraliDocumento'];

$tipo_documento = $database->fetchOne('SELECT CONCAT("(", `codice`, ") ", `title`) AS descrizione FROM `fe_tipi_documento` LEFT JOIN `fe_tipi_documento_lang` ON (`fe_tipi_documento_lang`.`id_record` = `fe_tipi_documento`.`codice` AND `fe_tipi_documento_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).') WHERE codice = '.prepare($dati_generali['TipoDocumento']))['descrizione'];

// Gestione per fattura elettroniche senza pagamento definito
$pagamenti = [];
if (isset($fattura_body['DatiPagamento'])) {
    $pagamenti = $fattura_body['DatiPagamento'];
    $pagamenti = isset($pagamenti[0]) ? $pagamenti : [$pagamenti];
}

$metodi = [];
foreach ($pagamenti as $pagamento) {
    $rate = $pagamento['DettaglioPagamento'];
    $rate = isset($rate[0]) ? $rate : [$rate];

    $metodi = array_merge($metodi, $rate);
}
$metodi = isset($metodi[0]) ? $metodi : [$metodi];

$codice_modalita_pagamento = $metodi[0]['ModalitaPagamento'];

echo '
<form action="" method="post">
    <input type="hidden" name="filename" value="'.$record['name'].'">
    <input type="hidden" name="op" value="generate">';

// Mostra la barra di progresso solo se siamo in modalità importazione in sequenza
if (get('sequence') == 1) {
    echo '
    <div class="row mb-3">
        <div class="col-md-12">
            <div class="progress">
                <div id="import-progress-bar" class="progress-bar progress-bar-striped progress-bar-animated bg-warning" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0%">0%</div>
            </div>
            <div class="text-center mt-1">
                <small id="progress-text" class="text-muted">'.tr('Importazione in sequenza: elaborazione documento...').'</small>
            </div>
        </div>
    </div>';
}

echo '

    <div class="row">';

// Fornitore
echo '
        <div class="col-md-4">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fa fa-industry mr-2"></i>'.tr('Fornitore').'
                    </h3>
                </div>
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div>
                            <span class="text-primary font-weight-bold">'.$ragione_sociale.'</span>
                            '.(empty($anagrafica) ? '<span class="badge badge-warning ml-2">'.tr('Nuova anagrafica').'</span>' : '<small class="ml-2">'.Modules::link('Anagrafiche', $anagrafica->id, '', null, '').'</small>').'
                            <div class="small">
                                '.(!empty($codice_fiscale) ? '<span class="mr-2"><i class="fa fa-id-card mr-1 text-muted"></i>'.$codice_fiscale.'</span>' : '').'
                                '.(!empty($partita_iva) ? '<span class="mr-2"><i class="fa fa-building mr-1 text-muted"></i>'.$partita_iva.'</span>' : '').'
                                <span><i class="fa fa-map-marker mr-1 text-muted"></i>'.$cap.' '.$citta.' ('.$provincia.')</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>';

// Documento
echo '
        <div class="col-md-4">
            <div class="card card-outline card-info">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fa fa-file-text-o mr-2"></i>'.tr('Documento').'
                    </h3>
                    <div class="card-tools">
                        <a href="'.$structure->fileurl('view.php').'?filename='.$record['name'].'" class="btn btn-info btn-sm" target="_blank" >
                            <i class="fa fa-eye"></i> '.tr('Visualizza XML').'
                        </a>
                    </div>
                </div>
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div>
                            <span class="text-info font-weight-bold">'.$tipo_documento.' '.$dati_generali['Numero'].'</span>
                            <div class="small">
                                <span class="mr-2"><i class="fa fa-calendar mr-1 text-muted"></i>'.Translator::dateToLocale($dati_generali['Data']).'</span>
                                <span><i class="fa fa-euro mr-1 text-muted"></i>'.$dati_generali['Divisa'].'</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>';

// Pagamento
if (!empty($pagamenti)) {
    echo '
        <div class="col-md-4">
            <div class="card card-outline card-success">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fa fa-money mr-2"></i>'.tr('Pagamento').'
                    </h3>
                </div>
                <div class="card-body p-3">
                    <div class="table-responsive">
                        <table class="table table-sm table-striped table-bordered mb-0">
                            <thead>
                                <tr>
                                    <th>'.tr('Modalità').'</th>
                                    <th>'.tr('Data').'</th>
                                    <th class="text-right">'.tr('Importo').'</th>
                                </tr>
                            </thead>
                            <tbody>';

    foreach ($pagamenti as $pagamento) {
        $rate = $pagamento['DettaglioPagamento'];
        $rate = isset($rate[0]) ? $rate : [$rate];

        // Scadenze di pagamento
        foreach ($rate as $rata) {
            $descrizione = !empty($rata['ModalitaPagamento']) ? $database->fetchOne('SELECT `title` FROM `fe_modalita_pagamento` LEFT JOIN `fe_modalita_pagamento_lang` ON (`fe_modalita_pagamento_lang`.`id_record`=`fe_modalita_pagamento`.`codice` AND `fe_modalita_pagamento_lang`.`id_lang`='.prepare(Models\Locale::getDefault()->id).') WHERE `codice` = '.prepare($rata['ModalitaPagamento']))['descrizione'] : '';
            $data = !empty($rata['DataScadenzaPagamento']) ? FatturaElettronica::parseDate($rata['DataScadenzaPagamento']) : '';

            echo '
                <tr>
                    <td><small><i class="fa fa-credit-card mr-1 text-muted"></i>'.$descrizione.'</small></td>
                    <td><small><i class="fa fa-calendar mr-1 text-muted"></i>'.dateFormat($data).'</small></td>
                    <td class="text-right"><small><i class="fa fa-euro mr-1 text-muted"></i>'.moneyFormat($rata['ImportoPagamento']).'</small></td>
                </tr>';
        }
    }

    echo '
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>';
}

echo '
    </div>';

// Tipo del documento
$query = "SELECT `co_tipidocumento`.`id`, CONCAT('(', `codice_tipo_documento_fe`, ') ', `title`) AS descrizione FROM `co_tipidocumento` LEFT JOIN `co_tipidocumento_lang` ON (`co_tipidocumento_lang`.`id_record` = `co_tipidocumento`.`id` AND `co_tipidocumento_lang`.`id_lang` = ".prepare(Models\Locale::getDefault()->id).") WHERE `dir` = 'uscita'";
$query_tipo = $query.' AND `codice_tipo_documento_fe` = '.prepare($dati_generali['TipoDocumento']);
$numero_tipo = $database->fetchNum($query_tipo);
if (!empty($numero_tipo)) {
    $query = $query_tipo;
}

$id_tipodocumento = $database->fetchOne($query_tipo)['id'];

echo '
    <div class="row mt-3">
        <div class="col-md-12">
            <div class="card card-outline card-secondary">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fa fa-cog mr-2"></i>'.tr('Impostazioni').'
                    </h3>
                </div>
                <div class="card-body p-3">
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

// Pagamento
$pagamento = Pagamento::where('codice_modalita_pagamento_fe', $codice_modalita_pagamento)->where('predefined', '1')->first();
echo '
                        <div class="col-md-3">
                            {[ "type": "select", "label": "'.tr('Pagamento').'", "name": "pagamento", "required": 1, "ajax-source": "pagamenti", "select-options": '.json_encode(['codice_modalita_pagamento_fe' => $codice_modalita_pagamento]).', "value": "'.$pagamento->id.'" ]}
                            <button type="button" class="btn btn-info btn-xs" onclick="updateSelectOption(\'codice_modalita_pagamento_fe\', \'\')">
                                <i class="fa fa-refresh"></i> '.tr('Visualizza tutte le modalità').'
                            </button>
                        </div>
                    </div>';

if (!empty($anagrafica)) {
    $query = "SELECT
            `co_documenti`.`id`,
            CONCAT('Fattura num. ', `co_documenti`.`numero_esterno`, ' del ', DATE_FORMAT(`co_documenti`.`data`, '%d/%m/%Y')) AS descrizione
        FROM `co_documenti`
            INNER JOIN `co_tipidocumento` ON `co_tipidocumento`.`id` = `co_documenti`.`idtipodocumento`
        WHERE
            `co_tipidocumento`.`dir` = 'uscita' AND
            (`co_documenti`.`data` BETWEEN NOW() - INTERVAL 1 YEAR AND NOW()) AND
            `co_documenti`.`idstatodocumento` IN (SELECT `id_record` FROM `co_statidocumento_lang` WHERE `title` != 'Bozza') AND
            `co_documenti`.`idanagrafica` = ".prepare($anagrafica->id);

    // Riferimenti ad altre fatture
    if (in_array($dati_generali['TipoDocumento'], ['TD04', 'TD05']) || $dati_generali['TipoDocumento'] == 'TD06' || $is_autofattura) {
        echo '
                    <div class="row mt-2">';

        if (in_array($dati_generali['TipoDocumento'], ['TD04', 'TD05'])) {
            echo '
                        <div class="col-md-4">
                            {[ "type": "select", "label": "'.tr('Fattura collegata').'", "name": "ref_fattura", "required": 0, "values": "query='.$query.'" ]}
                        </div>';
        } elseif ($dati_generali['TipoDocumento'] == 'TD06') {
            $query .= 'AND `co_documenti`.`id_segment` = (SELECT `zz_segments`.`id` FROM `zz_segments` LEFT JOIN `zz_segments_lang` ON (`zz_segments_lang`.`id_record` = `zz_segments`.`id` AND `zz_segments_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).") WHERE `title` = 'Fatture pro-forma' AND `id_module` = ".prepare($id_module).')';

            echo '
                        <div class="col-md-4">
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

            $autofattura_collegata = Fattura::where('progressivo_invio', '=', $fattura_pa->getHeader()['DatiTrasmissione']['ProgressivoInvio'])->first();

            echo '
                        <div class="col-md-4">
                            {[ "type": "select", "label": "'.tr('Autofattura collegata').'", "name": "autofattura", "values": "query='.$query.'", "value": "'.$autofattura_collegata->id.'" ]}
                        </div>';
        }

        echo '
                    </div>';
    }
}

// Movimentazioni
echo '
                    <div class="row mt-2">
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
                            {[ "type": "checkbox", "label": "'.tr('Ritenuta pagata dal fornitore').'", "name": "is_ritenuta_pagata", "value": 0, "help": "'.tr('Attivare se la ritenuta è stata pagata dal fornitore').'" ]}
                        </div>';
}
echo '
                    </div>
                </div>
            </div>
        </div>
    </div>';

// Righe
if (setting('Aggiorna info di acquisto') == 'Non aggiornare') {
    $update_info = 'update_not';
} elseif (setting('Aggiorna info di acquisto') == 'Aggiorna prezzo di listino') {
    $update_info = 'update_price';
} else {
    $update_info = 'update_all';
}

$righe = $fattura_pa->getRighe();
if (!empty($righe)) {
    echo '
    <div class="card card-outline card-warning mt-3">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fa fa-list mr-2"></i>'.tr('Righe').'
            </h3>
            <div class="card-tools">
                <button type="button" class="btn btn-info btn-sm" onclick="copia()"><i class="fa fa-copy"></i> '.tr('Copia dati contabili').'</button>
                <button type="button" class="btn btn-info btn-sm ml-2" onclick="copy_rif()"><i class="fa fa-copy"></i> '.tr('Copia riferimento vendita').'</button>
            </div>
        </div>
        <div class="card-body">
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
    $DatiOrdini = XML::forceArray($fattura_pa->getBody()['DatiGenerali']['DatiOrdineAcquisto']);
    $DatiDDT = XML::forceArray($fattura_pa->getBody()['DatiGenerali']['DatiDDT']);

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
        $id_articolo = 0;

        foreach ($codici as $codice) {
            $codici_articoli[] = (($i == 0) ? '<b>' : '').$codice['CodiceValore'].' ('.$codice['CodiceTipo'].')'.(($i == 0) ? '</b>' : '');
            if (str_contains((string) $codice['CodiceTipo'], 'serial') || str_contains((string) $codice['CodiceTipo'], 'Serial')) {
                $serial[] = $codice['CodiceValore'];
            }
            ++$i;

            $codice_principale = $codice['CodiceValore'];

            if (!empty($anagrafica) && empty($id_articolo)) {
                $id_articolo = $database->fetchOne('SELECT `id_articolo` AS id FROM `mg_fornitore_articolo` WHERE `codice_fornitore` = '.prepare($codice_principale).' AND id_fornitore = '.prepare($anagrafica->id))['id'];
                if (empty($id_articolo)) {
                    $id_articolo = $database->fetchOne('SELECT `id_articolo` AS id FROM `mg_fornitore_articolo` WHERE REPLACE(`codice_fornitore`, " ", "") = '.prepare($codice_principale).' AND `id_fornitore` = '.prepare($anagrafica->id))['id'];
                }
            }

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
        <tr class="riga-fattura" data-id="'.$key.'" data-qta="'.$qta.'" data-descrizione="'.$riga['Descrizione'].'" data-prezzo_unitario="'.$prezzo_unitario.'" data-iva_percentuale="'.$riga['AliquotaIVA'].'">
            <td style="height: 60px;">
                <div class="d-flex align-items-center h-100">
                    <div class="flex-grow-1">
                        <input type="hidden" name="qta_riferimento['.$key.']" id="qta_riferimento_'.$key.'" value="'.$riga['Quantita'].'">
                        <input type="hidden" name="tipo_riferimento['.$key.']" id="tipo_riferimento_'.$key.'" value="">
                        <input type="hidden" name="id_riferimento['.$key.']" id="id_riferimento_'.$key.'" value="">
                        <input type="hidden" name="id_riga_riferimento['.$key.']" id="id_riga_riferimento_'.$key.'" value="">
                        <input type="hidden" name="tipo_riga_riferimento['.$key.']" id="tipo_riga_riferimento_'.$key.'" value="">

                        <input type="hidden" name="tipo_riferimento_vendita['.$key.']" id="tipo_riferimento_vendita_'.$key.'" value="">
                        <input type="hidden" name="id_riferimento_vendita['.$key.']" id="id_riferimento_vendita_'.$key.'" value="">
                        <input type="hidden" name="id_riga_riferimento_vendita['.$key.']" id="id_riga_riferimento_vendita_'.$key.'" value="">
                        <input type="hidden" name="tipo_riga_riferimento_vendita['.$key.']" id="tipo_riga_riferimento_vendita_'.$key.'" value="">

                        <div>'.$riga['Descrizione'].'</div>
                        '.(!empty($codici_articoli) ? '<small class="text-muted">'.implode(', ', $codici_articoli).'</small>' : '').'
                        <b id="riferimento_'.$key.'_descrizione"></b>
                    </div>
                    <div class="ml-2 text-right">
                        '.(empty($codice_principale) ? '<div style="padding:7px;" class="badge badge-warning text-muted articolo-warning hidden">'.tr('Creazione automatica articolo non disponibile').'</div>' : '<label class="badge badge-success text-muted articolo-warning hidden"><input class="check" type="checkbox" name="crea_articoli['.$key.']"/> <span style="position:relative;top:-2px;" >'.tr('Crea automaticamente questo articolo').'</span></label>').'
                        <div><small class="text-muted" id="riferimento_'.$key.'"></small></div>
                        <div><small class="text-muted">'.$riferimento_fe.'</small></div>
                    </div>
                </div>
            </td>

            <td class="text-center align-middle" style="height: 60px;">
                '.numberFormat($qta, 'qta').' '.$um.'
                <span id="riferimento_'.$key.'_qta"></span>
            </td>

            <td class="text-right align-middle" style="height: 60px;">
                '.moneyFormat($prezzo_unitario);
        if (abs($sconto_unitario) > 0) {
            $text = ($prezzo_unitario >= 0 && $sconto_unitario > 0) || ($prezzo_unitario < 0 && $sconto_unitario < 0) ? tr('sconto _TOT_ _TYPE_', ['_TOT_' => Translator::numberToLocale(abs($sconto_unitario)), '_TYPE_' => $tipo]) : tr('maggiorazione _TOT__TYPE_', ['_TOT_' => Translator::numberToLocale(abs($sconto_unitario)), '_TYPE_' => $tipo]);
            echo '
                        <br> <span class="right badge badge-danger">'.$text.'</span>';
        }
        echo '
                <span id="riferimento_'.$key.'_prezzo"></span>
            </td>

            <td class="text-right align-middle" style="height: 60px;">
                '.replace('_VALUE_ _DESC_', [
            '_VALUE_' => empty($riga['Natura']) ? numberFormat($riga['AliquotaIVA'], 0).'%' : $riga['Natura'],
            '_DESC_' => $riga['RiferimentoNormativo'] ? ' - '.$riga['RiferimentoNormativo'] : '',
        ]).'
                <span id="riferimento_'.$key.'_iva"></span>
            </td>
        </tr>';
        echo '
        <tr>
            <td colspan="4">
                <div class="card card-outline card-primary">
                    <div class="card-header d-flex align-items-center">
                        <div class="col-md-11">
                            <div class="row">
                                <div class="col-md-3">
                                    {[ "type": "select", "label": "'.tr('Articolo').'", "name": "articoli['.$key.']", "ajax-source": "articoli", "select-options": '.json_encode(['permetti_movimento_a_zero' => 1, 'dir' => 'uscita', 'idanagrafica' => $anagrafica ? $anagrafica->id : 0, 'id_anagrafica' => $anagrafica ? $anagrafica->id : 0, 'idsede_partenza' => 0, 'idsede_destinazione' => 0]).', "value": "'.$id_articolo.'", "icon-after": "add|'.tr('Crea articolo').'|'.base_path().'/add.php?id_module='.Modules::get('Articoli')['id'].'", "readonly": "'.($is_descrizione ? 1 : 0).'", "onchange": "verificaSerial(this)" ]}
                                </div>

                                <div class="col-md-3">
                                    {[ "type": "select", "label": "'.tr('Conto').'", "name": "conti['.$key.']", "ajax-source": "conti-acquisti", "value": "'.$idconto_acquisto.'", "required": 1 ]}
                                </div>

                                <div class="col-md-3">
                                    {[ "type": "select", "label": "'.tr('Iva').'", "name": "iva['.$key.']", "values": "query='.$query.'", "required": 1 ]}
                                </div>

                                <div class="col-md-3">
                                    {[ "type": "select", "label": "'.tr('Aggiorna info acquisto').'", "name": "update_info['.$key.']", "values": "list=\"update_not\":\"'.tr('Non aggiornare').'\",\"update_price\":\"'.tr('Aggiorna prezzo di listino').'\",\"update_all\":\"'.tr('Aggiorna prezzo di listino e di acquisto').'\"", "value": "'.$update_info.'" ]}
                                </div>
                            </div>
                        </div>

                        <div class="col-md-1 text-right">
                            <button type="button" class="btn btn-primary btn-sm" onclick="toggleRiferimenti('.$key.')" title="'.tr('Mostra/nascondi riferimenti').'">
                                <i class="fa fa-link mr-1"></i> <i class="fa fa-plus" id="toggle-icon-'.$key.'"></i>
                            </button>
                        </div>
                    </div>

                    <div class="card-body p-0" id="riferimenti-body-'.$key.'" style="display: none;">
                        <div class="p-3 bg-light border-top">
                            <div class="row">
                                <div class="col-md-12 mb-2">
                                    <h5 class="text-primary"><i class="fa fa-link mr-2"></i>'.tr('Riferimenti').'</h5>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-3">
                                    {[ "type": "select", "name": "selezione_riferimento['.$key.']", "ajax-source": "riferimenti-fe", "select-options": '.json_encode(['id_anagrafica' => $anagrafica ? $anagrafica->id : '']).', "label": "'.tr('Riferimento acquisto').'", "icon-after": '.json_encode('<button type="button" onclick="rimuoviRiferimento(this)" class="btn btn-danger disabled" id="rimuovi_riferimento_'.$key.'"><i class="fa fa-close"></i></button>').', "help": "'.tr('Articoli contenuti in Ordini o DDT del Fornitore').'" ]}
                                </div>

                                <div class="col-md-3">
                                    {[ "type": "select", "name": "selezione_riferimento_vendita['.$key.']", "ajax-source": "riferimenti-vendita-fe", "select-options": '.json_encode(['id_articolo' => $id_articolo]).', "label": "'.tr('Riferimento vendita').'", "icon-after": '.json_encode('<button type="button" onclick="rimuoviRiferimentoVendita(this)" class="btn btn-danger disabled" id="rimuovi_riferimento_vendita_'.$key.'"><i class="fa fa-close"></i></button>').', "help": "'.tr('Articoli contenuti in Ordini Cliente').'" ]}
                                </div>

                                <div class="col-md-6">
                                    {[ "type": "select", "name": "update_info['.$key.']", "values": "list=\"update_not\":\"Nessuna operazione\", \"update_price\":\"Crea listino del fornitore (se non presente) e aggiorna il prezzo di acquisto\", \"update_all\":\"Crea listino del fornitore (se non presente) aggiorna prezzo di acquisto e imposta fornitore come predefinito\"", "label": "'.tr('Aggiorna informazioni di acquisto').'", "value": "'.$update_info.'", "help": "'.tr('Creazione automatica articolo deve essere attiva o l\'articolo deve essere selezionato affinché questa impostazione abbia effetto').'.", "readonly": "'.(empty($codice_principale) ? 1 : 0).'" ]}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>';

        if (!empty($serial)) {
            echo '
                <div class="row mt-2">
                    <div class="col-md-12">
                        <div class="card card-outline card-warning">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="fa fa-barcode mr-2"></i>'.tr('Serial number').'
                                </h3>
                            </div>
                            <div class="card-body p-3">';

            foreach ($serial as $s) {
                echo '
                                <div class="col-md-4">
                                    {[ "type": "text", "label": "'.tr('Serial').'", "name": "serial['.$key.'][]", "value": "'.$s.'" ]}
                                </div>';
            }

            echo '
                            </div>
                        </div>
                    </div>
                </div>';
        }

        // Riferimento ordine
        if (!empty($dati_ordini[(int) $riga['NumeroLinea']])) {
            $riferimento = $dati_ordini[(int) $riga['NumeroLinea']];

            $query = "SELECT
                `or_ordini`.`id`,
                CONCAT('Ordine ', `or_ordini`.`numero`, ' del ', DATE_FORMAT(`or_ordini`.`data`, '%d/%m/%Y')) AS descrizione
            FROM `or_ordini`
                INNER JOIN `or_righe_ordini` ON `or_righe_ordini`.`idordine` = `or_ordini`.`id`
            WHERE
                `or_ordini`.`idanagrafica` = ".prepare($anagrafica->id).' AND
                `or_ordini`.`numero` = '.prepare($riferimento['numero'])." AND
                DATE_FORMAT(`or_ordini`.`data`, '%d/%m/%Y') = ".prepare($riferimento['data']).'
            GROUP BY `or_ordini`.`id`';

            $ordini = $database->fetchArray($query);

            if (!empty($ordini)) {
                echo '
                <div class="row">
                    <div class="col-md-12">
                        <div class="box box-info">
                            <div class="box-header with-border">
                                <h3 class="box-title">'.tr('Riferimento ordine').'</h3>
                            </div>
                            <div class="box-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        {[ "type": "select", "label": "'.tr('Ordine').'", "name": "selezione_riferimento_vendita['.$key.']", "values": "query='.$query.'", "onchange": "aggiornaRiferimento(this, '.$key.')" ]}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>';
            }
        }

        echo '
            </td>
        </tr>';
    }

    echo '
                </tbody>
            </table>
        </div>
    </div>
</div>';
}

echo '
    <div class="row">
        <div class="col-md-12 text-right">
            <button type="button" class="btn btn-warning" onclick="skip()">
                <i class="fa fa-ban"></i> '.tr('Salta fattura').'
            </button>

            <button type="submit" class="btn btn-primary">
                <i class="fa fa-plus"></i> '.tr('Crea fattura').'
            </button>
        </div>
    </div>
</form>

<script>
function copia() {
    let conti = $("select[name^=conti]");
    let iva = $("select[name^=iva]");
    let update_info = $("select[name^=update_info]");

    // Individuazione del primo conto selezionato
    let conto_selezionato = null;
    for (const conto of conti) {
        const data = $(conto).selectData();
        if (data && data.id) {
            conto_selezionato = data;
            break;
        }
    }

    // Individuazione della prima iva selezionata
    let iva_selezionata = null;
    for (const i of iva) {
        const data = $(i).selectData();
        if (data && data.id) {
            iva_selezionata = data;
            break;
        }
    }

    // Individuazione del primo update_info selezionato
    let update_info_selezionato = null;
    for (const info of update_info) {
        const data = $(info).selectData();
        if (data && data.id) {
            update_info_selezionato = data;
            break;
        }
    }

    // Selezione generale per il conto
    if (conto_selezionato) {
        conti.each(function() {
            $(this).selectSetNew(conto_selezionato.id, conto_selezionato.text, conto_selezionato);
        });
    }

    // Selezione generale per l\'iva
    if (iva_selezionata) {
        iva.each(function() {
            $(this).selectSetNew(iva_selezionata.id, iva_selezionata.text, iva_selezionata);
        });
    }

    // Selezione generale per l\'update_info
    if (update_info_selezionato) {
        update_info.each(function() {
            $(this).selectSetNew(update_info_selezionato.id, update_info_selezionato.text, update_info_selezionato);
        });
    }
}

function aggiornaRiferimento(select, id) {
    let data = $(select).selectData();

    if (data) {
        $.ajax({
            url: globals.rootdir + "/ajax_complete.php",
            type: "get",
            dataType: "json",
            data: {
                op: "riferimento_vendita",
                id_ordine: data.id,
                id_riga: id,
            },
            success: function(response) {
                if (response.result) {
                    $("#riferimento_" + id).html(response.text);
                    $("#riferimento_" + id + "_descrizione").html(response.descrizione);
                    $("#riferimento_" + id + "_qta").html(response.qta);
                    $("#riferimento_" + id + "_prezzo").html(response.prezzo);
                    $("#riferimento_" + id + "_iva").html(response.iva);
                } else {
                    $("#riferimento_" + id).html("");
                    $("#riferimento_" + id + "_descrizione").html("");
                    $("#riferimento_" + id + "_qta").html("");
                    $("#riferimento_" + id + "_prezzo").html("");
                    $("#riferimento_" + id + "_iva").html("");
                }
            }
        });
    } else {
        $("#riferimento_" + id).html("");
        $("#riferimento_" + id + "_descrizione").html("");
        $("#riferimento_" + id + "_qta").html("");
        $("#riferimento_" + id + "_prezzo").html("");
        $("#riferimento_" + id + "_iva").html("");
    }
}

function rimuoviRiferimento(button) {
    let riga = $(button).closest("tr").prev();
    let id_riga = riga.data("id");

    impostaRiferimento(id_riga, {}, {});

    input("selezione_riferimento[" + id_riga + "]").enable()
        .getElement().selectReset();
    $(button).addClass("disabled");
    riga.removeClass("success").removeClass("warning");
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
    tooltip_icona = documento.match_documento_da_fe ? "La corrispondenza trovata è avvenuta in base a quanto ha specificato il fornitore nella fattura elettronica" : "Nessuna corrispondenza con quanto ha specificato il fornitore nella fattura elettronica, il riferimento potrebbe non essere corretto";

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
            input("conti["+id_riga+"]").getElement().selectSetNew(riga.id_conto, riga.desc_conto.replace(/_/g, " ").replace(/\n/g, "<br>"));
        }
    }
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

function skip() {
    redirect("'.$skip_link.'");
}

function verificaSerial(select) {
    let data = $(select).selectData();
    let id = $(select).attr("name").split("[")[1].split("]")[0];
    let seriali = $("[name^=\'serial[" + id + "]\']");

    if (data && data.abilita_serial == 1) {
        seriali.attr("disabled", !$("#flag_crea_seriali").is(":checked"));
    } else {
        seriali.attr("disabled", true);
    }
}

$("#flag_crea_seriali").on("change", function() {
    let articoli = $("select[name^=articoli]");
    articoli.each(function() {
        verificaSerial($(this));
    });
});

$("#flag_crea_articoli").on("change", function() {
    if ($(this).is(":checked")) {
        $(".articolo-warning").removeClass("hidden");
    } else {
        $(".articolo-warning").addClass("hidden");
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

// Funzione per aggiornare la progress bar
function updateProgressBar(current, total) {
    // Verifica se la barra di progresso esiste
    if ($("#import-progress-bar").length === 0) {
        return;
    }

    // Assicurati che current e total siano numeri validi
    current = parseInt(current);
    total = parseInt(total);

    if (isNaN(current) || current < 1) current = 1;
    if (isNaN(total) || total < 1) total = 1;
    if (total < current) total = current;

    let percentage = Math.round((current / total) * 100);
    percentage = Math.min(percentage, 100);

    $("#import-progress-bar").css("width", percentage + "%")
                            .attr("aria-valuenow", percentage)
                            .text(percentage + "%");

    // Aggiorna anche il testo sotto la barra di progresso
    $("#progress-text").text("'.tr('Importazione in sequenza').': " + current + " '.tr('di').' " + total);

    // Debug
    console.log("UpdateProgressBar - Current:", current, "Total:", total, "Percentage:", percentage + "%");
}

// Inizializza la progress bar con i dati correnti solo se siamo in modalità importazione in sequenza
$(document).ready(function() {
    // Verifica se siamo in modalità importazione in sequenza
    let isSequence = '.(get('sequence') == 1 ? 'true' : 'false').';

    // Se non siamo in modalità importazione in sequenza, non mostrare la barra di progresso
    if (!isSequence) {
        return;
    }

    // Debug
    console.log("Inizializzazione progress bar - Sequence:", isSequence);

    let currentIndex = parseInt('.$id_record.');
    if (isNaN(currentIndex) || currentIndex < 1) {
        currentIndex = 1;
    }

    // Debug
    console.log("ID record corrente:", currentIndex);

    // Verifica se ci sono altri documenti dopo questo
    let hasNext = '.($has_next ? 'true' : 'false').';
    console.log("Has next:", hasNext);

    // Mostra una stima iniziale in base a hasNext
    if (hasNext) {
        let minTotalDocuments = currentIndex + 1;
        console.log("Stima iniziale documenti totali:", minTotalDocuments);
        updateProgressBar(currentIndex, minTotalDocuments);
    } else {
        console.log("Nessun documento successivo rilevato");
        updateProgressBar(currentIndex, currentIndex);
    }

    // Ottieni il numero totale di documenti da importare
    $.ajax({
        url: globals.rootdir + "/actions.php",
        data: {
            op: "list",
            id_module: "'.$id_module.'",
            id_plugin: "'.$id_plugin.'",
        },
        type: "post",
        dataType: "json",
        success: function(data) {
            try {
                console.log("Risposta AJAX ricevuta:", data);

                // Assicurati che i dati siano in formato JSON
                let jsonData = data;
                if (typeof data === "string") {
                    try {
                        jsonData = JSON.parse(data);
                        console.log("Dati convertiti da stringa a JSON:", jsonData);
                    } catch (e) {
                        console.error("Errore nel parsing JSON:", e);
                    }
                }

                // Verifica che jsonData sia un array
                if (!Array.isArray(jsonData)) {
                    console.error("I dati ricevuti non sono un array:", jsonData);

                    if (jsonData && typeof jsonData === "object") {
                        for (let key in jsonData) {
                            if (Array.isArray(jsonData[key])) {
                                console.log("Trovato array in proprietà:", key);
                                jsonData = jsonData[key];
                                break;
                            }
                        }
                    }

                    // Se ancora non è un array, usa una stima
                    if (!Array.isArray(jsonData)) {
                        console.log("Impossibile trovare un array nei dati, uso stima");
                        if (hasNext) {
                            updateProgressBar(currentIndex, currentIndex + 1);
                        } else {
                            updateProgressBar(currentIndex, currentIndex);
                        }
                        return;
                    }
                }

                let totalDocuments = jsonData.length;
                console.log("Numero totale di documenti trovati:", totalDocuments);

                // Se non ci sono documenti, usa 1 come fallback
                if (totalDocuments === 0) {
                    console.log("Nessun documento trovato, uso 1 come fallback");
                    totalDocuments = 1;
                }

                // Assicurati che totalDocuments sia almeno uguale a currentIndex
                if (totalDocuments < currentIndex) {
                    console.log("Total documents < currentIndex, aggiorno a:", currentIndex);
                    totalDocuments = currentIndex;
                }

                console.log("Documento corrente:", currentIndex, "Totale documenti:", totalDocuments);

                // Aggiorna la barra di progresso con i valori corretti
                updateProgressBar(currentIndex, totalDocuments);
            } catch (e) {
                console.error("Errore nell\'elaborazione dei dati:", e);
                // In caso di errore, usa una stima basata su hasNext
                if (hasNext) {
                    updateProgressBar(currentIndex, currentIndex + 1);
                } else {
                    updateProgressBar(currentIndex, currentIndex);
                }
            }
        },
        error: function(xhr, status, error) {
            console.error("Errore nella richiesta AJAX:", error);
            // In caso di errore, usa una stima basata su hasNext
            if (hasNext) {
                updateProgressBar(currentIndex, currentIndex + 1);
            } else {
                updateProgressBar(currentIndex, currentIndex);
            }
        }
    });
});

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

function updateSelectOption(option, value) {
    let select = $("select[name=pagamento]");
    let data = select.selectData();

    select.selectReset();
    select.selectSetNew(data.id, data.text);
}

function toggleRiferimenti(key) {
    const body = $("#riferimenti-body-" + key);
    const icon = $("#toggle-icon-" + key);

    if (body.is(":visible")) {
        body.hide(300);
        icon.removeClass("fa-minus").addClass("fa-plus");
    } else {
        body.show(300);
        icon.removeClass("fa-plus").addClass("fa-minus");
    }
}
</script>';
