<?php

namespace Modules\Backups;

use Backup;
use Hooks\Manager;

class BackupHook extends Manager
{
    public function manage()
    {
        $result = Backup::daily();

        return $result;
    }

    public function response($update)
    {
        return [
            'icon' => 'fa fa-file-o text-info',
            'message' => tr('Backup completato!'),
            'notify' => true,
        ];
    }

    public function prepare()
    {
        $result = setting('Backup automatico') && !Backup::isDailyComplete() && self::getHook()->processing == 0;

        return [
            'icon' => 'fa fa-file-o text-danger',
            'message' => tr('Backup in corso...'),
            'execute' => $result,
        ];
    }
}
