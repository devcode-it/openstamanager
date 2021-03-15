<?php
/*
 * OpenSTAManager: il software gestionale open source per l'assistenza tecnica e la fatturazione
 * Copyright (C) DevCode s.r.l.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

namespace App\OSM\Widgets;

use App\OSM\ComponentManager;

/**
 * Classe dedicata alla gestione di base dei widget del gestionale.
 * Introduce un rendering di base e definisce i comportamenti standard da estendere per un utilizzo più completo.
 *
 * @since 2.5
 */
abstract class Manager extends ComponentManager
{
    protected $record_id;

    public function setRecord(?int $record_id = null)
    {
        $this->record_id = $record_id;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|string
     */
    public function render()
    {
        $widget = $this->model;

        $title = $this->getTitle();
        $content = $this->getContent();
        $attributes = $this->getAttributes();

        return view('components.widget', [
            'widget' => $widget,
            'title' => $title,
            'content' => $content,
            'attrs' => $attributes,
        ]);
    }

    abstract public function getTitle(): string;

    abstract public function getContent(): string;

    public function getAttributes(): string
    {
        return 'href="#"';
    }
}
