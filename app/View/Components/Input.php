<?php

namespace App\View\Components;

use Illuminate\Support\Collection;
use Illuminate\View\Component;

class Input extends Component
{
    public $props;

    /**
     * Create a new component instance.
     *
     * @param string $name
     * @param string|null $id
     * @param string|null $value
     * @param bool|string $required
     * @param string|null $label
     * @param string|null $placeholder
     */
    public function __construct(
        $name,
        $id = null,
        $value = null,
        $required = false,
        $label = null,
        $placeholder = null
    ) {
        // Definizione ID dell'elemento
        $id = isset($id) ? $id : $name;
        $rand = rand(0, 9999);
        $unique_id = $id.$rand;

        // Elemento obbligatorio o meno
        $required = is_string($required) ? $required == 'true' : (bool) $required;

        // Label e placeholder corretti in base al contenuti obbligatorio o meno
        if ($required) {
            if (!empty($label)) {
                $label .= '*';
            }

            // Aggiunta
            elseif (!empty($placeholder)) {
                $placeholder .= '*';
            }
        }

        $this->props = $this->newAttributeBag([
            'name' => $name,
            'id' => $id,
            'value' => $value,
            'unique_id' => $unique_id,
            'required' => $required,
            'label' => $label,
            'placeholder' => $placeholder,
            'class' => collect(['form-control', 'openstamanager-input']),
        ]);

        // Operazioni finali
        $this->init();
    }

    public function get($key, $default = null)
    {
        return $this->props->get($key, $default);
    }

    public function set($values)
    {
        $this->props->setAttributes(array_merge($this->props->getAttributes(), $values));
    }

    public function init()
    {
    }

    /**
     * Extract the public properties for the component.
     *
     * @return array
     */
    public function extractPublicProperties()
    {
        $values = parent::extractPublicProperties();

        foreach ($this->props as $key => $value) {
            $values[$key] = $value instanceof Collection ? $value->join(' ') : $value;
        }

        return $values;
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
