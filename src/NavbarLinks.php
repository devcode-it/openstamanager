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
 * `render()` ritorna HTML pronto per essere concatenato dentro `include/top.php`,
 * inserito tra le voci core (info) e il logout. Ogni campo DB è escapato via `e()`.
 */
class NavbarLinks
{
    /** Regex di sicurezza per value di type=javascript (nome funzione globale). */
    private const JS_VALUE_REGEX = '/^[a-zA-Z_$][a-zA-Z0-9_$.]*$/';

    public static function render(): string
    {
        $links = self::getTopLevelLinks();

        $html = '';
        foreach ($links as $link) {
            if (!self::visible($link)) {
                continue;
            }
            $html .= self::renderItem($link);
        }

        return $html;
    }

    private static function getTopLevelLinks(): Collection
    {
        return Link::query()
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

    /**
     * Risolve un singolo entry di `zz_links.assets` in path relativo da OSM root.
     *
     * Regole:
     * - entry contiene `/` → trattato come path completo da OSM root.
     * - entry senza `/` → shorthand `modules/{link.id_module.directory}/assets/dist/js/{entry}`.
     *   Se `id_module` non valorizzato o modulo non trovato → null (skip).
     */
    public static function resolveAssetPath(string $entry, Link $link): ?string
    {
        $entry = trim($entry);
        if ($entry === '') {
            return null;
        }

        if (str_contains($entry, '/')) {
            return '/'.ltrim($entry, '/');
        }

        if (!$link->id_module) {
            return null;
        }

        $mod = Module::find($link->id_module);
        if (!$mod || empty($mod->directory)) {
            return null;
        }

        return '/modules/'.$mod->directory.'/assets/dist/js/'.$entry;
    }

    /**
     * Raccoglie tutti gli asset JS dei link enabled, dedup, e ritorna array path relativi.
     */
    public static function collectEnabledAssets(): array
    {
        $out = [];
        $links = Link::where('enabled', 1)
            ->whereNotNull('assets')
            ->get(['id', 'id_module', 'assets']);

        foreach ($links as $lk) {
            $files = $lk->assets ?: [];
            if (!is_array($files)) {
                continue;
            }
            foreach ($files as $entry) {
                if (!is_string($entry)) {
                    continue;
                }
                $resolved = self::resolveAssetPath($entry, $lk);
                if ($resolved && !in_array($resolved, $out, true)) {
                    $out[] = $resolved;
                }
            }
        }

        return $out;
    }
}
