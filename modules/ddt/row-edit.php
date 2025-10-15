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

use Modules\DDT\DDT;

include_once __DIR__.'/../../core.php';

// Info contratto
$documento = DDT::find($id_record);

// Impostazioni per la gestione
$options = [
    'op' => 'manage_riga',
    'action' => 'edit',
    'dir' => $documento->direzione,
    'idanagrafica' => $documento['idanagrafica'],
    'totale_imponibile_documento' => $documento->totale_imponibile,
    'totale_documento' => $documento->totale,
    'select-options' => [
        'articoli' => [
            'idanagrafica' => $documento->idanagrafica,
            'dir' => $documento->direzione,
            'idsede_partenza' => $documento->idsede_partenza,
            'idsede_destinazione' => $documento->idsede_destinazione,
            'permetti_movimento_a_zero' => intval($documento->direzione == 'uscita'),
        ],
    ],
];

// Dati della riga
$id_riga = get('riga_id');
$type = get('riga_type');
$riga = $documento->getRiga($type, $id_riga);

$result = $riga->toArray();
$result['prezzo'] = $riga->prezzo_unitario;
$result['type'] = $type;
if ($result['sconto'] == 0) {
    $result['tipo_sconto'] = (setting('Tipo di sconto predefinito') == '%' ? 'PRC' : 'UNT');
}

// Importazione della gestione dedicata
$file = 'riga';
if ($riga->isDescrizione()) {
    $file = 'descrizione';

    $options['op'] = 'manage_descrizione';
} elseif ($riga->isArticolo()) {
    $file = 'articolo';

    $options['op'] = 'manage_articolo';
} elseif ($riga->isSconto()) {
    $file = 'sconto';

    $options['op'] = 'manage_sconto';
}

echo App::load($file.'.php', $result, $options);
