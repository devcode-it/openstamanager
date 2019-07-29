<?php

namespace Modules\Checklists;

use Common\Model;

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
