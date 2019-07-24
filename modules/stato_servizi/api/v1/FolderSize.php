<?php

namespace Modules\StatoServizi\API\v1;

use API\Interfaces\RetrieveInterface;
use API\Resource;
use App;
use Util\FileSystem;

class FolderSize extends Resource implements RetrieveInterface
{
    public function retrieve($request)
    {
        $dirs = $request['dirs'];

        if (empty($dirs)) {
            $backup_dir = App::getConfig()['backup_dir'];

            $dirs = [
                $backup_dir => tr('Backup'),
                DOCROOT.'/files' => tr('Allegati'),
                DOCROOT.'/logs' => tr('Logs'),
            ];
        } else {
            $array = explode(',', $dirs);
            foreach ($array as $key => $value) {
                $dirs = [
                    DOCROOT.'/'.$value => $key,
                ];
            }
        }

        $results = [];
        $total = 0;
        foreach ($dirs as $dir => $description) {
            $size = FileSystem::folderSize($dir);

            $results[] = [
                'description' => $description,
                'size' => $size,
                'formattedSize' => FileSystem::formatBytes($size),
            ];

            $total += $size;
        }

        $response = [
            'dirs' => $results,
            'size' => $total,
            'formattedSize' => FileSystem::formatBytes($total),
        ];

        return $response;
    }
}
