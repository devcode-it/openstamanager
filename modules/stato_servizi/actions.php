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

use API\Services;
use Carbon\Carbon;
use Models\Cache;
use Models\Module;
use Models\Plugin;
use Util\FileSystem;

$id = post('id');

switch (filter('op')) {
    case 'cambia-dimensione':
        $result = $dbo->update('zz_widgets', [
            'class' => post('valore'),
        ], [
            'id' => post('id'),
        ]);

        echo json_encode([
            'result' => $result,
        ]);

        if ($result) {
            flash()->info('Impostazione modificata con successo!');
        } else {
            flash()->error('Errore durante il salvataggio!');
        }

        break;

    case 'rimuovi-modulo':
        $id = filter('id');
        $is_plugin = filter('tipo') == 'plugin';
        if (empty($id)) {
            echo json_encode([]);
        }

        // Ricerca del modulo/plugin tra quelli disponibili
        if (!$is_plugin) {
            $struttura = Module::where('default', '=', 0)->find($id);
        } else {
            $struttura = Plugin::where('default', '=', 0)->find($id);
        }

        // Modulo/plugin non trovato
        if (empty($struttura)) {
            echo json_encode([]);
        }

        // Eliminazione del modulo/plugin dal sistema di navigazione
        $struttura->delete();

        // Esecuzione dello script di disinstallazione (se presente)
        $uninstall_script = $struttura->path.'/update/uninstall.php';
        if (file_exists($uninstall_script)) {
            include_once $uninstall_script;
        }

        // Eliminazione dei file del modulo/plugin
        delete($struttura->path);

        // Messaggio informativo
        if (!$is_plugin) {
            flash()->info(tr('Modulo "_NAME_" disinstallato!', [
                '_NAME_' => $struttura->title,
            ]));
        } else {
            flash()->info(tr('Plugin "_NAME_" disinstallato!', [
                '_NAME_' => $struttura->title,
            ]));
        }

        echo json_encode([]);

        break;

    case 'disabilita-modulo':
        $id = filter('id');
        $is_plugin = filter('tipo') == 'plugin';

        // Disabilitazione del modulo indicato
        $database->table($is_plugin ? 'zz_plugins' : 'zz_modules')
            ->where('id', '=', $id)
            ->update(['enabled' => 0]);

        // Cascata in tutti i sotto-moduli
        if (!$is_plugin) {
            $moduli_interessati = collect([$id]);
            while (!$moduli_interessati->isEmpty()) {
                $id_modulo = $moduli_interessati->pop();

                // Disabilitazione dei sotto-moduli
                $database->table('zz_modules')
                    ->where('parent', '=', $id_modulo)
                    ->update(['enabled' => 0]);

                // Ricerca sotto-moduli
                $sotto_moduli = $database->table('zz_modules')
                    ->where('parent', '=', $id_modulo)
                    ->select('id')
                    ->get()->pluck('id');
                $moduli_interessati->concat($sotto_moduli);
            }
        }

        // Disabilitazione modulo/plugin indicato
        $moduli_sempre_attivi = ['Utenti e permessi', 'Stato dei servizi'];
        $database->table('zz_modules')
            ->whereIn('name', $moduli_sempre_attivi)
            ->update(['enabled' => 1]);

        // Messaggio informativo
        $struttura = $is_plugin ? Plugin::find($id) : Module::find($id);
        if (!$is_plugin) {
            flash()->info(tr('Modulo "_NAME_" disabilitato!', [
                '_NAME_' => $struttura->title,
            ]));
        } else {
            flash()->info(tr('Plugin "_NAME_" disabilitato!', [
                '_NAME_' => $struttura->title,
            ]));
        }

        echo json_encode([]);

        break;

    case 'abilita-sotto-modulo':
        $id = filter('id');

        // Cascata in tutti i sotto-moduli
        $moduli_interessati = collect([$id]);
        while (!$moduli_interessati->isEmpty()) {
            $id_modulo = $moduli_interessati->pop();

            // Disabilitazione dei sotto-moduli
            $database->table('zz_modules')
                ->where('parent', '=', $id_modulo)
                ->update(['enabled' => 1]);

            // Ricerca sotto-moduli
            $sotto_moduli = $database->table('zz_modules')
                ->where('parent', '=', $id_modulo)
                ->select('id')
                ->get()->pluck('id');
            $moduli_interessati->concat($sotto_moduli);
        }

        // no break
    case 'abilita-modulo':
        $id = filter('id');
        $is_plugin = filter('tipo') == 'plugin';

        // Abilitazione del modulo/plugin indicato
        $database->table($is_plugin ? 'zz_plugins' : 'zz_modules')
            ->where('id', '=', $id)
            ->update(['enabled' => 1]);

        // Messaggio informativo
        $struttura = $is_plugin ? Plugin::find($id) : Module::find($id);
        if (!isset($moduli_interessati)) {
            if (!$is_plugin) {
                flash()->info(tr('Modulo "_NAME_" abilitato!', [
                    '_NAME_' => $struttura->title,
                ]));
            } else {
                flash()->info(tr('Plugin "_NAME_" abilitato!', [
                    '_NAME_' => $struttura->title,
                ]));
            }
        } else {
            $modulo = Modules::get($id);
            flash()->info(tr('Moduli sotto a "_NAME_" abilitati!', [
                '_NAME_' => $struttura->title,
            ]));
        }

        echo json_encode([]);

        break;

    case 'disabilita-widget':
        $id = filter('id');

        // Abilitazione del widget indicato
        $database->table('zz_widgets')
            ->where('id', '=', $id)
            ->update(['enabled' => 0]);

        // Messaggio informativo
        $widget = $database->table('zz_widgets')
            ->where('id', '=', $id)
            ->first();
        flash()->info(tr('Widget "_NAME_" disabilitato!', [
            '_NAME_' => $widget->name,
        ]));

        echo json_encode([]);

        break;

    case 'abilita-widget':
        $id = filter('id');

        // Abilitazione del widget indicato
        $database->table('zz_widgets')
            ->where('id', '=', $id)
            ->update(['enabled' => 1]);

        // Messaggio informativo
        $widget = $database->table('zz_widgets')
            ->where('id', '=', $id)
            ->first();
        flash()->info(tr('Widget "_NAME_" abilitato!', [
            '_NAME_' => $widget->name,
        ]));

        echo json_encode([]);

        break;

    case 'sposta-widget':
        $id = filter('id');

        // Individuazione widget
        $widget = $database->table('zz_widgets')
            ->where('id', '=', $id)
            ->first();
        if (empty($widget)) {
            echo json_encode([]);
        }

        // Individuazione dello spostamento da effettuare
        $pieces = explode('_', $widget->location);
        $location = $pieces[0].'_'.($pieces[1] == 'right' ? 'top' : 'right');

        // Abilitazione del widget indicato
        $database->table('zz_widgets')
            ->where('id', '=', $id)
            ->update(['location' => $location]);

        // Messaggio informativo
        flash()->info(tr('Posizione del widget "_NAME_" aggiornata!', [
            '_NAME_' => $widget->name,
        ]));

        echo json_encode([]);

        break;

        // Ordinamento moduli di primo livello
    case 'sort_modules':
        $order = explode(',', post('order', true));

        foreach ($order as $i => $id) {
            $dbo->query('UPDATE zz_modules SET `order`='.prepare($i).' WHERE id='.prepare($id));
        }

        break;

    case 'sort_widgets':
        $order = explode(',', post('order', true));

        foreach ($order as $i => $id) {
            $dbo->query('UPDATE zz_widgets SET `order`='.prepare($i).' WHERE id='.prepare($id));
        }

        break;

    case 'svuota-cache-hooks':
        // Svuota cache hooks
        $database->table('zz_cache')
        ->update(['expire_at' => Carbon::now()->subMinutes(1)]);

        // Messaggio informativo
        flash()->info(tr('Cache hooks svuotata!', []));

        echo json_encode([]);
        break;

    case 'disabilita-hook':
        $id = filter('id');

        // Abilitazione del widget indicato
        $database->table('zz_hooks')
            ->where('id', '=', $id)
            ->update(['enabled' => 0]);

        // Messaggio informativo
        $hook = $database->table('zz_hooks')
            ->where('id', '=', $id)
            ->first();
        flash()->info(tr('Hook "_NAME_" disabilitato!', [
            '_NAME_' => $hook->name,
        ]));

        echo json_encode([]);

        break;

    case 'abilita-hook':
        $id = filter('id');

        // Abilitazione del widget indicato
        $database->table('zz_hooks')
            ->where('id', '=', $id)
            ->update(['enabled' => 1]);

        // Messaggio informativo
        $hook = $database->table('zz_hooks')
            ->where('id', '=', $id)
            ->first();
        flash()->info(tr('Hook "_NAME_" abilitato!', [
            '_NAME_' => $hook->name,
        ]));

        echo json_encode([]);

        break;

    case 'sizes':
        $results = [];

        $backup_dir = App::getConfig()['backup_dir'];

        $dirs = [
            $backup_dir => tr('Backup'),
            base_dir().'/files' => tr('Allegati'),
            base_dir().'/logs' => tr('Logs'),
        ];

        foreach ($dirs as $dir => $description) {
            $excluded_extensions = ['htaccess', 'gitkeep'];
            // Tutte le cartelle che non prevedono log in zz_files
            $excluded_dir = [DOCROOT.'\files\impianti', DOCROOT.'\files\importFE', DOCROOT.'\files\exportFE', DOCROOT.'\files\receiptFE', DOCROOT.'\files\temp'];

            $size = FileSystem::folderSize($dir, array_merge($excluded_extensions, $excluded_dir));

            $results[] = [
                'description' => $description,
                'size' => $size,
                'formattedSize' => FileSystem::formatBytes($size),
                'count' => FileSystem::fileCount($dir, array_merge($excluded_extensions, $excluded_dir)) ?: 0,
                'dbSize' => ($description == 'Allegati') ? $dbo->fetchOne('SELECT SUM(`size`) AS dbsize FROM zz_files')['dbsize'] : 0,
                'dbCount' => ($description == 'Allegati') ? $dbo->fetchOne('SELECT COUNT(`id`) AS dbcount FROM zz_files')['dbcount'] : 0,
                'dbExtensions' => ($description == 'Allegati') ? $dbo->fetchArray("SELECT SUBSTRING_INDEX(filename, '.', -1) AS extension, COUNT(*) AS num FROM zz_files GROUP BY extension ORDER BY num DESC LIMIT 10") : 0,
            ];
        }

        echo json_encode($results);

        break;

    case 'informazioni-fe':
        $info = Cache::pool('Informazioni su spazio FE');
        if (!$info->isValid()) {
            $response = Services::request('POST', 'informazioni_fe');
            $response = Services::responseBody($response);

            $info->set($response['result']);
        }

        $informazioni = $info->content;

        $spazio_totale = floatval($informazioni['maxSize']) * (1024 ** 2);
        $avviso_spazio = !empty($spazio_totale) && floatval($informazioni['size']) > 0.9 * $spazio_totale;

        // Restrizione storico agli ultimi 3 anni
        $history = (array) $informazioni['history'];
        $history = array_slice($history, 0, 3);

        $max_number = $informazioni['maxNumber'];
        $avviso_numero = !empty($max_number) && floatval($history[0]['number']) > 0.9 * $max_number;

        // Formattazione dei contenuti dello storico
        foreach ($history as $key => $value) {
            $history[$key]['size'] = (($history[$key]['size']) ? Filesystem::formatBytes($value['size']) : '-');
            // $history[$key]['invoices_size'] = Filesystem::formatBytes($value['invoices_size']);
            // $history[$key]['notifies_size'] = Filesystem::formatBytes($value['notifies_size']);
        }

        // Formattazione dei contenuti generici
        echo json_encode([
            // 'invoices_size' => Filesystem::formatBytes($informazioni['invoices_size']),
            // 'notifies_size' => Filesystem::formatBytes($informazioni['notifies_size']),

            'invoice_number' => $informazioni['invoice_number'],
            'maxNumber' => $max_number,
            'avviso_numero' => $avviso_numero,
            'avviso_spazio' => $avviso_spazio,
            'spazio_totale' => Filesystem::formatBytes($spazio_totale),
            'spazio_occupato' => Filesystem::formatBytes($informazioni['size']),

            'history' => $history,
        ]);
        break;
}
