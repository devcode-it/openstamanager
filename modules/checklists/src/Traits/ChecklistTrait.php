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

namespace Modules\Checklists\Traits;

use Modules\Checklists\Check;
use Modules\Checklists\Checklist;

trait ChecklistTrait
{
    public function checks()
    {
        return $this->hasMany(Check::class, $this->component_identifier);
    }

    public function recordChecks($id_record)
    {
        return $this->checks()->where('id_record', $id_record)->orderBy('order')->get();
    }

    public function mainChecks($id_record)
    {
        return $this->checks()->where('id_record', $id_record)->whereNull('id_parent')->orderBy('order')->get();
    }

    public function checklists()
    {
        return $this->hasMany(Checklist::class, $this->component_identifier);
    }
}
