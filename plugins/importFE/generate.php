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
echo '
        <div class="col-md-3">
            {[ "type": "date", "label": "'.tr('Data di registrazione').'", "name": "data_registrazione", "required": 1, "value": "'.(get('data_registrazione') ?: $dati_generali['Data']).'", "max-date": "-now-", "min-date": "'.$dati_generali['Data'].'", "readonly": "'.(intval(get('data_registrazione') != null)).'" ]}
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

if (!empty($codice_modalita_pagamento)) {
    $_SESSION['superselect']['codice_modalita_pagamento_fe'] = $codice_modalita_pagamento;
}

// Pagamento
echo '
    <div class="row" >
		<div class="col-md-6">
		    <button type="button" class="btn btn-info btn-xs pull-right" onclick="session_set(\'superselect,codice_modalita_pagamento_fe\', \'\', 0)">
		        <i class="fa fa-refresh"></i> '.tr('Reset modalità').'
            </button>
		    
            {[ "type": "select", "label": "'.tr('Pagamento').'", "name": "pagamento", "required": 1, "ajax-source": "pagamenti" ]}
        </div>';

// Movimentazioni
echo '
        <div class="col-md-6">
            {[ "type": "checkbox", "label": "'.tr('Movimenta gli articoli').'", "name": "movimentazione", "value": 1 ]}
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
        <table class="table table-hover table-striped table-condensed">
            <tr>
                <th>'.tr('Descrizione').'</th>
                <th width="25%">'.tr('Dati contabili').'*</th>
                <th width="25%">'.tr('Articolo').'</th>
            </tr>';

    foreach ($righe as $key => $riga) {
        $query = 'SELECT id, IF(codice IS NULL, descrizione, CONCAT(codice, " - ", descrizione)) AS descrizione FROM co_iva WHERE percentuale = '.prepare($riga['AliquotaIVA']);

        if (!empty($riga['Natura'])) {
            $query .= ' AND codice_natura_fe = '.prepare($riga['Natura']);
        }

        $query .= ' ORDER BY descrizione ASC';

        /*Visualizzo codici articoli*/
        $codici_articoli = '';

        //caso di un solo codice articolo
        if (isset($riga['CodiceArticolo']) and empty($riga['CodiceArticolo'][0]['CodiceValore'])) {
            $riga['CodiceArticolo'][0]['CodiceValore'] = $riga['CodiceArticolo']['CodiceValore'];
            $riga['CodiceArticolo'][0]['CodiceTipo'] = $riga['CodiceArticolo']['CodiceTipo'];
        }

        foreach ($riga['CodiceArticolo'] as $key2 => $item) {
            foreach ($item as $key2 => $name) {
                if ($key2 == 'CodiceValore') {
                    if (!empty($item['CodiceValore'])) {
                        $codici_articoli .= '<small>'.$item['CodiceValore'].' ('.$item['CodiceTipo'].')</small>';

                        if (($item['CodiceValore'] != end($riga['CodiceArticolo'][(count($riga['CodiceArticolo']) - 1)])) and (is_array($riga['CodiceArticolo'][1]))) {
                            $codici_articoli .= ', ';
                        }
                    }
                }
            }
        }

        echo '
        <tr>
            <td>
                '.$riga['Descrizione'].'<br>
				
				'.(($codici_articoli != '') ? $codici_articoli.'<br>' : '').'
                
                <small>'.tr('Q.tà: _QTA_ _UM_', [
                    '_QTA_' => Translator::numberToLocale($riga['Quantita']),
                    '_UM_' => $riga['UnitaMisura'],
                ]).'</small><br>
                
                <small>'.tr('Aliquota IVA: _VALUE_ _DESC_', [
                    '_VALUE_' => empty($riga['Natura']) ? numberFormat($riga['AliquotaIVA']).'%' : $riga['Natura'],
                    '_DESC_' => $riga['RiferimentoNormativo'] ? ' - '.$riga['RiferimentoNormativo'] : '',
                ]).'</small>
            </td>
            <td>
                {[ "type": "select", "name": "iva['.$key.']", "values": "query='.str_replace('"', '\"', $query).'", "required": 1, "placeholder": "Aliquota iva" ]}
                <br>
                {[ "type": "select", "name": "conto['.$key.']", "ajax-source": "conti-acquisti", "required": 1, "placeholder": "Conto acquisti" ]}
            </td>
            <td>
                {[ "type": "select", "name": "articoli['.$key.']", "ajax-source": "articoli", "class": "", "icon-after": "add|'.Modules::get('Articoli')['id'].'|codice='.htmlentities($riga['CodiceArticolo'][0]['CodiceValore']).'&descrizione='.htmlentities($riga['Descrizione']).'" ]}
            </td>
        </tr>';
    }

    echo '
        </table>
    </div>';

    echo '
    <script>
    function copy(){
        var first_iva = null;
        var first_conto = null;

        $("select[name^=iva").each( function(){
            if( $(this).val() != "" && first_iva == null ){
                first_iva = $(this);
            }
        });

        $("select[name^=conto").each( function(){
            if( $(this).val() != "" && first_conto == null ){
                first_conto = $(this);
            }
        });

        if(first_iva) {
            $iva = first_iva.selectData();

            $("select[name^=iva").each(function(){
                $(this).selectSet($iva.id);
            });
        }

        if(first_conto) {
            $conto = first_conto.selectData();

            $("select[name^=conto").each(function(){
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
</form>';
