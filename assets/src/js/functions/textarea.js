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

/**
 * Funzione per l'inizializzazione dei campi textarea.
 * @param input
 */
function initTextareaInput(input) {
    autosize($(input));

    return true;
}

function waitCKEditor(input) {
    setTimeout(function () {
        initEditorInput(input);
    }, 100);
}

/**
 * Funzione per l'inizializzazione dei campi editor.
 * @param input
 */
function initEditorInput(input) {
    if (window.CKEDITOR && CKEDITOR.status === "loaded") {
        $(document).ready(function () {
            initCKEditor(input);
        })
    } else {
        waitCKEditor(input);
    }

    return true;
}

function initCKEditor(input) {
    let $input = $(input);
    let name = input.getAttribute("id");

    // Controllo su istanza già esistente
    let instance = CKEDITOR.instances[name];
    if (instance) {
        return;
    }

    // Avvio di CKEditor
    CKEDITOR.replace(name, {
        toolbar: globals.ckeditorToolbar,
        language: globals.locale,
        scayt_autoStartup: true,
        scayt_sLang: globals.full_locale,
        disableNativeSpellChecker: false,
    });

    // Gestione di eventi noti
    CKEDITOR.instances[name].on("key", function (event) {
        $input.trigger("keydown", event.data);
        $input.trigger("keyup", event.data);
    });

    CKEDITOR.instances[name].on("change", function (event) {
        $input.trigger("change", event);
    });

    CKEDITOR.instances[name].on("focus", function (event) {
        $input.trigger("focus", event);
    });
}
