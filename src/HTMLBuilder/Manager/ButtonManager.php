<?php
/*
 * OpenSTAManager: il software gestionale open source per l'assistenza tecnica e la fatturazione
 * Copyright (C) DevCode s.n.c.
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

namespace HTMLBuilder\Manager;

use Modules\Emails\Template;

/**
 * @since 2.4
 */
class ButtonManager implements ManagerInterface
{
    public function manage($options)
    {
        $options['parameters'] = isset($options['parameters']) ? $options['parameters'] : null;

        // Impostazione id HTML automatico
        if (empty($options['html_id'])) {
            $options['html_id'] = ($options['type'] == 'print') ? 'print-button' : 'email-button';
        }

        if (isset($options['id'])) {
            $result = $this->link($options);
        } else {
            $result = $this->dropdown($options);
        }

        return $result;
    }

    protected function getInfo($options)
    {
        if ($options['type'] == 'print') {
            $print = \Prints::get($options['id']);

            $result = [
                'link' => \Prints::getHref($options['id'], $options['id_record'], $options['parameters']),
                'title' => tr('Stampa').' '.((strtoupper($print['title']) == $print['title']) ? $print['title'] : lcfirst($print['title'])),
                'icon' => $print['icon'],
            ];
        } else {
            $template = Template::find($options['id']);

            $result = [
                'link' => ROOTDIR.'/mail.php?id_module='.$options['id_module'].'&id_record='.$options['id_record'].'&id='.$options['id'].$options['parameters'],
                'title' => tr('Invia').' '.((strtoupper($template['name']) == $template['name']) ? $template['name'] : lcfirst($template['name'])),
                'icon' => $template['icon'],
                'type' => 'modal',
            ];
        }

        return $result;
    }

    protected function link($options)
    {
        $info = $this->getInfo($options);

        $class = isset($options['class']) ? $options['class'] : 'btn-info';
        $class = !empty($class) ? ' class="btn '.$class.'" ' : '';

        $title = isset($options['label']) ? $options['label'] : $info['title'];

        $icon = !empty($options['icon']) ? $options['icon'] : $info['icon'];
        $icon = str_replace('|default|', $info['icon'], $icon);

        // Modal
        if (isset($info['type']) && $info['type'] == 'modal') {
            $result = '
<a '.$class.' data-href="'.$info['link'].'" data-toggle="modal" data-title="'.$title.'" id="'.$options['html_id'].'">';
        }

        // Link normale
        else {
            $result = '
<a '.$class.' href="'.$info['link'].'" target="_blank" id="'.$options['html_id'].'">';
        }

        $result .= '
    <i class="'.$icon.'"></i> '.$title.'
</a>';

        return $result;
    }

    protected function getList($options)
    {
        if ($options['type'] == 'print') {
            $results = \Prints::getModulePrints($options['id_module']);
        } else {
            $results = Template::where('id_module', $options['id_module'])->get()->toArray();
        }

        return $results;
    }

    protected function dropdown($options)
    {
        $list = $this->getList($options);
        $count = count($list);

        $options['class'] = isset($options['class']) ? $options['class'] : 'btn-info';

        if ($count > 1) {
            $result = '
<div class="btn-group" id="'.$options['html_id'].'">';

            $predefined = array_search(1, array_column($list, 'predefined'));
            if ($predefined !== false) {
                $element = $list[$predefined];

                $result .= $this->link([
                    'type' => $options['type'],
                    'id' => $element['id'],
                    'id_module' => $options['id_module'],
                    'id_record' => $options['id_record'],
                    'class' => $options['class'],
                    'parameters' => $options['parameters'],
                ]);

                unset($list[$predefined]);
            }

            $result .= '
    <button type="button" class="btn '.$options['class'].' dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
        '.($predefined === false ? $this->defaultText($options).' ' : '').'<span class="caret"></span>
        <span class="sr-only">Toggle Dropdown</span>
    </button>
    <ul class="dropdown-menu dropdown-menu-right">';

            foreach ($list as $element) {
                $result .= '
        <li>'.$this->link([
            'type' => $options['type'],
            'id' => $element['id'],
            'id_module' => $options['id_module'],
            'id_record' => $options['id_record'],
            'class' => false,
            'parameters' => $options['parameters'],
        ]).'</li>';
            }

            $result .= '
    </ul>
</div>';
        } elseif ($count == 1) {
            $result = $this->link([
                'type' => $options['type'],
                'id' => $list[0]['id'],
                'id_module' => $options['id_module'],
                'id_record' => $options['id_record'],
                'class' => $options['class'],
                'parameters' => $options['parameters'],
                'html_id' => $options['html_id'],
            ]);
        } else {
            $result = ' ';
        }

        return $result;
    }

    protected function defaultText($options)
    {
        if ($options['type'] == 'print') {
            $result = '<i class="fa fa-print"></i> '.tr('Stampa');
        } else {
            $result = '<i class="fa fa-envelope"></i> '.tr('Invia');
        }

        return $result;
    }
}
