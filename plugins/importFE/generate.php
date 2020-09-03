<?php

include_once __DIR__.'/../../core.php';

echo '
<script>
$(document).ready(function() {
    $("#save").hide();
});
</script>';

$skip_link = $has_next ? ROOTDIR.'/editor.php?id_module='.$id_module.'&id_plugin='.$id_plugin.'&id_record='.($id_record + 1).'&sequence='.get('sequence') : ROOTDIR.'/editor.php?id_module='.$id_module;

if (empty($fattura_pa)) {
    if (!empty($error)) {
        echo '
<p>'.tr("Errore durante l'apertura della fattura elettronica _NAME_", [
    '_NAME_' => $record['name'],
]).'.</p>';
    } elseif (!empty($imported)) {
        echo '
<p>'.tr('La fattura elettrnica _NAME_ è già stata importata in passato', [
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
$dati_generali = $fattura_pa->getBody()['DatiGenerali']['DatiGeneraliDocumento'];

$tipo_documento = $database->fetchOne('SELECT CONCAT("(", codice, ") ", descrizione) AS descrizione FROM fe_tipi_documento WHERE codice = '.prepare($dati_generali['TipoDocumento']))['descrizione'];

$pagamenti = $fattura_pa->getBody()['DatiPagamento'];
$metodi = $pagamenti['DettaglioPagamento'];
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

			    '.(empty($anagrafica) ? '<span class="badge badge-success">'.tr('Nuova anagrafica').'</span>' : '<small>'.Modules::link('Anagrafiche', $anagrafica->id, '', null, '')).'</small><br>

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

    // Scadenze di pagamento
    foreach ($metodi as $metodo) {
        $descrizione = !empty($metodo['ModalitaPagamento']) ? $database->fetchOne('SELECT descrizione FROM fe_modalita_pagamento WHERE codice = '.prepare($metodo['ModalitaPagamento']))['descrizione'] : '';
        $data = !empty($metodo['DataScadenzaPagamento']) ? Translator::dateToLocale($metodo['DataScadenzaPagamento']).' ' : '';

        echo '
				<li>
				    '.$data.'
				    '.moneyFormat($metodo['ImportoPagamento']).'
                    ('.$descrizione.')
                </li>';
    }

    echo '
            </ol>
        </div>';
}

echo '
	</div>';

// Tipo del documento
$query = "SELECT id, CONCAT (descrizione, IF((codice_tipo_documento_fe IS NULL), '', CONCAT(' (', codice_tipo_documento_fe, ')' ) )) AS descrizione FROM co_tipidocumento WHERE dir = 'uscita'";
$query_tipo = $query.' AND codice_tipo_documento_fe = '.prepare($dati_generali['TipoDocumento']);
if ($database->fetchNum($query_tipo)) {
    $query = $query_tipo;
}

echo '
    <div class="row">
        <div class="col-md-3">
            {[ "type": "select", "label": "'.tr('Tipo fattura').'", "name": "id_tipo", "required": 1, "values": "query='.$query.'" ]}
        </div>';

// Sezionale
echo '
        <div class="col-md-3">
            {[ "type": "select", "label": "'.tr('Sezionale').'", "name": "id_segment", "required": 1, "values": "query=SELECT id, name AS descrizione FROM zz_segments WHERE is_fiscale = 1 AND id_module='.$id_module.' ORDER BY name", "value": "'.$_SESSION['module_'.$id_module]['id_segment'].'" ]}
        </div>';

// Data di registrazione
$data_registrazione = get('data_registrazione');
$data_registrazione = new \Carbon\Carbon($data_registrazione);
echo '
        <div class="col-md-3">
            {[ "type": "date", "label": "'.tr('Data di registrazione').'", "name": "data_registrazione", "required": 1, "value": "'.($data_registrazione ?: $dati_generali['Data']).'", "max-date": "-now-", "min-date": "'.$dati_generali['Data'].'" ]}
        </div>';

if (!empty($anagrafica)) {
    $query = "SELECT
            co_documenti.id,
            CONCAT('Fattura num. ', co_documenti.numero_esterno, ' del ', DATE_FORMAT(co_documenti.data, '%d/%m/%Y')) AS descrizione
        FROM co_documenti
            INNER JOIN co_tipidocumento ON co_tipidocumento.id = co_documenti.idtipodocumento
        WHERE
            co_tipidocumento.dir = 'uscita' AND
            (co_documenti.data BETWEEN NOW() - INTERVAL 1 YEAR AND NOW()) AND
            co_documenti.idstatodocumento IN (SELECT id FROM co_statidocumento WHERE descrizione != 'Bozza') AND
            co_documenti.idanagrafica = ".prepare($anagrafica->id);

    // Riferimenti ad altre fatture
    if (in_array($dati_generali['TipoDocumento'], ['TD04', 'TD05'])) {
        echo '
        <div class="col-md-3">
            {[ "type": "select", "label": "'.tr('Fattura collegata').'", "name": "ref_fattura", "required": 1, "values": "query='.$query.'" ]}
        </div>';
    } elseif ($dati_generali['TipoDocumento'] == 'TD06') {
        $query .= "AND co_documenti.id_segment = (SELECT id FROM zz_segments WHERE name = 'Fatture pro-forma' AND id_module = ".prepare($id_module).')';

        echo '
        <div class="col-md-3">
            {[ "type": "select", "label": "'.tr('Fattura pro-forma').'", "name": "ref_fattura", "values": "query='.$query.'" ]}
        </div>';
    }
}

echo '
    </div>';

// Pagamento
echo '
    <div class="row" >
		<div class="col-md-6">
		    <button type="button" class="btn btn-info btn-xs pull-right" onclick="updateSelectOption(\'codice_modalita_pagamento_fe\', \'\')">
		        <i class="fa fa-refresh"></i> '.tr('Visualizza tutte le modalità').'
            </button>

            {[ "type": "select", "label": "'.tr('Pagamento').'", "name": "pagamento", "required": 1, "ajax-source": "pagamenti", "select-options": '.json_encode(['codice_modalita_pagamento_fe' => $codice_modalita_pagamento]).' ]}
        </div>';

// Movimentazioni
echo '
        <div class="col-md-3">
            {[ "type": "checkbox", "label": "'.tr('Movimenta gli articoli').'", "name": "movimentazione", "value": 1 ]}
        </div>

        <div class="col-md-3">
            {[ "type": "checkbox", "label": "'.tr('Creazione automatica articoli').'", "name": "crea_articoli", "value": 0, "help": "'.tr("Nel caso di righe con tag CodiceArticolo, il gestionale procede alla creazione dell'articolo se la riga non risulta assegnata manualmente").'" ]}
        </div>
    </div>';

// Righe
$righe = $fattura_pa->getRighe();

if (!empty($righe)) {
    echo '
    <h4>
        '.tr('Righe').'
        <button type="button" class="btn btn-info btn-sm pull-right" onclick="copy()"><i class="fa fa-copy"></i> '.tr('Copia dati contabili dalla prima riga valorizzata').'</button>
        <div class="clearfix"></div>
    </h4>

    <div class="table-responsive">
        <table class="table table-striped table-hover table-condensed table-bordered">
            <thead>
                <tr>
                    <th>'.tr('Descrizione').'</th>
                    <th class="text-center" width="10%">'.tr('Quantità').'</th>
                    <th class="text-center" width="10%">'.tr('Prezzo unitario').'</th>
                    <th class="text-center" width="10%">'.tr('Aliquota').'</th>
                </tr>
            </thead>

            <tbody>';

    foreach ($righe as $key => $riga) {
        $query = 'SELECT id, IF(codice IS NULL, descrizione, CONCAT(codice, " - ", descrizione)) AS descrizione FROM co_iva WHERE percentuale = '.prepare($riga['AliquotaIVA']);

        if (!empty($riga['Natura'])) {
            $query .= ' AND codice_natura_fe = '.prepare($riga['Natura']);
        }

        $query .= ' ORDER BY descrizione ASC';

        // Visualizzazione codici articoli
        $codici = $riga['CodiceArticolo'] ?: [];
        $codici = !empty($codici) && !isset($codici[0]) ? [$codici] : $codici;

        $codici_articoli = [];
        foreach ($codici as $codice) {
            $codici_articoli[] = $codice['CodiceValore'].' ('.$codice['CodiceTipo'].')';
        }

        // Individuazione articolo con codice relativo
        $id_articolo = null;
        $codice_principale = $codici[0]['CodiceValore'];
        if (!empty($codice_principale)) {
            $id_articolo = $database->fetchOne('SELECT id FROM mg_articoli WHERE codice = '.prepare($codice_principale))['id'];
        }

        $qta = $riga['Quantita'];
        $um = $riga['UnitaMisura'];
        $prezzo_unitario = $riga['PrezzoUnitario'];

        echo '
        <tr data-id="'.$key.'" data-qta="'.$qta.'">
            <td>
                '.$riga['Descrizione'].'<br>

				'.(!empty($codici_articoli) ? '<small>'.implode(', ', $codici_articoli).'</small><br>' : '').'
                <span id="riferimento_'.$key.'_descrizione"></span>
                <span id="riferimento_'.$key.'"></span>
            </td>

            <td class="text-center">
                '.numberFormat($qta, 'qta').' '.$um.'
                <span id="riferimento_'.$key.'_qta"></span>
            </td>

            <td class="text-right">
                '.moneyFormat($prezzo_unitario).'
                <span id="riferimento_'.$key.'_prezzo"></span>
            </td>

            <td class="text-right">
                '.replace('_VALUE_ _DESC_', [
                    '_VALUE_' => empty($riga['Natura']) ? numberFormat($riga['AliquotaIVA']).'%' : $riga['Natura'],
                    '_DESC_' => $riga['RiferimentoNormativo'] ? ' - '.$riga['RiferimentoNormativo'] : '',
                ]).'
            </td>
        </tr>

        <tr id="dati_'.$key.'">
            <td colspan="4" class="row">
                <span class="hide" id="aliquota['.$key.']">'.$riga['AliquotaIVA'].'</span>
                <input type="hidden" name="qta_riferimento['.$key.']" id="qta_riferimento_'.$key.'" value="'.$riga['Quantita'].'">

                <input type="hidden" name="tipo_riferimento['.$key.']" id="tipo_riferimento_'.$key.'" value="">
                <input type="hidden" name="id_riferimento['.$key.']" id="id_riferimento_'.$key.'" value="">
                <input type="hidden" name="id_riga_riferimento['.$key.']" id="id_riga_riferimento_'.$key.'" value="">
                <input type="hidden" name="tipo_riga_riferimento['.$key.']" id="tipo_riga_riferimento_'.$key.'" value="">

                <div class="col-md-3">
                    {[ "type": "select", "name": "articoli['.$key.']", "ajax-source": "articoli", "select-options": '.json_encode(['permetti_movimento_a_zero' => 1, 'dir' => 'entrata', 'idanagrafica' => $anagrafica ? $anagrafica->id : '']).', "icon-after": "add|'.Modules::get('Articoli')['id'].'|codice='.htmlentities($codice_principale).'&descrizione='.htmlentities($riga['Descrizione']).'", "value": "'.$id_articolo.'", "label": "'.tr('Articolo').'" ]}
                </div>

                <div class="col-md-3">
                    {[ "type": "select", "name": "conto['.$key.']", "ajax-source": "conti-acquisti", "required": 1, "label": "'.tr('Conto acquisti').'" ]}
                </div>

                <div class="col-md-3">
                    {[ "type": "select", "name": "iva['.$key.']", "values": '.json_encode('query='.$query).', "required": 1, "label": "'.tr('Aliquota IVA').'" ]}
                </div>

                <div class="col-md-3">
                    {[ "type": "select", "name": "selezione_riferimento['.$key.']", "ajax-source": "riferimenti-fe", "select-options": '.json_encode(['id_anagrafica' => $anagrafica ? $anagrafica->id : '']).', "required": 1, "label": "'.tr('Riferimento').'", "icon-after": '.json_encode('<button type="button" onclick="rimuoviRiferimento(this)" class="btn btn-primary disabled" id="rimuovi_riferimento_'.$key.'"><i class="fa fa-close"></i></button>').' ]}
                </div>
            </td>
        </tr>';
    }

    echo '
            </tbody>
        </table>
    </div>';

    echo '
    <script>
    function copy() {
        let first_iva = null;
        let first_conto = null;

        $("select[name^=iva").each(function() {
            if ($(this).val() != "" && first_iva == null){
                first_iva = $(this);
            }
        });

        $("select[name^=conto").each(function() {
            if ($(this).val() != "" && first_conto == null) {
                first_conto = $(this);
            }
        });

        if(first_iva) {
            $iva = first_iva.selectData();

            $("select[name^=iva").each(function() {
                $(this).selectSet($iva.id);
            });
        }

        if(first_conto) {
            $conto = first_conto.selectData();

            $("select[name^=conto").each(function() {
                $(this).selectSetNew($conto.id, $conto.text);
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
 $("select[name^=selezione_riferimento").change(function() {
    let $this = $(this);
    let data = $this.selectData();

    if (data) {
        let riga = $this.closest("tr").prev();
        selezionaRiferimento(riga, data.tipo, data.id);
    }
});

function rimuoviRiferimento(button) {
    let riga = $(button).closest("tr").prev();
    let id_riga = riga.data("id");

    impostaRiferimento(id_riga, {}, {});

    input("selezione_riferimento[" + id_riga + "]").enable()
        .getElement().selectReset();
    $(button).addClass("disabled");
}

function selezionaRiferimento(riga, tipo_documento, id_documento) {
    let id_riga = riga.data("id");
    let qta = riga.data("qta");

    let riferimenti = getRiferimenti();
    let query = {
        id_module: "'.$id_module.'",
        id_record: "'.$id_record.'",
        qta: qta,
        id_riga: id_riga,
        id_documento: id_documento,
        tipo_documento: tipo_documento,
        righe_ddt: riferimenti.ddt,
        righe_ordini: riferimenti.ordini,
    };

    let url = "'.$structure->fileurl('riferimento.php').'?" + Object.keys(query).map(function(k) {
        return encodeURIComponent(k) + "=" + encodeURIComponent(query[k])
    }).join("&")

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
    $("#tipo_riferimento_" + id_riga).val(documento.tipo);
    $("#id_riferimento_" + id_riga).val(documento.id);

    $("#tipo_riga_riferimento_" + id_riga).val(riga.tipo);
    $("#id_riga_riferimento_" + id_riga).val(riga.id);

    $("#riferimento_" + id_riga).html(documento.descrizione ? documento.descrizione : "");

    input("selezione_riferimento[" + id_riga + "]").disable();
    $("#rimuovi_riferimento_" + id_riga).removeClass("disabled");
}
</script>';
