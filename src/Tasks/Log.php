<?php

namespace Tasks;

use Common\Model;

/**
 * Risorsa per la gestione dei log per le task ricorrenti del gestionale.
 */
class Log extends Model
{
    protected $table = 'zz_tasks_logs';

    protected $casts = [
        'context' => 'array',
    ];

    public function task()
    {
        return $this->belongsTo(Task::class, 'id_task');
    }
}
