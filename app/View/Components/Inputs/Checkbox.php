<?php

namespace App\View\Components\Inputs;

use App\View\Components\Input;
use Illuminate\View\Component;

class Checkbox extends Input
{
    public function init()
    {
        $class = $this->get('class');

        // Rimozione classe CSS predefinita
        $key = $class->search('form-control');
        $class->forget($key);

        // Correzione valore impostato a boolean
        $value = $this->get('value');
        $this->set([
            'value' => empty($value) || $value == 'off' ? false : true,
            'placeholder' => $this->get('placeholder', $this->get('label')),
        ]);
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|string
     */
    public function render()
    {
        return view('components.inputs.checkbox');
    }
}
