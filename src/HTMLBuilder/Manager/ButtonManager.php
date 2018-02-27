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
        } else {
            $template = \Mail::getTemplate($options['id']);

            $result = [
                'link' => ROOTDIR.'/mail.php?id_module='.$options['id_module'].'&id_record='.$options['id_record'].'&id='.$options['id'],
                'title' => $template['name'],
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
<a '.$class.' data-href="'.$info['link'].'" data-toggle="modal" data-title="'.$title.'" data-target="#bs-popup">';
        }

        // Link normale
        else {
            $result = '
<a '.$class.' href="'.$info['link'].'" target="_blank">';
        }

        $result .= '
    <i class="'.$icon.'"></i> '.$title.'
</a>';

        return $result;
    }

    protected function getList($options)
    {
        $results = [];

        if ($options['type'] == 'print') {
            $results = \Prints::getModulePrints($options['id_module']);
        } else {
            $results = \Mail::getModuleTemplates($options['id_module']);
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
<div class="btn-group">';

            $main = array_search(1, array_column($list, 'main'));
            if ($main !== false) {
                $element = $list[$main];

                $result .= $this->link([
                    'type' => $options['type'],
                    'id' => $element['id'],
                    'id_module' => $options['id_module'],
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
            'id_module' => $options['id_module'],
            'id_record' => $options['id_record'],
            'class' => false,
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
            ]);
        } else {
            $result = ' ';
        }

        return $result;
    }

    protected function defaultText($options)
    {
        $result = '';

        if ($options['type'] == 'print') {
            $result = '<i class="fa fa-print"></i> '.tr('Stampe');
        } else {
            $result = '<i class="fa fa-envelope"></i> '.tr('Email');
        }

        return $result;
    }
}
