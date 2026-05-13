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

use Modules\Preventivi\Preventivo;

include_once __DIR__.'/../../core.php';

// Info contratto
$documento = Preventivo::find($id_record);

// Impostazioni per la gestione
$options = [
    'op' => 'manage_riga',
    'action' => 'add',
    'dir' => $documento->direzione,
    'id_anagrafica' => $documento['id_anagrafica'],
    'totale_imponibile_documento' => $documento->totale_imponibile,
    'totale_documento' => $documento->totale,
    'select-options' => [
        'articoli' => [
            'id_anagrafica' => $documento->id_anagrafica,
            'dir' => $documento->direzione,
            'permetti_movimento_a_zero' => 1,
            'idagente' => $documento->idagente,
        ],
    ],
];

// Dati di default
$result = [
    'descrizione' => '',
    'qta' => 1,
    'um' => '',
    'prezzo' => 0,
    'sconto_unitario' => 0,
    'tipo_sconto' => (setting('Tipo di sconto predefinito') == '%' ? 'PRC' : 'UNT'),
    'idiva' => '',
    'provvigione_default' => 0,
    'tipo_provvigione_default' => 'PRC',
];

// Leggo la provvigione predefinita per l'anagrafica
$result['provvigione_default'] = $dbo->fetchOne('SELECT provvigione_default FROM an_anagrafiche WHERE id='.prepare($documento->idagente))['provvigione_default'];

// Leggo l'iva predefinita per l'anagrafica e se non c'è leggo quella predefinita generica
$iva = $dbo->fetchArray('SELECT id_iva_vendite AS idiva FROM an_anagrafiche WHERE id='.prepare($documento['id_anagrafica']));
$result['idiva'] = $iva[0]['idiva'] ?: setting('Iva predefinita');

// Importazione della gestione dedicata
$file = 'riga';
if (!empty(get('is_descrizione'))) {
    $file = 'descrizione';

    $options['op'] = 'manage_descrizione';
} elseif (!empty(get('is_sconto'))) {
    $file = 'sconto';

    $options['op'] = 'manage_sconto';
}

echo App::load($file.'.php', $result, $options);
