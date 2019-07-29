<?php

namespace Modules\Checklists\Traits;

use Modules\Checklists\Check;
use Modules\Checklists\Checklist;

trait ChecklistTrait
{
    public function checks($id_record)
    {
        return $this->hasMany(Check::class, $this->component_identifier)->where('id_record', $id_record)->orderBy('created_at')->get();
    }

    public function mainChecks($id_record)
    {
        return $this->hasMany(Check::class, $this->component_identifier)->where('id_record', $id_record)->whereNull('id_parent')->orderBy('created_at')->get();
    }

    public function checklists()
    {
        return $this->hasMany(Checklist::class, $this->component_identifier)->orderBy('created_at')->get();
    }
}
