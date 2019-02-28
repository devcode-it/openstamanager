<?php

include_once __DIR__.'/../../core.php';

echo '

<form action="" class="text-right" method="post" id="form-copy">
    <input type="hidden" name="backto" value="record-edit">
    <input type="hidden" name="op" value="copy">
</form>

<button type="button" class="btn btn-primary" onclick="if( confirm(\'Duplicare questa fattura?\') ){ $(\'#form-copy\').submit(); }">
    <i class="fa fa-copy"></i> '.tr('Duplica fattura').'
</button>';

if ($dir == 'entrata') {
    echo '
<div class="btn-group">
    <button type="button" class="btn btn-primary unblockable dropdown-toggle '.(!empty($record['ref_documento']) || $record['stato'] == 'Emessa' ? '' : 'disabled').'" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
        <i class="fa fa-magic"></i> '.tr('Crea').' <span class="caret"></span>
        <span class="sr-only">Toggle Dropdown</span>
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
    $msg = '{[ "type": "select", "label": "'.tr('Sezionale').'", "name": "id_segment", "required": 1, "values": "query=SELECT id, name AS descrizione FROM zz_segments WHERE id_module=\''.$id_module.'\' AND is_fiscale = 1 ORDER BY name" ]}';

    echo '
    <button type="button" class="btn btn-warning ask" data-msg="'.tr('Vuoi trasformare questa fattura pro-forma in una di tipo fiscale?').'<br>'.prepareToField(\HTMLBuilder\HTMLBuilder::replace($msg)).'" data-op="transform" data-button="'.tr('Trasforma').'" data-class="btn btn-lg btn-warning" data-backto="record-edit">
        <i class="fa fa-upload"></i> '.tr('Trasforma in fattura fiscale').'
    </button>';
}


?>

<?php

if (!empty($record['is_fiscale'])) {
    // Aggiunta prima nota solo se non c'è già, se non si è in bozza o se il pagamento non è completo
    $n2 = $dbo->fetchNum('SELECT id FROM co_movimenti WHERE iddocumento='.prepare($id_record).' AND primanota=1');

    $rs3 = $dbo->fetchArray('SELECT SUM(da_pagare-pagato) AS differenza, SUM(da_pagare) FROM co_scadenziario GROUP BY iddocumento HAVING iddocumento='.prepare($id_record));
    $differenza = isset($rs3[0]) ? $rs3[0]['differenza'] : null;
    $da_pagare = isset($rs3[0]) ? $rs3[0]['da_pagare'] : null;

    if (($n2 <= 0 && $record['stato'] == 'Emessa') || $differenza != 0) {
?>
					<button type="button" class="btn btn-primary <?php echo (!empty(Modules::get('Prima nota'))) ? '' : 'disabled'; ?>" onclick="launch_modal( '<?php echo tr('Aggiungi prima nota'); ?>', '<?php echo $rootdir; ?>/add.php?id_module=<?php echo Modules::get('Prima nota')['id']; ?>&iddocumento=<?php echo $id_record; ?>&dir=<?php echo $dir; ?>', 1 );"><small><i class="fa fa-euro"></i> <?php echo tr('Registrazione contabile pagamento'); ?>...</small></button>
<?php
    }

    if ($record['stato'] == 'Pagato') {
?>
					<button type="button" class="btn btn-primary tip" onclick="if( confirm('<?php echo tr('Se riapri questa fattura verrà azzerato lo scadenzario e la prima nota. Continuare?'); ?>') ){ $.post( '<?php echo $rootdir; ?>/editor.php?id_module=<?php echo $id_module; ?>&id_record=<?php echo $id_record; ?>', { id_module: '<?php echo $id_module; ?>', id_record: '<?php echo $id_record; ?>', op: 'reopen' }, function(){ location.href='<?php echo $rootdir; ?>/editor.php?id_module=<?php echo $id_module; ?>&id_record=<?php echo $id_record; ?>'; } ); }" title="<?php echo tr('Riporta la fattura in stato bozza e ne elimina i movimenti contabili.'); ?>" ><i class="fa fa-folder-open"></i> <?php echo tr('Riapri fattura'); ?>...</button>
<?php
    }
}
?>