<?php

/**
 * Classe per la gestione delle informazioni relative ai moduli installati.
 *
 * @since 2.3
 */
class Modules
{
    /** @var int Identificativo del modulo corrente */
    protected static $current_module;
    /** @var int Identificativo dell'elemento corrente */
    protected static $current_element;

    /** @var array Elenco dei moduli disponibili */
    protected static $modules = [];
    /** @var array Elenco delle condizioni aggiuntive disponibili */
    protected static $additionals = [];
    /** @var array Elenco delle query generiche dei moduli */
    protected static $queries = [];

    /** @var array Elenco gerarchico dei moduli */
    protected static $hierarchy = [];
    /** @var array Profondità dell'elenco gerarchico */
    protected static $depth;
    /** @var array Struttura HTML dedicata al menu principale */
    protected static $menu;

    /**
     * Restituisce tutte le informazioni di tutti i moduli installati.
     *
     * @return array
     */
    public static function getModules()
    {
        if (empty(self::$modules)) {
            $database = Database::getConnection();

            $user = Auth::user();

            $results = $database->fetchArray('SELECT * FROM `zz_modules` LEFT JOIN (SELECT `idmodule`, `permessi` FROM `zz_permissions` WHERE `idgruppo` = (SELECT `idgruppo` FROM `zz_users` WHERE `id` = '.prepare($user['id_utente']).')) AS `zz_permissions` ON `zz_modules`.`id`=`zz_permissions`.`idmodule` LEFT JOIN (SELECT `idmodule`, `clause`, `position` FROM `zz_group_module` WHERE `idgruppo` = (SELECT `idgruppo` FROM `zz_users` WHERE `id` = '.prepare($user['id_utente']).') AND `enabled` = 1) AS `zz_group_module` ON `zz_modules`.`id`=`zz_group_module`.`idmodule`');

            $modules = [];
            $additionals = [];

            foreach ($results as $result) {
                if (empty($additionals[$result['id']])) {
                    $additionals[$result['id']]['WHR'] = [];
                    $additionals[$result['id']]['HVN'] = [];
                }

                if (!empty($result['clause'])) {
                    $result['clause'] = self::replacePlaceholder($result['clause']);
                    $additionals[$result['id']][$result['position']][] = $result['clause'];
                }

                if (empty($modules[$result['id']])) {
                    if (empty($result['permessi'])) {
                        if (Auth::admin()) {
                            $result['permessi'] = 'rw';
                        } else {
                            $result['permessi'] = '-';
                        }
                    }

                    unset($result['clause']);
                    unset($result['position']);
                    unset($result['idmodule']);

                    $modules[$result['id']] = $result;
                    $modules[$result['name']] = $result['id'];
                }
            }

            self::$modules = $modules;
            self::$additionals = $additionals;
        }

        return self::$modules;
    }

    /**
     * Restituisce le informazioni relative a un singolo modulo specificato.
     *
     * @param string|int $module
     *
     * @return array
     */
    public static function get($module)
    {
        if (!is_numeric($module) && !empty(self::getModules()[$module])) {
            $module = self::getModules()[$module];
        }

        return self::getModules()[$module];
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
        return self::get($module)['permessi'];
    }

    /**
     * Restituisce i filtri aggiuntivi dell'utente in relazione al modulo specificato.
     *
     * @param int $id
     *
     * @return string
     */
    public static function getAdditionals($module)
    {
        return (array) self::$additionals[self::get($module)['id']];
    }

    /**
     * Restituisce le condizioni SQL aggiuntive del modulo.
     *
     * @param string $name
     *
     * @return array
     */
    public static function getAdditionalsQuery($module, $type = null)
    {
        $array = self::getAdditionals($module);
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
     * Restituisce l'identificativo del modulo attualmente in utilizzo.
     *
     * @return int
     */
    public static function getCurrentModule()
    {
        if (empty(self::$current_module)) {
            self::$current_module = filter('id_module');
        }

        return self::get(self::$current_module);
    }

    /**
     * Restituisce l'identificativo dell'elemento attualmente in utilizzo.
     *
     * @return int
     */
    public static function getCurrentElement()
    {
        if (empty(self::$current_element)) {
            self::$current_element = filter('id_record');
        }

        return self::$current_element;
    }

    /**
     * Restituisce un'insieme di array comprendenti le informazioni per la costruzione della query del modulo indicato.
     *
     * @param int $id
     *
     * @return array
     */
    public static function getQuery($id)
    {
        if (empty(self::$queries[$id])) {
            $database = Database::getConnection();
            $module = self::get($id);

            $fields = [];
            $summable = [];
            $search_inside = [];
            $search = [];
            $slow = [];
            $order_by = [];
            $select = '*';

            $options = !empty($module['options2']) ? $module['options2'] : $module['options'];
            if (str_contains($options, '|select|')) {
                $query = $options;

                $user = Auth::user();

                $datas = $database->fetchArray('SELECT * FROM `zz_views` WHERE `id_module`='.prepare($id).' AND `id` IN (SELECT `id_vista` FROM `zz_group_view` WHERE `id_gruppo`=(SELECT `idgruppo` FROM `zz_users` WHERE `id`='.prepare($user['id_utente']).')) ORDER BY `order` ASC');

                if (!empty($datas)) {
                    $select = '';

                    foreach ($datas as $data) {
                        $select .= $data['query'].(!empty($data['name']) ? " AS '".$data['name']."', " : '');

                        if ($data['enabled']) {
                            $data['name'] = trim($data['name']);
                            $data['search_inside'] = trim($data['search_inside']);
                            $data['order_by'] = trim($data['order_by']);

                            $fields[] = trim($data['name']);

                            $search_inside[] = !empty($data['search_inside']) ? $data['search_inside'] : $data['name'];
                            $order_by[] = !empty($data['order_by']) ? $data['order_by'] : $data['name'];
                            $search[] = $data['search'];
                            $slow[] = $data['slow'];
                            $format[] = $data['format'];

                            if ($data['summable']) {
                                $summable[] = 'SUM(`'.trim($data['name']."`) AS 'sum_".(count($fields) - 1)."'");
                            }
                        }
                    }

                    $select = substr($select, 0, strlen($select) - 2);
                }
            } else {
                $options = self::readOldQuery($options);

                $query = $options['query'];
                $fields = explode(',', $options['fields']);
                foreach ($fields as $key => $value) {
                    $fields[$key] = trim($value);
                    $search[] = 1;
                    $slow[] = 0;
                    $format[] = 0;
                }

                $search_inside = $fields;
                $order_by = $fields;
            }

            $result = [];
            $result['query'] = $query;
            $result['select'] = $select;
            $result['fields'] = $fields;
            $result['search_inside'] = $search_inside;
            $result['order_by'] = $order_by;
            $result['search'] = $search;
            $result['slow'] = $slow;
            $result['format'] = $format;
            $result['summable'] = $summable;

            self::$queries[$id] = $result;
        }

        return self::$queries[$id];
    }

    public static function readOldQuery($options)
    {
        $options = str_replace(["\r", "\n", "\t"], ' ', $options);
        $options = json_decode($options, true);

        return $options['main_query'][0];
    }

    public static function replacePlaceholder($query, $custom = null)
    {
        $user = Auth::user();

        $custom = empty($custom) ? $user['idanagrafica'] : $custom;
        $result = str_replace(['|idagente|', '|idtecnico|', '|idanagrafica|'], prepare($custom), $query);

        return $result;
    }

    /**
     * Restituisce tutte le informazioni dei moduli installati in una scala gerarchica fino alla profondità indicata.
     *
     *
     * @param int $depth
     *
     * @return array
     */
    public static function getHierarchy($depth = 3)
    {
        if (empty(self::$hierarchy) || self::$depth != $depth) {
            $database = Database::getConnection();

            $depth = ($depth < 2) ? 2 : $depth;

            $fields = [];
            for ($i = 0; $i < $depth; ++$i) {
                $fields[] = '`t'.$i."`.`id` AS 't".$i.".id'";
            }

            $query = 'SELECT '.implode(', ', $fields).' FROM `zz_modules` AS `t0`';

            for ($i = 1; $i < $depth; ++$i) {
                $query .= ' LEFT JOIN `zz_modules` AS `t'.$i.'` ON `t'.$i.'`.`parent` = `t'.($i - 1).'`.`id`';
            }

            $query .= ' WHERE `t0`.`parent` IS NULL ORDER BY ';

            for ($i = 0; $i < $depth; ++$i) {
                $query .= '`t'.$i.'`.`order` ASC';

                if ($i != $depth - 1) {
                    $query .= ', ';
                }
            }

            $modules = $database->fetchArray($query);

            $hierarchy = [];
            foreach ($modules as $module) {
                $hierarchy = self::buildArray($module, $hierarchy);
            }

            self::$depth = $depth;
            self::$hierarchy = $hierarchy;
        }

        return self::$hierarchy;
    }

    /**
     * Restituisce l'elaborazione dell'array secondo una struttura ad albero (molteplici root).
     *
     * @param int   $id
     * @param array $data
     * @param int   $actual
     *
     * @return array
     */
    protected static function buildArray($module, $data = [], $actual = 0)
    {
        if (!empty($module['t'.$actual.'.id'])) {
            $pos = array_search($module['t'.$actual.'.id'], array_column($data, 'id'));
            if ($pos === false && !empty($module['t'.$actual.'.id'])) {
                $array = self::get($module['t'.$actual.'.id']);
                $array['childrens'] = [];

                $data[] = $array;
                $pos = count($data) - 1;
            }

            if (!empty($module['t'.($actual + 1).'.id'])) {
                $data[$pos]['childrens'] = self::buildArray($module, $data[$pos]['childrens'], $actual + 1);
            }
        }

        return $data;
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
        if (empty(self::$menu) || self::$depth != $depth) {
            $menus = self::getHierarchy($depth);

            $module_name = self::getCurrentModule()['name'];

            $result = '';
            foreach ($menus as $menu) {
                $result .= self::sidebarMenu($menu, isset($module_name) ? $module_name : '')[0];
            }

            self::$menu = $result;
        }

        return self::$menu;
    }

    /**
     * Restituisce l'insieme dei menu derivato da un'array strutturato ad albero.
     *
     * @param array $element
     * @param int   $actual
     *
     * @return string
     */
    protected static function sidebarMenu($element, $actual = null)
    {
        global $rootdir;

        $options = ($element['options2'] != '') ? $element['options2'] : $element['options'];
        $link = ($options != '' && $options != 'menu') ? $rootdir.'/controller.php?id_module='.$element['id'] : 'javascript:;';
        $title = $element['title'];
        $target = ($element['new'] == 1) ? '_blank' : '_self';
        $active = ($actual == $element['name']);
        $show = (self::getPermission($element['id']) != '-' && !empty($element['enabled'])) ? true : false;

        $submenus = $element['childrens'];
        if (!empty($submenus)) {
            $temp = '';
            foreach ($submenus as $submenu) {
                $r = self::sidebarMenu($submenu, $actual);
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

    /**
     * Undocumented function.
     *
     * @param string|int $modulo
     * @param int        $id_record
     * @param string     $testo
     * @param string     $alternativo
     * @param string     $extra
     *
     * @return string
     */
    public static function link($modulo, $id_record = null, $testo = null, $alternativo = true, $extra = null, $blank = true)
    {
        $testo = isset($testo) ? nl2br($testo) : tr('Visualizza scheda');
        $alternativo = is_bool($alternativo) && $alternativo ? $testo : $alternativo;

        // Aggiunta automatica dell'icona di riferimento
        if (!str_contains($testo, '<i ')) {
            $testo = $testo.' <i class="fa fa-external-link"></i>';
        }

        $module = self::get($modulo);

        $extra .= !empty($blank) ? ' target="_blank"' : '';

        if (!empty($module) && in_array($module['permessi'], ['r', 'rw'])) {
            $link = !empty($id_record) ? 'editor.php?id_module='.$module['id'].'&id_record='.$id_record : 'controller.php?id_module='.$module['id'];

            return '<a href="'.ROOTDIR.'/'.$link.'" '.$extra.'>'.$testo.'</a>';
        } else {
            return $alternativo;
        }
    }
}
