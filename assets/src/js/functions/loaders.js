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
 * Funzione per gestire il loader locale per uno specifico DIV.
 *
 * @param div Identificatore JS dell'elemento
 * @param show
 */
function localLoading(div, show) {
    let container = $(div);

    // Ricerca del loader esistente
    let loader = container.find(".panel-loading");

    // Aggiunta del loader in caso di assenza
    if (!loader.length) {
        let html = `<div class="text-center hidden local-loader">
    <div class="local-loader-content">
        <i class="fa fa-refresh fa-spin fa-3x fa-fw"></i>
        <span class="sr-only">
            ` + globals.translations.loading + `
        </span>
    </div>
</div>`;

        container.prepend(html);
        loader = container.find(".local-loader");
    }

    // Visualizzazione del loader
    if (show) {
        loader.removeClass("hidden");
        container.addClass("div-loading");
    }
    // Rimozione del loader
    else {
        loader.addClass("hidden");
        container.removeClass("div-loading");
    }
}

/**
 * Funzione per gestire la visualizzazione del loader generale del gestionale.
 * @param show
 */
function mainLoader(show) {
    let loader = $("#main_loading");

    if (show) {
        loader.show();
    } else {
        loader.fadeOut();
    }
}
