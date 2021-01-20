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

use Modules\Interventi\Intervento;

include_once __DIR__.'/../../core.php';

$documento = Intervento::find($id_record);
$show_prezzi = Auth::user()['gruppo'] != 'Tecnici' || (Auth::user()['gruppo'] == 'Tecnici' && setting('Mostra i prezzi al tecnico'));

// Impostazioni per la gestione
$options = [
    'op' => 'manage_riga',
    'action' => 'add',
    'dir' => $documento->direzione,
    'idanagrafica' => $documento['idanagrafica'],
    'totale_imponibile_documento' => $documento->totale_imponibile,
    'totale_documento' => $documento->totale,
    'nascondi_prezzi' => !$show_prezzi,
    'select-options' => [
        'articoli' => [
            'idanagrafica' => $documento->idanagrafica,
            'dir' => $documento->direzione,
            'idsede_partenza' => $documento->idsede_partenza,
            'idsede_destinazione' => $documento->idsede_destinazione,
            'permetti_movimento_a_zero' => 0,
        ],
        'impianti' => [
            'idintervento' => $documento->id,
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
    'tipo_sconto' => '',
    'idiva' => '',
    'idconto' => $idconto,
    'ritenuta_contributi' => true,
];

// Leggo l'iva predefinita per l'anagrafica e se non c'è leggo quella predefinita generica
$iva = $dbo->fetchArray('SELECT idiva_vendite AS idiva FROM an_anagrafiche WHERE idanagrafica='.prepare($documento['idanagrafica']));
$result['idiva'] = $iva[0]['idiva'] ?: setting('Iva predefinita');

// Importazione della gestione dedicata
$file = 'riga';
if (get('is_descrizione') !== null) {
    $file = 'descrizione';

    $options['op'] = 'manage_descrizione';
} elseif (get('is_articolo') !== null) {
    $file = 'articolo';

    // Aggiunta sconto di default da listino per le vendite
    $listino = $dbo->fetchOne('SELECT prc_guadagno FROM an_anagrafiche INNER JOIN mg_listini ON an_anagrafiche.idlistino_vendite=mg_listini.id WHERE idanagrafica='.prepare($documento['idanagrafica']));

    if (!empty($listino['prc_guadagno'])) {
        $result['sconto_percentuale'] = $listino['prc_guadagno'];
        $result['tipo_sconto'] = 'PRC';
    }

    $options['op'] = 'manage_articolo';
} elseif (get('is_sconto') !== null) {
    $file = 'sconto';

    $options['op'] = 'manage_sconto';
} elseif (get('is_barcode') !== null) {
    $file = 'barcode';

    $options['op'] = 'manage_barcode';
}

echo App::load($file.'.php', $result, $options);
