<?php

namespace App\View\Components;

use Illuminate\View\Component;

class Input extends Component
{
    public $unique_id;

    public $id;
    public $name;
    public $required;
    public $label;
    public $placeholder;

    public $class;

    /**
     * Create a new component instance.
     *
     * @param string $name
     * @param null $id
     * @param bool $required
     * @param null $label
     * @param null $placeholder
     */
    public function __construct(
        $name,
        $id = null,
        $required = false,
        $label = null,
        $placeholder = null
    ) {
        $this->id = isset($id) ? $id : $name;
        $this->name = $name;

        $this->required = is_string($required) ? $required == 'true' : (bool) $required;
        $this->label = $label;
        $this->placeholder = $placeholder;

        $rand = rand(0, 9999);
        $this->unique_id = $id.$rand;

        $this->class = 'form-control openstamanager-input';

        // Label e placeholder corretti in base al contenuti obbligatorio o meno
        if ($this->required) {
            if (!empty($this->label)) {
                $this->label .= '*';
            }

            // Aggiunta
            elseif (!empty($this->placeholder)) {
                $this->placeholder .= '*';
            }
        }
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|string
     */
    public function render()
    {
        return view('components.input');
    }
}
