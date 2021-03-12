<?php

namespace App\View\Components\Inputs;

use App\View\Components\Input;
use Illuminate\View\Component;

class Editor extends Input
{
    public function init()
    {
        // Aggiunta classe CSS dedicata
        $this->get('class')
            ->add('editor-input');
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|string
     */
    public function render()
    {
        return view('components.inputs.editor');
    }
}
