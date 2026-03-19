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

use Models\Module;
use Models\Plugin;
use Util\Zip;

// Validazione preliminare
if (!setting('Attiva aggiornamenti')) {
    exit(tr('Accesso negato'));
}

if (!extension_loaded('zip')) {
    flash()->error(tr('Estensione zip non supportata!').'<br>'.tr('Verifica e attivala sul tuo file _FILE_', [
        '_FILE_' => '<b>php.ini</b>',
    ]));

    return;
}

$extraction_dir = Zip::extract($_FILES['blob']['tmp_name']);

// ============================================================================
// FUNZIONI HELPER PER GESTIONE COMPONENTI
// ============================================================================

/**
 * Crea un Finder ottimizzato per cercare file di configurazione
 */
function createComponentFinder($extraction_dir)
{
    return Symfony\Component\Finder\Finder::create()
        ->files()
        ->ignoreDotFiles(true)
        ->ignoreVCS(true)
        ->in($extraction_dir);
}

/**
 * Estrae il tipo di componente dal nome del file
 */
function getComponentType($filename)
{
    $types = [
        'MODULE' => 'module',
        'PLUGIN' => 'plugin',
        'TEMPLATES' => 'template',
    ];

    return $types[$filename] ?? null;
}

/**
 * Ottiene la configurazione del componente in base al tipo
 */
function getComponentConfig($type)
{
    $configs = [
        'module' => [
            'directory' => 'modules',
            'table' => 'zz_modules',
        ],
        'plugin' => [
            'directory' => 'plugins',
            'table' => 'zz_plugins',
        ],
        'template' => [
            'directory' => 'templates',
            'table' => 'zz_prints',
        ],
    ];

    return $configs[$type] ?? [];
}

/**
 * Verifica se un componente è già installato
 */
function isComponentInstalled($type, $info)
{
    switch ($type) {
        case 'module':
            return Module::where('name', $info['name'])->first();
        case 'plugin':
            return Plugin::where('name', $info['name'])->first();
        case 'template':
            return isset(Prints::getPrints()[$info['name']]);
        default:
            return false;
    }
}

/**
 * Prepara i dati per l'inserimento nel database
 */
function prepareInsertData($type, $info)
{
    $baseData = [
        'directory' => $info['directory'],
        'name' => $info['name'],
        'options' => $info['options'] ?? '',
        'version' => $info['version'] ?? '',
        'compatibility' => $info['compatibility'] ?? '',
        'order' => 100,
        'enabled' => 1,
    ];

    switch ($type) {
        case 'module':
            return array_merge($baseData, [
                'default' => 0,
                'icon' => $info['icon'] ?? '',
                'parent' => Module::where('name', $info['parent'] ?? '')->first()?->id,
            ]);

        case 'plugin':
            return array_merge($baseData, [
                'default' => 0,
                'idmodule_from' => Module::where('name', $info['module_from'] ?? '')->first()?->id,
                'idmodule_to' => Module::where('name', $info['module_to'] ?? '')->first()?->id,
                'position' => $info['position'] ?? '',
            ]);

        case 'template':
            return array_merge($baseData, [
                'id_module' => Module::where('name', $info['module'] ?? '')->first()?->id,
                'is_record' => $info['is_record'] ?? 0,
                'icon' => $info['icon'] ?? '',
                'predefined' => 0,
            ]);

        default:
            return $baseData;
    }
}

/**
 * Prepara i dati per la tabella _lang
 */
function prepareLangData($type, $info)
{
    $baseLangData = [
        'title' => $info['title'] ?? $info['name'],
        'id_lang' => Models\Locale::getDefault()->id,
    ];

    if ($type === 'template') {
        $baseLangData['filename'] = $info['filename'] ?? '';
    }

    return $baseLangData;
}

/**
 * Inserisce il componente nel database
 */
function insertComponent($type, $info, $table, $dbo)
{
    $installed = isComponentInstalled($type, $info);

    if (!empty($installed)) {
        flash()->error(tr('Aggiornamento completato!'));
        return;
    }

    $insertData = prepareInsertData($type, $info);

    // Validazione per i template: il modulo deve esistere
    if ($type === 'template' && empty($insertData['id_module'])) {
        flash()->error(tr('Errore: il modulo "_MODULE_" non è installato. Installare prima il modulo richiesto.', [
            '_MODULE_' => $info['module'] ?? 'sconosciuto',
        ]));
        return;
    }

    // Validazione per i plugin: i moduli devono esistere
    if ($type === 'plugin' && (empty($insertData['idmodule_from']) || empty($insertData['idmodule_to']))) {
        $missing = [];
        if (empty($insertData['idmodule_from'])) {
            $missing[] = $info['module_from'] ?? 'sconosciuto';
        }
        if (empty($insertData['idmodule_to'])) {
            $missing[] = $info['module_to'] ?? 'sconosciuto';
        }
        flash()->error(tr('Errore: i moduli "_MODULES_" non sono installati. Installare prima i moduli richiesti.', [
            '_MODULES_' => implode(', ', $missing),
        ]));
        return;
    }

    $langData = prepareLangData($type, $info);

    $dbo->insert($table, $insertData);
    $id_record = $dbo->lastInsertedID();

    $langData['id_record'] = $id_record;
    $dbo->insert("{$table}_lang", $langData);

    flash()->error(tr('Installazione completata!'));
}

/**
 * Elabora un singolo componente (modulo, plugin o template)
 */
function processComponent($file, $dbo)
{
    $filename = basename($file->getRealPath());
    $type = getComponentType($filename);

    if (!$type) {
        return;
    }

    $info = Util\Ini::readFile($file->getRealPath());
    $config = getComponentConfig($type);

    // Copia i file nella cartella relativa
    copyr(dirname($file->getRealPath()), base_dir().'/'.$config['directory'].'/'.$info['directory']);

    // Inserisce il componente nel database
    insertComponent($type, $info, $config['table'], $dbo);
}

// ============================================================================
// ELABORAZIONE PRINCIPALE
// ============================================================================

// Aggiornamento del progetto (versione completa)
if (file_exists($extraction_dir.'/VERSION')) {
    handleProjectUpdate($extraction_dir);
} else {
    // Elaborazione componenti (moduli, plugin, template)
    handleComponentsUpload($extraction_dir, $dbo);
}

/**
 * Gestisce l'aggiornamento completo del progetto
 */
function handleProjectUpdate($extraction_dir)
{
    // Salva il file di configurazione
    $config = file_get_contents(base_dir().'/config.inc.php');

    // Rinomina la cartella vendor per evitare conflitti
    if (is_dir(base_dir().'/vendor')) {
        copyr(base_dir().'/vendor', base_dir().'/vendor.old');
        copyr($extraction_dir.'/vendor', base_dir().'/vendor.new');
    }

    // Copia i file dalla cartella temporanea alla root
    copyr($extraction_dir, base_dir());
    delete(base_dir().'/vendor');
    rename(base_dir().'/vendor.new', base_dir().'/vendor');
    delete(base_dir().'/vendor.old');

    // Ripristina il file di configurazione dell'installazione
    file_put_contents(base_dir().'/config.inc.php', $config);
}

/**
 * Gestisce il caricamento di componenti (moduli, plugin, template)
 */
function handleComponentsUpload($extraction_dir, $dbo)
{
    $finder = createComponentFinder($extraction_dir);

    // Elabora tutti i file di configurazione trovati
    $configFiles = $finder->name('MODULE')->name('PLUGIN')->name('TEMPLATES');

    foreach ($configFiles as $file) {
        processComponent($file, $dbo);
    }
}

// Rimozione delle risorse inutilizzate
delete($extraction_dir);

// Redirect
$database->commitTransaction();
redirect_url(base_path_osm().'/editor.php?id_module='.$id_module);
exit;
