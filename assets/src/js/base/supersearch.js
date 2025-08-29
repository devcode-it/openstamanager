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
    if (searchInput.length === 0) {
        return;
    }

    // Disabilita il widget sidebar-search di AdminLTE
    const sidebarSearchWidget = searchInput.closest('[data-widget="sidebar-search"]');
    if (sidebarSearchWidget.length > 0) {
        sidebarSearchWidget.removeAttr('data-widget');
        sidebarSearchWidget.removeClass('sidebar-search');

        // Previeni la reinizializzazione da parte di AdminLTE
        sidebarSearchWidget.attr('data-widget-disabled', 'true');
    }

    // Disabilita anche eventuali event listener di AdminLTE già attaccati
    searchInput.off('.adminlte.sidebar-search');
    searchInput.parent().off('.adminlte.sidebar-search');

    // Forza la rimozione di qualsiasi classe AdminLTE correlata alla ricerca
    setTimeout(function() {
        $('.sidebar-search-open').removeClass('sidebar-search-open');
        $('.sidebar-search-results').remove();
    }, 100);

    const searchButton = searchInput.parent().find('i');
    const searches = [];
    let searchResultsContainer = null;

    // Inizializza il container per i risultati di ricerca nella sidebar
    function initSearchResultsContainer() {
        if (!searchResultsContainer) {
            searchResultsContainer = $('<div id="search-results-container" class="mt-2" style="display: none;"></div>');
            $('.nav-sidebar').after(searchResultsContainer);
        }
        return searchResultsContainer;
    }

    // Funzione per evidenziare il termine di ricerca nel testo
    function highlightSearchTerm(text, searchTerm) {
        if (!searchTerm || !text) return text;

        const regex = new RegExp(`(${searchTerm})`, 'gi');
        return text.replace(regex, '<span class="search-highlight">$1</span>');
    }

    // Funzione per filtrare i moduli (funzionalità AdminLTE)
    function filterModules(searchTerm) {
        const navItems = $('.nav-sidebar .nav-item');

        if (!searchTerm) {
            navItems.show();
            return;
        }

        navItems.each(function() {
            const $item = $(this);
            const text = $item.find('.nav-link').text().toLowerCase();

            if (text.includes(searchTerm.toLowerCase())) {
                $item.show();
            } else {
                $item.hide();
            }
        });
    }

    // Funzione per gestire la ricerca unificata
    function performUnifiedSearch(searchTerm) {
        const container = initSearchResultsContainer();

        if (!searchTerm || searchTerm.length < 1) {
            container.hide().empty();
            $('.nav-sidebar').show().removeClass('search-hidden');
            // Ripristina tutti i moduli quando non c'è ricerca
            filterModules('');
            return;
        }

        // Se il termine è molto corto (1-2 caratteri), mostra solo filtro moduli
        if (searchTerm.length <= 2) {
            container.hide().empty();
            $('.nav-sidebar').show().removeClass('search-hidden');
            filterModules(searchTerm);
            return;
        }

        // Mostra indicatore di caricamento
        searchButton
            .removeClass('fa-search')
            .addClass('fa-spinner fa-spin');

        // Mostra loading nel container
        container.show().html(`
            <div class="search-loading">
                <i class="fa fa-spinner fa-spin"></i>
                Ricerca in corso...
            </div>
        `);
        $('.nav-sidebar').hide();

        // Registrazione ricerca
        searches.push(searchTerm);

        // Esegui ricerca AJAX per i record
        $.ajax({
            url: globals.rootdir + '/ajax_search.php',
            dataType: "JSON",
            data: {
                term: searchTerm,
            },
            success: function (data) {
                // Fix per gestione risultati null
                data = data ? data : [];

                // Rimozione ricerca in corso
                searches.pop();
                if (searches.length === 0) {
                    searchButton
                        .removeClass('fa-spinner fa-spin')
                        .addClass('fa-search');
                }

                // Mostra risultati nella sidebar
                displayUnifiedResults(searchTerm, data);
            },
            error: function (){
                searches.pop();
                if (searches.length === 0) {
                    searchButton
                        .removeClass('fa-spinner fa-spin')
                        .addClass('fa-exclamation-triangle');
                }
            }
        });
    }

    // Funzione per ottenere i moduli che corrispondono alla ricerca
    function getMatchingModules(searchTerm) {
        const matchingModules = [];
        $('.nav-sidebar .nav-item').each(function() {
            const $item = $(this);
            const $link = $item.find('.nav-link').first();
            const text = $link.text().toLowerCase();
            const href = $link.attr('href');

            if (text.includes(searchTerm.toLowerCase()) && href && href !== '#' && href !== 'javascript:;') {
                matchingModules.push({
                    title: $link.text().trim(),
                    link: href,
                    icon: $link.find('i').attr('class') || 'fa fa-folder'
                });
            }
        });
        return matchingModules;
    }

    // Funzione per visualizzare i risultati unificati nella sidebar
    function displayUnifiedResults(searchTerm, recordResults) {
        const container = initSearchResultsContainer();
        container.empty();

        // Nascondi il menu normale e mostra i risultati
        $('.nav-sidebar').hide().addClass('search-hidden');
        container.show();

        // Aggiungi header per i risultati
        container.append(`
            <div class="search-results-header">
                <h6>
                    <span>
                        <i class="fa fa-search"></i> Risultati per:
                        <span class="search-term">${searchTerm}</span>
                    </span>
                    <button class="btn btn-sm" id="clear-search" title="Cancella ricerca">
                        <i class="fa fa-times"></i>
                    </button>
                </h6>
            </div>
        `);

        // Ottieni moduli corrispondenti
        const matchingModules = getMatchingModules(searchTerm);

        // Mostra moduli corrispondenti per primi
        if (matchingModules.length > 0) {
            container.append(`
                <div class="search-category">
                    <h6 class="text-success">
                        <i class="fa fa-th-large"></i> Moduli
                        <span class="badge">${matchingModules.length}</span>
                    </h6>
                    <ul class="nav nav-pills nav-sidebar flex-column search-results-list">
                    </ul>
                </div>
            `);

            const modulesList = container.find('.search-results-list').last();

            matchingModules.forEach(module => {
                const highlightedTitle = highlightSearchTerm(module.title, searchTerm);
                modulesList.append(`
                    <li class="nav-item">
                        <a href="${module.link}" class="nav-link search-result-item">
                            <i class="nav-icon ${module.icon}"></i>
                            <div class="search-result-text">
                                <div>${highlightedTitle}</div>
                            </div>
                        </a>
                    </li>
                `);
            });
        }

        // Raggruppa i risultati dei record per categoria
        const groupedResults = {};
        recordResults.forEach(result => {
            const category = result.category || 'Altri';
            if (!groupedResults[category]) {
                groupedResults[category] = [];
            }
            groupedResults[category].push(result);
        });

        // Mostra i risultati dei record raggruppati
        Object.keys(groupedResults).forEach(category => {
            const results = groupedResults[category];

            container.append(`
                <div class="search-category">
                    <h6 class="text-primary">
                        <i class="fa fa-folder-o"></i> ${category}
                        <span class="badge">${results.length}</span>
                    </h6>
                    <ul class="nav nav-pills nav-sidebar flex-column search-results-list">
                    </ul>
                </div>
            `);

            const categoryList = container.find('.search-results-list').last();

            results.forEach(result => {
                const title = result.title;
                const labels = result.labels ? result.labels.join('').split('<br/>,').join(' • ') : '';

                // Preserva l'evidenziazione esistente e applica quella per search-highlight
                let processedLabels = labels;
                // Sostituisci la classe highlight con search-highlight per coerenza
                processedLabels = processedLabels.replace(/class=['"]highlight['"]/g, 'class="search-highlight"');

                const cleanLabels = labels.replace(/<[^>]*>/g, ''); // Rimuovi HTML per il tooltip

                // Evidenzia il termine di ricerca nel titolo
                const highlightedTitle = highlightSearchTerm(title, searchTerm);

                // Applica evidenziazione anche alle labels processate
                const highlightedLabels = highlightSearchTerm(processedLabels, searchTerm);
                const simplifiedLabels = highlightedLabels.replace(/:/g, '');

                categoryList.append(`
                    <li class="nav-item">
                        <a href="${result.link}" class="nav-link search-result-item" title="${cleanLabels}">
                            <i class="nav-icon fa fa-file-o"></i>
                            <div class="search-result-text">
                                <div>${highlightedTitle}</div>
                                ${simplifiedLabels ? `<small>${simplifiedLabels}</small>` : ''}
                            </div>
                        </a>
                    </li>
                `);
            });
        });

        // Se non ci sono risultati né di moduli né di record
        if (matchingModules.length === 0 && recordResults.length === 0) {
            container.append(`
                <div class="search-no-results">
                    <i class="fa fa-search-minus"></i>
                    <p>Nessun risultato trovato per "<strong>${searchTerm}</strong>"</p>
                </div>
            `);
        }

        // Gestisci click per cancellare la ricerca
        container.find('#clear-search').on('click', function() {
            searchInput.val('');
            container.hide().empty();
            $('.nav-sidebar').show().removeClass('search-hidden');
            filterModules(''); // Ripristina tutti i moduli
        });
    }

    // Event listener per l'input di ricerca con debounce
    let searchTimeout;
    searchInput.on('input', function() {
        const searchTerm = $(this).val().toLowerCase();

        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(function() {
            performUnifiedSearch(searchTerm);
        }, 500);
    });

    // Gestisci il tasto Esc per cancellare la ricerca
    searchInput.on('keydown', function(e) {
        if (e.key === 'Escape') {
            $(this).val('');
            const container = initSearchResultsContainer();
            container.hide().empty();
            $('.nav-sidebar').show().removeClass('search-hidden');
            filterModules(''); // Ripristina tutti i moduli
        }
    });

    // Gestisci il focus out per mantenere la ricerca attiva
    searchInput.on('blur', function() {
        // Non nascondere i risultati quando si perde il focus
        // L'utente può cliccare sui risultati
    });
});
