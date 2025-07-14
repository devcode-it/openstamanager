<?php
/*
 * OpenSTAManager: il software gestionale open source per l'assistenza tecnica e la fatturazione
 * Copyright (C) DevCode s.r.l.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

include_once __DIR__.'/../../core.php';
use Models\Module;

?><form action="" method="post" id="edit-form">
	<input type="hidden" name="op" value="update">
	<input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="id_record" value="<?php echo $id_record; ?>">
	<input type="hidden" name="idmastrino" value="<?php echo $record['idmastrino']; ?>">
	<input type="hidden" name="iddocumento" value="<?php echo $record['iddocumento']; ?>">

    <div class="row">
	<?php

    // Controllo se alla prima nota solo collegate più fatture
    $rs_doc = $dbo->fetchArray('SELECT DISTINCT iddocumento, (SELECT IFNULL(numero_esterno, numero) FROM co_documenti WHERE id=co_movimenti.iddocumento) AS numero FROM co_movimenti WHERE idmastrino='.prepare($record['idmastrino']).' AND iddocumento!=0');

if (sizeof($rs_doc) > 0) {
    if (sizeof($rs_doc) == 1) {
        $rs = $dbo->fetchArray('SELECT `dir` FROM `co_tipidocumento` INNER JOIN `co_documenti` ON `co_tipidocumento`.`id`=`co_documenti`.`idtipodocumento` WHERE `co_documenti`.`id`='.prepare($rs_doc[0]['iddocumento']));
        $id_modulo = ($rs[0]['dir'] == 'entrata') ? Module::where('name', 'Fatture di vendita')->first()->id : Module::where('name', 'Fatture di acquisto')->first()->id; ?>
            
            <div class="col-md-2">
                <br>
                <div class="btn-group">
                    <a href="<?php echo base_path(); ?>/editor.php?id_module=<?php echo $rs[0]['dir'] == 'uscita' ? Module::where('name', 'Fatture di acquisto')->first()->id : Module::where('name', 'Fatture di vendita')->first()->id; ?>&id_record=<?php echo $rs_doc[0]['iddocumento']; ?>" class="btn btn-info"><i class="fa fa-chevron-left"></i> <?php echo tr('Vai alla fattura'); ?></a>
                    <a type="button" class="btn btn-info dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <span class="caret"></span>
                        <span class="sr-only">Toggle Dropdown</span>
                    </a>
                    <ul class="dropdown-menu">
                        <a class="btn dropdown-item" href="<?php echo base_path(); ?>/controller.php?id_module=<?php echo $id_modulo; ?>"><i class="fa fa-chevron-left"></i> <?php echo tr('Vai all\'elenco delle fatture'); ?></a>
                    </ul>
                </div>
            </div>
        <?php
    } else {
        ?>
            <div class="col-md-2">
                <br>
                <div class="btn-group">
                    <button type="button" class="btn btn-info dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <?php echo tr('Più fatture collegate'); ?>... <span class="caret"></span>
                        <span class="sr-only">Toggle Dropdown</span>
                    </button>

                    <ul class="dropdown-menu">
        <?php
        for ($i = 0; $i < sizeof($rs_doc); ++$i) {
            $rs = $dbo->fetchArray('SELECT `dir` FROM `co_tipidocumento` INNER JOIN `co_documenti` ON `co_tipidocumento`.`id`=`co_documenti`.`idtipodocumento` WHERE `co_documenti`.`id`='.prepare($rs_doc[$i]['iddocumento']));
            $id_modulo = ($rs[0]['dir'] == 'entrata') ? Module::where('name', 'Fatture di vendita')->first()->id : Module::where('name', 'Fatture di acquisto')->first()->id; ?>
                        <a href="<?php echo base_path(); ?>/editor.php?id_module=<?php echo $id_modulo; ?>&id_record=<?php echo $rs_doc[$i]['iddocumento']; ?>" class="btn dropdown-item">
                            <i class="fa fa-chevron-left"></i> <?php echo tr('Vai alla fattura n. '.$rs_doc[$i]['numero']); ?>
                        </a>
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

    <!-- Note -->
    <div class="row">
        <div class="col-md-12">
            {[ "type": "textarea", "label": "<?php echo tr('Note'); ?>", "name": "note", "required": 0, "value": "$note$" ]}
        </div>
    </div>

</form>

{( "name": "filelist_and_upload", "id_module": "$id_module$", "id_record": "$id_record$" )}

<script>
    $("#edit-form").submit(function(e) {
        return controllaConti();
    });
</script>

<?php
// Controllo se il mastrino è collegato a un ammortamento
$ammortamento = $dbo->fetchOne('SELECT co_righe_ammortamenti.id, co_righe_documenti.id AS id_riga FROM co_righe_ammortamenti 
    INNER JOIN co_righe_documenti ON co_righe_documenti.id = co_righe_ammortamenti.id_riga 
    WHERE co_righe_ammortamenti.id_mastrino = '.prepare($id_record));

// Se il mastrino è collegato a un ammortamento, mostro un avviso e disabilito il pulsante elimina
if (!empty($ammortamento)) {
    echo '
    <div class="alert alert-warning text-center">
        <i class="fa fa-warning"></i> '.tr('Non è possibile eliminare questo movimento perché generato da un ammortamento.').
        ' '.Modules::link('Ammortamenti / Cespiti', $ammortamento['id_riga']).'
    </div>';
    
    // Disabilito il pulsante elimina
    echo '
    <a class="btn btn-danger disabled">
        <i class="fa fa-trash"></i> '.tr('Elimina').'
    </a>';
} else {
    // Mostro il pulsante elimina normalmente
    echo '
    <a class="btn btn-danger ask" data-backto="record-list" data-idmastrino="'.$record['idmastrino'].'">
        <i class="fa fa-trash"></i> '.tr('Elimina').'
    </a>';
}
?>
