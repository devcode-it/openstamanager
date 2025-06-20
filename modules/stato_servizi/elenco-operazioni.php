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

include_once __DIR__.'/../../core.php';

use Models\User;

// Parametri per la paginazione
$offset = intval(filter('offset') ?: 0);
$limit = intval(filter('limit') ?: 20);

// Limitiamo a massimo 100 operazioni totali
$max_operations = 100;

// Query per recuperare le operazioni
$query = "SELECT
    zz_operations.id_module,
    zz_operations.id_plugin,
    zz_operations.id_record,
    zz_operations.op,
    zz_operations.created_at,
    zz_operations.id_utente,
    zz_users.username,
    an_anagrafiche.ragione_sociale,
    COALESCE(zz_modules_lang.title, 'Sistema') as module_name,
    zz_plugins_lang.title as plugin_name
FROM zz_operations
LEFT JOIN zz_users ON zz_operations.id_utente = zz_users.id
LEFT JOIN an_anagrafiche ON zz_users.idanagrafica = an_anagrafiche.idanagrafica
LEFT JOIN zz_modules_lang ON zz_operations.id_module = zz_modules_lang.id_record AND zz_modules_lang.id_lang = ".prepare(Models\Locale::getDefault()->id)."
LEFT JOIN zz_plugins_lang ON zz_operations.id_plugin = zz_plugins_lang.id_record AND zz_plugins_lang.id_lang = ".prepare(Models\Locale::getDefault()->id)."
ORDER BY zz_operations.created_at DESC
LIMIT ".$limit." OFFSET ".$offset;

$operazioni = $dbo->fetchArray($query);

// Query per contare il totale delle operazioni
$count_query = "SELECT COUNT(*) as total FROM zz_operations";
$total_count = $dbo->fetchOne($count_query)['total'];

if (!empty($operazioni)) {
    echo '
<table class="table table-hover table-sm mb-0">
    <thead>
        <tr>
            <th width="25%">'.tr('Modulo/Plugin').'</th>
            <th width="15%">'.tr('Record').'</th>
            <th width="20%">'.tr('Operazione').'</th>
            <th width="20%">'.tr('Utente').'</th>
            <th width="20%">'.tr('Data e ora').'</th>
        </tr>
    </thead>
    <tbody>';

    foreach ($operazioni as $operazione) {
        // Determina il nome del modulo/plugin
        $nome_modulo = $operazione['module_name'];
        if (!empty($operazione['plugin_name'])) {
            $nome_modulo .= ' - '.$operazione['plugin_name'];
        }

        // Formatta l'operazione e determina il colore
        $operazione_formattata = ucfirst(str_replace('_', ' ', $operazione['op']));

        // Determina il colore in base al tipo di operazione
        $color_class = 'text-primary'; // Default blu
        $op_lower = strtolower($operazione['op']);

        if (strpos($op_lower, 'delete') !== false || strpos($op_lower, 'elimina') !== false || strpos($op_lower, 'rimuovi') !== false) {
            $color_class = 'text-danger'; // Rosso per eliminazioni
        } elseif (strpos($op_lower, 'add') !== false || strpos($op_lower, 'aggiungi') !== false || strpos($op_lower, 'crea') !== false || strpos($op_lower, 'nuovo') !== false) {
            $color_class = 'text-success'; // Verde per aggiunte
        } elseif (strpos($op_lower, 'update') !== false || strpos($op_lower, 'modifica') !== false || strpos($op_lower, 'salva') !== false || strpos($op_lower, 'edit') !== false) {
            $color_class = 'text-warning'; // Arancione per modifiche
        }

        // Formatta la data
        $data_formattata = Translator::timestampToLocale($operazione['created_at']);

        // Recupera informazioni complete dell'utente
        $user = User::find($operazione['id_utente']);
        $username = $operazione['username'];

        // Foto e nome completo dell'utente
        $user_photo = null;
        $nome_completo = $username;
        if ($user) {
            $user_photo = $user->photo;
            $nome_completo = $user->nome_completo;
        }

        echo '
        <tr>
            <td>
                <strong>'.$nome_modulo.'</strong>
            </td>
            <td class="text-center">
                '.($operazione['id_record'] ? '<span class="badge badge-secondary">'.$operazione['id_record'].'</span>' : '-').'
            </td>
            <td>
                <small class="'.$color_class.' font-weight-bold">'.$operazione_formattata.'</small>
            </td>
            <td>
                '.($user_photo ? '<img class="attachment-img tip mr-2" src="'.$user_photo.'" title="'.$nome_completo.'" style="width: 20px; height: 20px;">' : '<i class="fa fa-user-circle-o mr-2 tip" title="'.$nome_completo.'"></i>').'
                <strong>'.$username.'</strong>
            </td>
            <td>
                <small>'.$data_formattata.'</small>
            </td>
        </tr>';
    }

    echo '
    </tbody>
</table>';

    // Pulsante "Carica di più" se ci sono altre operazioni da mostrare
    $next_offset = $offset + $limit;
    $max_offset = $max_operations; // Limitiamo a 100 operazioni totali

    if ($next_offset < $total_count && $next_offset < $max_offset) {
        $remaining = min($max_offset - $next_offset, $total_count - $next_offset);
        echo '
        <div class="text-center p-3">
            <button type="button" class="btn btn-sm btn-outline-primary" onclick="caricaAltreOperazioni('.$next_offset.', '.$remaining.')">
                <i class="fa fa-plus mr-1"></i>'.tr('Carica altre _NUM_ operazioni', ['_NUM_' => $remaining]).'
            </button>
        </div>';
    }
} else {
    echo '
    <div class="alert alert-info mb-0">
        <i class="fa fa-info-circle mr-1"></i>'.tr('Nessuna operazione trovata.').'
    </div>';
}
