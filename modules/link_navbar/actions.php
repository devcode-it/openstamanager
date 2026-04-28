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

include_once __DIR__.'/../../core.php';

use Models\Link;

/**
 * Normalizza il campo assets dal POST.
 * Accetta JSON array o stringa vuota. Restituisce stringa JSON o null.
 */
$normalize_assets = function ($raw) {
    $raw = trim((string) $raw);
    if ($raw === '') {
        return null;
    }
    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        return false; // marker errore parsing
    }
    // Filtra elementi non-string e vuoti
    $clean = [];
    foreach ($decoded as $entry) {
        if (is_string($entry) && trim($entry) !== '') {
            $clean[] = trim($entry);
        }
    }

    return empty($clean) ? null : json_encode($clean, JSON_UNESCAPED_SLASHES);
};

/**
 * Validazione e normalizzazione type/value.
 */
$validate_value = function ($type, $value) {
    if ($type === 'javascript') {
        return NavbarLinks::validateValue('javascript', (string) $value);
    }

    return true;
};

switch (post('op')) {
    case 'update':
        $name = trim((string) post('name'));
        $type = post('type');
        $value = trim((string) post('value'));

        if (empty($name)) {
            flash()->error(tr('Il nome interno è obbligatorio.'));
            break;
        }

        // Unique name check (escludi record corrente)
        $exists = Link::where('name', $name)->where('id', '!=', $id_record)->exists();
        if ($exists) {
            flash()->error(tr('Il nome interno _NAME_ è già in uso.', ['_NAME_' => $name]));
            break;
        }

        if (!$validate_value($type, $value)) {
            flash()->error(tr('Per type=javascript il valore deve essere un nome funzione globale valido (regex ^[a-zA-Z_$][a-zA-Z0-9_$.]*$).'));
            break;
        }

        $assets_normalized = $normalize_assets(post('assets'));
        if ($assets_normalized === false) {
            flash()->error(tr('Il campo Assets contiene JSON non valido.'));
            break;
        }

        $link->name = $name;
        $link->icon = (string) post('icon');
        $link->color = post('color') ?: null;
        $link->order = (int) post('order');
        $link->enabled = (int) post('enabled');
        $link->type = $type;
        $link->value = $value;
        $link->parent = post('parent') ?: null;
        $link->id_module = post('id_module') ?: null;
        $link->assets = $assets_normalized;
        $link->save();

        $link->setTranslation('label', (string) post('label'));
        $link->setTranslation('title', (string) post('title'));

        flash()->info(tr('Voce navbar salvata.'));
        break;

    case 'add':
        $name = trim((string) post('name'));
        $label = trim((string) post('label'));
        $type = post('type');
        $value = trim((string) post('value'));

        if (empty($name) || empty($label) || empty($type) || empty($value)) {
            flash()->error(tr('Compilare tutti i campi obbligatori.'));
            break;
        }

        if (Link::where('name', $name)->exists()) {
            flash()->error(tr('Il nome interno _NAME_ è già in uso.', ['_NAME_' => $name]));
            break;
        }

        if (!$validate_value($type, $value)) {
            flash()->error(tr('Per type=javascript il valore deve essere un nome funzione globale valido.'));
            break;
        }

        $new = new Link();
        $new->name = $name;
        $new->icon = (string) post('icon') ?: 'fa fa-link';
        $new->color = post('color') ?: null;
        $new->order = (int) post('order');
        $new->enabled = 1;
        $new->type = $type;
        $new->value = $value;
        $new->save();

        $new->setTranslation('label', $label);
        $new->setTranslation('title', $label);

        $id_record = $new->id;
        flash()->info(tr('Voce navbar creata.'));
        break;

    case 'delete':
        if (!empty($link)) {
            $link->delete();
            flash()->info(tr('Voce navbar eliminata.'));
        }
        break;
}
