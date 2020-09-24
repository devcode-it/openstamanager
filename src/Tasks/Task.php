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

namespace Tasks;

use Carbon\Carbon;
use Common\SimpleModelTrait;
use Cron\CronExpression;
use Illuminate\Database\Eloquent\Model;

/*
 * Risorsa per la gestione delle task ricorrenti del gestionale.
 */
class Task extends Model
{
    use SimpleModelTrait;

    protected $table = 'zz_tasks';

    protected $dates = [
        'next_execution_at',
        'last_executed_at',
    ];

    public function log($level, $message, $context = [])
    {
        $log = new static();

        $log->level = $level;
        $log->message = $message;
        $log->context = $context;

        $log->task()->associate($this);

        $log->save();
    }

    public function execute()
    {
        // Registrazione dell'inizio nei log
        $this->log('info', 'Inizio esecuzione');

        // Individuazione del gestore
        $class = $this->attributes['class'];
        $manager = new $class($this);

        // Esecuzione
        $result = $manager->execute();

        // Salvtagggio dell'esecuzione
        $this->last_executed_at = new Carbon();

        // Individuazione della data per la prossima esecuzione dalla relativa espressione
        $this->registerNextExecution($this->last_executed_at);
        $this->save();

        // Registrazione del completamento nei log
        $this->log('info', 'Fine esecuzione');

        return $result;
    }

    public function registerMissedExecution(Carbon $now)
    {
        // Registrazione del completamento nei log
        $this->log('warning', 'Esecuzione mancata', [
            'timestamp' => $this->next_execution_at->toDateTimeString(),
        ]);

        $this->registerNextExecution($now);
        $this->save();
    }

    public function registerNextExecution(Carbon $now)
    {
        $cron = CronExpression::factory($this->expression);
        $this->next_execution_at = Carbon::instance($cron->getNextRunDate($now));
    }

    public function delete()
    {
        return false;
    }

    public function logs()
    {
        return $this->hasMany(Log::class, 'id_task');
    }
}
