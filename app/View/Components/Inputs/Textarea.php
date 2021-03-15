<?php

namespace App\View\Components\Inputs;

use App\View\Components\InputWrapper;
use Illuminate\View\Component;

class Textarea extends InputWrapper
{
    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|string
     */
    public function render()
    {
        return view('components.inputs.textarea');
    }
}
