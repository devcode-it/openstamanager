<?php

namespace Modules\Checklists\Traits;

use Modules\Checklists\Check;
use Modules\Checklists\Checklist;

trait ChecklistTrait
{
    public function checks()
    {
        return $this->hasMany(Check::class, $this->component_identifier);
    }

    public function recordChecks($id_record)
    {
        return $this->checks()->where('id_record', $id_record)->orderBy('order')->get();
    }

    public function mainChecks($id_record)
    {
        return $this->checks()->where('id_record', $id_record)->whereNull('id_parent')->orderBy('order')->get();
    }

    public function checklists()
    {
        return $this->hasMany(Checklist::class, $this->component_identifier);
    }
}
