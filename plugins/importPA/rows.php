<?php

include_once __DIR__.'/../../core.php';

$directory = Uploads::getDirectory($id_module);

$xml = file_get_contents(DOCROOT.'/'.$directory.'/'.get('filename'));
$fattura_pa = new Plugins\ImportPA\FatturaElettronica($xml, post('id_segment'));

$righe = $fattura_pa->getRighe();

$pagamenti = $fattura_pa->getBody()['DatiPagamento'];

$metodi = $pagamenti['DettaglioPagamento'];
$metodi = isset($metodi[0]) ? $metodi : [$metodi];

$query = 'SELECT id, descrizione FROM co_pagamenti WHERE prc '.($pagamenti['CondizioniPagamento'] == 'TP01' ? '!' : '').'= 100 AND codice_modalita_pagemento_fe = '.prepare($metodi[0]['ModalitaPagamento']).' GROUP BY descrizione ORDER BY descrizione ASC';

echo '
<form action="'.$rootdir.'/actions.php" method="post">
    <input type="hidden" name="id_module" value="'.$id_module.'">
    <input type="hidden" name="id_plugin" value="'.$id_plugin.'">
    <input type="hidden" name="filename" value="'.get('filename').'">
    <input type="hidden" name="id_segment" value="'.get('id_segment').'">
    <input type="hidden" name="id" value="'.get('id').'">
    <input type="hidden" name="backto" value="record-edit">
    <input type="hidden" name="op" value="generate">

    {[ "type": "select", "label": "'.tr('Pagamento').'", "name": "pagamento", "required": 1, "values": "query='.$query.'" ]}';

if (!empty($righe)) {
    echo '
    <table class="table table-hover table-striped">
        <tr>
            <th width="10%">'.tr('Riga').'</th>
            <th width="40%">'.tr('Descrizione').'</th>
            <th width="10%">'.tr('Quantità').'</th>
            <th width="10%">'.tr('Prezzo unitario').'</th>
            <th width="10%">'.tr('Iva associata').'*</th>
            <th width="20%">'.tr('Articolo associato').'</th>
        </tr>';

    foreach ($righe as $key => $riga) {
        $query = 'SELECT id, IF(codice IS NULL, descrizione, CONCAT(codice, " - ", descrizione)) AS descrizione FROM co_iva WHERE percentuale = '.prepare($riga['AliquotaIVA']);

        if (!empty($riga['Natura'])) {
            $query .= ' AND codice_natura_fe = '.prepare($riga['Natura']);
        }

        $query .= ' ORDER BY descrizione ASC';

        echo '
        <tr>
            <td>'.$key.'</td>
            <td>'.$riga['Descrizione'].'</td>
            <td>'.$riga['Quantita'].' '.$riga['UnitaMisura'].'</td>
            <td>'.$riga['PrezzoUnitario'].'</td>
            <td>
                {[ "type": "select", "name": "iva['.$key.']", "values": "query='.str_replace('"', '\"', $query).'", "required": 1 ]}
            </td>
            <td>
                {[ "type": "select", "name": "articoli['.$key.']", "ajax-source": "articoli" ]}
            </td>
        </tr>';
    }

    echo '
    </table>';
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
