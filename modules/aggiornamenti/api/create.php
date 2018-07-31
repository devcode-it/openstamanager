<?php

switch ($resource) {
    case 'allegato':
        $module = Modules::get($request['module']);

        $upload = Uploads::upload($_FILES['upload'], [
            'name' => $request['name'],
            'id_module' => $module['id'],
            'id_record' => $id_record,
        ]);

        $results['filename'] = $upload;

        break;
}

return [
    'allegato',
];
