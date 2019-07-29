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
     * @param string         $contenuto
     * @param int            $id_parent
     *
     * @return self
     */
    public static function build(User $user, User $assigned_user, $structure, $id_record, $contenuto, $id_parent = null)
    {
        $model = parent::build();

        $model->user()->associate($user);
        $model->assignedUser()->associate($assigned_user);

        if ($structure instanceof Module) {
            $model->module()->associate($structure);
        } elseif ($structure instanceof Plugin) {
            $model->plugin()->associate($structure);
        }

        $model->id_record = $id_record;
        $model->id_parent = $id_parent;
        $model->content = $contenuto;

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
