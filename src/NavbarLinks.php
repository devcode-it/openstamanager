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

use Illuminate\Database\Eloquent\Collection;
use Models\Link;
use Models\Module;
use Models\Plugin;

/**
 * Helper per il rendering delle voci navbar dalla tabella `zz_links`.
 *
 * `renderRight()` / `renderLeft()` ritornano HTML pronto per essere concatenato
 * dentro `include/top.php`. Ogni campo DB è escapato via `e()`.
 */
class NavbarLinks
{
    /** Regex di sicurezza per value di type=javascript (nome funzione globale). */
    private const JS_VALUE_REGEX = '/^[a-zA-Z_$][a-zA-Z0-9_$.]*$/';

    public static function renderRight(): string
    {
        return self::renderPosition('right');
    }

    public static function renderLeft(): string
    {
        return self::renderPosition('left');
    }

    private static function renderPosition(string $position): string
    {
        $links = self::getTopLevelLinks($position);

        $html = '';
        foreach ($links as $link) {
            if (!self::visible($link)) {
                continue;
            }
            $html .= self::renderItem($link);
        }

        return $html;
    }

    private static function getTopLevelLinks(string $position): Collection
    {
        return Link::query()
            ->where('position', $position)
            ->whereNull('parent')
            ->where('enabled', 1)
            ->orderBy('order')
            ->with(['children' => function ($q) {
                $q->where('enabled', 1)->orderBy('order');
            }])
            ->get();
    }

    private static function renderItem(Link $link): string
    {
        if ($link->hasChildren()) {
            return self::renderDropdown($link);
        }

        return self::renderSingle($link);
    }

    private static function renderSingle(Link $link): string
    {
        $iconClass = trim($link->icon.' nav-icon '.(string) $link->color);

        return '<li class="nav-item">'
            .'<a href="'.e(self::url($link)).'" class="nav-link" '
            .self::onclickAttr($link).' '
            .self::targetAttr($link).' '
            .'title="'.e($link->title).'">'
            .'<i class="'.e($iconClass).'"></i>'
            .'</a>'
            .'</li>';
    }

    private static function renderDropdown(Link $link): string
    {
        $iconClass = trim($link->icon.' nav-icon '.(string) $link->color);

        $items = '';
        foreach ($link->children as $child) {
            if (!self::visible($child)) {
                continue;
            }
            $childIcon = trim($child->icon.' '.(string) $child->color);
            $items .= '<a class="dropdown-item" href="'.e(self::url($child)).'" '
                .self::onclickAttr($child).' '
                .self::targetAttr($child).' '
                .'title="'.e($child->title).'">'
                .'<i class="'.e($childIcon).'"></i> '
                .e($child->label)
                .'</a>';
        }

        return '<li class="nav-item dropdown">'
            .'<a href="#" class="nav-link dropdown-toggle" data-toggle="dropdown" '
            .'title="'.e($link->title).'">'
            .'<i class="'.e($iconClass).'"></i>'
            .'</a>'
            .'<div class="dropdown-menu dropdown-menu-right">'
            .$items
            .'</div>'
            .'</li>';
    }

    public static function url(Link $link): string
    {
        switch ($link->type) {
            case 'link':
                return (string) $link->value;

            case 'javascript':
                return '#';

            case 'module':
                $mod = Module::where('name', $link->value)->first();
                if (!$mod) {
                    return '#';
                }

                return base_path_osm().'/controller.php?id_module='.$mod->id;

            case 'plugin':
                $plg = Plugin::where('name', $link->value)->first();
                if (!$plg) {
                    return '#';
                }

                return base_path_osm().'/plugin_editor.php?id_plugin='.$plg->id;
        }

        return '#';
    }

    public static function onclickAttr(Link $link): string
    {
        if ($link->type !== 'javascript') {
            return '';
        }

        $fn = (string) $link->value;
        if (!preg_match(self::JS_VALUE_REGEX, $fn)) {
            return '';
        }

        return 'onclick="if(typeof window[\''.$fn.'\']===\'function\'){window[\''.$fn.'\']();}return false;"';
    }

    public static function targetAttr(Link $link): string
    {
        return $link->type === 'link' ? 'target="_blank"' : '';
    }

    public static function visible(Link $link): bool
    {
        if (!$link->enabled) {
            return false;
        }

        if ($link->type === 'module') {
            $mod = Module::where('name', $link->value)->first();
            if (!$mod || !$mod->enabled) {
                return false;
            }

            return Modules::getPermission($mod->id) !== '-';
        }

        if ($link->type === 'plugin') {
            $plg = Plugin::where('name', $link->value)->first();
            if (!$plg || !$plg->enabled) {
                return false;
            }

            return $plg->permission !== '-';
        }

        if ($link->id_module) {
            return Modules::getPermission($link->id_module) !== '-';
        }

        return true;
    }

    public static function validateValue(string $type, string $value): bool
    {
        if ($type === 'javascript') {
            return (bool) preg_match(self::JS_VALUE_REGEX, $value);
        }

        return true;
    }
}
