<?php

namespace Hooks;

use Models\Hook;

abstract class Manager
{
    /**
     * Restituisce le informazioni sull'esecuzione dell'hook.
     *
     * @return mixed
     */
    abstract public function manage();

    /**
     * Restituisce le informazioni per la visualizzazione dell'hook.
     *
     * @param $results
     *
     * @return array
     */
    abstract public function response($results);

    /**
     * Restituisce le informazioni per l'inizializzazione dell'hook.
     *
     * @return array|null
     */
    public function prepare()
    {
        return null;
    }

    /**
     * Restituisce l'hook Eloquent relativo alla classe.
     *
     * @return Hook|null
     */
    protected static function getHook()
    {
        $class = get_called_class();

        $hook = Hook::where('class', $class)->first();

        return $hook;
    }
}
