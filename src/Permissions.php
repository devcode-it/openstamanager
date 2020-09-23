<?php
/*
 * OpenSTAManager: il software gestionale open source per l'assistenza tecnica e la fatturazione
 * Copyright (C) DevCode s.n.c.
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
     *
     * @param string $id
     */
    public static function addModule($module)
    {
        $id = Modules::get($module)['id'];
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
            if (!Auth::check() && getURLPath() == slashes(base_path().'/index.php')) {
                redirect(base_path().'/index.php');
                $result = false;
                exit();
            } else {
                if (!empty(self::$permissions)) {
                    foreach (self::$permissions as $module) {
                        if (!in_array(Modules::getPermission($module), $permissions)) {
                            $result = false;
                        }
                    }
                }

                if (!$result && $die) {
                    die(tr('Accesso negato'));
                }
            }
        }

        return $result;
    }
}
