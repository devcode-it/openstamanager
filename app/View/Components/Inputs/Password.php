<?php

namespace App\View\Components\Inputs;

use App\View\Components\Input;

class Password extends Input
{
    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|string
     */
    public function render()
    {
        return view('components.inputs.password');
    }
}
