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

use Hooks\CachedManager;
use Models\Module;

/**
 * Hook dedicato all'importazione automatica delle Fatture Elettroniche di acquisto rilevate dal sistema automatico di gestione.
 */
class InvoiceHook extends CachedManager
{
    public function getCacheName()
    {
        return 'Fatture Elettroniche';
    }

    public function cacheData()
    {
        return Interaction::getInvoiceList();
    }

    public function response()
    {
        $results = (array) $this->getCache()->content;

        $count = count($results);
        $notify = false;

        $module = Module::find((new Module())->getByField('name', 'Fatture di acquisto'));
        $plugins = $module->plugins;

        if (!empty($plugins)) {
            $notify = !empty($count);

            $plugin = $plugins->first(function ($value, $key) {
                return $value->getTranslation('name') == 'Fatturazione Elettronica';
            });

            $link = base_path().'/controller.php?id_module='.$module->id.'#tab_'.$plugin->id;
        }

        $message = tr('_NUM_ fattur_A_ passiv_A_ da importare', [
            '_NUM_' => (($count>1) ? tr('Ci sono'): tr('C\'è')).' '.$count,
            '_A_' => (($count>1) ? tr('e'): tr('a'))
        ]);

        return [
            'icon' => 'fa fa-file-text-o text-yellow',
            'link' => $link,
            'message' => $message,
            'show' => $notify,
        ];
    }
}
