<?php

namespace Models\Checklist;

use Common\Model;
use Traits\HierarchyTrait;

class Checklist extends Model
{
    use HierarchyTrait;

    protected static $parent_identifier = 'id_parent';
    protected $table = 'zz_checklist_items';

    /**
     * Crea un nuovo elemento della checklist.
     *
     * @param Checklist      $checklist
     * @param string    $contenuto
     * @param int       $id_parent
     *
     * @return self
     */
    public static function build(Checklist $checklist, $contenuto, $id_parent = null)
    {
        $model = parent::build();

        $model->checklist()->associate($checklist);
        $model->id_parent = $id_parent;
        $model->content = $contenuto;

        $model->save();

        return $model;
    }

    /* Relazioni Eloquent */

    public function checklist()
    {
        return $this->belongsTo(Checklist::class, 'id_checklist');
    }
}
