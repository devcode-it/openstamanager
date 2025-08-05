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

use Models\Module;

/**
 * Classe per gestire i permessi di accesso alle diverse sezioni del progetto.
 *
 * @since 2.3
 */
class Permissions
{
    /** @var array Elenco dei permessi necessari */
    protected static $permissions = [];
    /** @var bool Condizione riguardante il controllo effettivo dei permessi */
    protected static $skip_permissions = false;

    /**
     * Aggiunge un modulo di cui eseguire il controllo dei permessi.
     */
    public static function addModule($module)
    {
        $id = Module::find($module)->id;
        if (!in_array($id, self::$permissions)) {
            self::$permissions[] = $id;
        }
    }

    /**
     * Ignora il controllo dei permessi per la pagina corrente.
     */
    public static function skip()
    {
        self::$skip_permissions = true;
    }

    /**
     * Riabilita il controllo dei permessi per la pagina corrente.
     */
    public static function execute()
    {
        self::$skip_permissions = false;
    }

    /**
     * Restituisce la variabile per cui si effettua o meno il controllo dei permessi per la pagina corrente.
     *
     * @return bool
     */
    public static function getSkip()
    {
        return self::$skip_permissions;
    }

    /**
     * Esegue il controllo dei permessi.
     *
     * @return bool
     */
    public static function check($permissions = [], $die = true)
    {
        if (empty($permissions)) {
            $permissions = ['r', 'rw'];
        } elseif (!is_array($permissions)) {
            $permissions = [$permissions];
        }

        $result = true;

        if (!self::getSkip()) {
            // Gestione accesso tramite token
            if (self::isTokenAccess()) {
                $permissions = ['r', 'rw', 'ra', 'rwa'];

                // Verifica permessi del token
                if (!self::checkTokenPermissions()) {
                    $result = false;
                } else {
                    // Se è un accesso tramite token, usa i permessi del token
                    $token_info = $_SESSION['token_access'];
                    $token_permission = $token_info['permessi'] ?? 'r';

                    // Verifica se il permesso del token è sufficiente
                    if (!in_array($token_permission, $permissions)) {
                        $result = false;
                    }
                }
            }
            // Gestione accesso normale
            else {
                if (!Auth::check() && getURLPath() == slashes(base_path().'/index.php')) {
                    redirect(base_path().'/index.php');
                    $result = false;
                    exit;
                } else {
                    if (!empty(self::$permissions)) {
                        foreach (self::$permissions as $module) {
                            if (!in_array(Modules::getPermission($module), $permissions)) {
                                $result = false;
                            }
                        }
                    }
                }
            }

            if (!$result && $die) {
                exit(tr('Accesso negato'));
            }
        }

        return $result;
    }

    /**
     * Verifica se l'utente è autenticato tramite token con restrizioni.
     *
     * @return bool
     */
    public static function isTokenAccess()
    {
        return Auth::check() && !empty($_SESSION['token_access']);
    }

    /**
     * Verifica i permessi per l'accesso tramite token.
     *
     * @return bool
     */
    public static function checkTokenPermissions()
    {
        if (!self::isTokenAccess()) {
            return true; // Accesso normale, usa i permessi standard
        }

        $token_info = $_SESSION['token_access'];
        $current_module = Modules::getCurrent();

        if (!$current_module) {
            Modules::setCurrent($token_info['id_module_target']);
            $current_module = Modules::getCurrent();
        }

        // Se il token ha un modulo target specifico
        if (!empty($token_info['id_module_target']) && $token_info['id_module_target'] > 0) {
            // L'utente può accedere solo al modulo specificato nel token
            if ($current_module->id != $token_info['id_module_target']) {
                return false;
            }
        }

        // Verifica permessi specifici del token
        if (!empty($token_info['permessi'])) {
            // Se il token ha permessi 'r' (sola lettura), forza sempre sola lettura
            if ($token_info['permessi'] == 'r') {
                $_SESSION['token_forced_readonly'] = true;

                return true; // Permette l'accesso ma in sola lettura
            }

            // Se il token ha permessi 'rw', verifica i permessi normali del modulo
            if ($token_info['permessi'] == 'rw') {
                return true; // Permette l'accesso in lettura e scrittura
            }
        }

        return true;
    }

    /**
     * Verifica se l'utente può accedere a un record specifico tramite token.
     *
     * @param int $record_id ID del record
     *
     * @return bool
     */
    public static function checkTokenRecordAccess($record_id)
    {
        if (!self::isTokenAccess()) {
            return true; // Accesso normale, nessuna restrizione
        }

        $token_info = $_SESSION['token_access'];

        // Se il token ha un record target specifico
        if (!empty($token_info['id_record_target']) && $token_info['id_record_target'] > 0) {
            // L'utente può accedere solo al record specificato nel token
            return $record_id == $token_info['id_record_target'];
        }

        return true; // Nessuna restrizione sul record
    }

    /**
     * Restituisce l'URL di redirect appropriato per l'accesso tramite token.
     *
     * @return string|null
     */
    public static function getTokenRedirectURL()
    {
        if (!self::isTokenAccess()) {
            return null;
        }

        $token_info = $_SESSION['token_access'];

        // Se il token ha un modulo target specifico
        if (!empty($token_info['id_module_target']) && $token_info['id_module_target'] > 0) {
            $base_url = base_path();

            // Se ha anche un record target specifico, redirect a editor.php
            if (!empty($token_info['id_record_target']) && $token_info['id_record_target'] > 0) {
                return $base_url.'/editor.php?id_module='.$token_info['id_module_target'].'&id_record='.$token_info['id_record_target'];
            } else {
                // Se non ha un record target, redirect a controller.php
                return $base_url.'/controller.php?id_module='.$token_info['id_module_target'];
            }
        }

        return null;
    }
}
