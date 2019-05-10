<?php

include_once __DIR__.'/../../core.php';

$filename = get('filename');
$fattura_pa = \Plugins\ImportFE\FatturaElettronica::manage($filename);

$filename = basename($filename, '.p7m');

echo '
<form action="'.$rootdir.'/actions.php" method="post">
    <input type="hidden" name="id_module" value="'.$id_module.'">
    <input type="hidden" name="id_plugin" value="'.$id_plugin.'">
    <input type="hidden" name="filename" value="'.$filename.'">
    <input type="hidden" name="id_segment" value="'.get('id_segment').'">
    <input type="hidden" name="id" value="'.get('id').'">
    <input type="hidden" name="backto" value="record-edit">
    <input type="hidden" name="op" value="generate">';

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
$descrizione_documento = database()->fetchOne('SELECT CONCAT("(", codice, ") ", descrizione) AS descrizione FROM fe_tipi_documento WHERE codice = '.prepare($dati_generali['TipoDocumento']));

echo '
    <div class="row" >
		<div class="col-md-6">
			<h4>'.
    $ragione_sociale.' '.((empty($idanagrafica = $dbo->fetchOne('SELECT idanagrafica FROM an_anagrafiche WHERE ( codice_fiscale = '.prepare($codice_fiscale).' AND codice_fiscale != \'\' ) OR ( piva = '.prepare($partita_iva).' AND piva != \'\' ) ')['idanagrafica'])) ? '<span class="badge badge-success" >'.tr('Nuova').'</span>' : '<small>'.Modules::link('Anagrafiche', $idanagrafica, '', null, '')).'</small>'.'<br>
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
				<a href="'.$structure->fileurl('view.php').'?filename='.$filename.'" class="btn btn-info btn-xs" target="_blank" >
					<i class="fa fa-eye"></i> '.tr('Visualizza').'
				</a>';

        echo '
				<br><small>
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

    $codice_modalita_pagamento = $metodi[0]['ModalitaPagamento'];

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

// Pagamento
echo '
    <div class="row" >
		<div class="col-md-6">
            {[ "type": "select", "label": "'.tr('Pagamento').'", "name": "pagamento", "required": 1, "values": "query='.$query.'" ]}
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

        foreach ($riga['CodiceArticolo'] as $key => $item) {
            foreach ($item as $key => $name) {
                if ($key == 'CodiceValore') {
                    if (!empty($item['CodiceValore'])) {
                        $codici_articoli .= '<small>'.$item['CodiceValore'].' ('.$item['CodiceTipo'].')</small>';

                        if (($item['CodiceValore'] != end($riga['CodiceArticolo'][(count($riga['CodiceArticolo']) - 1)])) and (is_array($riga['CodiceArticolo'][1]))) {
                            $codici_articoli .= ', ';
                        }
                    }
                }
            }
        }
        /*###*/

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
            <button type="submit" class="btn btn-primary">
                <i class="fa fa-arrow-right"></i> '.tr('Continua').'...
            </button>
        </div>
    </div>
</form>';

echo '
<script src="'.$rootdir.'/lib/init.js"></script>';
