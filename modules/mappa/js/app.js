$(document).ready(function() {
    // Initialize the map after a short delay
    setTimeout(function () {
        caricaMappa();
        reload_pointers();

        // Resize map when window is resized
        $(window).on('resize', function() {
            if (map) {
                map.invalidateSize();
            }
        });

        // Inizializza lo stato del pulsante toggle
        $('#toggle-filters-btn i').addClass('text-primary');
        $('#toggle-filters-btn').removeClass('filters-hidden');

        // Assicurati che i pulsanti di toggle funzionino correttamente
        $('#toggle-filters-btn, #menu-filtri-toggle').off('click').on('click', function() {
            var isFilterButton = $(this).attr('id') === 'toggle-filters-btn';
            toggleFiltersPanel(isFilterButton ? undefined : false);
        });

        // Gestione pulsante fullscreen
        $('#fullscreen-btn').off('click').on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            toggleFullscreen();
            return false;
        });

        // Gestisci il ridimensionamento della finestra in modalità fullscreen
        $(window).on('resize', function() {
            if ($('#map-container .card').hasClass('module-fullscreen')) {
                var moduleHeight = $('.content').height() - 20;
                $('#mappa').css('height', moduleHeight + 'px');
                $('#map-container .card-body').css('height', moduleHeight + 'px');

                // Assicurati che il pannello dei filtri non venga influenzato dalla modalità fullscreen
                $('#menu-filtri').removeClass('module-fullscreen');
                $('#filters-container').removeClass('module-fullscreen');

                // Rimuovi l'altezza fissa dal pannello dei filtri
                $('#lista-filtri').css('height', '');
                $('#lista-filtri').attr('style', function(_, style) {
                    return style && style.replace(/height[^;]+;?/g, '');
                });
            }
        });
    }, 1000);

    // Initialize tooltips
    $('[data-toggle="tooltip"]').tooltip();
});

let map;
var markers = [];

// Funzione per gestire la visibilità del pannello filtri
function toggleFiltersPanel(show) {
    var $filtersContainer = $('#filters-container');

    if (show === undefined) {
        // Toggle se non è specificato
        show = $filtersContainer.is(':hidden');
    }

    if (show) {
        // Assicurati che il pannello sia completamente visibile
        $filtersContainer.css({
            'opacity': 0,
            'right': '-300px',
            'display': 'block'
        }).animate({
            'opacity': 1,
            'right': '10px'
        }, 300);
        $('#toggle-filters-btn i').addClass('text-primary');
        $('#toggle-filters-btn').removeClass('filters-hidden');
    } else {
        // Nascondi pannello filtri con animazione
        $filtersContainer.animate({
            'opacity': 0,
            'right': '-300px'
        }, 300, function() {
            $(this).hide();
        });
        $('#toggle-filters-btn i').removeClass('text-primary');
        $('#toggle-filters-btn').addClass('filters-hidden');
    }

    // Ridimensiona la mappa per adattarla al nuovo contenitore
    setTimeout(function() {
        if (map) {
            map.invalidateSize();
        }
    }, 300);
}

// I gestori di eventi per i pulsanti di toggle sono definiti nel document.ready

// Funzione per gestire la modalità fullscreen
function toggleFullscreen() {
    var $mapContainer = $('#mappa');
    var $mapCard = $('#map-container .card');
    var $mapCardBody = $('#map-container .card-body');
    var $icon = $('#fullscreen-btn').find('i');
    var isFullscreen = $mapCard.hasClass('module-fullscreen');
    var $body = $('body');
    var wasSidebarCollapsed = $body.hasClass('sidebar-collapse');
    var originalHeight;

    if (!isFullscreen) {
        // Salva l'altezza originale per ripristinarla dopo
        originalHeight = $mapContainer.height();
        $mapCard.attr('data-original-height', originalHeight);

        // Salva lo stato del menu laterale
        $mapCard.attr('data-sidebar-state', wasSidebarCollapsed ? 'collapsed' : 'expanded');

        // Enter module fullscreen
        $mapCard.addClass('module-fullscreen');
        $icon.removeClass('fa-expand').addClass('fa-compress');

        // Comprimi il menu laterale se non è già compresso
        if (!wasSidebarCollapsed) {
            $body.addClass('sidebar-collapse');
        }

        // Espandi la mappa per riempire lo spazio disponibile
        var windowHeight = $(window).height();
        var headerHeight = $('.main-header').outerHeight() || 0;
        var moduleHeaderHeight = $('.content-header').outerHeight() || 0;
        var cardHeaderHeight = $mapCard.find('.card-header').outerHeight() || 0;
        var footerHeight = $('.main-footer').outerHeight() || 0;
        var padding = 40; // Padding aggiuntivo

        var availableHeight = windowHeight - headerHeight - moduleHeaderHeight - cardHeaderHeight - footerHeight - padding;

        $mapContainer.css('height', availableHeight + 'px');
        $mapCardBody.css('height', availableHeight + 'px');

    } else {
        // Exit module fullscreen
        $mapCard.removeClass('module-fullscreen');
        $icon.removeClass('fa-compress').addClass('fa-expand');

        // Ripristina lo stato del menu laterale
        var sidebarState = $mapCard.attr('data-sidebar-state');
        if (sidebarState === 'expanded') {
            $body.removeClass('sidebar-collapse');
        }

        // Ripristina l'altezza originale
        var originalHeight = $mapCard.attr('data-original-height') || '700px';
        $mapContainer.css('height', originalHeight);
        $mapCardBody.css('height', '');
    }

    // Assicurati che il pannello dei filtri non venga modificato
    $('#menu-filtri').removeClass('module-fullscreen');
    $('#filters-container').removeClass('module-fullscreen');

    // Rimuovi eventuali stili inline applicati al pannello dei filtri
    $('#lista-filtri').css('height', '');
    $('#lista-filtri').attr('style', function(_, style) {
        return style && style.replace(/height[^;]+;?/g, '');
    });

    // Resize map to fit new container
    setTimeout(function() {
        if (map) {
            map.invalidateSize();
        }
    }, 400);
}


function reload_pointers() {
    // Show loading indicator
    $('#mappa').append('<div class="map-loading"><i class="fa fa-spinner fa-spin"></i> Caricamento...</div>');

    clearMarkers();
    var check = [];
    var svgContent = "";

    // Collect checked states
    $("input[type='checkbox']").each(function() {
        if($(this).is(':checked')){
            id = $(this).attr('id');
            check.push(id);
        }
    });

    // Load SVG marker template
    $.ajax({
        url: ROOTDIR + '/assets/dist/img/leaflet/place-marker.svg',
        dataType: 'text',
        success: function(data) {
            svgContent = data;

            // Get markers data
            $.get(ROOTDIR + '/modules/mappa/actions.php?op=get_markers&idanagrafica='+$('#idanagrafica').val()+'&check='+check, function(data){
                var dettagli = JSON.parse(data);
                var markerArray = [];

                // Process each marker
                dettagli.forEach(function(dettaglio) {
                    if (dettaglio.lat && dettaglio.lng) {
                        // Create custom marker icon with the activity color
                        let svgIcon = L.divIcon({
                            html: svgContent.replace('fill="#cccccc"','fill="' + dettaglio.colore + '"'),
                            className: '',
                            shadowUrl: ROOTDIR + "/assets/dist/img/leaflet/marker-shadow.png",
                            iconSize: [40, 55], // Slightly smaller for better appearance
                            iconAnchor: [12, 41],
                            popupAnchor: [1, -34],
                            shadowSize: [41, 41]
                        });

                        // Create marker and add to array
                        var marker = L.marker([dettaglio.lat, dettaglio.lng], {
                            icon: svgIcon,
                            riseOnHover: true, // Rise marker on hover for better UX
                            title: dettaglio.title || "" // Imposta il titolo per il tooltip nativo
                        });

                        // Aggiungi il tooltip personalizzato
                        if (dettaglio.title) {
                            marker.bindTooltip(dettaglio.title, {
                                permanent: false,
                                direction: 'top',
                                offset: [0, -38],
                                opacity: 0.9,
                                className: 'custom-tooltip'
                            });

                            // Forza la visualizzazione del tooltip al passaggio del mouse
                            marker.on('mouseover', function() {
                                this.openTooltip();
                            });

                            marker.on('mouseout', function() {
                                this.closeTooltip();
                            });
                        }

                        // Aggiungi il popup
                        marker.bindPopup(dettaglio.descrizione, {
                            maxWidth: 300,
                            minWidth: 200,
                            className: 'custom-popup'
                        });

                        markerArray.push(marker);
                        marker.addTo(map);
                    }
                });

                // Add search location marker if coordinates are set
                if (input("lat").get() && input("lng").get()) {
                    const lat = parseFloat(input("lat").get());
                    const lng = parseFloat(input("lng").get());

                    var searchIcon = new L.Icon({
                        iconUrl: ROOTDIR + "/assets/dist/img/marker-icon.png",
                        shadowUrl: ROOTDIR + "/assets/dist/img/leaflet/marker-shadow.png",
                        iconSize: [25, 41],
                        iconAnchor: [12, 41],
                        popupAnchor: [1, -34],
                        shadowSize: [41, 41]
                    });

                    var searchMarker = L.marker([lat, lng], {
                        icon: searchIcon,
                        zIndexOffset: 1000 // Make sure search marker is on top
                    }).bindPopup("<strong>La tua posizione</strong>");

                    markerArray.push(searchMarker);
                    searchMarker.addTo(map);
                }

                // Fit map to show all markers if we have any
                if (markerArray.length > 0) {
                    var group = L.featureGroup(markerArray);
                    map.fitBounds(group.getBounds(), {
                        padding: [50, 50] // Add some padding around the bounds
                    });
                }

                // Remove loading indicator
                $('.map-loading').remove();
            });
        },
        error: function() {
            alert('Failed to load SVG marker file.');
            $('.map-loading').remove();
        }
    });
}

function clearMarkers() {
    map.eachLayer(function (layer) {
        if(layer instanceof L.Marker) {
            map.removeLayer(layer);
        }
    });
}

// Gestione degli switch per gli stati
$("input[type='checkbox']").change(function() {
    // Aggiungi una piccola animazione quando cambia lo stato
    var $label = $(this).closest('.stato-item').find('.stato-label');
    $label.css('font-weight', 'bold');
    setTimeout(function() {
        $label.css('font-weight', 'normal');
    }, 300);

    reload_pointers();
});

// Gestione del cambio anagrafica
$('#idanagrafica').change(function() {
    // Mostra un messaggio di caricamento
    $('#mappa').append('<div class="map-loading"><i class="fa fa-spinner fa-spin"></i> Caricamento...</div>');

    setTimeout(function() {
        reload_pointers();
    }, 100);
});

function calcolaPercorso(indirizzo_partenza, indirizzo_destinazione) {
    // Funzione di fallback per rilevare dispositivi mobili
    function isMobileDevice() {
        // Controlla se isMobile è disponibile
        if (typeof isMobile !== 'undefined' && isMobile.any) {
            return true;
        }

        // Fallback usando globals.is_mobile se disponibile
        if (typeof globals !== 'undefined' && globals.is_mobile) {
            return true;
        }

        // Fallback usando user agent
        return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
    }

    if (isMobileDevice()) {
        window.open("geo:" + indirizzo_destinazione + "?z=16&q=" + indirizzo_destinazione);
    } else {
        window.open("https://www.openstreetmap.org/directions?engine=fossgis_osrm_car&route=" + indirizzo_partenza + ";" + indirizzo_destinazione);
    }
}

