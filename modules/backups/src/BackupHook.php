<?php

namespace Modules\Backups;

use Backup;
use Hooks\Manager;

class BackupHook extends Manager
{
    public function isSingleton()
    {
        return true;
    }

    public function needsExecution()
    {
        return setting('Backup automatico') && !Backup::isDailyComplete();
    }

    public function execute()
    {
        $result = Backup::daily();

        return $result;
    }

    public function response()
    {
        $show = boolval(setting('Backup automatico')) && !Backup::isDailyComplete();
        $message = $show ? tr('Backup in corso...') : tr('Backup automatico completato!');

        return [
            'icon' => 'fa fa-file-o text-success',
            'message' => $message,
            'show' => $show,
        ];
    }
}
