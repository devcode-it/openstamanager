<?php

namespace Modules\Aggiornamenti;

use Symfony\Component\Finder\Finder;
use GuzzleHttp\Client;
use Parsedown;
use InvalidArgumentException;
use Util\Zip;
use Util\Ini;
use Models\Module;
use Models\Plugin;
use Models\Group;
use Modules;
use Plugins;
use Update;

class Aggiornamento
{
    protected static $client = null;

    protected $directory = null;
    protected $components = null;

    protected $groups = null;

    /**
     * Crea l'istanza dedicata all'aggiornamento presente nella cartella indicata.
     *
     * @param string $directory
     *
     * @throws InvalidArgumentException
     */
    public function __construct($directory = null)
    {
        $this->directory = $directory ?: Zip::getExtractionDirectory();

        if (!$this->isCoreUpdate() && empty($this->componentUpdates())) {
            throw new InvalidArgumentException();
        }
    }

    public function getDirectory()
    {
        return $this->directory;
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

                $info = Ini::readFile($file->getRealPath());
                if ($is_module) {
                    $type = 'modules';
                    $installed = Modules::get($info['name']);
                } elseif ($is_plugin) {
                    $type = 'plugins';
                    $installed = Plugins::get($info['name']);
                }

                if (!isset($results[$type])) {
                    $results[$type] = [];
                }
                $results[$type][] = [
                    'path' => dirname($file->getRealPath()),
                    'config' => $file->getRealPath(),
                    'is_installed' => !empty($installed),
                    'current_version' => !empty($installed) ? $installed->version : null,
                    'info' => $info,
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
        $info = $module['info'];

        // Informazioni aggiuntive per il database
        $insert = [
            'parent' => Modules::get($info['parent']),
            'icon' => $info['icon'],
        ];

        $id = $this->executeComponent($module['path'], 'modules', 'zz_modules', $insert, $info, $module['is_installed']);

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
        $info = $plugin['info'];

        // Informazioni aggiuntive per il database
        $insert = [
            'idmodule_from' => Modules::get($info['module_from'])['id'],
            'idmodule_to' => Modules::get($info['module_to'])['id'],
            'position' => $info['position'],
        ];

        $id = $this->executeComponent($plugin['path'], 'plugins', 'zz_plugins', $insert, $info, $plugin['is_installed']);

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
     * Restituisce l'oggetto per la connessione all'API del progetto.
     *
     * @return Client
     */
    protected static function getClient()
    {
        if (!isset(self::$client)) {
            self::$client = new Client([
                'base_uri' => 'https://api.github.com/repos/devcode-it/openstamanager/',
                'verify' => false
            ]);
        }

        return self::$client;
    }

    /**
     * Restituisce i contenuti JSON dell'API del progetto
     *
     * @return array
     */
    protected static function getAPI()
    {
        $response = self::getClient()->request('GET', 'releases');
        $body = $response->getBody();

        return json_decode($body, true)[0];
    }

    /**
    * Controlla se è disponibile un aggiornamento nella repository GitHub.
    *
    * @return string
    */
    public static function isAvailable()
    {
        $api = self::getAPI();

        $version = ltrim($api['tag_name'], 'v');
        $current = Update::getVersion();

        if (version_compare($current, $version) < 0) {
            return $version;
        }

        return 'none';
    }

    /**
    * Scarica la release più recente (se presente).
    *
    * @return boolean
    */
    public static function download()
    {
        if (self::isAvailable() != 'none') {
            return false;
        }

        $directory = Zip::getExtractionDirectory();
        $file = $directory.'/release.zip';
        directory($directory);

        $api = self::getAPI();
        self::getClient()->request('GET', $api['assets'][0]['browser_download_url'], ['sink' => $file]);

        self::make($file);
        delete($file);

        return true;
    }

    /**
     * Restituisce il changelog presente nel percorso indicato a partire dalla versione specificata.
     *
     * @param string $path
     * @param string $version
     * @return string
     */
    public static function getChangelog($path, $version = null)
    {
        $result = file_get_contents($path.'/CHANGELOG.md');

        $start = strpos($result, '## ');
        $result = substr($result, $start);
        if (!empty($version)) {
            $last = strpos($result, '## '.$version.' ');

            if ($last !== false) {
                $result = substr($result, 0, $last);
            }
        }

        $result = Parsedown::instance()->text($result);
        $result = str_replace(['h4>', 'h3>', 'h2>'], ['p>', 'b>', 'h4>'], $result);

        return $result;
    }
}
