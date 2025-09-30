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

use Illuminate\Support\Collection;
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

    /** @var Collection Elenco dei moduli disponibili */
    protected static $all_modules;

    /**
     * Restituisce tutte le informazioni di tutti i moduli installati.
     *
     * @return array
     */
    public static function getModules()
    {
        if (!isset(self::$all_modules)) {
            self::$all_modules = Module::getAll();
        }

        // Caricamento dei plugin
        if (!self::$all_modules->first()->relationLoaded('plugins')) {
            self::$all_modules->load('plugins');
        }

        return self::$all_modules;
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

        return Module::find($module);
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

            $results = $database->fetchArray('SELECT 
                `zz_group_module`.`clause`, 
                `zz_group_module`.`position`
            FROM 
                `zz_group_module` 
                LEFT JOIN `zz_group_module_lang` ON (`zz_group_module`.`id` = `zz_group_module_lang`.`id_record` AND `zz_group_module_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).') 
                LEFT JOIN `zz_users` ON `zz_users`.`idgruppo` = `zz_group_module`.`idgruppo`
            WHERE 
                `zz_users`.`id` = '.prepare($user['id']).' 
                AND 
                `zz_group_module`.`enabled` = 1
                AND
                `zz_group_module`.`idmodule` = '.prepare($module['id']));

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
        $user = Auth::user();

        if (!isset(self::$segments[$module])) {
            $database = database();

            self::$segments[$module] = $database->fetchArray('SELECT `zz_segments`.*, `zz_segments_lang`.`title` FROM `zz_segments` LEFT JOIN `zz_segments_lang` ON (`zz_segments`.`id` = `zz_segments_lang`.`id_record` AND `zz_segments_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).') INNER JOIN `zz_group_segment` ON `zz_segments`.`id` = `zz_group_segment`.`id_segment` WHERE `id_gruppo` = '.prepare($user->idgruppo).' AND `id_module` = '.prepare($module).' ORDER BY `predefined` DESC, `zz_segments`.`id` ASC');
        }

        return (array) self::$segments[$module];
    }

    /**
     * Restituisce le condizioni SQL aggiuntive del modulo.
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
     * @param bool $filter_permissions Se true, filtra solo i moduli con permessi di accesso
     *
     * @return array
     */
    public static function getHierarchy($filter_permissions = false)
    {
        if (!isset(self::$hierarchy)) {
            self::$hierarchy = Module::getHierarchy()->toArray();
        }

        if ($filter_permissions) {
            return self::filterHierarchyByPermissions(self::$hierarchy);
        }

        return self::$hierarchy;
    }

    /**
     * Restituisce la gerarchia dei moduli filtrata per i permessi di accesso.
     *
     * @return array
     */
    public static function getAvailableHierarchy()
    {
        return self::getHierarchy(true);
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
        // Utilizza la gerarchia filtrata per i permessi per ottimizzare le performance
        $menus = self::getAvailableHierarchy();

        $module = Modules::getCurrent();
        $module_name = isset($module) ? $module->getTranslation('title') : '';

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
     * @param string      $params
     *
     * @return string
     */
    public static function link($modulo, $id_record = null, $testo = null, $alternativo = true, $extra = null, $blank = true, $anchor = null, $params = null)
    {
        $testo = isset($testo) ? nl2br($testo) : tr('Visualizza scheda');
        $alternativo = is_bool($alternativo) && $alternativo ? $testo : $alternativo;

        // Aggiunta automatica dell'icona di riferimento
        if (!string_contains($testo, '<i ')) {
            $testo = $testo.' <i class="fa fa-external-link"></i>';
        }

        $module = self::get(Module::where('name', $modulo)->orWhere('id', $modulo)->first()->id);

        $extra .= !empty($blank) ? ' target="_blank"' : '';

        if (!empty($module) && in_array($module->permission, ['r', 'rw'])) {
            $link = !empty($id_record) ? 'editor.php?id_module='.$module->id.'&id_record='.$id_record : 'controller.php?id_module='.$module->id;

            return '<a href="'.base_path().'/'.$link.$params.'#'.$anchor.'" '.$extra.'>'.$testo.'</a>';
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
     * Filtra ricorsivamente la gerarchia dei moduli in base ai permessi di accesso.
     *
     * @param array      $hierarchy
     * @param array|null $availableModulesIds Cache degli ID dei moduli disponibili per ottimizzare le performance
     *
     * @return array
     */
    protected static function filterHierarchyByPermissions($hierarchy, $availableModulesIds = null)
    {
        // Ottimizzazione: carica una sola volta i moduli disponibili
        if ($availableModulesIds === null) {
            $availableModules = self::getAvailableModules();
            // Crea un array associativo per lookup veloce
            $availableModulesIds = [];
            foreach ($availableModules as $module) {
                $availableModulesIds[$module->id] = true;
            }
        }

        $filtered = [];

        foreach ($hierarchy as $element) {
            // Verifica se l'elemento ha permessi di accesso usando il cache
            $hasPermission = isset($availableModulesIds[$element['id']]) && !empty($element['enabled']);

            // Filtra ricorsivamente i figli
            $filteredChildren = [];
            if (!empty($element['all_children'])) {
                $filteredChildren = self::filterHierarchyByPermissions($element['all_children'], $availableModulesIds);
            }

            // Include l'elemento se ha permessi o se ha figli con permessi
            if ($hasPermission || !empty($filteredChildren)) {
                $element['all_children'] = $filteredChildren;
                $element['has_permission'] = $hasPermission;
                $filtered[] = $element;
            }
        }

        return $filtered;
    }

    /**
     * Restituisce l'insieme dei menu derivato da un'array strutturato ad albero.
     *
     * @param array $element
     * @param int   $actual
     * @param int   $max_depth
     * @param int   $actual_depth
     *
     * @return array Array contenente [html_string, is_active, should_show]
     */
    protected static function sidebarMenu($element, $actual = null, $max_depth = 3, $actual_depth = 0)
    {
        if ($actual_depth >= $max_depth) {
            return ['', false, false];
        }

        $link = (!empty($element['option']) && $element['option'] != 'menu') ? base_path().'/controller.php?id_module='.$element['id'] : 'javascript:;';
        $title = $element['title'];
        $target = '_self';
        $active = ($actual == $title);

        // Utilizza le informazioni sui permessi già filtrate se disponibili
        // Altrimenti fallback alla verifica tradizionale per compatibilità
        $show = $element['has_permission'] ?? self::getPermission($element['id']) != '-' && !empty($element['enabled']);

        $submenus = $element['all_children'];
        if (!empty($submenus)) {
            $temp = '';
            foreach ($submenus as $submenu) {
                $r = self::sidebarMenu($submenu, $actual, $max_depth, $actual_depth + 1);
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
            $hasSubmenus = !empty($submenus) && !empty($temp);
            $onclickAttr = (!$hasSubmenus && $link != 'javascript:;') ? 'onclick="location.href=\''.$link.'\';"' : '';

            $result .= '<li class="nav-item'.($active && $hasSubmenus ? ' menu-open' : '').'" id="'.$element['id'].'" data-id="'.$element['id'].'" '.$onclickAttr.'>';

            if ($hasSubmenus) {
                // Per i menu con sottomenu, distinguiamo tra menu con modulo e menu contenitori
                $hasModule = ($link != 'javascript:;');
                $menuTextClass = $hasModule ? 'menu-text' : '';
                $dataAttribute = $hasModule ? ' data-has-submenu="true"' : '';

                $result .= '
                <a href="'.$link.'" class="nav-link'.($active ? ' active' : '').'" data-widget="treeview" role="button" aria-expanded="'.($active ? 'true' : 'false').'"'.$dataAttribute.'>
                    <i class="'.$element['icon'].'">&nbsp;</i>
                    <p><span class="'.$menuTextClass.'">'.$title.'</span>
                        <i class="right fa fa-angle-left"></i>
                    </p>
                </a>
                <ul class="nav nav-treeview">
                    '.$temp.'
                </ul>';
            } else {
                // Per i menu senza sottomenu, comportamento normale
                $result .= '
                <a href="'.$link.'" class="nav-link'.($active ? ' active' : '').'" target="'.$target.'">
                    <i class="'.$element['icon'].'">&nbsp;</i>
                    <p>'.$title.'</p>
                </a>';
            }
            $result .= '</li>';
        }

        return [$result, $active, $show];
    }
}
