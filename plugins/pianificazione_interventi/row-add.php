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

use Plugins\PianificazioneInterventi\Promemoria;

include_once __DIR__.'/../../core.php';

$documento = Promemoria::find($id_record);

// Impostazioni per la gestione
$options = [
    'op' => 'manage_riga',
    'action' => 'add',
    'dir' => $documento->direzione,
    'id_anagrafica' => $documento['id_anagrafica'],
    'totale_imponibile_documento' => $documento->totale_imponibile,
    'totale_documento' => $documento->totale,
    'id_plugin' => $id_plugin, // Modificato
];

// Dati di default
$result = [
    'descrizione' => '',
    'qta' => 1,
    'um' => '',
    'prezzo' => 0,
    'sconto_unitario' => 0,
    'tipo_sconto' => '',
    'id_iva' => '',
    'id_conto' => $id_conto,
    'ritenuta_contributi' => true,
];

// Leggo l'iva predefinita per l'anagrafica e se non c'è leggo quella predefinita generica
$iva = $dbo->fetchArray('SELECT id_iva_vendite AS id_iva FROM an_anagrafiche WHERE id='.prepare($documento['id_anagrafica']));
$result['id_iva'] = $iva[0]['id_iva'] ?: setting('Iva predefinita');

// Importazione della gestione dedicata
$file = 'riga';
if (!empty(get('is_descrizione'))) {
    $file = 'descrizione';

    $options['op'] = 'manage_descrizione';
} elseif (!empty(get('is_articolo'))) {
    $file = 'articolo';

    // Aggiunta sconto di default da listino per le vendite
    $listino = $dbo->fetchOne('SELECT prc_guadagno FROM an_anagrafiche INNER JOIN mg_piani_sconto ON an_anagrafiche.id_piano_sconto_vendite=mg_piani_sconto.id WHERE id_anagrafica='.prepare($documento['id_anagrafica']));

    if (!empty($listino['prc_guadagno'])) {
        $result['sconto_percentuale'] = $listino['prc_guadagno'];
        $result['tipo_sconto'] = 'PRC';
    }

    $options['op'] = 'manage_articolo';
} elseif (!empty(get('is_sconto'))) {
    $file = 'sconto';

    $options['op'] = 'manage_sconto';
}

// Modificato
echo '
<div id="riga-promemoria">';

echo App::load($file.'.php', $result, $options);

echo '
</div>
<script type="text/javascript">
    $(document).ready(function() {
        $("#riga-promemoria").ajaxForm({
            success: function(responseText, statusText, xhr, form){
                $(form).closest(".modal").modal("hide");

                refreshRighe('.$id_record.');
            }
        });
    });
</script>';
