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

namespace Modules\Backups;

use Backup;
use Tasks\Manager;

/**
 * Task dedicato alla gestione del backup giornaliero automatico, se abilitato da Impostazioni.
 */
class BackupTask extends Manager
{
    public function needsExecution()
    {
        return setting('Backup automatico') && !Backup::isDailyComplete();
    }

    public function execute()
    {
        if (setting('Backup automatico') && !Backup::isDailyComplete()) {
            Backup::daily();
        }
    }
}
