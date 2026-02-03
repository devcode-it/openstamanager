<?php

include_once __DIR__.'/../../../core.php';

$id_listino = filter('id_listino');
$listino = $dbo->selectOne('mg_listini', '*', ['id' => $id_listino]);
$search = filter('search') ? filter('search')['value'] : null;
$start = filter('start');
$length = filter('length');

$tot_articoli = $dbo->select('mg_listini_articoli', '*', [], ['id_listino' => $id_listino]);

if (empty($search)) {
    $articoli = $dbo->fetchArray('SELECT `mg_listini_articoli`.*, `mg_articoli`.`codice`, `mg_articoli_lang`.`title` AS descrizione,  `mg_articoli`.'.($prezzi_ivati ? 'minimo_vendita_ivato' : 'minimo_vendita').' AS minimo_vendita FROM `mg_listini_articoli` LEFT JOIN `mg_articoli` ON `mg_listini_articoli`.`id_articolo`=`mg_articoli`.`id` LEFT JOIN `mg_articoli_lang` ON (`mg_articoli_lang`.`id_record` = `mg_articoli`.`id` AND `mg_articoli_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).') WHERE `id_listino`='.prepare($id_listino).' LIMIT '.$start.', '.$length);
} else {
    $resource = 'articoli_listino';
    include_once __DIR__.'/select.php';

    $articoli = $results;
}

foreach ($articoli as $articolo) {
    $riga = [
        '<div class="text-center align-middle"><input class="check" type="checkbox" id="'.$articolo['id'].'"/></div>',
        '<div class="align-middle">'.Modules::link('Articoli', $articolo['id_articolo'], $articolo['codice'], null, '').'</div>',
        '<div class="align-middle">'.$articolo['descrizione'].'</div>',
        '<div class="text-center align-middle">'.($articolo['data_scadenza'] ? dateFormat($articolo['data_scadenza']) : '<span class="text-muted">'.dateFormat($listino['data_scadenza_predefinita']).'</span>').'</div>',
        '<div class="text-right align-middle">'.($articolo['minimo_vendita'] != 0 ? moneyFormat($articolo['minimo_vendita']) : '-').'</div>',
        '<div class="text-right align-middle">'.moneyFormat($articolo['prezzo_unitario']).'</div>',
        '<div class="text-right align-middle">'.moneyFormat($articolo['prezzo_unitario_ivato']).'</div>',
        '<div class="text-right align-middle">'.($articolo['sconto_percentuale'] != 0 ? numberFormat($articolo['sconto_percentuale']).' %' : '-').'</div>',
        '<div class="text-right align-middle">'.($articolo['minimo'] != 0 ? numberFormat($articolo['minimo']) : '-').'</div>',
        '<div class="text-right align-middle">'.($articolo['massimo'] != 0 ? numberFormat($articolo['massimo']) : '-').'</div>',
        '<div class="text-center align-middle">
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
