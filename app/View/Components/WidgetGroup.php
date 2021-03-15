<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Models\Module;

class WidgetGroup extends Component
{
    /**
     * @var array
     */
    protected $widgets;
    /**
     * @var string
     */
    protected $position;

    /**
     * Create a new component instance.
     *
     * @param string|int $module
     * @param string $place
     * @param string $position
     * @throws \Exception
     */
    public function __construct(
        $module,
        $place,
        $position
    ) {
        $module = module($module);
        Module::setCurrent($module->id);

        $query = 'SELECT id FROM zz_widgets WHERE id_module = '.prepare($module->id).' AND (|position|) AND enabled = 1 ORDER BY `order` ASC';

        // Mobile (tutti i widget a destra)
        if (isMobile()) {
            if ($position == 'right') {
                $position = "location = '".$place."_right' OR location = '".$place."_top'";
            } elseif ($position == 'top') {
                $position = '1=0';
            }
        }

        // Widget a destra
        elseif ($position == 'right') {
            $position = "location = '".$place."_right'";
        }

        // Widget in alto
        elseif ($position == 'top') {
            $position = "location = '".$place."_top'";
        }

        $query = str_replace('|position|', $position, $query);

        // Individuazione dei widget interessati
        $this->widgets = database()->fetchArray($query);
        $this->position = $position;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|string
     */
    public function render()
    {
        return view('components.widget-group', [
            'widgets' => $this->widgets,
            'position' => $this->position,
        ]);
    }
}
