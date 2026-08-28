<?php

include_once __DIR__.'/../../core.php';

$period_start = $_SESSION['period_start'] ?? date('Y-01-01');
$period_end = $_SESSION['period_end'] ?? date('Y-12-31');
$today = date('Y-m-d');
$default_date = noteSpeseIsDateInPeriod($today, $period_start, $period_end) ? $today : $period_end;
$lang = (int) Models\Locale::getDefault()->id;

$duplicate_id = (int) get('duplicate_id');
$duplicate_source = [];
if ($duplicate_id > 0) {
    $duplicate_source = $dbo->fetchOne(
        'SELECT `data`, `id_tipologia`, `descrizione`, `importo`, `controparte`, `id_operatore`, `note` '
        .'FROM `co_note_spese` WHERE `id` = '.prepare($duplicate_id).' LIMIT 1'
    ) ?: [];
}

$is_duplicate = !empty($duplicate_source);
$default_date = $is_duplicate ? (string) $duplicate_source['data'] : $default_date;
$default_category = $is_duplicate ? (int) $duplicate_source['id_tipologia'] : null;
$default_amount = $is_duplicate ? (float) $duplicate_source['importo'] : null;
$default_description = $is_duplicate ? (string) $duplicate_source['descrizione'] : '';
$default_counterparty = $is_duplicate ? (string) ($duplicate_source['controparte'] ?? '') : '';
$default_notes = $is_duplicate ? (string) ($duplicate_source['note'] ?? '') : '';

$current_user = auth_osm()->getUser();
$current_operator = (int) ($current_user['id_anagrafica'] ?? 0);
$source_operator = $is_duplicate ? (int) ($duplicate_source['id_operatore'] ?? 0) : 0;
$operator_candidate = $source_operator > 0 ? $source_operator : $current_operator;
$default_operator = noteSpeseOperatorExists($dbo, $operator_candidate) ? ($operator_candidate ?: null) : null;

$category_query = 'SELECT t.`id`, COALESCE(l.`title`, t.`descrizione`) AS `descrizione` '
    .'FROM `co_note_spese_tipologie` t '
    .'LEFT JOIN `co_note_spese_tipologie_lang` l ON l.`id_record` = t.`id` AND l.`id_lang` = '.$lang.' '
    .'WHERE t.`enabled` = 1 ORDER BY t.`ordine`, `descrizione`';
$operator_query = noteSpeseOperatorSelectQuery($default_operator);
?>

<ul class="nav nav-tabs" role="tablist">
    <li class="nav-item">
        <a class="nav-link active" data-toggle="tab" href="#ns-manuale">
            <i class="fa fa-plus mr-1"></i><?php echo $is_duplicate ? tr('Duplica spesa') : tr('Nuova spesa'); ?>
        </a>
    </li>
    <?php if (!$is_duplicate) { ?>
    <li class="nav-item">
        <a class="nav-link" data-toggle="tab" href="#ns-excel">
            <i class="fa fa-table mr-1"></i><?php echo tr('Incolla dati'); ?>
        </a>
    </li>
    <?php } ?>
</ul>

<div class="tab-content pt-3">
    <div class="tab-pane fade show active" id="ns-manuale">
        <?php if (!$is_duplicate) { ?>
        <div class="alert alert-light border py-2 mb-3">
            <i class="fa fa-user mr-1"></i>
            <?php echo tr('Le Note spese registrano costi anticipati personalmente da un Operatore. Le uscite aziendali senza fattura seguono flussi distinti e non vengono importate automaticamente da Scadenzario o Automezzi.'); ?>
        </div>
        <?php } ?>

        <form action="" method="post" id="add-form">
            <input type="hidden" name="op" value="add">
            <input type="hidden" name="backto" value="record-edit">

            <?php if ($is_duplicate) { ?>
            <div class="alert alert-info py-2">
                <i class="fa fa-copy mr-1"></i><?php echo tr('Copia della spesa #_ID_: modifica i dati prima di salvarla. Gli allegati e l’origine non vengono copiati. La copia entrerà come Da verificare.', ['_ID_' => $duplicate_id]); ?>
            </div>
            <?php } ?>

            <div class="row">
                <div class="col-md-3">
                    {[ "type": "date", "label": "<?php echo tr('Data'); ?>", "name": "data", "required": 1, "value": "<?php echo prepareToField($default_date); ?>" ]}
                </div>
                <div class="col-md-3">
                    {[ "type": "select", "label": "<?php echo tr('Tipologia'); ?>", "name": "id_tipologia", "required": 1, "value": "<?php echo (int) $default_category; ?>", "values": "query=<?php echo $category_query; ?>" ]}
                </div>
                <div class="col-md-3">
                    {[ "type": "select", "label": "<?php echo tr('Operatore'); ?>", "name": "id_operatore", "required": 1, "value": "<?php echo (int) $default_operator; ?>", "values": "query=<?php echo $operator_query; ?>", "help": "<?php echo tr('Persona che ha anticipato personalmente la spesa.'); ?>" ]}
                </div>
                <div class="col-md-3">
                    {[ "type": "number", "label": "<?php echo tr('Importo'); ?>", "name": "importo", "required": 1, "value": "<?php echo $default_amount !== null ? prepareToField(number_format($default_amount, 2, '.', '')) : ''; ?>", "icon-after": "€", "min-value": "0.01", "decimals": 2 ]}
                </div>
            </div>

            <div class="row">
                <div class="col-md-8">
                    {[ "type": "text", "label": "<?php echo tr('Descrizione'); ?>", "name": "descrizione", "required": 1, "value": "<?php echo prepareToField($default_description); ?>" ]}
                </div>
                <div class="col-md-4">
                    {[ "type": "text", "label": "<?php echo tr('Controparte'); ?>", "name": "controparte", "value": "<?php echo prepareToField($default_counterparty); ?>", "help": "<?php echo tr('Esempio: Telepass, Q8, Comune, Ristorante.'); ?>" ]}
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    {[ "type": "textarea", "label": "<?php echo tr('Note'); ?>", "name": "note", "value": "<?php echo prepareToField($default_notes); ?>" ]}
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center">
                <small class="text-muted">
                    <i class="fa fa-check-square-o mr-1"></i><?php echo tr('La nuova registrazione entrerà come Da verificare e dovrà essere confermata esplicitamente.'); ?>
                </small>
                <button type="submit" class="btn btn-primary">
                    <i class="fa <?php echo $is_duplicate ? 'fa-copy' : 'fa-plus'; ?>"></i>
                    <?php echo $is_duplicate ? tr('Crea copia') : tr('Aggiungi spesa'); ?>
                </button>
            </div>
        </form>
    </div>

    <?php if (!$is_duplicate) { ?>
    <div class="tab-pane fade" id="ns-excel">
        <div class="alert alert-light border py-2 mb-3">
            <strong><?php echo tr('Formati supportati'); ?></strong><br>
            <span class="d-block"><code><?php echo tr('Data | Descrizione | Importo'); ?></code> — <?php echo tr('la tipologia viene proposta automaticamente.'); ?></span>
            <span class="d-block"><code><?php echo tr('Data | Tipologia | Descrizione | Importo | Controparte | Note'); ?></code></span>
            <small class="text-muted"><?php echo tr('Sono accettati dati separati da TAB o punto e virgola. Tutte le righe importate entrano come Da verificare.'); ?></small>
        </div>

        <form action="" method="post">
            <input type="hidden" name="op" value="import_excel">
            <input type="hidden" name="backto" value="record-list">

            <div class="row">
                <div class="col-md-5">
                    {[ "type": "select", "label": "<?php echo tr('Operatore'); ?>", "name": "id_operatore_excel", "required": 1, "value": "<?php echo (int) $default_operator; ?>", "values": "query=<?php echo $operator_query; ?>", "help": "<?php echo tr('L’Operatore selezionato viene associato a tutte le righe incollate.'); ?>" ]}
                </div>
            </div>

            <div class="form-group">
                <textarea class="form-control" name="righe_excel" rows="9" placeholder="07/08/2026&#9;Parcheggio cliente ABC&#9;8,00&#10;08/08/2026&#9;Vitto&#9;Pranzo trasferta&#9;18,50&#9;Ristorante XYZ&#9;Ricevuta"></textarea>
            </div>

            <div class="d-flex justify-content-between align-items-center">
                <small class="text-muted">
                    <i class="fa fa-info-circle mr-1"></i><?php echo tr('Le righe riconducibili a costi aziendali estranei alle Note spese vengono ignorate; gli importi negativi vengono convertiti in positivo.'); ?>
                </small>
                <button type="submit" class="btn btn-secondary"><i class="fa fa-paste"></i> <?php echo tr('Importa righe'); ?></button>
            </div>
        </form>
    </div>
    <?php } ?>
</div>
