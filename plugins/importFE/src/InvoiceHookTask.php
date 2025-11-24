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

use Tasks\Manager;

class InvoiceHookTask extends Manager
{
    public function execute()
    {
        $result = [
            'response' => 1,
            'message' => tr('FE passive aggiornate correttamente!'),
        ];

        try {
            $list = Interaction::getInvoiceList();
            if (empty($list)) {
                $result['message'] = tr('Nessuna FE passiva da importare');
            }
        } catch (\Exception $e) {
            $result['response'] = 2;
            $result['message'] = tr('Errore durante l\'importazione delle FE passive: _ERR_', [
                '_ERR_' => $e->getMessage(),
            ]).'<br>';
        }

        return $result;
    }
}
