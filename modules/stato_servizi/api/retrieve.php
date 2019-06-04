<?php

use Util\FileSystem;

switch ($resource) {
    case 'folder_size':

        $dirs = $_GET['dirs'];

        if (empty($dirs)) {
            $backup_dir = App::getConfig()['backup_dir'];
            $dirs = [
                $backup_dir => tr('Backup'),
                'files' => tr('Allegati'),
                'logs' => tr('Logs'),
            ];
        } else {
            $array = explode(',', $dirs);
            foreach ($array as $key => $value) {
                $dirs = [
                    $value => $key,
                ];
            }
        }

        $tot_byte_size = 0;
        foreach ($dirs as $dir => $description) {
            $size = FileSystem::folderSize($dir);

            $results[] = [
                'description' => $description,
                'size' => $size,
                'formattedSize' => FileSystem::formatBytes($size),
            ];

            $tot_byte_size += $size;
        }

        $results[count($dirs)]['totalbyte'] = $tot_byte_size;

        $response = $results;

        break;
}

return [
    'folder_size',
];
