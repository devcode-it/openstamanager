<?php

namespace App\View\Components\Inputs;

use App\View\Components\Input;
use Illuminate\View\Component;

class Number extends Input
{
    /**
     * Create a new component instance.
     *
     * @param string $name
     * @param string|null $id
     * @param string|null $value
     * @param bool|string $required
     * @param string|null $label
     * @param string|null $placeholder
     * @param null $minValue
     */
    public function __construct(
        $name,
        $id = null,
        $value = null,
        $required = false,
        $label = null,
        $placeholder = null,
        $minValue = null
    ) {
        parent::__construct($name, $id, $value, $required, $label, $placeholder);

        // Aggiunta classe CSS dedicata
        $this->get('class')
            ->add('number-input');

        $value = $this->get('value');
        if (empty($value)) {
            $this->set(['value' => 0]);
        }

        // Gestione della precisione (numero specifico, oppure "qta" per il valore previsto nell'impostazione "Cifre decimali per quantità").
        $decimals = $this->get('decimals');
        if (string_starts_with($decimals, 'qta')) {
            $decimals = setting('Cifre decimali per quantità');

            // Se non è previsto un valore minimo, lo imposta a 1
            $minValue =  isset($minValue) ? $minValue : '0.'.str_repeat('0', $decimals - 1).'1';

            $this->set([
                'decimals' => $decimals,
                'min-value' => $minValue,
            ]);
        }
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|string
     */
    public function render()
    {
        return view('components.inputs.text');
    }
}
