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

use Models\Module;
use Util\Query;

/**
 * Classe per la gestione delle informazioni relative ai moduli installati.
 *
 * @since 2.3
 */
class Modules
{
    /** @var array Elenco delle condizioni aggiuntive disponibili */
    protected static $additionals = [];
    /** @var array Elenco dei segmenti disponibili */
    protected static $segments = [];

    /** @var array Elenco gerarchico dei moduli */
    protected static $hierarchy;

    /**
     * Restituisce tutte le informazioni di tutti i moduli installati.
     *
     * @return array
     */
    public static function getModules()
    {
        $results = Module::getAll();

        // Caricamento dei plugin
        if (!$results->first()->relationLoaded('plugins')) {
            $results->load('plugins');
        }

        return $results;
    }

    /**
     * Restituisce l'elenco dei moduli con permessi di accesso accordati.
     *
     * @return array
     */
    public static function getAvailableModules()
    {
        // Individuazione dei moduli con permesso di accesso
        $modules = self::getModules();

        foreach ($modules as $key => $module) {
            if ($module->permission == '-') {
                unset($modules[$key]);
            }
        }

        return $modules;
    }

    /**
     * Restituisce le informazioni relative a un singolo modulo specificato.
     *
     * @param string|int $module
     *
     * @return Module
     */
    public static function get($module)
    {
        self::getModules();

        return Module::pool($module);
    }

    /**
     * Restituisce il modulo attualmente in utilizzo.
     *
     * @return Module
     */
    public static function getCurrent()
    {
        return Module::getCurrent();
    }

    /**
     * Imposta il modulo attualmente in utilizzo.
     *
     * @param int $id
     */
    public static function setCurrent($id)
    {
        Module::setCurrent($id);
    }

    /**
     * Restituisce i permessi accordati all'utente in relazione al modulo specificato.
     *
     * @param string|int $module
     *
     * @return string
     */
    public static function getPermission($module)
    {
        $result = self::get($module);

        return $result ? $result->permission : null;
    }

    /**
     * Restituisce i filtri aggiuntivi dell'utente in relazione al modulo specificato.
     *
     * @param int $id
     *
     * @return string
     */
    public static function getAdditionals($module, $include_segments = true)
    {
        $module = self::get($module);
        $user = Auth::user();

        if (!isset(self::$additionals[$module['id']])) {
            $database = database();

            $additionals['WHR'] = [];
            $additionals['HVN'] = [];

            $results = $database->fetchArray('SELECT * FROM `zz_group_module` WHERE `idgruppo` = (SELECT `idgruppo` FROM `zz_users` WHERE `id` = '.prepare($user['id']).') AND `enabled` = 1 AND `idmodule` = '.prepare($module['id']));
            foreach ($results as $result) {
                if (!empty($result['clause'])) {
                    $result['clause'] = Query::replacePlaceholder($result['clause']);

                    $additionals[$result['position']][] = $result['clause'];
                }
            }

            // Aggiunta dei segmenti
            if ($include_segments) {
                $segments = self::getSegments($module['id']);
                $id_segment = isset($_SESSION['module_'.$module['id']]) ? $_SESSION['module_'.$module['id']]['id_segment'] : null;
                foreach ($segments as $result) {
                    if (!empty($result['clause']) && $result['id'] == $id_segment) {
                        $result['clause'] = Query::replacePlaceholder($result['clause']);

                        $additionals[$result['position']][] = $result['clause'];
                    }
                }
            }

            self::$additionals[$module['id']] = $additionals;
        }

        return (array) self::$additionals[$module['id']];
    }

    /**
     * Restituisce i filtri aggiuntivi dell'utente in relazione al modulo specificato.
     *
     * @param int $module
     *
     * @return array
     */
    public static function getSegments($module)
    {
        if (Update::isUpdateAvailable()) {
            return [];
        }

        $module = self::get($module)['id'];

        if (!isset(self::$segments[$module])) {
            $database = database();

            self::$segments[$module] = $database->fetchArray('SELECT * FROM `zz_segments` WHERE `id_module` = '.prepare($module).' ORDER BY `predefined` DESC, `id` ASC');
        }

        return (array) self::$segments[$module];
    }

    /**
     * Restituisce le condizioni SQL aggiuntive del modulo.
     *
     * @param string $name
     *
     * @return array
     */
    public static function getAdditionalsQuery($module, $type = null, $include_segments = true)
    {
        $array = self::getAdditionals($module, $include_segments);
        if (!empty($type) && isset($array[$type])) {
            $result = (array) $array[$type];
        } else {
            $result = array_merge((array) $array['WHR'], (array) $array['HVN']);
        }

        $result = implode(' AND ', $result);

        $result = empty($result) ? $result : ' AND '.$result;

        return $result;
    }

    public static function replaceAdditionals($id_module, $query)
    {
        $result = $query;

        // Aggiunta delle condizione WHERE
        $result = str_replace('1=1', '1=1'.self::getAdditionalsQuery($id_module, 'WHR'), $result);

        // Aggiunta delle condizione HAVING
        $result = str_replace('2=2', '2=2'.self::getAdditionalsQuery($id_module, 'HVN'), $result);

        return $result;
    }

    /**
     * Restituisce tutte le informazioni dei moduli installati in una scala gerarchica fino alla profondità indicata.
     *
     * @param int $depth
     *
     * @return array
     */
    public static function getHierarchy()
    {
        if (!isset(self::$hierarchy)) {
            self::$hierarchy = Module::getHierarchy()->toArray();
        }

        return self::$hierarchy;
    }

    /**
     * Restituisce il menu principale del progetto.
     *
     * @param int $depth Profondità del menu
     *
     * @return string
     */
    public static function getMainMenu($depth = 3)
    {
        $menus = self::getHierarchy();

        $module = Modules::getCurrent();
        $module_name = isset($module) ? $module->name : '';

        $result = '';
        foreach ($menus as $menu) {
            $result .= self::sidebarMenu($menu, $module_name, $depth)[0];
        }

        return $result;
    }

    /**
     * Costruisce un link HTML per il modulo e il record indicati.
     *
     * @param string|int  $modulo
     * @param int         $id_record
     * @param string      $testo
     * @param bool|string $alternativo
     * @param string      $extra
     * @param bool        $blank
     * @param string      $anchor
     *
     * @return string
     */
    public static function link($modulo, $id_record = null, $testo = null, $alternativo = true, $extra = null, $blank = true, $anchor = null)
    {
        $testo = isset($testo) ? nl2br($testo) : tr('Visualizza scheda');
        $alternativo = is_bool($alternativo) && $alternativo ? $testo : $alternativo;

        // Aggiunta automatica dell'icona di riferimento
        if (!string_contains($testo, '<i ')) {
            $testo = $testo.' <i class="fa fa-external-link"></i>';
        }

        $module = self::get($modulo);

        $extra .= !empty($blank) ? ' target="_blank"' : '';

        if (!empty($module) && in_array($module->permission, ['r', 'rw'])) {
            $link = !empty($id_record) ? 'editor.php?id_module='.$module['id'].'&id_record='.$id_record : 'controller.php?id_module='.$module['id'];

            return '<a href="'.base_path().'/'.$link.'#'.$anchor.'" '.$extra.'>'.$testo.'</a>';
        } else {
            return $alternativo;
        }
    }

    /**
     * Individua il percorso per il file.
     *
     * @param string|int $element
     * @param string     $file
     *
     * @return string|null
     */
    public static function filepath($element, $file)
    {
        $element = self::get($element);

        return $element ? $element->filepath($file) : null;
    }

    /**
     * Restituisce l'insieme dei menu derivato da un'array strutturato ad albero.
     *
     * @param array $element
     * @param int   $actual
     * @param int   $max_depth
     * @param int   $actual_depth
     *
     * @return string
     */
    protected static function sidebarMenu($element, $actual = null, $max_depth = 3, $actual_depth = 0)
    {
        if ($actual_depth >= $max_depth) {
            return '';
        }

        $link = (!empty($element['option']) && $element['option'] != 'menu') ? base_path().'/controller.php?id_module='.$element['id'] : 'javascript:;';
        $title = $element['title'];
        $target = '_self'; // $target = ($element['new'] == 1) ? '_blank' : '_self';
        $active = ($actual == $element['name']);
        $show = (self::getPermission($element['id']) != '-' && !empty($element['enabled'])) ? true : false;

        $submenus = $element['all_children'];
        if (!empty($submenus)) {
            $temp = '';
            foreach ($submenus as $submenu) {
                $r = self::sidebarMenu($submenu, $actual, $actual_depth + 1);
                $active = $active || $r[1];
                if (!$show && $r[2]) {
                    $link = 'javascript:;';
                }
                $show = $show || $r[2];
                $temp .= $r[0];
            }
        }

        $result = '';
        if ($show) {
            $result .= '<li class="treeview';
            if ($active) {
                $result .= ' active actual';
            }
            $result .= '" id="'.$element['id'].'">
                <a href="'.$link.'" target="'.$target.'" >
                    <i class="'.$element['icon'].'"></i>
                    <span>'.$title.'</span>';
            if (!empty($submenus) && !empty($temp)) {
                $result .= '
                    <span class="pull-right-container">
                        <i class="fa fa-angle-left pull-right"></i>
                    </span>
                </a>
                <ul class="treeview-menu">
                    '.$temp.'
                </ul>';
            } else {
                $result .= '
                </a>';
            }
            $result .= '
            </li>';
        }

        return [$result, $active, $show];
    }
}
