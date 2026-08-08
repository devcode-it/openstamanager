<?php

include_once __DIR__.'/../../core.php';

$period_start = $_SESSION['period_start'] ?? date('Y-01-01');
$period_end = $_SESSION['period_end'] ?? date('Y-12-31');
$print = $dbo->fetchOne('SELECT `id` FROM `zz_prints` WHERE `name` = '.prepare('Nota spese').' AND `enabled` = 1 LIMIT 1');
$print_id = (int) ($print['id'] ?? 0);

$print_url = $print_id > 0 ? base_path_osm().'/pdfgen.php?id_print='.$print_id.'&id_record=0' : null;
$csv_url = base_path_osm().'/modules/note_spese/export.php?id_module='.(int) $id_module;
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
    <div class="text-muted small mb-2 mb-md-0">
        <i class="fa fa-calendar mr-1"></i>
        <?php echo tr('Periodo selezionato: _START_ - _END_', [
            '_START_' => Translator::dateToLocale($period_start),
            '_END_' => Translator::dateToLocale($period_end),
        ]); ?>
        <?php if ($structure->permission === 'rw') { ?>
            <span class="ml-2 tip" title="<?php echo prepareToField(tr('Modifica rapida: usa la matita su Data, Descrizione, Controparte e Importo per aggiornare la riga senza aprire la scheda.')); ?>">
                <i class="fa fa-pencil text-primary"></i> <?php echo tr('Modifica rapida'); ?>
            </span>
        <?php } ?>
    </div>
    <div class="btn-group" role="group">
        <?php if (!empty($print_url)) { ?>
        <a class="btn btn-primary" href="<?php echo $print_url; ?>" target="_blank">
            <i class="fa fa-print"></i> <?php echo tr('Stampa periodo'); ?>
        </a>
        <?php } ?>
        <a class="btn btn-default" href="<?php echo $csv_url; ?>">
            <i class="fa fa-file-excel-o"></i> <?php echo tr('CSV periodo'); ?>
        </a>
    </div>
</div>
