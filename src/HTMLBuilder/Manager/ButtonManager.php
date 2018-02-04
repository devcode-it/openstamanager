<?php

namespace HTMLBuilder\Manager;

/**
 * @since 2.4
 */
class ButtonManager implements ManagerInterface
{
    public function manage($options)
    {
        $result = '';

        if (isset($options['id'])) {
            $result = $this->link($options);
        } else {
            $result = $this->dropdown($options);
        }

        return $result;
    }

    protected function getInfo($options)
    {
        $result = [];

        if ($options['type'] == 'print') {
            $print = \Prints::get($options['id']);

            $result = [
                'link' => \Prints::getHref($options['id'], $options['id_record'], $options['parameters']),
                'title' => $print['title'],
                'icon' => $print['icon'],
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

        return '
<a '.$class.' href="'.$info['link'].'" target="_blank"><i class="'.$icon.'"></i> '.$title.'</a>';
    }

    protected function getList($options)
    {
        $results = [];

        if ($options['type'] == 'print') {
            $results = \Prints::getModulePrints($options['id_module']);
        }

        return $results;
    }

    protected function dropdown($options)
    {
        $list = $this->getList($options);

        $options['class'] = isset($options['class']) ? $options['class'] : 'btn-info';

        if (count($list) > 1) {
            $result = '
<div class="btn-group">';

            $main = array_search(1, array_column($list, 'main'));
            if ($main !== false) {
                $element = $list[$main];

                $result .= $this->link([
                    'type' => $options['type'],
                    'id' => $element['id'],
                    'id_record' => $options['id_record'],
                    'class' => $options['class'],
                ]);

                unset($list[$main]);
            }

            $result .= '
    <button type="button" class="btn '.$options['class'].' dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
        '.($main === false ? $this->defaultText($options).' ' : '').'<span class="caret"></span>
        <span class="sr-only">Toggle Dropdown</span>
    </button>
    <ul class="dropdown-menu">';

            foreach ($list as $element) {
                $result .= '
        <li>'.$this->link([
            'type' => $options['type'],
            'id' => $element['id'],
            'id_record' => $options['id_record'],
            'class' => false,
        ]).'</li>';
            }

            $result .= '
    </ul>
</div>';
        } else {
            $result = $this->link([
                'type' => $options['type'],
                'id' => $list[0]['id']['id'],
                'id_record' => $options['id_record'],
                'class' => $options['class'],
            ]);
        }

        return $result;
    }

    protected function defaultText($options)
    {
        $result = '';

        if ($options['type'] == 'print') {
            $result = '<i class="fa fa-print"></i> '.tr('Stampe');
        }

        return $result;
    }
}
