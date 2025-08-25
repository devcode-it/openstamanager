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

namespace Plugins\ImportFE;

use Hooks\Manager;
use Models\Module;
use Models\Cache;

/**
 * Hook dedicato all'importazione automatica delle Fatture Elettroniche di acquisto rilevate dal sistema automatico di gestione.
 */
class InvoiceHook extends Manager

{
    public $cache_name = 'Fatture Elettroniche';

    public function needsExecution()
    {
        return true;
    }

    public function execute()
    {
        return false;
    }

    public function response()
    {
        $results = Cache::where('name', $this->cache_name)->first()->content;

        $count = $results ? count($results) : 0;
        $notify = false;

        $module = Module::where('name', 'Fatture di acquisto')->first();
        $plugins = $module->plugins;

        if (!empty($plugins)) {
            $notify = !empty($count);

            $plugin = $plugins->first(fn ($value, $key) => $value->getTranslation('title') == 'Fatturazione Elettronica');

            $link = base_path().'/controller.php?id_module='.$module->id.'#tab_'.$plugin->id;
        }

        $message = tr('_NUM_ fattur_A_ passiv_A_ da importare', [
            '_NUM_' => (($count > 1) ? tr('Ci sono') : tr('C\'è')).' '.$count,
            '_A_' => (($count > 1) ? tr('e') : tr('a')),
        ]);

        return [
            'icon' => 'fa fa-file-text-o text-yellow',
            'link' => $link,
            'message' => $message,
            'show' => $notify,
        ];
    }
}
