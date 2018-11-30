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

echo '
    <h4>'.tr('Fornitore').'</h4>

    <p>'.tr('Le informazioni principali del fornitore sono le seguenti').':</p>
    <ul>
        <li>'.tr('Ragione Sociale').': '.$ragione_sociale.'</li>
        <li>'.tr('Codice Fiscale').': '.$codice_fiscale.'</li>
        <li>'.tr('Partita IVA').': '.$partita_iva.'</li>
    </ul>';

// Pagamenti
$pagamenti = $fattura_pa->getBody()['DatiPagamento'];

$metodi = $pagamenti['DettaglioPagamento'];
$metodi = isset($metodi[0]) ? $metodi : [$metodi];

// prc '.($pagamenti['CondizioniPagamento'] == 'TP01' ? '!' : '').'= 100 AND
$query = 'SELECT id, descrizione FROM co_pagamenti WHERE codice_modalita_pagamento_fe = '.prepare($metodi[0]['ModalitaPagamento']).' GROUP BY descrizione ORDER BY descrizione ASC';

echo '
    <h4>'.tr('Pagamento').'</h4>

    <p>'.tr('La fattura importata presenta _NUM_ rate di pagamento con le seguenti scadenze', [
        '_NUM_' => count($metodi),
    ]).':</p>
    <ul>';

// Scadenze di pagamento
foreach ($metodi as $metodo) {
    echo '
        <li>'.Translator::dateToLocale($metodo['DataScadenzaPagamento']).'</li>';
}

echo '
    </ul>';

echo '
    {[ "type": "select", "label": "'.tr('Pagamento').'", "name": "pagamento", "required": 1, "values": "query='.$query.'" ]}';

// Sezionale
echo '
    {[ "type": "select", "label": "'.tr('Sezionale').'", "name": "id_segment", "required": 1, "values": "query=SELECT id, name AS descrizione FROM zz_segments WHERE id_module='.$id_module.' ORDER BY name", "value": "'.$_SESSION['module_'.$id_module]['id_segment'].'" ]}';

// Righe
$righe = $fattura_pa->getRighe();

if (!empty($righe)) {
    echo '
    <h4>'.tr('Righe').'</h4>

    <div class="table-responsive">
        <table class="table table-hover table-striped table-condensed">
            <tr>
                <th>'.tr('Descrizione').'</th>
                <th width="10%">'.tr('Q.tà').'</th>
                <th width="15%">'.tr('Prezzo unitario').'</th>
                <th width="15%">'.tr('Iva associata').'*</th>
                <th width="15%">'.tr('Conto').'*</th>
                <th width="15%">'.tr('Articolo associato').'</th>
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
            <td>'.Translator::numberToLocale($riga['PrezzoUnitario']).'&nbsp;&euro;</td>
            <td>
                {[ "type": "select", "name": "iva['.$key.']", "values": "query='.str_replace('"', '\"', $query).'", "required": 1 ]}
            </td>
            <td>
                {[ "type": "select", "name": "conto['.$key.']", "ajax-source": "conti-acquisti", "required": 1 ]}
            </td>
            <td>
                {[ "type": "select", "name": "articoli['.$key.']", "ajax-source": "articoli" ]}
            </td>
        </tr>';
    }

    echo '
        </table>
    </div>';
} else {
    echo '
    <p>Non ci sono righe nella fattura.</p>';
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
