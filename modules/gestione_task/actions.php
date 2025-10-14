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

include_once __DIR__.'/../../core.php';
use Tasks\Task;

switch (post('op')) {
    case 'update':
        $name = post('name');
        $task_new = Task::where('name', $name)->first()->id;

        if (!empty($task_new) && $task_new != $id_record) {
            flash()->error(tr('Questo nome è già stato utilizzato per un altro task.'));
        } else {
            $task->class = post('class');
            $task->expression = post('expression');
            $task->enabled = post('enabled');
            $task->save();

            $task->setTranslation('title', $name);
            flash()->info(tr('Informazioni salvate correttamente.'));
        }

        break;
}
