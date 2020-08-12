<?php

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
