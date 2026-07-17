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

namespace Modules\Interventi\API\v1;

use API\Interfaces\UpdateInterface;
use API\Resource;
use Models\Module;

class Firma extends Resource implements UpdateInterface
{
    public function update($request)
    {
        $database = database();
        $data = $request['data'];

        $database->update('in_interventi', [
            'firma_data' => $data['firma_data'],
            'firma_nome' => $data['firma_nome'],
        ], ['id' => $data['id']]);

        if (!empty($data['firma_contenuto'])) {
            $this->salvaFirma($data['firma_contenuto'], $data['id']);
        }

        return [
            'id' => $data['id'],
            'status' => 'success',
        ];
    }

    protected function salvaFirma($firma_base64, $id_intervento)
    {
        if (empty($firma_base64)) {
            return;
        }

        $parts = explode(',', (string) $firma_base64);
        if (count($parts) < 2) {
            return;
        }

        try {
            $img = getImageManager()->decodeBinary(base64_decode($parts[1]));
            $img->scaleDown(680, 202);
            $encoded_image = $img->encodeUsingMediaType('image/jpeg');
            $file_content = $encoded_image->toString();

            $module_id = Module::where('name', 'Interventi')->first()->id;

            \Uploads::upload($file_content, [
                'name' => 'firma.jpg',
                'category' => 'Firme',
                'id_module' => $module_id,
                'id_record' => $id_intervento,
                'key' => 'signature',
            ]);
        } catch (\Exception $e) {
        }
    }
}
