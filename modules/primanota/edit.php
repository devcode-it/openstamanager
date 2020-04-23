<?php

include_once __DIR__.'/../../core.php';

?><form action="" method="post" id="edit-form">
	<input type="hidden" name="op" value="update">
	<input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="id_record" value="<?php echo $id_record; ?>">
	<input type="hidden" name="idmastrino" value="<?php echo $record['idmastrino']; ?>">
	<input type="hidden" name="iddocumento" value="<?php echo $record['iddocumento']; ?>">


    <div class="row">
	<?php

    $rs_doc = $dbo->fetchArray('SELECT DISTINCT iddocumento, (SELECT IFNULL(numero_esterno, numero) FROM co_documenti WHERE id=co_movimenti.iddocumento) AS numero FROM co_movimenti WHERE idmastrino='.prepare($record['idmastrino']).' AND iddocumento!=0');

    if (sizeof($rs_doc) > 0) {
        if (sizeof($rs_doc) == 1) {
            $rs = $dbo->fetchArray('SELECT dir FROM co_tipidocumento INNER JOIN co_documenti ON co_tipidocumento.id=co_documenti.idtipodocumento WHERE co_documenti.id='.prepare($rs_doc[0]['iddocumento']));
            $modulo = ($rs[0]['dir'] == 'entrata') ? 'Fatture di vendita' : 'Fatture di acquisto'; ?>
            <div class=" col-md-2">
                <br>
                <a href="<?php echo $rootdir; ?>/editor.php?id_module=<?php echo Modules::get($modulo)['id']; ?>&id_record=<?php echo $rs_doc[0]['iddocumento']; ?>" class="btn btn-info"><i class="fa fa-chevron-left"></i> <?php echo tr('Vai alla fattura'); ?></a>
            </div>
        <?php
        } else {
            ?>
            <div class=" col-md-2">
                <br>
                <div class="dropdown">
                    <button class="btn btn-primary dropdown-toggle" type="button" data-toggle="dropdown" style="width:100%;">Fatture collegate
                    <span class="caret"></span></button>
                    <ul class="dropdown-menu">
        <?php
            for ($i = 0; $i < sizeof($rs_doc); ++$i) {
                $rs = $dbo->fetchArray('SELECT dir FROM co_tipidocumento INNER JOIN co_documenti ON co_tipidocumento.id=co_documenti.idtipodocumento WHERE co_documenti.id='.prepare($rs_doc[$i]['iddocumento']));
                $modulo = ($rs[0]['dir'] == 'entrata') ? 'Fatture di vendita' : 'Fatture di acquisto'; ?>
                        <li><a href="<?php echo $rootdir; ?>/editor.php?id_module=<?php echo Modules::get($modulo)['id']; ?>&id_record=<?php echo $rs_doc[$i]['iddocumento']; ?>" class="dropdown-item"><?php echo tr('Vai alla fattura n. '.$rs_doc[$i]['numero']); ?></a></li>
        <?php
            } ?>
                    </ul>
                </div>
            </div>
        <?php
        }
    }
    ?>

		<div class="col-md-3">
			{[ "type": "date", "label": "<?php echo tr('Data movimento'); ?>", "name": "data", "required": 1, "value": "$data$" ]}
		</div>

		<div class="col-md-7">
			{[ "type": "text", "label": "<?php echo tr('Causale'); ?>", "name": "descrizione", "required": 1, "value": "$descrizione$" ]}
		</div>
	</div>
<?php

$movimenti = $mastrino->movimenti->toArray();

include $structure->filepath('movimenti.php');

?>
</form>

{( "name": "filelist_and_upload", "id_module": "$id_module$", "id_record": "$id_record$" )}

<script>
    $("#edit-form").submit(function(e) {
        return controllaConti();
    });
</script>

<a class="btn btn-danger ask" data-backto="record-list" data-idmastrino="<?php echo $record['idmastrino']; ?>">
    <i class="fa fa-trash"></i> <?php echo tr('Elimina'); ?>
</a>
