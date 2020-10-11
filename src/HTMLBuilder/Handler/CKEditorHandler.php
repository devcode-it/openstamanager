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

namespace HTMLBuilder\Handler;

/**
 *  Gestione dell'input di tipo "ckeditor".
 *
 * @since 2.4.2
 */
class CKEditorHandler implements HandlerInterface
{
    public function handle(&$values, &$extras)
    {
        $values['class'][] = 'openstamanager-input';
        $values['class'][] = 'editor-input';

        // Generazione del codice HTML
        return '
    <textarea |attr|>|value|</textarea>';
        /*
    <script src="'.base_path().'/assets/dist/js/ckeditor/ckeditor.js"></script>
    <script>
        CKEDITOR.addCss(".cke_editable img { max-width: 100% !important; height: auto !important; }");

        CKEDITOR.replace("'.prepareToField($values['id']).'", {
            toolbar: globals.ckeditorToolbar,
            language: globals.locale,
            scayt_autoStartup: true,
            scayt_sLang: globals.full_locale,
            disableNativeSpellChecker: false,
        });
    </script>*/
    }
}
