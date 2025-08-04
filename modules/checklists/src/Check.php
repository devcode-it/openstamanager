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

namespace Modules\Checklists;

use Common\SimpleModelTrait;
use Illuminate\Database\Eloquent\Model;
use Models\Group;
use Models\Module;
use Models\Plugin;
use Models\Upload;
use Models\User;
use Modules\Checklists\Traits\ChecklistTrait;
use Traits\HierarchyTrait;

class Check extends Model
{
    use SimpleModelTrait;
    use HierarchyTrait;

    protected static $parent_identifier = 'id_parent';
    protected $table = 'zz_checks';

    /**
     * Crea un nuovo elemento della checklist.
     *
     * @param ChecklistTrait $structure
     * @param int            $id_record
     * @param string         $content
     * @param int            $parent_id
     *
     * @return self
     */
    public static function build(?User $user = null, $structure = null, $id_record = null, $content = null, $parent_id = null, $is_titolo = 0, $order = 99, $id_module_from = 0, $id_record_from = 0)
    {
        $model = new static();

        $model->user()->associate($user);
        $model->id_parent = $parent_id;

        if ($structure instanceof Module) {
            $model->module()->associate($structure);
        } elseif ($structure instanceof Plugin) {
            $model->plugin()->associate($structure);
        }

        $model->id_record = $id_record;
        $model->content = $content;
        $model->is_titolo = $is_titolo;

        // Ordinamento temporaneo alla creazione
        $model->order = $order;

        $model->id_module_from = $id_module_from;
        $model->id_record_from = $id_record_from;

        $model->save();

        return $model;
    }

    public function toggleCheck(User $user)
    {
        $checked_at = $this->checked_at ? null : date('Y-m-d H:i:s');
        $this->checked_at = $checked_at;
        $this->checkUser()->associate($user);
        $this->save();

        $children = $this->children;
        while (!$children->isEmpty()) {
            $child = $children->shift();
            $child->checked_at = $checked_at;
            $child->checkUser()->associate($user);
            $child->save();

            $children = $children->merge($child->children);
        }
    }

    public function setAccess($users, $group_id)
    {
        if (!empty($this->id_parent)) {
            $users = $this->parent->assignedUsers->pluck('id')->toArray();
            $this->assignedUsers()->sync($users);

            return;
        }

        if (empty($users)) {
            if (!empty($group_id)) {
                $group = Group::find($group_id);

                $users = $group->users->pluck('id')->toArray();
            } else {
                $users = User::all()->pluck('id')->toArray();
            }
        }

        $this->assignedUsers()->sync($users);
    }

    /*
     * Rimozione ricorsiva gestita da MySQL.
    public function delete()
    {
        return parent::delete();

        $children = $check->children;
        while (!$children->isEmpty()){
            $child = $children->shift();
            $child->delete();

            $children = $children->merge($child->children);
        }
    }
    */

    /**
     * Rimuove tutte le check di un determinato modulo/plugin e record.
     *
     * @param array $data
     */
    public static function deleteLinked($data)
    {
        $record = Check::where('id_module', $data['id_module'])
            ->where('id_module', $data['id_module'])
            ->where('id_record', $data['id_record'])
            ->where('id_module_from', $data['id_module_from'])
            ->where('id_record_from', $data['id_record_from'])
            ->get();

        foreach ($record as $r) {
            $r->delete();
        }
    }

    /* Relazioni Eloquent */

    public function user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function checkUser()
    {
        return $this->belongsTo(User::class, 'checked_by');
    }

    public function assignedUsers()
    {
        return $this->belongsToMany(User::class, 'zz_check_user', 'id_check', 'id_utente');
    }

    public function plugin()
    {
        return $this->belongsTo(Plugin::class, 'id_plugin');
    }

    public function module()
    {
        return $this->belongsTo(Module::class, 'id_module');
    }

    public function getImageAttribute()
    {
        if (empty($this->id_immagine)) {
            return null;
        }

        $file = Upload::find($this->id_immagine);

        $module = Module::where('id', $file->id_module)->first();
        $fileinfo = \Uploads::fileInfo($file->filename);

        $directory = '/'.$module->upload_directory.'/';
        $image = $directory.$file->filename;
        $image_thumbnail = $directory.$fileinfo['filename'].'_thumb600.'.$fileinfo['extension'];

        $url = file_exists(base_dir().$image_thumbnail) ? base_path().$image_thumbnail : base_path().$image;

        return $url;
    }

    public function delete()
    {
        if (!empty($this->id_immagine)) {
            // Se sono valorizzati id_module_from e id_record_from verifico l'id_immagine. Se non presente allora è stato inserito per la check e posso eliminare il file
            if (!empty($this->id_module_from) && !empty($this->id_record_from)) {
                $check = Check::where('id_module_from', $this->id_module_from)->where('id_record_from', $this->id_record_from)->where('id_immagine', $this->id_immagine)->where('id', '!=', $this->id)->first();
                if (!empty($check)) {
                    return parent::delete();
                }
            }

            $file = Upload::find($this->id_immagine);

            \Uploads::delete($file->filename, [
                'id_module' => $this->id_module,
                'id_record' => $this->id_record,
            ]);
        }

        return parent::delete();
    }
}
