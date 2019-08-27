<?php

namespace Modules\Backups;

use Backup;
use Hooks\Manager;

class BackupHook extends Manager
{
    public function execute()
    {
        $result = Backup::daily();

        return $result;
    }

    public function response($data)
    {
        return [
            'icon' => 'fa fa-file-o text-info',
            'message' => tr('Backup completato!'),
            'show' => true,
        ];
    }

    public function prepare()
    {
        $result = setting('Backup automatico') && !Backup::isDailyComplete();

        return [
            'icon' => 'fa fa-file-o text-danger',
            'message' => tr('Backup in corso...'),
            'execute' => $result,
        ];
    }
}
