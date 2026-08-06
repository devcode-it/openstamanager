<?php

/*
 * OpenSTAManager: il software gestionale open source per l'assistenza tecnica e la fatturazione
 * Copyright (C) DevCode s.r.l.
 *
 * Personalizzazione Scadenzario: riepilogo operativo per stato, coerente con
 * i permessi, il segmento attivo e gli eventuali placeholder temporali.
 */

use Util\Query;

$escape = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

$remove_outer_order_and_limit = static function ($query): string {
    $query = (string) $query;
    $length = strlen($query);
    $depth = 0;
    $quote = null;
    $escape_next = false;
    $cut_position = null;

    for ($i = 0; $i < $length; ++$i) {
        $char = $query[$i];

        if ($quote !== null) {
            if ($escape_next) {
                $escape_next = false;
                continue;
            }

            if ($char === '\\') {
                $escape_next = true;
                continue;
            }

            if ($char === $quote) {
                if ($i + 1 < $length && $query[$i + 1] === $quote) {
                    ++$i;
                    continue;
                }

                $quote = null;
            }

            continue;
        }

        if ($char === "'" || $char === '"' || $char === '`') {
            $quote = $char;
            continue;
        }

        if ($char === '(') {
            ++$depth;
            continue;
        }

        if ($char === ')') {
            $depth = max(0, $depth - 1);
            continue;
        }

        if ($depth !== 0) {
            continue;
        }

        $remaining = substr($query, $i);
        if (preg_match('/^\s*(?:ORDER\s+BY|LIMIT)\b/i', $remaining)) {
            $cut_position = $i;
            break;
        }
    }

    if ($cut_position !== null) {
        $query = substr($query, 0, $cut_position);
    }

    return rtrim(trim($query), ';');
};

$states = [
    'paid' => [
        'title' => tr('Scadenze pagate'),
        'short_title' => tr('Pagate'),
        'color' => '#CCFFCC',
        'icon' => 'fa fa-check-circle',
        'amount_label' => tr('saldati'),
    ],
    'agreed_overdue' => [
        'title' => tr('Data concordata superata'),
        'short_title' => tr('Concordata superata'),
        'color' => '#ec5353',
        'icon' => 'fa fa-exclamation-circle',
        'amount_label' => tr('residui'),
    ],
    'agreed' => [
        'title' => tr('Data concordata'),
        'short_title' => tr('Concordata'),
        'color' => '#b3d2e3',
        'icon' => 'fa fa-calendar-check-o',
        'amount_label' => tr('residui'),
    ],
    'overdue' => [
        'title' => tr('Scaduta'),
        'short_title' => tr('Scadute'),
        'color' => '#f08080',
        'icon' => 'fa fa-calendar-times-o',
        'amount_label' => tr('residui'),
    ],
    'due_soon' => [
        'title' => tr('Scadenza entro 10 giorni'),
        'short_title' => tr('Entro 10 giorni'),
        'color' => '#f9f9c6',
        'icon' => 'fa fa-clock-o',
        'amount_label' => tr('residui'),
    ],
    'future' => [
        'title' => tr('Scadenza futura'),
        'short_title' => tr('Future'),
        'color' => '#ffffff',
        'icon' => 'fa fa-calendar-o',
        'amount_label' => tr('residui'),
    ],
];

$summary = array_fill_keys(array_keys($states), [
    'count' => 0,
    'amount' => 0.0,
]);

$id_segment = $_SESSION['module_'.$id_module]['id_segment'] ?? null;
$segment_title = tr('Tutte le scadenze');

if (!empty($id_segment)) {
    $segment = $dbo->fetchOne('SELECT COALESCE(NULLIF(`zz_segments_lang`.`title`, \'\'), `zz_segments`.`name`) AS `title`
        FROM `zz_segments`
        LEFT JOIN `zz_segments_lang` ON (`zz_segments`.`id` = `zz_segments_lang`.`id_record` AND `zz_segments_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).')
        WHERE `zz_segments`.`id` = '.prepare($id_segment).' AND `zz_segments`.`id_module` = '.prepare($id_module));

    if (!empty($segment['title'])) {
        $segment_title = $segment['title'];
    }
}

$segment_title = html_entity_decode((string) $segment_title, ENT_QUOTES | ENT_HTML5, 'UTF-8');
$segment_title = strip_tags($segment_title);
$segment_title = trim((string) preg_replace('/\s+/', ' ', $segment_title));

$current_user = auth_osm()->getUser();
$current_user_id = (int) (is_array($current_user)
    ? ($current_user['id'] ?? 0)
    : ($current_user->id ?? 0));

$state_view = $dbo->fetchOne("SELECT COALESCE(NULLIF(`zz_views_lang`.`title`, ''), `zz_views`.`name`) AS `title`
    FROM `zz_views`
    INNER JOIN `zz_group_view` ON `zz_group_view`.`id_vista` = `zz_views`.`id`
    INNER JOIN `zz_users` ON `zz_users`.`idgruppo` = `zz_group_view`.`id_gruppo`
    LEFT JOIN `zz_views_lang` ON (`zz_views`.`id` = `zz_views_lang`.`id_record` AND `zz_views_lang`.`id_lang` = ".prepare(Models\Locale::getDefault()->id).")
    WHERE `zz_views`.`id_module` = ".prepare($id_module)."
        AND `zz_views`.`name` = ".prepare('Stato scadenza')."
        AND `zz_users`.`id` = ".prepare($current_user_id)."
    LIMIT 1");

$state_field = $state_view['title'] ?? 'Stato scadenza';
$state_header_id = 'th_'.searchFieldName($state_field);
$filter_available = !empty($state_view);
$summary_error = false;

unset($_SESSION['module_'.$id_module]['_search_'.searchFieldName($state_field)]);

$summary_cache_ttl = 30;
$summary_cache_key = hash('sha256', implode('|', [
    (string) $id_module,
    (string) $current_user_id,
    (string) $id_segment,
    (string) ($_SESSION['period_start'] ?? ''),
    (string) ($_SESSION['period_end'] ?? ''),
]));
$summary_cache = $_SESSION['osm_scadenzario_widget_cache'][$summary_cache_key] ?? null;

if (
    is_array($summary_cache)
    && isset($summary_cache['created_at'], $summary_cache['summary'])
    && (time() - (int) $summary_cache['created_at']) <= $summary_cache_ttl
    && is_array($summary_cache['summary'])
) {
    foreach ($summary_cache['summary'] as $state => $values) {
        if (isset($summary[$state]) && is_array($values)) {
            $summary[$state]['count'] = (int) ($values['count'] ?? 0);
            $summary[$state]['amount'] = (float) ($values['amount'] ?? 0);
        }
    }
} else {
    try {
        $state_expression = "IF(pagato=da_pagare,'paid',IF(data_concordata<>'0000-00-00',IF(data_concordata<NOW(),'agreed_overdue','agreed'),IF(scadenza<NOW(),'overdue',IF(DATEDIFF(scadenza,NOW())<10,'due_soon','future'))))";

        $technical_select = [
            '`co_scadenziario`.`id` AS `osm_scadenza_id`',
            $state_expression.' AS `osm_scadenza_stato`',
            'ABS(`co_scadenziario`.`da_pagare` - `co_scadenziario`.`pagato`) AS `osm_scadenza_residuo`',
            'ABS(`co_scadenziario`.`da_pagare`) AS `osm_scadenza_importo`',
        ];

        $having_additionals = Modules::getAdditionalsQuery($id_module, 'HVN');

        if (!empty($having_additionals)) {
            $module_query = Query::readQuery($structure);
            $detail_query = $module_query['query'] ?? '';
            $replacements = 0;
            $detail_query = preg_replace(
                '/^\s*SELECT\s+/i',
                'SELECT '.implode(', ', $technical_select).', ',
                (string) $detail_query,
                1,
                $replacements
            );

            if ($replacements !== 1) {
                throw new RuntimeException('SELECT modulare dello Scadenzario non riconosciuta.');
            }
        } else {
            $module = $dbo->fetchOne('SELECT `options`, `options2` FROM `zz_modules` WHERE `id` = '.prepare($id_module));
            $base_query = !empty($module['options2']) ? $module['options2'] : ($module['options'] ?? '');

            if (empty($base_query) || !str_contains($base_query, '|select|')) {
                throw new RuntimeException('Query modulare dello Scadenzario non compatibile con il riepilogo.');
            }

            $detail_query = str_replace('|select|', implode(', ', $technical_select), $base_query);
        }

        $detail_query = Modules::replaceAdditionals($id_module, $detail_query);
        $detail_query = Query::replacePlaceholder($detail_query);
        $detail_query = $remove_outer_order_and_limit($detail_query);

        $summary_query = 'SELECT
                `osm_scadenza_stato` AS `state`,
                COUNT(*) AS `count`,
                SUM(`osm_scadenza_valore`) AS `amount`
            FROM (
                SELECT
                    `osm_scadenza_id`,
                    MAX(`osm_scadenza_stato`) AS `osm_scadenza_stato`,
                    MAX(IF(`osm_scadenza_stato` = \'paid\', `osm_scadenza_importo`, `osm_scadenza_residuo`)) AS `osm_scadenza_valore`
                FROM ('.$detail_query.') AS `osm_scadenzario_widget_source`
                GROUP BY `osm_scadenza_id`
            ) AS `osm_scadenzario_widget_rows`
            GROUP BY `osm_scadenza_stato`';

        $rows = $dbo->fetchArray($summary_query);
        foreach ($rows as $row) {
            $state = $row['state'] ?? '';
            if (isset($summary[$state])) {
                $summary[$state]['count'] = (int) ($row['count'] ?? 0);
                $summary[$state]['amount'] = (float) ($row['amount'] ?? 0);
            }
        }

        $_SESSION['osm_scadenzario_widget_cache'][$summary_cache_key] = [
            'created_at' => time(),
            'summary' => $summary,
        ];
    } catch (Throwable $e) {
        $summary_error = true;
        error_log('[Scadenzario widget] '.$e->getMessage());
    }
}

$js_path = $structure->filepath('js/scadenzario-widgets.js');
$css_path = $structure->filepath('css/scadenzario-widgets.css');
$js_url = $structure->fileurl('js/scadenzario-widgets.js');
$css_url = $structure->fileurl('css/scadenzario-widgets.css');

$asset_version = (string) max(
    $js_path && is_file($js_path) ? (int) filemtime($js_path) : 0,
    $css_path && is_file($css_path) ? (int) filemtime($css_path) : 0
);

echo '
<link rel="stylesheet" href="'.$escape($css_url).'?v='.$asset_version.'">

<section class="osm-scadenzario-summary" id="osm-scadenzario-summary"
    data-module-id="'.$escape($id_module).'"
    data-state-header-id="'.$escape($state_header_id).'"
    data-state-field="'.$escape($state_field).'"
    data-filter-enabled="'.($filter_available ? '1' : '0').'"
    data-state-column-index=""
    data-active-state="">
    <div class="osm-scadenzario-summary-header">
        <div>
            <h4><i class="fa fa-line-chart"></i> '.tr('Riepilogo scadenze').'</h4>
            <div class="osm-scadenzario-segment">
                '.tr('Segmento attivo').': <strong>'.$escape($segment_title).'</strong>
            </div>
        </div>
        <button type="button" class="btn btn-default btn-sm osm-scadenzario-reset-filter hide">
            <i class="fa fa-times"></i> '.tr('Mostra tutte').'
        </button>
    </div>';

if ($summary_error) {
    echo '
    <div class="alert alert-warning osm-scadenzario-summary-error">
        <i class="fa fa-exclamation-triangle"></i>
        '.tr('Riepilogo temporaneamente non disponibile. La tabella dello Scadenzario rimane utilizzabile.').'
    </div>';
} else {
    if (!$filter_available) {
        echo '
    <div class="alert alert-info osm-scadenzario-summary-error">
        <i class="fa fa-info-circle"></i>
        '.tr('La vista tecnica necessaria ai filtri non è disponibile per il gruppo utente corrente. Completare l’aggiornamento dello Scadenzario.').'
    </div>';
    }

    echo '
    <div class="row osm-scadenzario-widget-row">';

    foreach ($states as $key => $state) {
        $count = $summary[$key]['count'];
        $amount = Translator::numberToLocale($summary[$key]['amount'], 2);
        $disabled = (!$filter_available || $count === 0) ? ' disabled aria-disabled="true"' : '';

        echo '
        <div class="col-xl-2 col-lg-4 col-md-6 col-sm-6 col-12">
            <button type="button"
                class="osm-scadenzario-widget"
                data-state="'.$escape($key).'"
                title="'.$escape($state['title']).'"
                aria-pressed="false"
                style="--osm-widget-color: '.$escape($state['color']).';"'.$disabled.'>
                <span class="osm-scadenzario-widget-icon"><i class="'.$escape($state['icon']).'"></i></span>
                <span class="osm-scadenzario-widget-content">
                    <span class="osm-scadenzario-widget-title">'.$escape($state['short_title']).'</span>
                    <span class="osm-scadenzario-widget-count">'.$count.' '.($count === 1 ? tr('scadenza') : tr('scadenze')).'</span>
                    <span class="osm-scadenzario-widget-amount">&euro; '.$escape($amount).' <small>'.$escape($state['amount_label']).'</small></span>
                </span>
            </button>
        </div>';
    }

    echo '
    </div>';
}

echo '
</section>

<script src="'.$escape($js_url).'?v='.$asset_version.'"></script>';
