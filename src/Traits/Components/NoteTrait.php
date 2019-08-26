<?php

namespace Traits\Components;

use Models\Note;

trait NoteTrait
{
    public function notes()
    {
        return $this->hasMany(Note::class, $this->component_identifier);
    }

    public function recordNotes($id_record)
    {
        return $this->notes()->where('id_record', $id_record)->orderBy('created_at')->get();
    }
}
