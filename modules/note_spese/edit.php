<?php

use Models\Module;

include_once __DIR__.'/../../core.php';

$lang = (int) Models\Locale::getDefault()->id;
$category_query = 'SELECT t.`id`, COALESCE(l.`title`, t.`descrizione`) AS `descrizione` '
    .'FROM `co_note_spese_tipologie` t LEFT JOIN `co_note_spese_tipologie_lang` l ON l.`id_record` = t.`id` AND l.`id_lang` = '.$lang.' '
    .'WHERE t.`enabled` = 1 OR t.`id` = '.(int) $record['id_tipologia'].' ORDER BY t.`ordine`, `descrizione`';
$status_query = 'SELECT st.`id`, COALESCE(l.`title`, st.`name`) AS `descrizione` '
    .'FROM `co_note_spese_stati` st LEFT JOIN `co_note_spese_stati_lang` l ON l.`id_record` = st.`id` AND l.`id_lang` = '.$lang.' '
    .'ORDER BY st.`ordine`, st.`id`';
$operator_query = noteSpeseOperatorSelectQuery($record['id_operatore'] ?? null);

$source_label = noteSpeseSourceLabel($record['origine'] ?? 'manuale');
$source_url = null;
$source_hint = null;

if (($record['origine'] ?? '') === 'automezzi_rifornimento' && !empty($record['id_origine'])) {
    $module_source = Module::where('name', 'Automezzi')->first();
    $can_read_source = !empty($module_source) && in_array(Modules::getPermission($module_source->id), ['r', 'rw'], true);
    if ($can_read_source) {
        $source = $dbo->fetchOne(
            'SELECT v.`id_sede` FROM `an_automezzi_rifornimenti` r '
            .'LEFT JOIN `an_automezzi_viaggi` v ON v.`id` = r.`id_viaggio` WHERE r.`id` = '.prepare($record['id_origine']).' LIMIT 1'
        );
        if (!empty($source['id_sede'])) {
            $source_url = base_path_osm().'/editor.php?id_module='.$module_source->id.'&id_record='.(int) $source['id_sede'];
            $source_hint = tr('Rifornimento #_ID_', ['_ID_' => (int) $record['id_origine']]);
        }
    }
} elseif (($record['origine'] ?? '') === 'scadenzario_generico' && !empty($record['id_origine'])) {
    $module_source = Module::where('name', 'Scadenzario')->first();
    if (!empty($module_source) && in_array(Modules::getPermission($module_source->id), ['r', 'rw'], true)) {
        $source_url = base_path_osm().'/editor.php?id_module='.$module_source->id.'&id_record='.(int) $record['id_origine'];
        $source_hint = tr('Scadenza #_ID_', ['_ID_' => (int) $record['id_origine']]);
    }
}

$duplicate = noteSpeseFindDuplicate(
    $dbo,
    $record['data'] ?? null,
    $record['importo'] ?? null,
    $record['descrizione'] ?? '',
    $record['controparte'] ?? '',
    (int) $id_record,
    $record['id_operatore'] ?? null
);

$period_start = $_SESSION['period_start'] ?? date('Y-01-01');
$period_end = $_SESSION['period_end'] ?? date('Y-12-31');
$is_in_period = noteSpeseIsDateInPeriod($record['data'] ?? null, $period_start, $period_end);
?>

<form action="" method="post" id="edit-form">
    <input type="hidden" name="backto" value="record-edit">
    <input type="hidden" name="op" value="update">

    <div class="card card-primary">
        <div class="card-header"><h3 class="card-title"><?php echo tr('Dati della spesa'); ?></h3></div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3">{[ "type": "date", "label": "<?php echo tr('Data'); ?>", "name": "data", "required": 1, "value": "$data$" ]}</div>
                <div class="col-md-3">{[ "type": "select", "label": "<?php echo tr('Tipologia'); ?>", "name": "id_tipologia", "required": 1, "value": "$id_tipologia$", "values": "query=<?php echo $category_query; ?>" ]}</div>
                <div class="col-md-3">{[ "type": "select", "label": "<?php echo tr('Operatore / tecnico'); ?>", "name": "id_operatore", "value": "$id_operatore$", "values": "query=<?php echo $operator_query; ?>", "help": "<?php echo tr('Opzionale. Sono proposte solo le anagrafiche attive di tipo Tecnico; eventuali utenti collegati devono essere attivi.'); ?>" ]}</div>
                <div class="col-md-3">{[ "type": "number", "label": "<?php echo tr('Importo'); ?>", "name": "importo", "required": 1, "value": "$importo$", "icon-after": "€", "min-value": "0.01", "decimals": 2 ]}</div>
            </div>

            <div class="row">
                <div class="col-md-6">{[ "type": "text", "label": "<?php echo tr('Descrizione'); ?>", "name": "descrizione", "required": 1, "value": "$descrizione$" ]}</div>
                <div class="col-md-3">{[ "type": "text", "label": "<?php echo tr('Controparte'); ?>", "name": "controparte", "value": "$controparte$" ]}</div>
                <div class="col-md-3">{[ "type": "select", "label": "<?php echo tr('Stato'); ?>", "name": "id_stato", "required": 1, "value": "$id_stato$", "values": "query=<?php echo $status_query; ?>" ]}</div>
            </div>

            <div class="row"><div class="col-md-12">{[ "type": "textarea", "label": "<?php echo tr('Note'); ?>", "name": "note", "value": "$note$" ]}</div></div>
        </div>
    </div>
</form>

<?php if (!$is_in_period) { ?>
<div class="alert alert-info py-2">
    <i class="fa fa-calendar mr-1"></i>
    <?php echo tr('Questa spesa è fuori dal periodo selezionato (_START_ - _END_).', [
        '_START_' => Translator::dateToLocale($period_start),
        '_END_' => Translator::dateToLocale($period_end),
    ]); ?>
</div>
<?php } ?>

<?php if (!empty($duplicate)) { ?>
<div class="alert alert-warning py-2 d-flex justify-content-between align-items-center">
    <span><i class="fa fa-exclamation-triangle mr-1"></i><?php echo tr('Possibile duplicato della spesa #_ID_: verificare prima di confermare.', ['_ID_' => (int) $duplicate['id']]); ?></span>
    <a class="btn btn-sm btn-outline-warning" href="<?php echo base_path_osm().'/editor.php?id_module='.(int) $id_module.'&id_record='.(int) $duplicate['id']; ?>" target="_blank"><i class="fa fa-external-link"></i> <?php echo tr('Apri'); ?></a>
</div>
<?php } ?>

<?php if (($record['origine'] ?? 'manuale') !== 'manuale' || !empty($source_url) || !empty($source_hint)) { ?>
<div class="d-flex justify-content-between align-items-center mb-3 px-1">
    <div class="text-muted small">
        <i class="fa fa-link mr-1"></i><strong><?php echo tr('Origine'); ?>:</strong> <?php echo htmlentities($source_label); ?>
        <?php if (!empty($source_hint)) { ?><span class="ml-2"><?php echo htmlentities($source_hint); ?></span><?php } ?>
    </div>
    <?php if (!empty($source_url)) { ?>
        <a class="btn btn-sm btn-outline-primary" href="<?php echo $source_url; ?>" target="_blank"><i class="fa fa-external-link"></i> <?php echo tr('Apri origine'); ?></a>
    <?php } ?>
</div>
<?php } ?>

{( "name": "filelist_and_upload", "id_module": "$id_module$", "id_record": "$id_record$" )}

<a class="btn btn-danger ask" data-backto="record-list"><i class="fa fa-trash"></i> <?php echo tr('Elimina'); ?></a>
