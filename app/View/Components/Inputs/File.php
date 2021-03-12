<?php

namespace App\View\Components\Inputs;

use App\View\Components\Input;
use Illuminate\View\Component;

class File extends Input
{
    public $type = 'file';

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|string
     */
    public function render()
    {
        return view('components.inputs.file');
    }
}
