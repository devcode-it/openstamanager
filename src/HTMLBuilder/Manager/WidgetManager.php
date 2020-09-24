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

use Util\Query;

/**
 * @since 2.4
 */
class WidgetManager implements ManagerInterface
{
    public function manage($options)
    {
        $result = '';

        if (isset($options['id'])) {
            $result = $this->widget($options);
        } else {
            $result = $this->group($options);
        }

        return $result;
    }

    protected function widget($options)
    {
        $database = database();

        // Widget richiesto
        $widget = $database->fetchArray('SELECT * FROM zz_widgets WHERE id = '.prepare($options['id']))[0];

        $result = ' ';

        // Generazione del widget in base al tipo
        switch ($widget['type']) {
            // Stampa
            case 'print':
                $result = $this->prints($widget);

                break;

            // Statistiche
            case 'stats':
                $result = $this->stats($widget);

                break;

            // Chart (codice PHP)
            case 'chart':
                $result = $this->chart($widget);

                break;

            // Personalizzato (codice PHP e icona)
            case 'custom':
                $result = $this->custom($widget);

                break;
        }

        return $result;
    }

    protected static function getModule()
    {
        return \Modules::get('Stato dei servizi');
    }

    protected function prints($widget)
    {
        return $this->stats($widget);
    }

    protected function stats($widget)
    {
        // Individuazione della query relativa
        $query = $widget['query'];

        $additionals = \Modules::getAdditionalsQuery($widget['id_module']);
        if (!empty($additionals)) {
            $query = str_replace('1=1', '1=1 '.$additionals, $query);
        }

        $query = Query::replacePlaceholder($query);

        // Individuazione del risultato della query
        $database = database();
        $value = null;
        if (!empty($query)) {
            $value = $database->fetchArray($query)[0]['dato'];
            if (!preg_match('/\\d/', $value)) {
                $value = '-';
            }
        }

        return $this->render($widget, $widget['text'], $value);
    }

    protected function chart($widget, $number = null)
    {
        $content = null;
        if (!empty($widget['php_include'])) {
            $is_title_request = true;

            ob_start();
            include base_dir().'/'.$widget['php_include'];
            $content = ob_get_clean();
        }

        return $this->render($widget, $content, $number);
    }

    protected function custom($widget)
    {
        $content = null;
        if (!empty($widget['php_include'])) {
            $is_number_request = true;
            ob_start();
            include base_dir().'/'.$widget['php_include'];
            $content = ob_get_clean();
        }

        return $this->chart($widget, $content);
    }

    protected function render($widget, $title, $number = null)
    {
        $result = '
        <button type="button" class="close" onclick="if(confirm(\'Disabilitare questo widget?\')) { $.post( \''.base_path().'/actions.php?id_module='.self::getModule()->id.'\', { op: \'disable_widget\', id: \''.$widget['id'].'\' }, function(response){ location.reload(); }); };" >
            <span aria-hidden="true">&times;</span><span class="sr-only">'.tr('Chiudi').'</span>
        </button>';

        if (!empty($widget['more_link'])) {
            $result .= '
<a class="clickable" ';

            // Link diretto
            if ($widget['more_link_type'] == 'link') {
                $result .= 'href="'.$widget['more_link'].'"';
            }

            // Modal
            elseif ($widget['more_link_type'] == 'popup') {
                $result .= 'data-href="'.$widget['more_link'].'" data-toggle="modal" data-title="'.$widget['text'].'"';
            }

            // Codice JavaScript
            elseif ($widget['more_link_type'] == 'javascript') {
                $link = $widget['more_link'];

                $link = Query::replacePlaceholder($link);

                $result .= 'onclick="'.$link.'"';
            }

            $result .= '>';
        }

        $result .= '

        <div class="info-box">
            <span class="info-box-icon" style="background-color:'.$widget['bgcolor'].'">';

        if (!empty($widget['icon'])) {
            $result .= '
                <i class="'.$widget['icon'].'"></i>';
        }

        $result .= '
            </span>

            <div class="info-box-content">
                <span class="info-box-text'.(!empty($widget['help']) ? ' tip' : '').'"'.(!empty($widget['help']) ? ' title="'.prepareToField($widget['help']).'" data-position="bottom"' : '').'>
                    '.$title.'

                    '.(!empty($widget['help']) ? '<i class="fa fa-question-circle-o"></i>' : '').'
                </span>';

        if (isset($number)) {
            $result .= '
                <span class="info-box-number">'.$number.'</span>';
        }

        $result .= '
            </div>';

        $result .= '
        </div>';

        if (!empty($widget['more_link'])) {
            $result .= '
            </a>';
        }

        return $result;
    }

    protected function group($options)
    {
        $query = 'SELECT id FROM zz_widgets WHERE id_module = '.prepare($options['id_module']).' AND (|position|) AND enabled = 1 ORDER BY `order` ASC';

        // Mobile (tutti i widget a destra)
        if (isMobile()) {
            if ($options['position'] == 'right') {
                $position = "location = '".$options['place']."_right' OR location = '".$options['place']."_top'";
            } elseif ($options['position'] == 'top') {
                $position = '1=0';
            }
        }

        // Widget a destra
        elseif ($options['position'] == 'right') {
            $position = "location = '".$options['place']."_right'";
        }

        // Widget in alto
        elseif ($options['position'] == 'top') {
            $position = "location = '".$options['place']."_top'";
        }

        $query = str_replace('|position|', $position, $query);

        // Individuazione dei widget interessati
        $database = database();
        $widgets = $database->fetchArray($query);

        $result = ' ';

        // Generazione del codice HTML
        if (!empty($widgets)) {
            $row_max = count($widgets);
            if ($row_max > 4) {
                $row_max = 4;
            } elseif ($row_max < 2) {
                $row_max = 2;
            }

            $result = '
<ul class="row widget" id="widget-'.$options['position'].'" data-class="">';

            // Aggiungo ad uno ad uno tutti i widget
            foreach ($widgets as $widget) {
                $result .= '
    <li class="col-sm-6 col-md-4 col-lg-'.intval(12 / $row_max).' li-widget" id="widget_'.$widget['id'].'">';

                $info = array_merge($options, [
                    'id' => $widget['id'],
                ]);
                $result .= $this->widget($info);

                $result .= '
    </li>';
            }

            $result .= '
</ul>';
        }

        return $result;
    }
}
