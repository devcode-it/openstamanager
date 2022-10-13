$(document).ready(function(){
    if(!$('body').hasClass('sidebar-collapse')){
        $('.sidebar-toggle').trigger('click');
        $('.nav').hide();
    }

    reload_pointers();
});

let map;
var markers = [];

function initialize(startLat, startLon) {

	if (startLat==undefined){
		startLat = 43.45291889;
	}
	if (startLon==undefined){
		startLon = 11.96411133;
	}

	var myLatlng = new google.maps.LatLng(startLat, startLon);

	var mapOptions = {
		zoom: 7,
		center: myLatlng,
		mapTypeId: google.maps.MapTypeId.ROADMAP,
		mapTypeControl: false,
		streetViewControl: false,
		panControl: false,
		scaleControl: false,
		rotateControl: false
	}

	map = new google.maps.Map(document.getElementById('mappa'), mapOptions);

}

$('#menu-filtri-toggle').click(function(){

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

function reload_pointers(){

    clearMarkers();

    var check = [];

    $("input[type='checkbox']").each(function(){
        if($(this).is(':checked')){
            id = $(this).attr('id');

            check.push(id);

        }
    });

    $.get(ROOTDIR+'/modules/mappa/actions.php?op=get_markers&idanagrafica='+$('#idanagrafica').val()+'&check='+check, function(data){

        var infowindow = new google.maps.InfoWindow();
        var bounds = new google.maps.LatLngBounds ();

        var dettagli = JSON.parse(data);

        var marker, i;
        var counter = 0;

        dettagli.forEach(function(dettaglio) {

            const posizione = new google.maps.LatLng(dettaglio.lat, dettaglio.lng);

            marker = new google.maps.Marker({
                position: posizione,
                map: map,
            });

            markers.push(marker);
            bounds.extend(posizione);

            google.maps.event.addListener(marker, 'click', (function(marker, i) {
                return function() {
                    infowindow.setContent(dettaglio.descrizione);
                    infowindow.open(map, marker);
                }
            })(marker, i));

            counter++;
        });

        if(counter>0){
            map.setCenter(bounds.getCenter()); // this will set the center of map to center of all markers
            map.fitBounds(bounds); // this will fit all the markers to screen
        }
    });
}

function setMapOnAll(map) {
    for (let i = 0; i < markers.length; i++) {
        markers[i].setMap(map);
    }
}

function clearMarkers() {
    setMapOnAll(null);
}

$("input[type='checkbox']").change(function(){
    reload_pointers();
});

$('#idanagrafica').change(function(){
    reload_pointers();
});

