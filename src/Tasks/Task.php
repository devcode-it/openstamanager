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

namespace Tasks;

use Carbon\Carbon;
use Common\SimpleModelTrait;
use Cron\CronExpression;
use Illuminate\Database\Eloquent\Model;
use Traits\RecordTrait;

/*
 * Risorsa per la gestione delle task ricorrenti del gestionale.
 */
class Task extends Model
{
    use SimpleModelTrait;
    use RecordTrait;
    protected $table = 'zz_tasks';

    protected static $translated_fields = [
        'title',
    ];

    protected $casts = [
        'next_execution_at' => 'datetime',
        'last_executed_at' => 'datetime',
    ];

    public function log($level, $message, $context = [], $log = null)
    {
        if (!empty($context)) {
            if (empty($log)) {
                $log = new Log();
            }

            $log->level = $level;
            $log->message = $message;
            $log->context = $context;

            $log->task()->associate($this);

            $log->save();
        }

        return $log;
    }

    public function execute()
    {
        // Registrazione dell'inizio nei log
        $log = $this->log('warning', 'Inizio esecuzione');

        // Individuazione del gestore
        $class = $this->attributes['class'];
        $manager = new $class($this);

        // Esecuzione
        $result = $manager->execute();

        // Salvataggio dell'esecuzione
        $this->last_executed_at = new Carbon();

        // Individuazione della data per la prossima esecuzione dalla relativa espressione
        $this->registerNextExecution($this->last_executed_at);
        $this->save();

        // Registrazione del completamento nei log
        $level = ($result['response'] == 1 ? 'info' : ($result['response'] == 2 ? 'warning' : 'error'));
        $this->log($level, 'Fine esecuzione', $result['message'], $log);

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
        $next_time = $now->copy()->addSecond();
        $calculated_next = Carbon::instance($cron->getNextRunDate($next_time));

        // Correzione bug: verifica che la prossima esecuzione non sia nel passato
        if ($calculated_next->lessThanOrEqualTo($now)) {
            // Se la data calcolata è nel passato o uguale a ora, calcola la successiva
            $calculated_next = Carbon::instance($cron->getNextRunDate($calculated_next->addSecond()));
        }

        $this->next_execution_at = $calculated_next;
    }

    public function delete()
    {
        return false;
    }

    public function logs()
    {
        return $this->hasMany(Log::class, 'id_task');
    }

    public function getModuleAttribute()
    {
        return '';
    }

    public static function getTranslatedFields()
    {
        return self::$translated_fields;
    }
}
