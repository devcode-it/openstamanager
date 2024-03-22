<?php

include_once __DIR__.'/../../../core.php';

$id_listino = filter('id_listino');
$listino = $dbo->selectOne('mg_listini', '*', ['id' => $id_listino]);
$search = filter('search') ? filter('search')['value'] : null;
$start = filter('start');
$length = filter('length');

$tot_articoli = $dbo->select('mg_listini_articoli', '*', [], ['id_listino' => $id_listino]);

if (empty($search)) {
    $articoli = $dbo->fetchArray('SELECT `mg_listini_articoli`.*, `mg_articoli`.`codice`, `mg_articoli_lang`.`name` AS descrizione,  `mg_articoli`.'.($prezzi_ivati ? 'minimo_vendita_ivato' : 'minimo_vendita').' AS minimo_vendita FROM `mg_listini_articoli` LEFT JOIN `mg_articoli` ON `mg_listini_articoli`.`id_articolo`=`mg_articoli`.`id` LEFT JOIN `mg_articoli_lang` ON (`mg_articoli_lang`.`id_record` = `mg_articoli`.`id` AND `mg_articoli_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).') WHERE `id_listino`='.prepare($id_listino).' LIMIT '.$start.', '.$length);
} else {
    $resource = 'articoli_listino';
    include_once __DIR__.'/select.php';

    $articoli = $results;
}

foreach ($articoli as $articolo) {
    $riga = [
        '<input class="check" type="checkbox" id="'.$articolo['id'].'"/>',
        Modules::link('Articoli', $articolo['id_articolo'], $articolo['codice'], null, ''),
        $articolo['descrizione'],
        $articolo['data_scadenza'] ? '<p class="text-center">'.dateFormat($articolo['data_scadenza']).'</p>' : '<p class="text-muted text-center">'.dateFormat($listino['data_scadenza_predefinita']).'</p>',
        '<p class="text-right">'.($articolo['minimo_vendita'] != 0 ? moneyFormat($articolo['minimo_vendita']) : '-').'</p>',
        '<p class="text-right">'.moneyFormat($articolo['prezzo_unitario']).'</p>',
        '<p class="text-right">'.moneyFormat($articolo['prezzo_unitario_ivato']).'</p>',
        '<p class="text-right">'.($articolo['sconto_percentuale'] != 0 ? numberFormat($articolo['sconto_percentuale']).' %' : '-').'</p>',
        '<div class="text-center">
            <a class="btn btn-xs btn-warning" title="'.tr('Modifica articolo').'" onclick="modificaArticolo($(this), '.$articolo['id'].')">
                <i class="fa fa-edit"></i>
            </a>
            <a class="btn btn-xs btn-danger" title="'.tr('Rimuovi articolo').'" onclick="rimuoviArticolo('.$articolo['id'].')">
                <i class="fa fa-trash"></i>
            </a>
        </div>',
    ];

    $righe[] = $riga;
    $class[] = 'text-right';
}

// Formattazione dei dati
echo json_encode([
    'data' => $righe,
    'recordsTotal' => sizeof($tot_articoli),
    'recordsFiltered' => sizeof($tot_articoli),
    'draw' => intval(filter('draw')),
]);
