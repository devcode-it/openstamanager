<?php

$dbo = database();
$period_start = $_SESSION['period_start'] ?? date('Y-01-01');
$period_end = $_SESSION['period_end'] ?? date('Y-12-31');
$id_module_note_spese = (int) ($widget['id_module'] ?? $id_module ?? 0);

if (!function_exists('noteSpeseWidgetSummary')) {
    /**
     * Dati condivisi dai widget della stessa richiesta.
     */
    function noteSpeseWidgetSummary($dbo, $period_start, $period_end, $id_module_note_spese)
    {
        static $cache = [];

        $cache_key = $period_start.'|'.$period_end.'|'.$id_module_note_spese;
        if (isset($cache[$cache_key])) {
            return $cache[$cache_key];
        }

        // Le confermate valide ai fini della rendicontazione 1.9.0 devono
        // avere Operatore e Tipologia attiva. Le righe storiche non coerenti
        // restano visibili nel registro ma non alterano i KPI.
        $summary = $dbo->fetchOne(
            'SELECT '
            .'COALESCE(SUM(IF(st.`name` = '.prepare('confermato').' AND COALESCE(n.`id_operatore`, 0) > 0 AND COALESCE(t.`enabled`, 0) = 1, n.`importo`, 0)), 0) AS confermato_totale, '
            .'SUM(IF(st.`name` = '.prepare('confermato').' AND COALESCE(n.`id_operatore`, 0) > 0 AND COALESCE(t.`enabled`, 0) = 1, 1, 0)) AS confermato_righe, '
            .'COALESCE(SUM(IF(st.`name` = '.prepare('da_verificare').', n.`importo`, 0)), 0) AS verifica_totale, '
            .'SUM(IF(st.`name` = '.prepare('da_verificare').', 1, 0)) AS verifica_righe '
            .'FROM `co_note_spese` n '
            .'LEFT JOIN `co_note_spese_stati` st ON st.`id` = n.`id_stato` '
            .'LEFT JOIN `co_note_spese_tipologie` t ON t.`id` = n.`id_tipologia` '
            .'WHERE n.`data` >= '.prepare($period_start).' AND n.`data` <= '.prepare($period_end)
        ) ?: [];

        $summary['senza_allegati'] = 0;
        if ($id_module_note_spese > 0) {
            $result = $dbo->fetchOne(
                'SELECT COUNT(*) AS totale '
                .'FROM `co_note_spese` n '
                .'INNER JOIN `co_note_spese_stati` st ON st.`id` = n.`id_stato` AND st.`name` = '.prepare('confermato').' '
                .'INNER JOIN `co_note_spese_tipologie` t ON t.`id` = n.`id_tipologia` AND t.`enabled` = 1 '
                .'WHERE n.`data` >= '.prepare($period_start).' AND n.`data` <= '.prepare($period_end).' '
                .'AND COALESCE(n.`id_operatore`, 0) > 0 '
                .'AND NOT EXISTS ('
                .'SELECT 1 FROM `zz_files` f '
                .'WHERE f.`id_module` = '.prepare($id_module_note_spese).' '
                .'AND f.`id_plugin` IS NULL '
                .'AND f.`id_record` = n.`id` '
                .'AND (f.`key` IS NULL OR f.`key` = "")'
                .')'
            );
            $summary['senza_allegati'] = (int) ($result['totale'] ?? 0);
        }

        $cache[$cache_key] = $summary;

        return $summary;
    }
}

if (!function_exists('noteSpeseWidgetValue')) {
    function noteSpeseWidgetValue($primary, $secondary)
    {
        return '<span class="ns-widget-value" style="display:flex;flex-direction:column;justify-content:flex-start;min-height:38px;margin:0;line-height:1.15;">'
            .'<span style="display:block;margin:0;line-height:1.15;">'.$primary.'</span>'
            .'<small class="text-muted font-weight-normal" style="display:block;line-height:1.15;margin-top:3px;">'.$secondary.'</small>'
            .'</span>';
    }
}

$summary = noteSpeseWidgetSummary($dbo, $period_start, $period_end, $id_module_note_spese);
$name = $widget['name'] ?? '';

switch ($name) {
    case 'Note spese - confermate':
        echo noteSpeseWidgetValue(
            moneyFormat($summary['confermato_totale'] ?? 0, 2),
            tr('_NUM_ registrazioni', ['_NUM_' => (int) ($summary['confermato_righe'] ?? 0)])
        );
        break;

    case 'Note spese - da verificare':
        echo noteSpeseWidgetValue(
            moneyFormat($summary['verifica_totale'] ?? 0, 2),
            tr('_NUM_ da verificare', ['_NUM_' => (int) ($summary['verifica_righe'] ?? 0)])
        );
        break;

    case 'Note spese - senza allegati':
        echo noteSpeseWidgetValue(
            (int) ($summary['senza_allegati'] ?? 0),
            tr('spese confermate')
        );
        break;

    default:
        echo noteSpeseWidgetValue('0', '&nbsp;');
        break;
}
