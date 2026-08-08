<?php

include_once __DIR__.'/../../core.php';

$period_start = $_SESSION['period_start'] ?? date('Y-01-01');
$period_end = $_SESSION['period_end'] ?? date('Y-12-31');
$period_end_ts = $period_end.' 23:59:59';
$today = date('Y-m-d');
$default_date = noteSpeseIsDateInPeriod($today, $period_start, $period_end) ? $today : $period_end;
$lang = (int) Models\Locale::getDefault()->id;

$duplicate_id = (int) get('duplicate_id');
$duplicate_source = [];
if ($duplicate_id > 0) {
    $duplicate_source = $dbo->fetchOne('SELECT `data`, `id_tipologia`, `descrizione`, `importo`, `controparte`, `id_operatore`, `note` FROM `co_note_spese` WHERE `id` = '.prepare($duplicate_id).' LIMIT 1') ?: [];
}

$is_duplicate = !empty($duplicate_source);
$default_date = $is_duplicate ? (string) $duplicate_source['data'] : $default_date;
$default_category = $is_duplicate ? (int) $duplicate_source['id_tipologia'] : null;
$default_amount = $is_duplicate ? (float) $duplicate_source['importo'] : null;
$default_description = $is_duplicate ? (string) $duplicate_source['descrizione'] : '';
$default_counterparty = $is_duplicate ? (string) ($duplicate_source['controparte'] ?? '') : '';
$current_user = auth_osm()->getUser();
$current_operator = (int) ($current_user['id_anagrafica'] ?? 0);
$operator_candidate = $is_duplicate ? (int) ($duplicate_source['id_operatore'] ?? 0) : $current_operator;
$default_operator = noteSpeseOperatorExists($dbo, $operator_candidate) ? ($operator_candidate ?: null) : null;
$default_notes = $is_duplicate ? (string) ($duplicate_source['note'] ?? '') : '';

$automezzi_module = Models\Module::where('name', 'Automezzi')->first();
$scadenzario_module = Models\Module::where('name', 'Scadenzario')->first();
$can_read_automezzi = !empty($automezzi_module) && in_array(Modules::getPermission($automezzi_module->id), ['r', 'rw'], true);
$can_read_scadenzario = !empty($scadenzario_module) && in_array(Modules::getPermission($scadenzario_module->id), ['r', 'rw'], true);

$rifornimenti_summary = $can_read_automezzi ? $dbo->fetchOne(
    'SELECT COUNT(*) AS totale, COALESCE(SUM(r.`costo`), 0) AS importo FROM `an_automezzi_rifornimenti` r '
    .'WHERE r.`data` >= '.prepare($period_start).' AND r.`data` <= '.prepare($period_end_ts).' '
    .'AND NOT EXISTS (SELECT 1 FROM `co_note_spese` n WHERE n.`origine` = '.prepare('automezzi_rifornimento').' AND n.`id_origine` = r.`id`)'
) : ['totale' => 0, 'importo' => 0];
$rifornimenti_count = (int) ($rifornimenti_summary['totale'] ?? 0);

$rifornimenti = $can_read_automezzi ? $dbo->fetchArray(
    'SELECT r.`id`, r.`data`, r.`luogo`, r.`costo`, g.`descrizione` AS gestore, '
    .'s.`nome` AS automezzo_nome, s.`targa`, a.`ragione_sociale` AS tecnico '
    .'FROM `an_automezzi_rifornimenti` r '
    .'LEFT JOIN `an_automezzi_viaggi` v ON v.`id` = r.`id_viaggio` '
    .'LEFT JOIN `an_sedi` s ON s.`id` = v.`id_sede` '
    .'LEFT JOIN `an_automezzi_gestori` g ON g.`id` = r.`id_gestore` '
    .'LEFT JOIN `an_anagrafiche` a ON a.`id` = v.`id_tecnico` '
    .'WHERE r.`data` >= '.prepare($period_start).' AND r.`data` <= '.prepare($period_end_ts).' '
    .'AND NOT EXISTS (SELECT 1 FROM `co_note_spese` n WHERE n.`origine` = '.prepare('automezzi_rifornimento').' AND n.`id_origine` = r.`id`) '
    .'ORDER BY r.`data` DESC, r.`id` DESC LIMIT 200'
) : [];

$scadenze_summary = $can_read_scadenzario ? $dbo->fetchOne(
    'SELECT COUNT(*) AS totale, COALESCE(SUM(ABS(s.`da_pagare`)), 0) AS importo FROM `co_scadenzario` s '
    .'WHERE (s.`id_documento` IS NULL OR s.`id_documento` = 0) AND s.`da_pagare` < 0 '
    .'AND s.`scadenza` >= '.prepare($period_start).' AND s.`scadenza` <= '.prepare($period_end).' '
    .'AND NOT EXISTS (SELECT 1 FROM `co_note_spese` n WHERE n.`origine` = '.prepare('scadenzario_generico').' AND n.`id_origine` = s.`id`)'
) : ['totale' => 0, 'importo' => 0];
$scadenze_count = (int) ($scadenze_summary['totale'] ?? 0);

$scadenze = $can_read_scadenzario ? $dbo->fetchArray(
    'SELECT s.`id`, s.`scadenza`, s.`data_emissione`, s.`descrizione`, s.`tipo`, s.`da_pagare`, '
    .'a.`ragione_sociale` '
    .'FROM `co_scadenzario` s '
    .'LEFT JOIN `an_anagrafiche` a ON a.`id` = s.`id_anagrafica` '
    .'WHERE (s.`id_documento` IS NULL OR s.`id_documento` = 0) AND s.`da_pagare` < 0 '
    .'AND s.`scadenza` >= '.prepare($period_start).' AND s.`scadenza` <= '.prepare($period_end).' '
    .'AND NOT EXISTS (SELECT 1 FROM `co_note_spese` n WHERE n.`origine` = '.prepare('scadenzario_generico').' AND n.`id_origine` = s.`id`) '
    .'ORDER BY s.`scadenza` DESC, s.`id` DESC LIMIT 200'
) : [];

$category_query = 'SELECT t.`id`, COALESCE(l.`title`, t.`descrizione`) AS `descrizione` '
    .'FROM `co_note_spese_tipologie` t '
    .'LEFT JOIN `co_note_spese_tipologie_lang` l ON l.`id_record` = t.`id` AND l.`id_lang` = '.$lang.' '
    .'WHERE t.`enabled` = 1 ORDER BY t.`ordine`, `descrizione`';
$operator_query = noteSpeseOperatorSelectQuery($default_operator);
?>

<ul class="nav nav-tabs" role="tablist">
    <li class="nav-item"><a class="nav-link active" data-toggle="tab" href="#ns-manuale"><i class="fa fa-plus mr-1"></i><?php echo $is_duplicate ? tr('Duplica spesa') : tr('Nuova spesa'); ?></a></li>
    <?php if (!$is_duplicate && $can_read_automezzi) { ?><li class="nav-item"><a class="nav-link" data-toggle="tab" href="#ns-automezzi"><i class="fa fa-car mr-1"></i><?php echo tr('Automezzi'); ?> <span class="badge badge-info"><?php echo $rifornimenti_count; ?></span></a></li><?php } ?>
    <?php if (!$is_duplicate && $can_read_scadenzario) { ?><li class="nav-item"><a class="nav-link" data-toggle="tab" href="#ns-scadenzario"><i class="fa fa-calendar mr-1"></i><?php echo tr('Scadenzario'); ?> <span class="badge badge-warning"><?php echo $scadenze_count; ?></span></a></li><?php } ?>
    <?php if (!$is_duplicate) { ?><li class="nav-item"><a class="nav-link" data-toggle="tab" href="#ns-excel"><i class="fa fa-table mr-1"></i><?php echo tr('Incolla dati'); ?></a></li><?php } ?>
</ul>

<div class="tab-content pt-3">
    <div class="tab-pane fade show active" id="ns-manuale">
        <form action="" method="post" id="add-form">
            <input type="hidden" name="op" value="add">
            <input type="hidden" name="backto" value="record-edit">

            <?php if ($is_duplicate) { ?>
            <div class="alert alert-info py-2">
                <i class="fa fa-copy mr-1"></i><?php echo tr('Copia della spesa #_ID_: modifica data e importo prima di salvarla. Gli allegati e l’origine non vengono copiati.', ['_ID_' => $duplicate_id]); ?>
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
                    {[ "type": "select", "label": "<?php echo tr('Operatore / tecnico'); ?>", "name": "id_operatore", "value": "<?php echo (int) $default_operator; ?>", "values": "query=<?php echo $operator_query; ?>", "help": "<?php echo tr('Opzionale. Sono proposte solo le anagrafiche attive di tipo Tecnico; eventuali utenti collegati devono essere attivi.'); ?>" ]}
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
                <small class="text-muted"><i class="fa fa-calendar mr-1"></i><?php echo tr('Periodo selezionato: _START_ - _END_', [
                    '_START_' => Translator::dateToLocale($period_start),
                    '_END_' => Translator::dateToLocale($period_end),
                ]); ?></small>
                <button type="submit" class="btn btn-primary"><i class="fa <?php echo $is_duplicate ? 'fa-copy' : 'fa-plus'; ?>"></i> <?php echo $is_duplicate ? tr('Crea copia') : tr('Aggiungi spesa'); ?></button>
            </div>
        </form>
    </div>

    <?php if (!$is_duplicate && $can_read_automezzi) { ?>
    <div class="tab-pane fade" id="ns-automezzi">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <small class="text-muted"><?php echo tr('Disponibili nel periodo: _COUNT_ - totale _TOTAL_', [
                '_COUNT_' => $rifornimenti_count,
                '_TOTAL_' => moneyFormat($rifornimenti_summary['importo'] ?? 0, 2),
            ]); ?></small>
            <small class="text-muted"><?php echo tr('Le righe importate saranno Da verificare.'); ?></small>
        </div>
        <?php if ($rifornimenti_count > 200) { ?>
            <div class="alert alert-warning py-2"><?php echo tr('Sono disponibili _COUNT_ rifornimenti: vengono mostrati i primi 200.', ['_COUNT_' => $rifornimenti_count]); ?></div>
        <?php } ?>

        <?php if (!empty($rifornimenti)) { ?>
            <form action="" method="post">
                <input type="hidden" name="op" value="import_rifornimenti">
                <input type="hidden" name="backto" value="record-list">
                <div class="table-responsive" style="max-height:360px; overflow-y:auto;">
                    <table class="table table-sm table-striped table-hover">
                        <thead class="thead-light">
                            <tr>
                                <th style="width:35px"><input type="checkbox" id="ns-check-all-rifornimenti"></th>
                                <th><?php echo tr('Data'); ?></th>
                                <th><?php echo tr('Automezzo'); ?></th>
                                <th><?php echo tr('Tecnico'); ?></th>
                                <th><?php echo tr('Gestore / luogo'); ?></th>
                                <th class="text-right"><?php echo tr('Importo'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($rifornimenti as $rifornimento) { ?>
                            <tr>
                                <td><input class="ns-rifornimento" type="checkbox" name="rifornimenti[]" value="<?php echo (int) $rifornimento['id']; ?>" data-amount="<?php echo (float) $rifornimento['costo']; ?>"></td>
                                <td><?php echo Translator::timestampToLocale($rifornimento['data']); ?></td>
                                <td><?php echo htmlentities(trim(($rifornimento['automezzo_nome'] ?: '').' '.($rifornimento['targa'] ?: ''))); ?></td>
                                <td><?php echo htmlentities((string) $rifornimento['tecnico']); ?></td>
                                <td><?php echo htmlentities(trim(($rifornimento['gestore'] ?: '').' '.($rifornimento['luogo'] ?: ''))); ?></td>
                                <td class="text-right"><?php echo moneyFormat($rifornimento['costo'], 2); ?></td>
                            </tr>
                        <?php } ?>
                        </tbody>
                    </table>
                </div>
                <div class="d-flex justify-content-between align-items-center mt-2">
                    <small class="text-muted ns-selected-rifornimenti"><?php echo tr('Selezionati: 0 - totale 0,00'); ?></small>
                    <button type="submit" class="btn btn-info ns-import-rifornimenti-btn" disabled><i class="fa fa-download"></i> <?php echo tr('Importa selezionati'); ?></button>
                </div>
            </form>
        <?php } else { ?>
            <div class="alert alert-light"><i class="fa fa-check"></i> <?php echo tr('Nessun rifornimento disponibile nel periodo selezionato.'); ?></div>
        <?php } ?>
    </div>
    <?php } ?>

    <?php if (!$is_duplicate && $can_read_scadenzario) { ?>
    <div class="tab-pane fade" id="ns-scadenzario">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <small class="text-muted"><?php echo tr('Disponibili nel periodo: _COUNT_ - totale _TOTAL_', [
                '_COUNT_' => $scadenze_count,
                '_TOTAL_' => moneyFormat($scadenze_summary['importo'] ?? 0, 2),
            ]); ?></small>
            <small class="text-muted"><?php echo tr('Solo scadenze generiche a debito senza documento collegato.'); ?></small>
        </div>
        <?php if ($scadenze_count > 200) { ?>
            <div class="alert alert-warning py-2"><?php echo tr('Sono disponibili _COUNT_ scadenze: vengono mostrate le prime 200.', ['_COUNT_' => $scadenze_count]); ?></div>
        <?php } ?>

        <?php if (!empty($scadenze)) { ?>
            <form action="" method="post">
                <input type="hidden" name="op" value="import_scadenzario">
                <input type="hidden" name="backto" value="record-list">
                <div class="table-responsive" style="max-height:360px; overflow-y:auto;">
                    <table class="table table-sm table-striped table-hover">
                        <thead class="thead-light">
                            <tr>
                                <th style="width:35px"><input type="checkbox" id="ns-check-all-scadenze"></th>
                                <th><?php echo tr('Scadenza'); ?></th>
                                <th><?php echo tr('Controparte'); ?></th>
                                <th><?php echo tr('Descrizione'); ?></th>
                                <th class="text-right"><?php echo tr('Importo'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($scadenze as $scadenza) { ?>
                            <?php $scadenza_importo = abs((float) $scadenza['da_pagare']); ?>
                            <tr>
                                <td><input class="ns-scadenza" type="checkbox" name="scadenze[]" value="<?php echo (int) $scadenza['id']; ?>" data-amount="<?php echo $scadenza_importo; ?>"></td>
                                <td><?php echo Translator::dateToLocale($scadenza['scadenza']); ?></td>
                                <td><?php echo htmlentities((string) $scadenza['ragione_sociale']); ?></td>
                                <td><?php echo htmlentities((string) $scadenza['descrizione']); ?></td>
                                <td class="text-right"><?php echo moneyFormat($scadenza_importo, 2); ?></td>
                            </tr>
                        <?php } ?>
                        </tbody>
                    </table>
                </div>
                <div class="d-flex justify-content-between align-items-center mt-2">
                    <small class="text-muted ns-selected-scadenze"><?php echo tr('Selezionati: 0 - totale 0,00'); ?></small>
                    <button type="submit" class="btn btn-warning ns-import-scadenze-btn" disabled><i class="fa fa-download"></i> <?php echo tr('Importa selezionati'); ?></button>
                </div>
            </form>
        <?php } else { ?>
            <div class="alert alert-light"><i class="fa fa-check"></i> <?php echo tr('Nessuna scadenza generica a debito disponibile nel periodo.'); ?></div>
        <?php } ?>
    </div>
    <?php } ?>

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
            <div class="form-group">
                <textarea class="form-control" name="righe_excel" rows="9" placeholder="07/08/2026&#9;Parcheggio cliente ABC&#9;8,00&#10;08/08/2026&#9;Vitto&#9;Pranzo trasferta&#9;18,50&#9;Ristorante XYZ&#9;Ricevuta"></textarea>
            </div>
            <div class="d-flex justify-content-between align-items-center">
                <small class="text-muted"><i class="fa fa-info-circle mr-1"></i><?php echo tr('Se si incolla un estratto con importi negativi, il valore viene convertito in positivo perché il registro contiene soltanto costi.'); ?></small>
                <button type="submit" class="btn btn-secondary"><i class="fa fa-paste"></i> <?php echo tr('Importa righe'); ?></button>
            </div>
        </form>
    </div>
    <?php } ?>
</div>

<script>
$(document).ready(function () {
    function formatAmount(value) {
        if (window.Intl && Intl.NumberFormat) {
            return new Intl.NumberFormat(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}).format(value);
        }
        return Number(value).toFixed(2).replace('.', ',');
    }

    function updateSelection(selector, outputSelector, buttonSelector) {
        var selected = $(selector + ':checked');
        var total = 0;
        selected.each(function () {
            total += parseFloat($(this).data('amount')) || 0;
        });
        var selectedLabel = <?php echo json_encode(tr('Selezionati'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
        var totalLabel = <?php echo json_encode(tr('totale'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
        $(outputSelector).text(selectedLabel + ': ' + selected.length + ' - ' + totalLabel + ' ' + formatAmount(total));
        $(buttonSelector).prop('disabled', selected.length === 0);
    }

    $('#ns-check-all-rifornimenti').on('change', function () {
        $('.ns-rifornimento').prop('checked', this.checked);
        updateSelection('.ns-rifornimento', '.ns-selected-rifornimenti', '.ns-import-rifornimenti-btn');
    });
    $(document).on('change', '.ns-rifornimento', function () {
        updateSelection('.ns-rifornimento', '.ns-selected-rifornimenti', '.ns-import-rifornimenti-btn');
    });

    $('#ns-check-all-scadenze').on('change', function () {
        $('.ns-scadenza').prop('checked', this.checked);
        updateSelection('.ns-scadenza', '.ns-selected-scadenze', '.ns-import-scadenze-btn');
    });
    $(document).on('change', '.ns-scadenza', function () {
        updateSelection('.ns-scadenza', '.ns-selected-scadenze', '.ns-import-scadenze-btn');
    });
});
</script>
