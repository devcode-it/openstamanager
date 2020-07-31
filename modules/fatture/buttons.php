<?php

include_once __DIR__.'/../../core.php';

echo '
<button type="button" class="btn btn-primary ask btn-primary" '.(empty($record['is_reversed']) ? '' : 'disabled').' data-msg="'.tr('Duplicare questa fattura?').'"  data-op="copy" data-button="'.tr('Duplica').'" data-class="btn btn-lg btn-warning" data-backto="record-edit" >
    <i class="fa fa-copy"></i> '.tr('Duplica fattura').'
</button>';

if ($module->name == 'Fatture di vendita') {
    $attributi_visibili = $record['dati_aggiuntivi_fe'] != null || $record['stato'] == 'Bozza';

    echo '
<a class="btn btn-info '.($attributi_visibili ? '' : 'disabled').'" data-toggle="modal" data-title="'.tr('Dati Fattura Elettronica').'" data-href="'.$structure->fileurl('fe/document-fe.php').'?id_module='.$id_module.'&id_record='.$id_record.'" '.($attributi_visibili ? '' : 'disabled').'>
    <i class="fa fa-file-code-o"></i> '.tr('Attributi avanzati').'
</a>';
}

if ($dir == 'entrata') {
    echo '
<div class="btn-group">
    <button type="button" class="btn btn-primary unblockable dropdown-toggle '.(((!empty($record['ref_documento']) || $record['stato'] != 'Bozza') and empty($record['is_reversed'])) ? '' : 'disabled').'" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
        <i class="fa fa-magic"></i> '.tr('Crea').'
        <span class="caret"></span>
    </button>

    <ul class="dropdown-menu dropdown-menu-right">
        <li><a href="'.$rootdir.'/editor.php?id_module='.$id_module.'&id_record='.$id_record.'&op=nota_addebito&backto=record-edit">
            '.tr('Nota di debito').'
        </a></li>

        <li><a data-href="'.$rootdir.'/modules/fatture/crea_documento.php?id_module='.$id_module.'&id_record='.$id_record.'&iddocumento='.$id_record.'" data-title="Aggiungi nota di credito">
            '.tr('Nota di credito').'
        </a></li>
    </ul>
</div>';
}

if (empty($record['is_fiscale'])) {
    $msg = '<br>{[ "type": "select", "label": "'.tr('Sezionale').'", "name": "id_segment", "required": 1, "values": "query=SELECT id, name AS descrizione FROM zz_segments WHERE id_module=\''.$id_module.'\' AND is_fiscale = 1 ORDER BY name" ]}
    {[ "type": "date", "label": "'.tr('Data').'", "name": "data", "required": 1, "value": "-now-" ]}';

    echo '
    <button type="button" class="btn btn-warning ask" data-msg="'.tr('Vuoi trasformare questa fattura pro-forma in una di tipo fiscale?').'<br>'.prepareToField(\HTMLBuilder\HTMLBuilder::replace($msg)).'" data-op="transform" data-button="'.tr('Trasforma').'" data-class="btn btn-lg btn-warning" data-backto="record-edit">
        <i class="fa fa-upload"></i> '.tr('Trasforma in fattura fiscale').'
    </button>';
}

?>

<?php

if (!empty($record['is_fiscale'])) {
    $disabled1 = 1;
    //Aggiunta insoluto
    if (!empty($record['riba']) && ($record['stato'] == 'Emessa' || $record['stato'] == 'Parzialmente pagato' || $record['stato'] == 'Pagato') && $dir == 'entrata') {
        $disabled1 = 0;
    } ?>
        <a class="btn btn-primary <?php echo (empty($disabled1)) ? '' : 'disabled'; ?>" data-href="<?php echo $rootdir; ?>/add.php?id_module=<?php echo Modules::get('Prima nota')['id']; ?>&id_documenti=<?php echo $id_record; ?>&single=1&is_insoluto=1" data-title="<?php echo tr('Registra insoluto'); ?>" ><i class="fa fa-ban fa-inverse"></i> <?php echo tr('Registra insoluto'); ?></a>
    <?php

    // Aggiunta prima nota solo se non c'è già, se non si è in bozza o se il pagamento non è completo
    $n2 = $dbo->fetchNum('SELECT id FROM co_movimenti WHERE iddocumento='.prepare($id_record).' AND primanota=1');

    $rs3 = $dbo->fetchArray('SELECT SUM(da_pagare-pagato) AS differenza, SUM(da_pagare) FROM co_scadenziario GROUP BY iddocumento HAVING iddocumento='.prepare($id_record));
    $differenza = isset($rs3[0]) ? $rs3[0]['differenza'] : null;
    $da_pagare = isset($rs3[0]) ? $rs3[0]['da_pagare'] : null;
    $disabled2 = 1;
    if (($n2 <= 0 && $record['stato'] == 'Emessa') || $differenza != 0) {
        $disabled2 = 0;
    } ?>

        <a class="btn btn-primary <?php echo (!empty(Modules::get('Prima nota')) and empty($disabled2)) ? '' : 'disabled'; ?>" data-href="<?php echo $rootdir; ?>/add.php?id_module=<?php echo Modules::get('Prima nota')['id']; ?>&id_documenti=<?php echo $id_record; ?>&single=1"  data-title="<?php echo tr('Registra contabile'); ?>" > <i class="fa fa-euro"></i> <?php echo tr('Registra contabile'); ?></a>

    <?php

    if ($record['stato'] == 'Pagato') {
        echo '
        <button type="button" class="btn btn-primary ask tip" data-msg="'.tr('Se riapri questa fattura verrà azzerato lo scadenzario e la prima nota. Continuare?').'" data-method="post" data-op="reopen" data-backto="record-edit" data-title="'.tr('Riaprire la fattura?').'" title="'.tr('Riporta la fattura in stato bozza e ne elimina i movimenti contabili').'">
            <i class="fa fa-folder-open"></i> '.tr('Riapri fattura').'...
        </button>';
    }
}
