<?php

namespace Traits;

use Models\Note;

trait NoteTrait
{
    public function notes($id_record)
    {
        return $this->hasMany(Note::class, $this->note_identifier)->where('id_record', $id_record)->orderBy('created_at')->get();
    }
}
