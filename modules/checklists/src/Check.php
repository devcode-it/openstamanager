<?php

namespace Modules\Checklists;

use Common\Model;
use Models\Module;
use Models\Plugin;
use Models\User;
use Modules\Checklists\Traits\ChecklistTrait;
use Traits\HierarchyTrait;

class Check extends Model
{
    use HierarchyTrait;

    protected static $parent_identifier = 'id_parent';
    protected $table = 'zz_checks';

    /**
     * Crea un nuovo elemento della checklist.
     *
     * @param User           $user
     * @param User           $assigned_user
     * @param ChecklistTrait $structure
     * @param int            $id_record
     * @param string         $content
     * @param int            $parent_id
     *
     * @return self
     */
    public static function build(User $user, $structure, $id_record, $content, User $assigned_user = null, $parent_id = null)
    {
        $model = parent::build();

        $model->user()->associate($user);
        $model->id_parent = $parent_id;

        if (empty($parent_id)) {
            $model->assignedUser()->associate($assigned_user);
        } else {
            $model->assignedUser()->associate($model->parent->assignedUser);
        }

        if ($structure instanceof Module) {
            $model->module()->associate($structure);
        } elseif ($structure instanceof Plugin) {
            $model->plugin()->associate($structure);
        }

        $model->id_record = $id_record;
        $model->content = $content;

        $model->save();

        return $model;
    }

    public function toggleCheck()
    {
        $checked_at = $this->checked_at ? null : date('Y-m-d H:i:s');
        $this->checked_at = $checked_at;
        $this->save();

        $children = $this->children;
        while (!$children->isEmpty()) {
            $child = $children->shift();
            $child->checked_at = $checked_at;
            $child->save();

            $children = $children->merge($child->children);
        }
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

    /* Relazioni Eloquent */

    public function user()
    {
        return $this->belongsTo(User::class, 'id_utente');
    }

    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'id_utente_assegnato');
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
