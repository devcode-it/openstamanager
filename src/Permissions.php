<?php

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
        $id = Modules::getModule($module)['id'];
        if (is_int($id) && !in_array($id, self::$permissions)) {
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
    public static function check($permissions = [])
    {
        if (empty($permissions)) {
            $permissions = ['r', 'rw'];
        } elseif (!is_array($permissions)) {
            $permissions = [$permissions];
        }

        $result = true;

        if (!self::getSkip()) {
            if (!Auth::check() && slashes($_SERVER['SCRIPT_FILENAME']) == slashes(DOCROOT.'/index.php')) {
                redirect(ROOTDIR.'/index.php');
                exit();
                $result = false;
            } else {
                if (!empty(self::$permissions)) {
                    foreach (self::$permissions as $module) {
                        if (!in_array(Modules::getPermission($module), $permissions)) {
                            $result = false;
                        }
                    }
                }

                if (!$result) {
                    die(tr('Accesso negato'));
                }
            }
        }

        return $result;
    }
}
