<?php

namespace Hooks;

use Models\Hook;

abstract class Manager
{
    protected $hook = null;

    public function __construct(Hook $hook)
    {
        $this->hook = $hook;
    }

    /**
     * Restituisce le informazioni sull'esecuzione dell'hook.
     *
     * @return mixed
     */
    abstract public function execute();

    /**
     * Restituisce le informazioni per la visualizzazione dell'hook.
     *
     * @return array
     */
    abstract public function response();

    /**
     * Restituisce se l'hook è un singletion, cioè deve essere richiamato solo da una istanza di navigazione.
     *
     * @return bool
     */
    public function isSingleton()
    {
        return false;
    }

    /**
     * Restituisce se l'hook ha bisogno di una esecuzione.
     *
     * @return bool
     */
    abstract public function needsExecution();

    /**
     * Gestisce la chiamata per l'esecuzione dell'hook.
     *
     * @return array|mixed
     */
    public function manage()
    {
        if (!$this->needsExecution()) {
            return [];
        }

        $results = $this->execute();

        return [];
    }

    /**
     * Restituisce l'hook Eloquent relativo alla classe.
     *
     * @return Hook|null
     */
    protected function getHook()
    {
        return $this->getHook();
    }
}
