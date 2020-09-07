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

    protected $hidden = [
        'task',
    ];

    public function task()
    {
        return $this->belongsTo(Task::class, 'id_task');
    }
}
