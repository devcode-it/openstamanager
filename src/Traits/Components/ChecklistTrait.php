<?php

namespace Traits\Components;

use Models\Checklist;

trait ChecklistTrait
{
    public function checklists($id_record)
    {
        return $this->hasMany(Checklist::class, $this->component_identifier)->where('id_record', $id_record)->whereNull('id_parent')->orderBy('created_at')->get();
    }
}
