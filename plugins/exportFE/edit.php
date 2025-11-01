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

include_once __DIR__.'/init.php';

use Modules\Fatture\StatoFE;
use Plugins\ExportFE\FatturaElettronica;
use Plugins\ExportFE\Interaction;
use Util\XML;

if ($fattura !== null) {
    /* Per le PA EC02 e EC01 sono dei stati successivi a NE il quale a sua volta è successivo a RC. EC01 e EC02 sono definiti all'interno della ricevuta di NE che di fatto indica il rifiuto o l'accettazione. */
    $stato_fe = StatoFE::find($fattura->codice_stato_fe);
    $abilita_genera = $fattura->stato->getTranslation('title') != 'Bozza' && (empty($fattura->codice_stato_fe) || intval($stato_fe->is_generabile));
    $ricevuta_principale = $fattura->getRicevutaPrincipale();

    if (!empty($fattura_pa)) {
        $generata = $fattura_pa->isGenerated();
    } else {
        $generata = false;
    }

    $checks = FatturaElettronica::controllaFattura($fattura);
    if (!empty($checks)) {
        echo '
    <div class="alert alert-warning">
        <p><i class="fa fa-warning mr-2"></i> '.tr('Prima di procedere alla generazione della fattura elettronica completa le seguenti informazioni').':</p>';

        foreach ($checks as $check) {
            echo '
        <p><b>'.$check['name'].' '.$check['link'].'</b></p>
        <ul>';

            foreach ($check['errors'] as $error) {
                if (!empty($error)) {
                    echo '
            <li>'.$error.'</li>';
                }
            }

            echo '
        </ul>';
        }

        echo '
    </div>';
    }

    echo '
    <div class="alert alert-info">
        <i class="fa fa-info-circle mr-2"></i>'.tr("Per effettuare la generazione dell'XML della fattura elettronica clicca sul pulsante _BTN_", [
        '_BTN_' => '<b>Genera</b>',
    ]).'. '.tr('Successivamente sarà possibile procedere alla visualizzazione e al download della fattura generata attraverso i pulsanti dedicati').'
    </div>

    <div class="alert alert-light border">
        <i class="fa fa-paperclip mr-2"></i>'.tr("Tutti gli allegati inseriti all'interno della categoria \"Allegati Fattura Elettronica\" saranno inclusi nell'XML").'
    </div>';

    echo '
    <div class="text-center">
        <form action="" method="post" role="form" style="display:inline-block" id="form-xml">
            <input type="hidden" name="id_plugin" value="'.$id_plugin.'">
            <input type="hidden" name="id_record" value="'.$id_record.'">
            <input type="hidden" name="backto" value="record-edit">
            <input type="hidden" name="op" value="generate">

            <button type="button" class="btn btn-primary btn-lg '.(!$abilita_genera ? 'disabled' : '').'" onclick="generaFE(this)">
                <i class="fa fa-file mr-1"></i> '.tr('Genera').'
            </button>
        </form>';

    $file = $generata ? $fattura->getFatturaElettronica() : null;

    echo '

        <i class="fa fa-arrow-right fa-fw text-muted"></i>

        <a href="'.base_path_osm().'/view.php?file_id='.($file ? $file->id : null).'" class="btn btn-info btn-lg '.($generata ? '' : 'disabled').'" target="_blank" '.($generata ? '' : 'disabled').'>
            <i class="fa fa-eye mr-1"></i> '.tr('Visualizza').'
        </a>';

    // Scelgo quando posso inviarla
    $inviabile = Interaction::isEnabled() && $generata && intval($stato_fe->is_inviabile);

    echo '
        <i class="fa fa-arrow-right fa-fw text-muted"></i>

        <a href="'.$structure->fileurl('download.php').'?id_record='.$id_record.'" class="btn btn-primary btn-lg '.($generata ? '' : 'disabled').'" target="_blank" '.($generata ? '' : 'disabled').'>
            <i class="fa fa-download mr-1"></i> '.tr('Scarica').'
        </a>';

    echo '

        <i class="fa fa-arrow-right fa-fw text-muted"></i>

        <button type="button" onclick="inviaFE(this)" class="btn btn-success btn-lg '.($inviabile ? '' : 'disabled').'">
            <i class="fa fa-paper-plane mr-1"></i> '.tr('Invia').'
        </button>';

    $verify = Interaction::isEnabled() && $generata;
    echo '
        <i class="fa fa-arrow-right fa-fw text-muted"></i>

        <button type="button" onclick="verificaNotificheFE(this)" class="btn btn-warning btn-lg '.($verify ? '' : 'disabled').'">
            <i class="fa fa-question-circle mr-1"></i> '.tr('Verifica ricevute').'
        </button>
    </div>';

    echo '<br><br>';

    // Messaggio informativo sulla ricevuta principale impostata
    if (!empty($ricevuta_principale)) {
        echo '
    <div class="alert alert-'.$stato_fe->tipo.'">
        <div class="float-right d-none d-sm-inline">
            <i class="fa fa-clock-o mr-1 tip" title="'.tr('Data e ora').'"></i> '.timestampFormat($record['data_stato_fe']);

        if (!empty($ultima_ricevuta)) {
            echo '
            <a href="'.ROOTDIR.'/view.php?file_id='.$ultima_ricevuta->id.'" target="_blank" class="btn btn-info btn-xs">
                <i class="fa fa-external-link mr-1"></i> '.tr('Visualizza ricevuta').'
            </a>';
        }

        echo '
        </div>

        <big>
            <i class="'.$stato_fe->icon.' mr-1" style="color:#fff;"></i>
            <b>'.$stato_fe->codice.'</b> - '.$stato_fe->name.'
        </big>';

        if (!empty($record['descrizione_ricevuta_fe'])) {
            echo '
        <br><b>'.tr('Note', [], ['upper' => true]).':</b> '.$record['descrizione_ricevuta_fe'];
        }

        if ($fattura->codice_stato_fe == 'GEN') {
            echo '
        <br><i class="fa fa-info-circle mr-1"></i> '.tr("La fattura è stata generata ed è pronta per l'invio").'.';
        }

        echo '
    </div>';

        // Lettura della ricevuta
        if (!empty($ricevuta_principale) && !empty($ricevuta_principale->filename) && file_exists($ricevuta_principale->filename)) {
            $contenuto_ricevuta = XML::readFile($ricevuta_principale->filename);
            $lista_errori = $contenuto_ricevuta['ListaErrori'];

            if (!empty($lista_errori)) {
                echo '
    <h4>'.tr('Elenco degli errori').'</h4>
    <table class="table table-striped">
        <thead>
            <tr>
                <th>'.tr('Codice').'</th>
                <th>'.tr('Descrizione').'</th>
            </tr>
        </thead>
        <tbody>';

                $lista_errori = $lista_errori[0] ? $lista_errori : [$lista_errori];
                foreach ($lista_errori as $errore) {
                    $errore = $errore['Errore'];
                    echo '
            <tr>
                <td>'.$errore['Codice'].'</td>
                <td>'.htmlentities((string) $errore['Descrizione']).'</td>
            </tr>';
                }

                echo '
        </tbody>
    </table>';
            }
        }
    }

    echo '
    <script>
    $(document).ready(function() {
        // Personalizzazione animazione di caricamento
        $.fn.dataTable.ext.pager.numbers_length = 5;
        $.extend(true, $.fn.dataTable.defaults, {
            language: {
                processing: "<div class=\"text-center\"><i class=\"fa fa-refresh fa-spin fa-2x fa-fw text-primary\"></i><div class=\"mt-2\">"+globals.translations.processing+"</div></div>"
            }
        });
    });

        function inviaFE(button) {
            if (confirm("'.tr('Inviare la fattura al SDI?').'")) {
                let restore = buttonLoading(button);

                // Mostra un\'animazione di caricamento
                $("#main_loading").show();

                $.ajax({
                    url: globals.rootdir + "/actions.php",
                    type: "post",
                    dataType: "json",
                    data: {
                        op: "send",
                        id_module: "'.$id_module.'",
                        id_plugin: "'.$id_plugin.'",
                        id_record: "'.$id_record.'",
                    },
                    success: function(data) {
                        $("#main_loading").fadeOut();
                        buttonRestore(button, restore);

                        if (data.code === 200) {
                            swal("'.tr('Fattura inviata!').'", data.message, "success");

                            $(button).attr("disabled", true).addClass("disabled");
                            // Ricarica la pagina dopo 3 secondi
                            setTimeout(function() { location.reload(); }, 3000);
                        } else if (data.code === 301) {
                            swal("'.tr('Invio già effettuato').'", data.code + " - " + data.message, "error");
                            $(button).attr("disabled", true).addClass("disabled");
                        } else if (data.code === 500) {
                            swal("'.tr("Errore durante l'invio").'", "'.tr("Si è verificato un problema durante l'invio della fattura! Riprova tra qualche minuto oppure contatta l'assistenza se il problema persiste.").'", "error");
                        } else {
                            swal("'.tr('Invio fallito').'", data.code + " - " + data.message, "error");
                        }
                    },
                    error: function() {
                        $("#main_loading").fadeOut();
                        swal("'.tr('Errore').'", "'.tr('Errore durante il salvataggio').'", "error");

                        buttonRestore(button, restore);
                    }
                });
            }
        }

        function verificaNotificheFE(button) {
            openModal("'.tr('Gestione ricevute').'", "'.$structure->fileurl('notifiche.php').'?id_module='.$id_module.'&id_plugin='.$id_plugin.'&id_record='.$id_record.'");

        /*
            let restore = buttonLoading(button);

            // Mostra un\'animazione di caricamento
            $("#main_loading").show();

            $.ajax({
                url: globals.rootdir + "/actions.php",
                type: "post",
                data: {
                    op: "verify",
                    id_module: "'.$id_module.'",
                    id_plugin: "'.$id_plugin.'",
                    id_record: "'.$id_record.'",
                },
                success: function(data) {
                    $("#main_loading").fadeOut();
                    buttonRestore(button, restore);
                    data = JSON.parse(data);

                    if (data.file) {
                        swal("'.tr('Verifica completata con successo!').'", "'.tr('Lo stato della Fattura Elettronica è stato aggiornato in base all\'ultima notifica disponibile nel sistema!').'", "success").then(function() {
                            location.reload(); // Ricaricamento pagina
                        });
                    } else {
                        swal("'.tr('Verifica fallita').'", data.code + " - " + data.message, "error");
                    }
                },
                error: function(data) {
                    $("#main_loading").fadeOut();
                    swal("'.tr('Errore').'", "'.tr('Errore durante la verifica').'", "error");

                    buttonRestore(button, restore);
                }
            });*/
        }

        function generaFE(button) {
            salvaForm("#edit-form", {}, button)
                .then(function(valid) {
                    // Mostra un\'animazione di caricamento
                    $("#main_loading").show();';

    if ($generata) {
        echo '
                    swal({
                        title: "'.tr('Sei sicuro di rigenerare la fattura?').'",
                        html: "<p>'.tr('Attenzione: sarà generato un nuovo progressivo invio').'.</p>",
                        type: "warning",
                        showCancelButton: true,
                        confirmButtonColor: "#30d64b",
                        cancelButtonColor: "#d33",
                        confirmButtonText: "'.tr('Genera').'"
                    }).then((result) => {
                        if (result) {
                            $("#form-xml").submit();
                        } else {
                            $("#main_loading").fadeOut();
                        }
                    });';
    } else {
        echo '
                    $("#form-xml").submit();';
    }
    echo '
                }).catch(function() {
                    $("#main_loading").fadeOut();
                    swal({
                        type: "error",
                        title: "'.tr('Errore').'",
                        text:  "'.tr('Alcuni campi obbligatori non sono stati compilati correttamente').'.",
                    });
                });
        };
    </script>';
}
