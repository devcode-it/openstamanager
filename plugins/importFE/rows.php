<?php

include_once __DIR__.'/../../core.php';

$fattura_pa = new Plugins\ImportFE\FatturaElettronica(get('filename'));

echo '
<form action="'.$rootdir.'/actions.php" method="post">
    <input type="hidden" name="id_module" value="'.$id_module.'">
    <input type="hidden" name="id_plugin" value="'.$id_plugin.'">
    <input type="hidden" name="filename" value="'.get('filename').'">
    <input type="hidden" name="id_segment" value="'.get('id_segment').'">
    <input type="hidden" name="id" value="'.get('id').'">
    <input type="hidden" name="backto" value="record-edit">
    <input type="hidden" name="op" value="generate">';

// Fornitore
$fornitore = $fattura_pa->getHeader()['CedentePrestatore']['DatiAnagrafici'];
$ragione_sociale = $fornitore['Anagrafica']['Denominazione'] ?: $fornitore['Anagrafica']['Nome'].' '.$fornitore['Anagrafica']['Cognome'];
$codice_fiscale = $fornitore['CodiceFiscale'];
$partita_iva = $fornitore['IdFiscaleIVA']['IdCodice'];

$sede = $fattura_pa->getHeader()['CedentePrestatore']['Sede'];

$cap = $sede['CAP'];
$citta = $sede['Comune'];
$provincia = $sede['Provincia'];

// Dati generali
$dati_generali = $fattura_pa->getBody()['DatiGenerali']['DatiGeneraliDocumento'];
$descrizione_documento = database()->fetchOne('SELECT CONCAT("(", codice, ") ", descrizione) AS descrizione FROM fe_tipi_documento WHERE codice = '.prepare($dati_generali['TipoDocumento']));

echo '
    <div class="row" >
		<div class="col-md-6">
			<h4>'.
    $ragione_sociale.'<br>
				<small>
					'.(!empty($codice_fiscale) ? (tr('Codice Fiscale').': '.$codice_fiscale.'<br>') : '').'
					'.(!empty($partita_iva) ? (tr('Partita IVA').': '.$partita_iva.'<br>') : '').'
					'.$cap.' '.$citta.' ('.$provincia.')<br>
				</small>
			</h4>
		</div>
		
		<div class="col-md-6">
			<h4>'.$dati_generali['Numero'];
		echo '
				<a href="'.ROOTDIR.'/view.php?file_id='.get('id').'" class="btn btn-info btn-xs" target="_blank" >
					<i class="fa fa-eye"></i> '.tr('Visualizza').'
				</a><br>
				<small>
					'.database()->fetchOne('SELECT CONCAT("(", codice, ") ", descrizione) AS descrizione FROM fe_tipi_documento WHERE codice = '.prepare($dati_generali['TipoDocumento']))['descrizione'].'
					<br>'.Translator::dateToLocale($dati_generali['Data']).'
					<br>'.$dati_generali['Divisa'].'
				</small>
			</h4>
		</div>
	</div>';

// Tipo del documento
$query = 'SELECT id, CONCAT (descrizione, IF((codice_tipo_documento_fe IS NULL), \'\', CONCAT( \' (\', codice_tipo_documento_fe, \')\' ) )) as descrizione FROM co_tipidocumento WHERE dir = \'uscita\'';
if (database()->fetchNum('SELECT id FROM co_tipidocumento WHERE codice_tipo_documento_fe = '.prepare($dati_generali['TipoDocumento']))) {
    $query .= ' AND codice_tipo_documento_fe = '.prepare($dati_generali['TipoDocumento']);
}
echo '
    <div class="row" >
		<div class="col-md-6">
            {[ "type": "select", "label": "'.tr('Tipo fattura').'", "name": "id_tipo", "required": 1, "values": "query='.$query.'" ]}
        </div>';

// Sezionale
echo '
        <div class="col-md-6">
            {[ "type": "select", "label": "'.tr('Sezionale').'", "name": "id_segment", "required": 1, "values": "query=SELECT id, name AS descrizione FROM zz_segments WHERE id_module='.$id_module.' ORDER BY name", "value": "'.$_SESSION['module_'.$id_module]['id_segment'].'" ]}
        </div>
    </div>';

// Blocco DatiPagamento è valorizzato (opzionale)
$pagamenti = $fattura_pa->getBody()['DatiPagamento'];
if (!empty($pagamenti)) {
    $metodi = $pagamenti['DettaglioPagamento'];
    $metodi = isset($metodi[0]) ? $metodi : [$metodi];

    echo '
		<h4>'.tr('Pagamento').'</h4>

        <p>'.tr('La fattura importata presenta _NUM_ rat_E_ di pagamento con le seguenti scadenze', [
            '_NUM_' => count($metodi),
            '_E_' => ((count($metodi) > 1) ? 'e' : 'a'),
        ]).':</p>
		<ol>';

    // Scadenze di pagamento
    foreach ($metodi as $metodo) {
        echo '
				<li>';

        //DataScadenzaPagamento è un nodo opzionale per il blocco DatiPagamento
        if (!empty($metodo['DataScadenzaPagamento'])) {
            echo Translator::dateToLocale($metodo['DataScadenzaPagamento']).' ';
        }

        $descrizione = !empty($metodo['ModalitaPagamento']) ? database()->fetchOne('SELECT descrizione FROM fe_modalita_pagamento WHERE codice = '.prepare($metodo['ModalitaPagamento']))['descrizione'] : '';

        echo Translator::numberToLocale($metodo['ImportoPagamento']).' &euro; 
            ('.$descrizione.')
            </li>';
    }

    echo '
		</ol>';
}

// prc '.($pagamenti['CondizioniPagamento'] == 'TP01' ? '!' : '').'= 100 AND
$query = 'SELECT id, CONCAT (descrizione, IF((codice_modalita_pagamento_fe IS NULL), \"\", CONCAT( \" (\", codice_modalita_pagamento_fe, \")\" ) )) as descrizione FROM co_pagamenti';
if (!empty($codice_modalita_pagamento)) {
    $query .= ' WHERE codice_modalita_pagamento_fe = '.prepare($codice_modalita_pagamento);
}
$query .= ' GROUP BY descrizione ORDER BY descrizione ASC';

echo '
    {[ "type": "select", "label": "'.tr('Pagamento').'", "name": "pagamento", "required": 1, "values": "query='.$query.'" ]}';

// Righe
$righe = $fattura_pa->getRighe();

if (!empty($righe)) {
    echo '
    <h4>
        '.tr('Righe').'
        <button type="button" class="btn btn-info btn-sm pull-right" onclick="copy()"><i class="fa fa-copy"></i> '.tr('Copia dati contabili dalla prima riga').'</button>
        <div class="clearfix"></div>
    </h4>

    <div class="table-responsive">
        <table class="table table-hover table-striped table-condensed">
            <tr>
                <th>'.tr('Descrizione').'</th>
                <th width="10%">'.tr('Q.tà').'</th>
                <th width="10%">'.tr('Prezzo unitario').'</th>
                <th width="15%">'.tr('Dati contabili').'*</th>
                <th width="25%">'.tr('Articolo').'</th>
            </tr>';

    foreach ($righe as $key => $riga) {
        $query = 'SELECT id, IF(codice IS NULL, descrizione, CONCAT(codice, " - ", descrizione)) AS descrizione FROM co_iva WHERE percentuale = '.prepare($riga['AliquotaIVA']);

        if (!empty($riga['Natura'])) {
            $query .= ' AND codice_natura_fe = '.prepare($riga['Natura']);
        }

        $query .= ' ORDER BY descrizione ASC';

        echo '
        <tr>
            <td>'.$riga['Descrizione'].'</td>
            <td>'.Translator::numberToLocale($riga['Quantita']).' '.$riga['UnitaMisura'].'</td>
            <td>'.Translator::numberToLocale($riga['PrezzoUnitario']).'&nbsp;&euro;<small class="help-block">Aliquota iva: '.$riga['AliquotaIVA'].'%</small></td>
            <td>
                {[ "type": "select", "name": "iva['.$key.']", "values": "query='.str_replace('"', '\"', $query).'", "required": 1, "placeholder": "Aliquota iva" ]}
                <br>
                {[ "type": "select", "name": "conto['.$key.']", "ajax-source": "conti-acquisti", "required": 1, "placeholder": "Conto acquisti" ]}
            </td>
            <td>
                {[ "type": "select", "name": "articoli['.$key.']", "ajax-source": "articoli", "class": "", "icon-after": "add|'.Modules::get('Articoli')['id'].'" ]}
            </td>
        </tr>';
    }

    echo '
        </table>
    </div>';

    echo '
    <script>
    function copy(){
        $iva = $("select[name^=iva").first().selectData();
        $conto = $("select[name^=conto").first().selectData();

        if($iva) {
            $("select[name^=iva").each(function(){
                $(this).selectSet($iva.id);
            });
        }

        if($conto) {
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
            <button type="submit" class="btn btn-primary">
                <i class="fa fa-arrow-right"></i> '.tr('Continua').'...
            </button>
        </div>
    </div>
</form>';

echo '
<script src="'.$rootdir.'/lib/init.js"></script>';
