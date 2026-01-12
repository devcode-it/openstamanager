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

namespace Modules\Aggiornamenti\Controlli;

/**
 * Controllo per verificare la presenza dei file .htaccess critici nelle cartelle di sistema.
 */
class FileHtaccess extends Controllo
{
    /**
     * Definizione delle cartelle che richiedono un file .htaccess e il relativo contenuto.
     */
    protected static $htaccess_config = [
        '' => [
            'description' => 'File di configurazione principale Apache per la sicurezza del gestionale',
            'content' => null, // Contenuto generato dinamicamente
        ],
        'files' => [
            'description' => 'Cartella per i file allegati degli utenti',
            'content' => "# Disable directory listing\nOptions -Indexes\n\n# Disable PHP rendering\n<IfModule mod_php5.c>\n    php_flag engine off\n</IfModule>\n<IfModule mod_php7.c>\n    php_flag engine off\n</IfModule>\n",
        ],
        'logs' => [
            'description' => 'Cartella per i file di log',
            'content' => "Deny from all\n",
        ],
        'backup' => [
            'description' => 'Cartella per i backup',
            'content' => "Deny from all\n",
        ],
        'update' => [
            'description' => 'Cartella per gli aggiornamenti',
            'content' => "Deny from all\n",
        ],
        'locale' => [
            'description' => 'Cartella per le traduzioni',
            'content' => "Deny from all\n",
        ],
    ];

    public function getName()
    {
        return tr('File .htaccess di sistema');
    }

    public function getType($record)
    {
        return 'warning';
    }

    public function getOptions($record)
    {
        return [
            [
                'name' => tr('Rigenera file'),
                'icon' => 'fa fa-refresh',
                'color' => 'success',
                'params' => ['action' => 'regenerate'],
            ],
        ];
    }

    public function hasGlobalActions()
    {
        return true;
    }

    public function getGlobalActions()
    {
        $missing_count = count($this->results);
        if ($missing_count === 0) {
            return [];
        }

        return [
            [
                'name' => tr('Rigenera tutti i file mancanti'),
                'icon' => 'fa fa-refresh',
                'color' => 'success',
                'params' => ['action' => 'regenerate_all'],
                'badge' => $missing_count.' '.tr('file'),
            ],
        ];
    }

    public function check()
    {
        foreach (self::$htaccess_config as $folder => $config) {
            // Gestione speciale per la directory root
            if ($folder === '') {
                $folder_path = base_dir();
                $htaccess_path = $folder_path.'/.htaccess';
                $display_path = '.htaccess';
                $display_folder = tr('directory principale');
            } else {
                $folder_path = base_dir().'/'.$folder;
                $htaccess_path = $folder_path.'/.htaccess';
                $display_path = $folder.'/.htaccess';
                $display_folder = '<code>'.$folder.'</code>';

                // Verifica se la cartella esiste (solo per sottocartelle)
                if (!is_dir($folder_path)) {
                    continue;
                }
            }

            // Verifica se il file .htaccess esiste
            if (!file_exists($htaccess_path)) {
                $this->addResult([
                    'id' => 'htaccess_'.($folder === '' ? 'root' : $folder),
                    'folder' => $folder,
                    'nome' => '<strong>.htaccess</strong><br><small class="text-muted">'.$display_path.'</small>',
                    'descrizione' => tr('File .htaccess mancante nella _FOLDER_', ['_FOLDER_' => $display_folder]).'<br><small class="text-muted">'.$config['description'].'</small>',
                ]);
            }
        }
    }

    public function execute($record, $params = [])
    {
        $action = $params['action'] ?? '';

        if ($action === 'regenerate') {
            return $this->regenerateHtaccess($record['folder']);
        }

        return tr('Azione non supportata');
    }

    /**
     * Rigenera tutti i file .htaccess mancanti.
     */
    public function solveGlobal($params = [])
    {
        $action = $params['action'] ?? '';
        $results = [];

        if ($action === 'regenerate_all') {
            foreach ($this->results as $record) {
                $results[$record['id']] = $this->regenerateHtaccess($record['folder']);
            }
        }

        return $results;
    }

    /**
     * Contenuto del file .htaccess principale (root).
     */
    protected static function getRootHtaccessContent()
    {
        return <<<'HTACCESS'
# Remove autoindex
<IfModule mod_autoindex.c>
    IndexIgnore */*
</IfModule>

# Deny access to files starting with a dot (e.g. .htaccess, .git)
<FilesMatch "^\.">
    Require all denied
</FilesMatch>

# Deny access to certain file types like log, sql, htaccess, etc.
<FilesMatch "\.(ini|psd|log|sh|sql|md|lock|phar)$">
    Require all denied
</FilesMatch>

# Deny access to VERSION, REVISION, LICENSE, and config files
<Files ~ "(VERSION$|REVISION$|LICENSE|(config.inc|config.example).php|(composer|package).json|gulpfile.js)">
    Require all denied
</Files>

# Disable indexing of php, html, htm, pdf files
ServerSignature Off
<IfModule mod_headers.c>
    Header set X-Robots-Tag: "noindex,nofollow"
    Header set X-Content-Type-Options nosniff
</IfModule>

<IfModule mod_rewrite.c>
	RewriteEngine On

	# Tell PHP that the mod_rewrite module is ENABLED.
	<IfModule mod_env.c>
        SetEnv HTTP_MOD_REWRITE On
    </IfModule>

	# Deny access to protected folders
    RewriteRule ^backup/ - [F,L]
    RewriteRule ^docs/ - [F,L]
    RewriteRule ^include/ - [F,L]
    RewriteRule ^locale/ - [F,L]
    RewriteRule ^logs/ - [F,L]
    RewriteRule ^update/ - [F,L]

	# Deny access to svn, git, node_modules and vendor folders
    RewriteRule ^.git/ - [F,L]
    RewriteRule ^.svn/ - [F,L]
    RewriteRule ^node_modules/ - [F,L]
    RewriteRule ^vendor/ - [F,L]

    # Disable HTTP TRACE
    RewriteCond %{REQUEST_METHOD} ^TRACE
    RewriteRule .* - [F]

    # Prevent hacks
	# proc/self/environ? no way!
	RewriteCond %{QUERY_STRING} proc/self/environ [OR]

	# Block out any script trying to set a mosConfig value through the URL
	RewriteCond %{QUERY_STRING} mosConfig_[a-zA-Z_]{1,21}(=|\%3D) [OR]

	# Block out any script trying to base64_encode crap to send via URL
	RewriteCond %{QUERY_STRING} base64_encode.*(.*) [OR]

	# Block out any script that includes a <script> tag in URL
	RewriteCond %{QUERY_STRING} (<|%3C).*script.*(>|%3E) [NC,OR]

	# Block out any script trying to set a PHP GLOBALS variable via URL
	RewriteCond %{QUERY_STRING} GLOBALS(=|[|\%[0-9A-Z]{0,2}) [OR]

	# Block out any script trying to modify a _REQUEST variable via URL
	RewriteCond %{QUERY_STRING} _REQUEST(=|[|\%[0-9A-Z]{0,2})

    # Set an environment variable for bad bots using user-agent patterns
    SetEnvIfNoCase User-Agent ".*(craftbot|download|extract|stripper|sucker|ninja|clshttp|webspider|leacher|collector|grabber|webpictures).*" HTTP_SAFE_BADBOT
    SetEnvIfNoCase User-Agent ".*(libwww-perl|aesop_com_spiderman).*" HTTP_SAFE_BADBOT

    # Deny access to requests from this environment variable
    <RequireAll>
        Require all granted
        Require not env HTTP_SAFE_BADBOT
    </RequireAll>
</ifModule>

# Compress text, html, javascript, css, ecc...
<IfModule mod_gzip.c>
    mod_gzip_on       Yes
    mod_gzip_dechunk  Yes
    mod_gzip_item_include file      \.(html?|txt|css|js|php|pl)$
    mod_gzip_item_include handler   ^cgi-script$
    mod_gzip_item_include mime      ^text/.*
    mod_gzip_item_include mime      ^application/x-javascript.*
    mod_gzip_item_exclude mime      ^image/.*
    mod_gzip_item_exclude rspheader ^Content-Encoding:.*gzip.*
</IfModule>

<IfModule mod_mime.c>
  AddType text/javascript mjs
</IfModule>
HTACCESS;
    }

    /**
     * Rigenera il file .htaccess per una cartella specifica.
     */
    protected function regenerateHtaccess($folder)
    {
        if (!isset(self::$htaccess_config[$folder])) {
            return tr('Configurazione non trovata per la cartella _FOLDER_', ['_FOLDER_' => $folder]);
        }

        // Gestione speciale per la directory root
        if ($folder === '') {
            $folder_path = base_dir();
            $display_folder = tr('directory principale');
        } else {
            $folder_path = base_dir().'/'.$folder;
            $display_folder = $folder;

            // Crea la cartella se non esiste (solo per sottocartelle)
            if (!is_dir($folder_path)) {
                if (!mkdir($folder_path, 0755, true)) {
                    return tr('Impossibile creare la cartella _FOLDER_', ['_FOLDER_' => $folder]);
                }
            }
        }

        $htaccess_path = $folder_path.'/.htaccess';

        // Ottieni il contenuto appropriato
        $content = self::$htaccess_config[$folder]['content'];
        if ($content === null && $folder === '') {
            $content = self::getRootHtaccessContent();
        }

        // Scrivi il contenuto del file .htaccess
        if (file_put_contents($htaccess_path, $content) === false) {
            return tr('Impossibile creare il file .htaccess nella _FOLDER_', ['_FOLDER_' => $display_folder]);
        }

        return tr('File .htaccess rigenerato con successo nella _FOLDER_', ['_FOLDER_' => $display_folder]);
    }
}
