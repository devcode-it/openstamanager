<?php

namespace Modules\Aggiornamenti;

use Symfony\Component\Finder\Finder;
use Util\Zip;
use Util\Ini;
use Models\Module;
use Models\Plugin;
use Models\Group;
use Modules;
use Plugins;

class Aggiornamento
{
    protected $directory = null;
    protected $components = null;

    protected $groups = null;

    public function __construct($directory = null)
    {
        $this->directory = $directory ?: Zip::getExtractionDirectory();
    }

    protected function groups()
    {
        if (!isset($this->groups)) {
            $groups = Group::where('nome', 'Amministratori')->get();

            $result = [];
            foreach ($groups as $group) {
                $result[$group->id] = [
                    'permission_level' => 'rw'
                ];
            }

            $this->groups = $result;
        }

        return $this->groups;
    }
    /**
     * Controlla se l'aggiornamento è di tipo globale.
     *
     * @return boolean
     */
    public function isCoreUpdate()
    {
        return file_exists($this->directory.'/VERSION');
    }

    /**
     * Individua i componenti indipendenti che compongono l'aggiornamento.
     *
     * @return array
     */
    public function componentUpdates()
    {
        if (!isset($this->components)) {
            $finder = Finder::create()
                ->files()
                ->ignoreDotFiles(true)
                ->ignoreVCS(true)
                ->in($this->directory);

            $files = $finder->name('MODULE')->name('PLUGIN');

            $results = [];
            foreach ($files as $file) {
                $is_module = basename($file->getRealPath()) == 'MODULE';
                $is_plugin = basename($file->getRealPath()) == 'PLUGIN';

                if ($is_module) {
                    $type = 'modules';
                } elseif ($is_plugin) {
                    $type = 'plugins';
                }

                if (!isset($results[$type])) {
                    $results[$type] = [];
                }
                $results[$type][] = [
                    'path' => dirname($file->getRealPath()),
                    'config' => $file->getRealPath(),
                ];
            }

            $this->components = $results;
        }

        return $this->components;
    }

    /**
     * Effettua l'aggiornamento.
     */
    public function execute()
    {
        if ($this->isCoreUpdate()) {
            $this->executeCore();
        } else {
            $components = $this->componentUpdates();

            foreach ((array) $components['modules'] as $module) {
                $this->executeModule($module);
            }

            foreach ((array) $components['plugins'] as $plugin) {
                $this->executeModule($plugin);
            }
        }

        $this->delete();
    }

    /**
     * Completa l'aggiornamento globale secondo la procedura apposita.
     *
     */
    public function executeCore()
    {
        // Salva il file di configurazione
        $config = file_get_contents(DOCROOT.'/config.inc.php');

        // Copia i file dalla cartella temporanea alla root
        copyr($this->directory, DOCROOT);

        // Ripristina il file di configurazione dell'installazione
        file_put_contents(DOCROOT.'/config.inc.php', $config);
    }

    /**
     * Completa l'aggiornamento del singolo componente come previsto dai parametri indicati.
     *
     * @param string $directory Percorso di copia dei contenuti
     * @param string $table Tabella interessata dall'aggiornamento
     * @param array $insert Informazioni per la registrazione
     * @param array $info Contenuti della configurazione
     * @param boolean $is_installed
     * @return int|null
     */
    protected function executeComponent($path, $directory, $table, $insert, $info, $is_installed = false)
    {
        // Copia dei file nella cartella relativa
        copyr($path, DOCROOT.'/'.$directory.'/'.$info['directory']);

        // Eventuale registrazione nel database
        if (empty($is_installed)) {
            $dbo = database();

            $dbo->insert($table, array_merge($insert, [
                'name' => $info['name'],
                'title' => !empty($info['title']) ? $info['title'] : $info['name'],
                'directory' => $info['directory'],
                'options' => $info['options'],
                'version' => $info['version'],
                'compatibility' => $info['compatibility'],
                'order' => 100,
                'default' => 0,
                'enabled' => 1,
            ]));

            return $dbo->lastInsertedID();
        }
    }

    /**
     * Completa l'aggiornamento con le informazioni specifiche per i moduli.
     *
     * @param array $module
     */
    public function executeModule($module)
    {
        // Informazioni dal file di configurazione
        $info = Ini::readFile($module['config']);

        // Informazioni aggiuntive per il database
        $insert = [
            'parent' => Modules::get($info['parent']),
            'icon' => $info['icon'],
        ];

        $is_installed = Modules::get($info['name']);

        $id = $this->executeComponent($module['path'], 'modules', 'zz_modules', $insert, $info, $is_installed);

        if (!empty($id)) {
            // Fix per i permessi di amministratore
            $element = Module::find($id);

            $element->groups()->syncWithoutDetaching($this->groups());
        }
    }

    /**
     * Completa l'aggiornamento con le informazioni specifiche per i plugin.
     *
     * @param array $plugin
     */
    public function executePlugin($plugin)
    {
        // Informazioni dal file di configurazione
        $info = Ini::readFile($plugin['config']);

        // Informazioni aggiuntive per il database
        $insert = [
            'idmodule_from' => Modules::get($info['module_from'])['id'],
            'idmodule_to' => Modules::get($info['module_to'])['id'],
            'position' => $info['position'],
        ];

        $is_installed = Plugins::get($info['name']);

        $id = $this->executeComponent($plugin['path'], 'plugins', 'zz_plugins', $insert, $info, $is_installed);

        if (!empty($id)) {
            // Fix per i permessi di amministratore
            $element = Plugin::find($id);

            $element->groups()->syncWithoutDetaching($this->groups());
        }
    }

    /**
     * Pulisce la cartella di estrazione.
     *
     * @return void
     */
    public function delete()
    {
        delete($this->directory);
    }

    /**
     * Instanzia un aggiornamento sulla base di uno zip indicato.
     *
     * @param string $file
     * @return static
     */
    public static function make($file)
    {
        $extraction_dir = Zip::extract($file);

        return new static($extraction_dir);
    }
    /**
    * Controlla se è disponibile un aggiornamento nella repository GitHub.
    *
    * @return string
    */
    public static function isAvaliable()
    {
        $api = json_decode(get_remote_data('https://api.github.com/repos/devcode-it/openstamanager/releases'), true);

        $version = ltrim($api[0]['tag_name'], 'v');
        $current = Update::getVersion();

        if (version_compare($current, $version) < 0) {
            return $version;
        }

        return 'none';
    }
}
