<?php

namespace App\View\Components;

use App\OSM\Widgets\Manager;
use App\OSM\Widgets\Widget as Model;
use Illuminate\View\Component;

class Widget extends Component
{
    protected $widget;
    /**
     * @var Manager
     */
    protected $manager;

    /**
     * Create a new component instance.
     *
     * @param $id
     */
    public function __construct(
        $id
    ) {
        $this->widget = Model::find($id);
        $this->manager = $this->widget->getManager();
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|string
     */
    public function render()
    {
        $manager = $this->manager;

        return $manager->render();
    }
}
