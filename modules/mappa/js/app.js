$(document).ready(function() {
    if(!$('body').hasClass('sidebar-collapse')){
        $('body').addClass('sidebar-collapse');
    }

    setTimeout(function () {
        caricaMappa();
        reload_pointers();
    }, 1000);
});

let map;
var markers = [];

$('#menu-filtri-toggle').click(function() {

    if($(this).parent().parent().parent().hasClass("open-menu")){
        $(this).parent().parent().parent().removeClass("open-menu");
        $(this).parent().parent().parent().addClass("closed-menu");

        $(this).removeClass('fa-forward');
        $(this).addClass('fa-backward');

        $('#lista-filtri').hide();
    }else{
        $(this).parent().parent().parent().removeClass("closed-menu");
        $(this).parent().parent().parent().addClass("open-menu");

        $(this).removeClass('fa-backward');
        $(this).addClass('fa-forward');

        $('#lista-filtri').show();
    }
});


function reload_pointers() {
    clearMarkers();
    var check = [];
    var svgContent = "";

    $("input[type='checkbox']").each(function() {
        if($(this).is(':checked')){
            id = $(this).attr('id');

            check.push(id);
        }
    });

    $.ajax({
        url: globals.rootdir + '/assets/dist/img/leaflet/place-marker.svg', // Percorso al file SVG
        dataType: 'text', // Imposta il tipo di dati attesi
        success: function(data) {
            svgContent = data; // Inserisci il contenuto SVG nella variabile
        },
        error: function() {
            alert('Failed to load SVG file.');
        }
    });

    $.get(globals.rootdir + '/modules/mappa/actions.php?op=get_markers&idanagrafica='+$('#idanagrafica').val()+'&check='+check, function(data){
        var dettagli = JSON.parse(data);
        dettagli.forEach(function(dettaglio) {

            if (dettaglio.lat && dettaglio.lng) {
                let svgIcon = L.divIcon({
                    html: svgContent.replace('fill="#cccccc"','fill="' + dettaglio.colore + '"'),
                    className: '',
                    shadowUrl:globals.rootdir + "/assets/dist/img/leaflet/marker-shadow.png",
                    iconSize: [45, 61],
                    iconAnchor: [12, 41],
                    popupAnchor: [1, -34],
                    shadowSize: [41, 41]
                });

                L.marker([dettaglio.lat, dettaglio.lng], {
                    icon: svgIcon
                }).bindTooltip("", 
                    {
                        permanent: false, 
                        direction: 'right'
                    }
                ).bindPopup(dettaglio.descrizione
                ).addTo(map);
            }
        });

        if (input("lat").get() && input("lng").get()) {
            const lat = parseFloat(input("lat").get());
            const lng = parseFloat(input("lng").get());

            var icon = new L.Icon({
                iconUrl: globals.rootdir + "/assets/dist/img/marker-icon.png",
                shadowUrl:globals.rootdir + "/assets/dist/img/leaflet/marker-shadow.png",
                iconSize: [25, 41],
                iconAnchor: [12, 41],
                popupAnchor: [1, -34],
                shadowSize: [41, 41]
            });

            L.marker([lat, lng], {
                icon: icon
            }).addTo(map);
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

$("input[type='checkbox']").change(function() {
    reload_pointers();
});

$('#idanagrafica').change(function() {
    reload_pointers();
});

