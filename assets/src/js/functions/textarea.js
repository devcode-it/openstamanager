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

/**
 * Funzione per l'inizializzazione dei campi textarea.
 * @param input
 */
function initTextareaInput(input) {
    autosize($(input));

    return true;
}

/**
 * Funzione per l'inizializzazione dei campi editor.
 * @param input
 */
function initEditorInput(input) {
    let $input = $(input);
    let name = input.getAttribute("id");

    loadScript(globals.rootdir + "/assets/dist/js/ckeditor/ckeditor.js")
        .then(function () {
            CKEDITOR.addCss(".cke_editable img { max-width: 100% !important; height: auto !important; }");

            CKEDITOR.replace(name, {
                toolbar: globals.ckeditorToolbar,
                language: globals.locale,
                scayt_autoStartup: true,
                scayt_sLang: globals.full_locale,
                disableNativeSpellChecker: false,
            });

            CKEDITOR.instances[name].on("key", function (event) {
                $input.trigger("keydown", event.data);
                $input.trigger("keyup", event.data);
            });

            CKEDITOR.instances[name].on("change", function (event) {
                $input.trigger("change", event);
            });
        });

    return true;
}
