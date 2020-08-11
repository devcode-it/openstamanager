<?php

namespace Tasks;

use Carbon\Carbon;
use Common\Model;

/**
 * Risorsa per la gestione delle task ricorrenti del gestionale.
 */
class Task extends Model
{
    protected $table = 'zz_tasks';

    protected $dates = [
        'last_executed_at',
    ];

    public function execute()
    {
        // Individuazione del gestore
        $class = $this->attributes['class'];
        $manager = new $class($this);

        // Esecuzione
        $result = $manager->execute();

        // Salvtagggio dell'esecuzione
        $this->last_executed_at = new Carbon();
        $this->save();

        return $result;
    }

    public function delete()
    {
        return false;
    }
}
