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

use Carbon\Carbon;
include_once __DIR__.'/../../core.php';

// Individuazione dati selezionabili
// Stati interventi
$stati_intervento = $dbo->fetchArray('SELECT idstatointervento AS id, descrizione, colore FROM in_statiintervento WHERE deleted_at IS NULL ORDER BY descrizione ASC');

// Tipi intervento
$tipi_intervento = $dbo->fetchArray('SELECT idtipointervento AS id, descrizione FROM in_tipiintervento ORDER BY descrizione ASC');

// Tecnici disponibili
$tecnici_disponibili = $dbo->fetchArray("SELECT an_anagrafiche.idanagrafica AS id, ragione_sociale, colore FROM an_anagrafiche
    INNER JOIN
    an_tipianagrafiche_anagrafiche ON an_anagrafiche.idanagrafica=an_tipianagrafiche_anagrafiche.idanagrafica
    INNER JOIN an_tipianagrafiche ON an_tipianagrafiche_anagrafiche.idtipoanagrafica=an_tipianagrafiche.idtipoanagrafica
    LEFT OUTER JOIN in_interventi_tecnici ON in_interventi_tecnici.idtecnico = an_anagrafiche.idanagrafica
    INNER JOIN in_interventi ON in_interventi_tecnici.idintervento=in_interventi.id
WHERE an_anagrafiche.deleted_at IS NULL AND an_tipianagrafiche.descrizione='Tecnico' ".Modules::getAdditionalsQuery('Interventi', null, false).'
GROUP BY an_anagrafiche.idanagrafica
ORDER BY ragione_sociale ASC');

// Zone
$zone = $dbo->fetchArray('(SELECT 0 AS ordine, \'0\' AS id, \'Nessuna zona\' AS descrizione) UNION (SELECT 1 AS ordine, id, descrizione FROM an_zone) ORDER BY ordine, descrizione ASC');

// Prima selezione globale per tutti i filtri
if (!isset($_SESSION['dashboard']['idtecnici'])) {
    $_SESSION['dashboard']['idtecnici'] = ["'-1'"];

    foreach ($tecnici_disponibili as $tecnico) {
        if(($user['gruppo']=='Tecnici' && $user['idanagrafica']==$tecnico['id']) || $user['gruppo']!='Tecnici'){
            $_SESSION['dashboard']['idtecnici'][] = "'".$tecnico['id']."'";
        }
    }
}

if (!isset($_SESSION['dashboard']['idstatiintervento'])) {
    $_SESSION['dashboard']['idstatiintervento'] = ["'-1'"];

    foreach ($stati_intervento as $stato) {
        $_SESSION['dashboard']['idstatiintervento'][] = "'".$stato['id']."'";
    }
}

if (!isset($_SESSION['dashboard']['idtipiintervento'])) {
    $_SESSION['dashboard']['idtipiintervento'] = ["'-1'"];

    foreach ($tipi_intervento as $tipo) {
        $_SESSION['dashboard']['idtipiintervento'][] = "'".$tipo['id']."'";
    }
}

if (!isset($_SESSION['dashboard']['idzone'])) {
    $_SESSION['dashboard']['idzone'] = ["'-1'"];

    foreach ($zone as $zona) {
        $_SESSION['dashboard']['idzone'][] = "'".$zona['id']."'";
    }
}

echo '
<!-- Filtri -->
<div class="row">
	<!-- STATI INTERVENTO -->
	<div class="dropdown col-md-3" id="dashboard_stati">
		<button type="button" class="btn btn-block counter_object" data-toggle="dropdown">
            <i class="fa fa-filter"></i> '.tr('Stati attività').'
            (<span class="selected_counter"></span>/<span class="total_counter"></span>) <i class="caret"></i>
        </button>
		<ul class="dropdown-menu" role="menu">';

// Stati intervento
$stati_sessione = session_get('dashboard.idstatiintervento', []);
foreach ($stati_intervento as $stato) {
    $attr = '';
    if (in_array("'".$stato['id']."'", $stati_sessione)) {
        $attr = 'checked="checked"';
    }

    echo '
            <li>
                <input type="checkbox" id="stato_'.$stato['id'].'" class="dashboard_stato" value="'.$stato['id'].'" '.$attr.'>
                <label for="stato_'.$stato['id'].'">
                    '.$stato['descrizione'].'<span style="position:relative;right:-7px;bottom:-3px;width:15px;height:15px;display:inline-block;border-radius:50%;background-color:'.$stato['colore'].';"></span>
                </label>
            </li>';
}

echo '
			<div class="btn-group float-right">
				<button type="button" class="btn btn-primary btn-sm seleziona_tutto">
                    '.tr('Tutti').'
                </button>
				<button type="button" class="btn btn-danger btn-sm deseleziona_tutto">
                    <i class="fa fa-times"></i>
                </button>
			</div>
		</ul>
	</div>

	<!-- TIPI INTERVENTO -->
	<div class="dropdown col-md-3" id="dashboard_tipi">
		<button type="button" class="btn btn-block counter_object" data-toggle="dropdown">
            <i class="fa fa-filter"></i> '.tr('Tipi attività').'
            (<span class="selected_counter"></span>/<span class="total_counter"></span>) <i class="caret"></i>
        </button>
		<ul class="dropdown-menu" role="menu">';

// Tipi intervento
$tipi_sessione = session_get('dashboard.idtipiintervento', []);
foreach ($tipi_intervento as $tipo) {
    $attr = '';
    if (in_array("'".$tipo['id']."'", $tipi_sessione)) {
        $attr = 'checked="checked"';
    }

    echo '
            <li>
                <input type="checkbox" id="tipo_'.$tipo['id'].'" class="dashboard_tipo" value="'.$tipo['id'].'" '.$attr.'>
                <label for="tipo_'.$tipo['id'].'">
                    '.$tipo['descrizione'].'
                </label>
            </li>';
}

echo '
			<div class="btn-group float-right">
				<button type="button" class="btn btn-primary btn-sm seleziona_tutto">
                    '.tr('Tutti').'
                </button>
				<button type="button" class="btn btn-danger btn-sm deseleziona_tutto">
                    <i class="fa fa-times"></i>
                </button>
			</div>
		</ul>
	</div>

	<!-- TECNICI -->
	<div class="dropdown col-md-3" id="dashboard_tecnici">
		<button type="button" class="btn btn-block counter_object" data-toggle="dropdown">
            <i class="fa fa-filter"></i> '.tr('Tecnici').'
            (<span class="selected_counter"></span>/<span class="total_counter"></span>) <i class="caret"></i>
        </button>
		<ul class="dropdown-menu" role="menu">';

$tecnici_sessione = session_get('dashboard.idtecnici', []);
foreach ($tecnici_disponibili as $tecnico) {
    $attr = '';
    if (in_array("'".$tecnico['id']."'", $tecnici_sessione)) {
        $attr = 'checked="checked"';
    }

    echo '
            <li>
                <input type="checkbox" id="tecnico_'.$tecnico['id'].'" class="dashboard_tecnico" value="'.$tecnico['id'].'" '.$attr.'>
                <label for="tecnico_'.$tecnico['id'].'">
                    '.$tecnico['ragione_sociale'].'<span style="position:relative;right:-7px;bottom:-3px;width:15px;height:15px;display:inline-block;border-radius:50%;background-color:'.$tecnico['colore'].';"></span>
                </label>
            </li>';
}

echo '
			<div class="btn-group float-right">
				<button type="button" class="btn btn-primary btn-sm seleziona_tutto">
                    '.tr('Tutti').'
                </button>
				<button type="button" class="btn btn-danger btn-sm deseleziona_tutto">
                    <i class="fa fa-times"></i>
                </button>
			</div>
		</ul>
	</div>

	<!-- ZONE -->
	<div class="dropdown col-md-3" id="dashboard_zone">
		<button type="button" class="btn btn-block counter_object" data-toggle="dropdown">
            <i class="fa fa-filter"></i> '.tr('Zone').'
            (<span class="selected_counter"></span>/<span class="total_counter"></span>) <i class="caret"></i>
        </button>
		<ul class="dropdown-menu" role="menu">';

// Zone
$zone_sessione = session_get('dashboard.idzone', []);
foreach ($zone as $zona) {
    $attr = '';
    if (in_array("'".$zona['id']."'", $zone_sessione)) {
        $attr = 'checked="checked"';
    }

    echo '
            <li>
                <input type="checkbox" id="zona_'.$zona['id'].'" class="dashboard_zona" value="'.$zona['id'].'" '.$attr.'>
                <label for="zona_'.$zona['id'].'">
                   '.$zona['descrizione'].'
                </label>
            </li>';
}

echo '
			<div class="btn-group float-right">
				<button type="button" class="btn btn-primary btn-sm seleziona_tutto">
                    '.tr('Tutti').'
                </button>
				<button type="button" class="btn btn-danger btn-sm deseleziona_tutto">
                    <i class="fa fa-times"></i>
                </button>
			</div>
		</ul>
	</div>
</div>
<br>';

$solo_promemoria_assegnati = setting('Mostra promemoria attività ai soli Tecnici assegnati');
$id_tecnico = null;
if ($user['gruppo'] == 'Tecnici' && !empty($user['idanagrafica'])) {
    $id_tecnico = $user['idanagrafica'];
}

$query_da_programmare = 'SELECT data_richiesta AS data FROM co_promemoria
    INNER JOIN co_contratti ON co_promemoria.idcontratto = co_contratti.id
    INNER JOIN an_anagrafiche ON co_contratti.idanagrafica = an_anagrafiche.idanagrafica
WHERE
    idcontratto IN (SELECT id FROM co_contratti WHERE idstato IN (SELECT id FROM co_staticontratti WHERE is_pianificabile = 1))
    AND idintervento IS NULL

UNION SELECT IF(data_scadenza IS NULL, data_richiesta, data_scadenza) AS data FROM in_interventi
    INNER JOIN an_anagrafiche ON in_interventi.idanagrafica = an_anagrafiche.idanagrafica';

// Visualizzo solo promemoria del tecnico loggato
if (!empty($id_tecnico) && !empty($solo_promemoria_assegnati)) {
    $query_da_programmare .= '
    INNER JOIN in_interventi_tecnici_assegnati ON in_interventi.id = in_interventi_tecnici_assegnati.id_intervento AND id_tecnico = '.prepare($id_tecnico);
}

$query_da_programmare .= '
WHERE (SELECT COUNT(*) FROM in_interventi_tecnici WHERE in_interventi_tecnici.idintervento = in_interventi.id) = 0 AND in_interventi.idstatointervento IN(SELECT idstatointervento FROM in_statiintervento WHERE is_completato = 0)';
$risultati_da_programmare = $dbo->fetchArray($query_da_programmare);

if (!empty($risultati_da_programmare)) {
    echo '
<div class="row">
    <div class="col-md-10">';
}

echo '
<div id="calendar"></div>';

if (!empty($risultati_da_programmare)) {
    echo '
    </div>

    <div id="external-events" class="hidden-xs hidden-sm col-md-2">
        <h4>'.tr('Promemoria da pianificare').'</h4>';

    // Controllo pianificazioni mesi precedenti
    // Promemoria contratti + promemoria interventi
    $query_mesi_precenti = 'SELECT co_promemoria.id FROM co_promemoria INNER JOIN co_contratti ON co_promemoria.idcontratto=co_contratti.id WHERE idstato IN(SELECT id FROM co_staticontratti WHERE is_pianificabile = 1) AND idintervento IS NULL AND DATE_ADD(co_promemoria.data_richiesta, INTERVAL 1 DAY) <= NOW()
    UNION SELECT in_interventi.id FROM in_interventi
        INNER JOIN an_anagrafiche ON in_interventi.idanagrafica=an_anagrafiche.idanagrafica';

    // Visualizzo solo promemoria del tecnico loggato
    if (!empty($id_tecnico) && !empty($solo_promemoria_assegnati)) {
        $query_mesi_precenti .= '
        INNER JOIN in_interventi_tecnici_assegnati ON in_interventi.id = in_interventi_tecnici_assegnati.id_intervento AND id_tecnico = '.prepare($id_tecnico);
    }

    $query_mesi_precenti .= '
WHERE (SELECT COUNT(*) FROM in_interventi_tecnici WHERE in_interventi_tecnici.idintervento = in_interventi.id) = 0 AND in_interventi.idstatointervento IN(SELECT idstatointervento FROM in_statiintervento WHERE is_completato = 0) AND DATE_ADD(IF(in_interventi.data_scadenza IS NULL, in_interventi.data_richiesta, in_interventi.data_scadenza), INTERVAL 1 DAY) <= NOW()';
    $numero_mesi_precenti = $dbo->fetchNum($query_mesi_precenti);

    if ($numero_mesi_precenti > 0) {
        echo '<div class="alert alert-warning alert-dismissible" role="alert"><button class="close" type="button" data-dismiss="alert" aria-hidden="true"><span aria-hidden="true">×</span><span class="sr-only">'.tr('Chiudi').'</span></button><i class="fa fa-exclamation-triangle"></i><span class="text-sm"> '.tr('Ci sono _NUM_ promemoria scaduti', [
                '_NUM_' => $numero_mesi_precenti,
        ]).'.</span></div>';
    }

    // Aggiunta della data corrente per visualizzare il mese corrente
    $risultati_da_programmare[] = [
        'data' => date('Y-m-d H:i:s'),
    ];

    $mesi = collect($risultati_da_programmare)
        ->unique(function ($item) {
            $data = Carbon::parse($item['data']);

            return $data->format('m-Y');
        })
        ->sortBy('data');

    echo '
    <select class="superselect openstamanager-input select-input" id="mese-promemoria">';

    foreach ($mesi as $mese) {
        $data = Carbon::parse($mese['data']);
        $chiave = $data->format('mY');
        $testo = $data->formatLocalized('%B %Y');

        
        if (checkdate($data->format('m'), $data->format('d'), $data->format('Y'))){
    echo '
            <option value="'.$chiave.'">'.ucfirst($testo).'</option>';
        }
    }

    echo '
        </select>
        <div id="elenco-promemoria"></div>
    </div>
</div>';
}

$vista = setting('Vista dashboard');
if ($vista == 'mese') {
    $def = 'dayGridMonth';
} elseif ($vista == 'giorno') {
    $def = 'timeGridDay';
} elseif ($vista == 'settimana') {
    $def = 'timeGridWeek';
} else {
    $def = 'listWeek';
}

$modulo_interventi = Modules::get('Interventi');

echo '
<script type="text/javascript">    
var Draggable = FullCalendar.Draggable;
globals.dashboard = {
        load_url: "'.$structure->fileurl('ajax.php').'?id_module='.$id_module.'",
        style: "'.$def.'",
        show_sunday: '.intval(setting('Visualizzare la domenica sul calendario')).',
        start_time: "'.setting('Ora inizio sul calendario').'",
        end_time: "'.((setting('Ora fine sul calendario') != '00:00:00' && !empty(setting('Ora fine sul calendario'))) ? setting('Ora fine sul calendario') : '23:59:59').'",
        write_permission: '.intval($modulo_interventi->permission == 'rw').',
        tooltip: '.intval(setting('Utilizzare i tooltip sul calendario')).',
        calendar: null,
        timeFormat: "H:mm",
        select: {
            title: "'.tr('Aggiungi intervento').'",
            url: globals.rootdir + "/add.php?id_module='.$modulo_interventi->id.'",
        },
        drop: {
            title: "'.tr('Pianifica intervento').'",
            url: globals.rootdir + "/add.php?id_module='.$modulo_interventi->id.'",
        },
        error: "'.tr('Errore durante la creazione degli eventi').'",
        genericError: "'.tr('Errore').'",
        genericWarning: "'.tr('Attenzione').'",
        informazioni_aggiuntive: '.intval(setting('Visualizza informazioni aggiuntive sul calendario')).',
    };

    function aggiorna_contatore(counter_id) {
        let counter = $(counter_id);

        let dropdown = counter.find(".dropdown-menu");
        let selected = dropdown.find("input:checked").length;
        let total = dropdown.find("input").length;

        counter.find(".selected_counter").html(selected);
        counter.find(".total_counter").html(total);

        let object = counter.find(".counter_object");

        if (total === 0) {
            object.addClass("btn-primary disabled");
            return;
        } else {
            object.removeClass("btn-primary disabled");
        }

        if (selected === total) {
            object.removeClass("btn-warning btn-danger").addClass("btn-success");
        } else if (selected === 0) {
            object.removeClass("btn-warning btn-success").addClass("btn-danger");
        } else {
            object.removeClass("btn-success btn-danger").addClass("btn-warning");
        }
    }

    function carica_interventi_da_pianificare(mese) {
        if (mese === undefined) {
            // Seleziono il mese corrente per gli interventi da pianificare
            let date = new Date();
            date.setDate(date.getDate());

            //Note: January is 0, February is 1, and so on.
            mese = ("0" + (date.getMonth() + 1)).slice(-2) + date.getFullYear();

            $("#mese-promemoria option[value=" + mese + "]").attr("selected", "selected").trigger("change");
        }

        $("#elenco-promemoria").html("<center><br><br><i class=\"fa fa-refresh fa-spin fa-2x fa-fw\"></i></center>");
        $.get(globals.dashboard.load_url, {
            op: "carica_interventi",
            mese: mese
        }).done(function (data) {
            $("#elenco-promemoria").html(data);

            $("#external-events .fc-event").each(function () {
                new Draggable( this, {
                    create: false,
                    revert: true,
                    eventData: {
                        title: $.trim($(this).text()),
                        stick: false
                    }
                } );
            });
        });
    }

    $(document).ready(function () {
        // Aggiornamento contatori iniziale
        aggiorna_contatore("#dashboard_stati");
        aggiorna_contatore("#dashboard_tipi");
        aggiorna_contatore("#dashboard_tecnici");
        aggiorna_contatore("#dashboard_zone");

        // Selezione di uno stato intervento
        $(".dashboard_stato").click(function (event) {
            let id = $(this).val();

            session_set_array("dashboard,idstatiintervento", id).then(function () {
                aggiorna_contatore("#dashboard_stati");
                globals.dashboard.calendar.refetchEvents();
            });
        });

        // Selezione di un tipo intervento
        $(".dashboard_tipo").click(function (event) {
            let id = $(this).val();

            session_set_array("dashboard,idtipiintervento", id).then(function () {
                aggiorna_contatore("#dashboard_tipi");
                globals.dashboard.calendar.refetchEvents();
            });
        });

        // Selezione di un tecnico
        $(".dashboard_tecnico").click(function (event) {
            let id = $(this).val();

            session_set_array("dashboard,idtecnici", id).then(function () {
                aggiorna_contatore("#dashboard_tecnici");
                globals.dashboard.calendar.refetchEvents();
            });
        });

        // Selezione di una zona
        $(".dashboard_zona").click(function (event) {
            let id = $(this).val();

            session_set_array("dashboard,idzone", id).then(function () {
                aggiorna_contatore("#dashboard_zone");
                globals.dashboard.calendar.refetchEvents();
            });
        });

        // Selezione di tutti gli elementi
        $(".seleziona_tutto").click(function () {
            $(this).closest("ul").find("input:not(:checked)").each(function () {
                $(this).click();
            });
        });

        // Deselezione di tutti gli elementi
        $(".deseleziona_tutto").click(function () {
            $(this).closest("ul").find("input:checked").each(function () {
                $(this).click();
            });
        });

        $("#mese-promemoria").change(function () {
            let mese = $(this).val();
            carica_interventi_da_pianificare(mese);
        });

        // Caricamento interventi da pianificare
        carica_interventi_da_pianificare();

        // Creazione del calendario
        create_calendar();
    });

    function create_calendar() {
        var calendarElement = document.getElementById("calendar");
        var clickCnt = 0;

        var calendar = new FullCalendar.Calendar(calendarElement, {
            //plugins: [adaptivePlugin, momentPlugin, dayGridPlugin, timeGridPlugin],
            /* locales: allLocales, */
            locale: globals.locale,
            slotEventOverlap: false,
            weekNumbers: true,
            weekText: "W",
            nowIndicator: true,
            schedulerLicenseKey: "GPL-My-Project-Is-Open-Source",
            hiddenDays: globals.dashboard.show_sunday ? [] : [0],
            headerToolbar: {
                left: "prev,next today",
                center: "title",
                right: "dayGridMonth,timeGridWeek,timeGridDay,listWeek"
            },
            eventDisplay: "block",
            timeFormat: globals.dashboard.timeFormat,
            slotLabelFormat: globals.dashboard.timeFormat,
            slotDuration: "00:15:00",
            snapDuration: globals.snapDuration,
            initialView: globals.dashboard.style,
            slotMinTime: globals.dashboard.start_time,
            slotMaxTime: globals.dashboard.end_time,
            lazyFetching: true,
            selectMirror: true,
            eventLimit: false, // allow "more" link when too many events
            allDaySlot: globals.dashboard.informazioni_aggiuntive,

            loading: function (isLoading, view) {
                if (isLoading) {
                    $("#tiny-loader").show();
                } else {
                    $("#tiny-loader").hide();
                }
            },

            droppable: globals.dashboard.write_permission,
            drop: function (info) {
                let date = info.date;

                let data = moment(date).format("YYYY-MM-DD");
                let ora_dal = moment(date).format("HH:mm");
                let ora_al = moment(date).add(1, "hours").format("HH:mm");

                let ref = info.draggedEl.dataset.ref;
                let name;
                if (ref === "promemoria") {
                    name = "idcontratto_riga";
                } else {
                    name = "id_intervento";
                }
                openModal(globals.dashboard.drop.title, globals.dashboard.drop.url + "&data=" + data + "&orario_inizio=" + ora_dal + "&orario_fine=" + ora_al + "&ref=dashboard&idcontratto=" + info.draggedEl.dataset.idcontratto + "&" + name + "=" + info.draggedEl.dataset.id + "&id_tecnico=" + info.draggedEl.dataset.id_tecnico);

                // Ricaricamento dei dati alla chiusura del modal
                $("#modals > div").on("hidden.bs.modal", function () {
                    globals.dashboard.calendar.refetchEvents();

                    let mese = $("#mese-promemoria").val();
                    carica_interventi_da_pianificare(mese);
                });
            },
            eventReceive: function(info){
                info.revert();
            },

            selectable: globals.dashboard.write_permission,
            select: function (info) { // info
                let start = info.start;
                let end = info.end;

                if (!info.allDay) {
                    let data = moment(start).format("YYYY-MM-DD");
                    let data_fine = moment(end).format("YYYY-MM-DD");
                    let orario_inizio = moment(start).format("HH:mm");
                    let orario_fine = moment(end).format("HH:mm");

                    // Fix selezione di un giorno avanti per vista mensile
                    if (globals.dashboard.calendar.view == "dayGridMonth") {
                        data_fine = moment(end).subtract(1, "days").format("YYYY-MM-DD");
                    }

                    openModal(globals.dashboard.select.title, globals.dashboard.select.url + "&ref=dashboard&data=" + data + "&data_fine=" + data_fine + "&orario_inizio=" + orario_inizio + "&orario_fine=" + orario_fine);
                }
            },

            editable: globals.dashboard.write_permission,
            eventDrop: function (info) {// info
                let event = info.event;

                if (event.allDay !== true) {
                    let start = event.start;
                    let end = (event.end!=null ? event.end : event.start);
                    $.post(globals.dashboard.load_url, {
                        op: "modifica_intervento",
                        id: event.id,
                        idintervento: event.extendedProps.idintervento,
                        timeStart: moment(start).format("YYYY-MM-DD HH:mm"),
                        timeEnd: moment(end).format("YYYY-MM-DD HH:mm")
                    }, function (data, responseType) {
                        data = $.trim(data);

                        if (responseType === "success" && data !== "ok") {
                            swal(globals.dashboard.genericWarning, data, "warning");
                        } else if (responseType !== "success") {
                            swal(globals.dashboard.genericError, data, "error");
                        }

                        if (data !== "ok") {
                            info.revert();
                        }

                    });
                } else {
                    info.revert();
                }
            },
            eventResize: function (info) {
                let event = info.event;
                let start = event.start;
                let end = (event.end!=null ? event.end : event.start);
                $.post(globals.dashboard.load_url, {
                    op: "modifica_intervento",
                    id: event.id,
                    idintervento: event.extendedProps.idintervento,
                    timeStart: moment(start).format("YYYY-MM-DD HH:mm"),
                    timeEnd: moment(end).format("YYYY-MM-DD HH:mm")
                }, function (data, responseType) {
                    data = $.trim(data);

                    if (responseType === "success" && data !== "ok") {
                        swal(globals.dashboard.genericWarning, data, "warning");
                    } else if (responseType !== "success") {
                        swal(globals.dashboard.genericError, data, "error");
                    }

                    if (data !== "ok") {
                        info.revert();
                    }

                });
            },';

if(isMobile() && setting('Utilizzare i tooltip sul calendario')){
    echo '
            eventClick: function(info) {
                let link = info.event.extendedProps.link;
                let element = $(this);
                clickCnt++;
                if (clickCnt === 1) {
                    oneClickTimer = setTimeout(function() {
                        clickCnt = 0;
                        element.trigger("mouseenter");
                    }, 400);
                } else if (clickCnt === 2) {
                    clearTimeout(oneClickTimer);
                    clickCnt = 0;
                    location.href = link;
                }          
            },';
}else{
    echo '
            eventClick: function(info) {
                info.jsEvent.preventDefault();
                location.href = info.event.extendedProps.link;
            },';
}

echo '
            eventContent: function (info) {
                return { html: info.event.title };
            },

            eventDidMount: function(info){
                let element = $(info.el);

                let id_record = info.event.extendedProps.idintervento;
                
                if (globals.dashboard.tooltip == 1) {
                    element.tooltipster({
                        content: globals.translations.loading + "...",
                        animation: "grow",
                        updateAnimation: "grow",
                        contentAsHTML: true,
                        hideOnClick: true,
                        speed: 200,
                        delay: 300,
                        maxWidth: 400,
                        theme: "tooltipster-shadow",
                        touchDevices: true,
                        trigger: "hover",
                        position: "left",
                        functionBefore: function (instance, helper) {
                            let $origin = $(helper.origin);

                            if ($origin.data("loaded") !== true) {
                                $.post(globals.dashboard.load_url, {
                                    op: "tooltip_info",
                                    id_record: id_record,
                                    allDay: info.event.allDay,
                                }, function (data, response) {
                                    instance.content(data);

                                    $origin.data("loaded", true);
                                });
                            }
                        }
                    });
                }
            },

            events: {
                url: globals.dashboard.load_url + "&op=interventi_periodo",
                type: "GET",
                error: function () {
                    swal(globals.dashboard.genericError, globals.dashboard.error, "error");
                }
            }
        });

        calendar.render();

        globals.dashboard.calendar = calendar;
    }
</script>';
