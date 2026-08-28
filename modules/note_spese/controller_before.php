<?php

include_once __DIR__.'/../../core.php';

$period_start = $_SESSION['period_start'] ?? date('Y-01-01');
$period_end = $_SESSION['period_end'] ?? date('Y-12-31');
$print = $dbo->fetchOne('SELECT `id` FROM `zz_prints` WHERE `name` = '.prepare('Nota spese').' AND `enabled` = 1 LIMIT 1');
$print_id = (int) ($print['id'] ?? 0);

$print_url = $print_id > 0 ? base_path_osm().'/pdfgen.php?id_print='.$print_id.'&id_record=0' : null;
$csv_url = base_path_osm().'/modules/note_spese/export.php?id_module='.(int) $id_module;

$legacy_summary = $dbo->fetchOne(
    'SELECT '
    .'SUM(CASE WHEN n.`origine` IN ('.prepare('automezzi_rifornimento').', '.prepare('scadenzario_generico').') THEN 1 ELSE 0 END) AS `automatiche`, '
    .'SUM(CASE WHEN COALESCE(n.`id_operatore`, 0) = 0 THEN 1 ELSE 0 END) AS `senza_operatore`, '
    .'SUM(CASE WHEN COALESCE(t.`enabled`, 0) != 1 THEN 1 ELSE 0 END) AS `categoria_non_attiva` '
    .'FROM `co_note_spese` n '
    .'LEFT JOIN `co_note_spese_stati` st ON st.`id` = n.`id_stato` '
    .'LEFT JOIN `co_note_spese_tipologie` t ON t.`id` = n.`id_tipologia` '
    .'WHERE n.`data` >= '.prepare($period_start).' AND n.`data` <= '.prepare($period_end).' '
    .'AND COALESCE(st.`name`, "") != '.prepare('escluso')
) ?: ['automatiche' => 0, 'senza_operatore' => 0, 'categoria_non_attiva' => 0];
$legacy_automatic_count = (int) ($legacy_summary['automatiche'] ?? 0);
$missing_operator_count = (int) ($legacy_summary['senza_operatore'] ?? 0);
$inactive_category_count = (int) ($legacy_summary['categoria_non_attiva'] ?? 0);
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

<?php if ($legacy_automatic_count > 0 || $missing_operator_count > 0 || $inactive_category_count > 0) { ?>
<div class="alert alert-warning py-2">
    <div><strong><i class="fa fa-exclamation-triangle mr-1"></i><?php echo tr('Verifica registrazioni storiche'); ?></strong></div>
    <?php if ($legacy_automatic_count > 0) { ?>
        <div><?php echo tr('_COUNT_ registrazioni del periodo provengono dalle vecchie sorgenti automatiche Automezzi/Scadenzario. Verificarne la reale natura di Nota spesa.', ['_COUNT_' => $legacy_automatic_count]); ?></div>
    <?php } ?>
    <?php if ($missing_operator_count > 0) { ?>
        <div><?php echo tr('_COUNT_ registrazioni del periodo non hanno un Operatore associato e non potranno essere confermate finché non verranno completate.', ['_COUNT_' => $missing_operator_count]); ?></div>
    <?php } ?>
    <?php if ($inactive_category_count > 0) { ?>
        <div><?php echo tr('_COUNT_ registrazioni del periodo usano una Tipologia non più attiva e richiedono riclassificazione prima della conferma.', ['_COUNT_' => $inactive_category_count]); ?></div>
    <?php } ?>
    <small><?php echo tr('L’aggiornamento 1.9.0 non modifica né elimina automaticamente i dati storici.'); ?></small>
</div>
<?php } ?>
