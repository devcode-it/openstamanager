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

namespace Modules\Checklists;

use Common\SimpleModelTrait;
use Illuminate\Database\Eloquent\Model;
use Traits\HierarchyTrait;

class ChecklistItem extends Model
{
    use SimpleModelTrait;
    use HierarchyTrait;

    protected static $parent_identifier = 'id_parent';
    protected $table = 'zz_checklist_items';

    /**
     * Crea un nuovo elemento della checklist.
     *
     * @param string $contenuto
     * @param int    $id_parent
     *
     * @return self
     */
    public static function build(Checklist $checklist, $contenuto, $id_parent = null)
    {
        $model = new static();

        $model->checklist()->associate($checklist);
        $model->id_parent = $id_parent;
        $model->content = $contenuto;

        $model->findOrder();

        $model->save();

        return $model;
    }

    /* Relazioni Eloquent */

    public function checklist()
    {
        return $this->belongsTo(Checklist::class, 'id_checklist');
    }

    protected function findOrder()
    {
        $this->order = orderValue($this->table, 'id_checklist', $this->id_checklist);
    }
}
