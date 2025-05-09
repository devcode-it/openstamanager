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

use Modules\Fatture\Fattura;

include_once __DIR__.'/../../core.php';

$documento = Fattura::find($id_record);
$dir = $documento->direzione;

// Impostazioni per la gestione
$options = [
    'op' => 'manage_riga',
    'action' => 'add',
    'dir' => $documento->direzione,
    'conti' => $documento->direzione == 'entrata' ? 'conti-vendite' : 'conti-acquisti',
    'idanagrafica' => $documento['idanagrafica'],
    'show-ritenuta-contributi' => !empty($documento['id_ritenuta_contributi']),
    'totale_imponibile_documento' => $documento->totale_imponibile,
    'totale_documento' => $documento->totale,
    'is_nota' => $documento->isNota(),
    'select-options' => [
        'articoli' => [
            'idanagrafica' => $documento->idanagrafica,
            'dir' => $documento->direzione,
            'idsede_partenza' => $documento->idsede_partenza,
            'idsede_destinazione' => $documento->idsede_destinazione,
            'permetti_movimento_a_zero' => intval($documento->direzione == 'uscita'),
            'idagente' => $documento->idagente,
        ],
        'iva' => [
            'split_payment' => $documento['split_payment'],
        ],
    ],
];

// Conto dalle impostazioni
if (empty($idconto)) {
    $idconto = ($dir == 'entrata') ? setting('Conto predefinito fatture di vendita') : setting('Conto predefinito fatture di acquisto');
}

// Dati di default
$result = [
    'descrizione' => '',
    'qta' => 1,
    'um' => '',
    'prezzo' => 0,
    'prezzo_acquisto' => 0,
    'sconto_unitario' => 0,
    'tipo_sconto' => (setting('Tipo di sconto predefinito') == '%' ? 'PRC' : 'UNT'),
    'idiva' => '',
    'idconto' => $idconto,
    'ritenuta_contributi' => true,
    'provvigione_default' => 0,
    'tipo_provvigione_default' => 'PRC',
];

// Leggo la provvigione predefinita per l'anagrafica
$result['provvigione_default'] = $dbo->fetchOne('SELECT provvigione_default FROM an_anagrafiche WHERE idanagrafica='.prepare($documento->idagente))['provvigione_default'];

// Leggo l'iva predefinita per l'anagrafica e se non c'è leggo quella predefinita generica
$iva = $dbo->fetchArray('SELECT idiva_'.($dir == 'uscita' ? 'acquisti' : 'vendite').' AS idiva FROM an_anagrafiche WHERE idanagrafica='.prepare($documento['idanagrafica']));
$result['idiva'] = $iva[0]['idiva'] ?: setting('Iva predefinita');

if (!empty($documento->dichiarazione)) {
    $result['idiva'] = setting("Iva per lettere d'intento");
}

// Leggo la ritenuta d'acconto predefinita per l'anagrafica e se non c'è leggo quella predefinita generica
// id_ritenuta_acconto_vendite oppure id_ritenuta_acconto_acquisti
$ritenuta_acconto = $dbo->fetchOne('SELECT id_ritenuta_acconto_'.($dir == 'uscita' ? 'acquisti' : 'vendite').' AS id_ritenuta_acconto FROM an_anagrafiche WHERE idanagrafica='.prepare($documento['idanagrafica']));
$id_ritenuta_acconto = $ritenuta_acconto['id_ritenuta_acconto'];
if ($dir == 'entrata' && empty($id_ritenuta_acconto)) {
    $id_ritenuta_acconto = setting("Ritenuta d'acconto predefinita");
}
$options['id_ritenuta_acconto_predefined'] = $id_ritenuta_acconto;

// Importazione della gestione dedicata
$file = 'riga';
if (!empty(get('is_descrizione'))) {
    $file = 'descrizione';

    $options['op'] = 'manage_descrizione';
} elseif (!empty(get('is_articolo'))) {
    $file = 'articolo';

    // Aggiunta sconto di default da listino per le vendite
    $join = ($dir == 'entrata' ? 'id_piano_sconto_vendite' : 'id_piano_sconto_acquisti');
    $listino = $dbo->fetchOne('SELECT prc_guadagno FROM an_anagrafiche INNER JOIN mg_piani_sconto ON an_anagrafiche.'.$join.'=mg_piani_sconto.id WHERE idanagrafica='.prepare($documento['idanagrafica']));

    if (!empty($listino['prc_guadagno'])) {
        $result['sconto_percentuale'] = $listino['prc_guadagno'];
        $result['tipo_sconto'] = 'PRC';
    }

    $options['op'] = 'manage_articolo';
} elseif (!empty(get('is_sconto'))) {
    $file = 'sconto';

    $options['op'] = 'manage_sconto';
}

echo App::load($file.'.php', $result, $options);
