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

$(document).ready(function () {
    const searchInput = $('#supersearch');
    const searchButton = searchInput.parent().find('i');
    const searches = [];

    autocomplete({
        minLength: 1,
        input: searchInput[0],
        emptyMsg: globals.translations.noResults,
        debounceWaitMs: 500,
        fetch: function(text, update) {
            text = text.toLowerCase();

            // Registrazione ricerca
            searches.push(text);
            searchButton
                .removeClass('fa-search')
                .addClass('fa-spinner fa-spin');

            $.ajax({
                url: globals.rootdir + '/ajax_search.php',
                dataType: "JSON",
                data: {
                    term: text,
                },
                success: function (data) {
                    // Fix per gestione risultati null
                    data = data ? data : [];

                    // Trasformazione risultati in formato leggibile
                    const results = data.map(function (result) {
                        return {
                            label: result.label ? result.label : '<h4>' + result.title + '</h4>' + result.labels
                                .join('').split('<br/>,').join('<br/>'),
                            group: result.category,
                            link: result.link,
                            value: result.title
                        }
                    });

                    // Rimozione ricerca in corso
                    searches.pop();
                    if (searches.length === 0) {
                        searchButton
                            .removeClass('fa-spinner fa-spin')
                            .addClass('fa-search');
                    }

                    update(results);
                },
                error: function (){
                    searchButton
                        .removeClass('fa-spinner fa-spin')
                        .addClass('fa-exclamation-triangle');
                }
            });
        },
        preventSubmit: true,
        disableAutoSelect: true,
        onSelect: function(item) {
            window.location.href = item.link;
        },
        customize: function(input, inputRect, container, maxHeight) {
            container.style.width = '600px';
        },
        render: function(item, currentValue){
            const itemElement = document.createElement("div");
            itemElement.innerHTML = item.label;
            // <a href='" + item.link + "' title='Clicca per aprire'><b>" + item.value + "</b><br/>" + item.label + "</a>
            return itemElement;
        }
    });
});
