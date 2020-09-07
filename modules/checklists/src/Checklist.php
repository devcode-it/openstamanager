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

use Common\Model;
use Models\Module;
use Models\Plugin;
use Models\User;

class Checklist extends Model
{
    protected $table = 'zz_checklists';

    /**
     * Crea una nuova checklist.
     *
     * @param string $nome
     *
     * @return self
     */
    public static function build($nome)
    {
        $model = parent::build();

        $model->name = $nome;
        $model->save();

        return $model;
    }

    public function mainChecks()
    {
        return $this->checks()->whereNull('id_parent')->orderBy('order')->get();
    }

    public function copia(User $user, $id_record, $users, $group_id)
    {
        $structure = $this->plugin ?: $this->module;

        $checks = $this->mainChecks();
        $relations = [];

        while (!$checks->isEmpty()) {
            $child = $checks->shift();
            $id_parent = $child->id_parent ? $relations[$child->id_parent] : null;

            $check = Check::build($user, $structure, $id_record, $child->content, $id_parent);
            $check->setAccess($users, $group_id);

            $relations[$child->id] = $check->id;

            $checks = $checks->merge($child->children);
        }
    }

    /* Relazioni Eloquent */

    public function checks()
    {
        return $this->hasMany(ChecklistItem::class, 'id_checklist');
    }

    public function plugin()
    {
        return $this->belongsTo(Plugin::class, 'id_plugin');
    }

    public function module()
    {
        return $this->belongsTo(Module::class, 'id_module');
    }
}
