<?php

namespace Modules\NoteSpese;

use Hooks\Manager;
use Models\Module;

/**
 * Hook per segnalare nella campanella OSM le spese ancora disponibili
 * per l'importazione da Automezzi e Scadenzario.
 */
class PendingExpensesHook extends Manager
{
    public function needsExecution()
    {
        return false;
    }

    public function execute()
    {
        return false;
    }

    public function response()
    {
        $dbo = database();
        $module = Module::where('name', 'Note spese')->first();

        if (empty($module) || !$dbo->tableExists('co_note_spese')) {
            return [
                'icon' => 'fa fa-money text-yellow',
                'link' => '',
                'message' => '',
                'show' => false,
            ];
        }

        $periodStart = $_SESSION['period_start'] ?? date('Y-01-01');
        $periodEnd = $_SESSION['period_end'] ?? date('Y-12-31');
        $periodEndTimestamp = $periodEnd.' 23:59:59';
        $count = 0;

        $automezzi = Module::where('name', 'Automezzi')->first();
        $canReadAutomezzi = !empty($automezzi)
            && in_array(\Modules::getPermission($automezzi->id), ['r', 'rw'], true)
            && $dbo->tableExists('an_automezzi_rifornimenti');

        if ($canReadAutomezzi) {
            $result = $dbo->fetchOne(
                'SELECT COUNT(*) AS totale FROM `an_automezzi_rifornimenti` r '
                .'WHERE r.`data` >= '.prepare($periodStart).' AND r.`data` <= '.prepare($periodEndTimestamp).' '
                .'AND NOT EXISTS (SELECT 1 FROM `co_note_spese` n WHERE n.`origine` = '.prepare('automezzi_rifornimento').' AND n.`id_origine` = r.`id`)'
            );
            $count += (int) ($result['totale'] ?? 0);
        }

        $scadenzario = Module::where('name', 'Scadenzario')->first();
        $canReadScadenzario = !empty($scadenzario)
            && in_array(\Modules::getPermission($scadenzario->id), ['r', 'rw'], true)
            && $dbo->tableExists('co_scadenzario');

        if ($canReadScadenzario) {
            $result = $dbo->fetchOne(
                'SELECT COUNT(*) AS totale FROM `co_scadenzario` s '
                .'WHERE (s.`id_documento` IS NULL OR s.`id_documento` = 0) AND s.`da_pagare` < 0 '
                .'AND s.`scadenza` >= '.prepare($periodStart).' AND s.`scadenza` <= '.prepare($periodEnd).' '
                .'AND NOT EXISTS (SELECT 1 FROM `co_note_spese` n WHERE n.`origine` = '.prepare('scadenzario_generico').' AND n.`id_origine` = s.`id`)'
            );
            $count += (int) ($result['totale'] ?? 0);
        }

        $message = $count === 1
            ? tr("C'e' 1 spesa da importare")
            : tr('Ci sono _NUM_ spese da importare', ['_NUM_' => $count]);

        return [
            'icon' => 'fa fa-money text-yellow',
            'link' => base_path_osm().'/controller.php?id_module='.$module->id,
            'message' => $message,
            'show' => $count > 0,
        ];
    }
}
