<?php

namespace App\View\Components\Inputs;

use AJAX;
use App\View\Components\InputWrapper;
use Illuminate\View\Component;

class Select extends InputWrapper
{
    /**
     * Create a new component instance.
     *
     * @param string      $name
     * @param string|null $id
     * @param string|null $value
     * @param bool|string $required
     * @param string|null $label
     * @param string|null $placeholder
     * @param bool        $multiple
     * @param string      $source
     * @param string      $query
     * @param array       $values
     * @param array       $options
     */
    public function __construct(
        $name,
        $id = null,
        $value = null,
        $required = false,
        $label = null,
        $placeholder = null,
        $multiple = false,
        $source = null,
        $query = null,
        $values = null,
        $options = []
    ) {
        parent::__construct($name, $id, $value, $required, $label, $placeholder);

        // Aggiunta classe CSS dedicata
        $this->get('class')
            ->add('select-input')
            // Individuazione della classe per la corretta gestione JavaScript
            ->add(!empty($source) ? 'superselectajax' : 'superselect');

        // Parsing del campo value come array
        $value = $this->get('value');
        $value = explode(',', $value);
        if (count($value) === 1 && strlen($value[0]) === 0) {
            $value = [];
        }

        // Impostazione dei dati aggiuntivi
        $this->set([
            'value' => $value,
            'multiple' => $multiple,
            'options' => $options,
            'source' => $source,
        ]);

        // Gestione del caricamento delle opzioni opzioni
        if (!empty($source)) {
            // Richiamo del file dedicato alle richieste AJAX per ottenere il valore iniziale del select
            $response = AJAX::select($source, $value, null, 0, 100, $options);
            $values = $response['results'];
        } elseif (!empty($query)) {
            $values = database()->fetchArray($query);
        } else {
            $values = isset($values) ? $values : [];
        }

        // Raggruppamento per campo optgroup
        $is_grouped = false;
        $groups = collect($values)->mapToGroups(function ($item) {
            return [isset($item['optgroup']) ? $item['optgroup'] : '' => $item];
        });
        if (!($groups->count() == 1 && $groups->keys()->first() == '')) {
            $values = $groups;
            $is_grouped = true;
        }

        // Aggiornamento delle informazioni
        $this->set([
            'values' => $values,
            'is_grouped' => $is_grouped,
        ]);
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|string
     */
    public function render()
    {
        return view('components.inputs.select');
    }
}
