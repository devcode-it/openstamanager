<?php

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
        return $this->checks()->whereNull('id_parent')->orderBy('created_at')->get();
    }

    public function copia(User $user, User $assigned_user, $id_record)
    {
        $structure = $this->plugin ?: $this->module;

        $checks = $this->mainChecks();
        $relations = [];

        while (!$checks->isEmpty()) {
            $child = $checks->shift();
            $id_parent = $child->id_parent ? $relations[$child->id_parent] : null;

            $check = Check::build($user, $structure, $id_record, $child->content, $assigned_user, $id_parent);
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
