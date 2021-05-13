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

include_once __DIR__.'/core.php';

$pageTitle = tr('Informazioni');

$paths = App::getPaths();

include_once App::filepath('include|custom|', 'top.php');

echo '
<div class="box">
    <div class="box-header">
        <img src="'.$paths['img'].'/logo.png" class="pull-left img-responsive" width="300" alt="'.tr('OSM Logo').'">
        <div class="pull-right">
            <i class="fa fa-info"></i> '.tr('Informazioni').'
        </div>
    </div>

    <div class="box-body">';

if (file_exists(base_dir().'/assistenza.php')) {
    include base_dir().'/assistenza.php';
} else {
    echo '
        <div class="row">
            <div class="col-md-8">
                <p>'.tr('<b>OpenSTAManager</b> è un <b>software libero</b> mantenuto da <a href="https://www.devcode.it" target="_blank">Devcode Srl</a>').'.</p>

                <p>'.tr('Il nome significa "Gestore di STA (<b>Servizio Tecnico Assistenza</b>) aperto" ed è stato creato per gestire e archiviare l\'assistenza tecnica fornita ai propri clienti').'.</p>
            </div>

            <div class="col-md-4">
                <p><b>'.tr('Sito web').':</b> <a href="https://www.openstamanager.com" target="_blank">www.openstamanager.com</a></p>

                <p><b>'.tr('Versione').':</b> '.$version.' <small class="text-muted">('.(!empty($revision) ? 'R'.$revision : tr('In sviluppo')).')</small></p>

                <p><b>'.tr('Licenza').':</b> <a href="https://www.gnu.org/licenses/gpl-3.0.txt" target="_blank" title="'.tr('Vai al sito per leggere la licenza').'">GPLv3</a></p>
            </div>
        </div>

        <hr>

        <div class="row">
            <div class="col-md-6">
                <div class="box box-primary">
                    <div class="box-header">
                        <h3 class="box-title text-uppercase"><i class="fa fa-globe"></i> '.tr('Perchè software libero').'</h3>
                    </div>

                    <div class="box-body">
                        <p>'.tr("Il progetto è software libero perchè permette a tutti di conoscere come funziona avendo il codice sorgente del programma e fornisce così la possibilità di studiare come funziona, modificarlo, adattarlo alle proprie esigenze e, in ambito commerciale, non obbliga l'utilizzatore ad essere legato allo stesso fornitore di assistenza").'.</p>

                        <p>'.tr("E' altrettanto importante sapere come funziona per conoscere come vengono trattati i VOSTRI dati, proteggendo così la vostra <b>privacy</b>").'.</p>

                        <p>'.tr('OpenSTAManager è inoltre stato progettato utilizzando altro software libero, tra cui principalmente').':</p>
                        <ul>
                            <li><a href="https://www.php.net" target="_blank"><i class="fa fa-circle-o-notch"></i> PHP</a></li>
                            <li><a href="https://www.mysql.com" target="_blank"><i class="fa fa-circle-o-notch"></i> MySQL</a></li>
                            <li><a href="https://jquery.com" target="_blank"><i class="fa fa-circle-o-notch"></i> JQuery</a></li>
                            <li><a href="https://getbootstrap.com" target="_blank"><i class="fa fa-circle-o-notch"></i> Bootstrap</a></li>
                            <li><a href="https://fortawesome.github.io/Font-Awesome" target="_blank"><i class="fa fa-circle-o-notch"></i> FontAwesome</a></li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="box box-danger">
                    <div class="box-header">
                        <h3 class="box-title text-uppercase"><i class="fa fa-group"></i> '.tr('Community').'</h3>
                    </div>

                    <div class="box-body">
                        <p>'.tr('La community è un componente importante in un progetto open-source perchè mette in contatto le persone tra di loro, utenti e programmatori').'.</p>

                        <p>'.tr('Con OpenSTAManager siamo presenti su').':</p>
                        <div class="well">
                            <div class="row">
                                <div class="col-xs-3 text-center">
                                    <a href="https://github.com/devcode-it/openstamanager" target="_blank">
                                        <i class="fa fa-2x fa-github"></i><br>
                                        '.tr('GitHub').'
                                    </a>
                                </div>
                                <div class="col-xs-3 text-center">
                                    <a href="https://forum.openstamanager.com/" target="_blank">
                                        <i class="fa fa-2x fa-edit"></i><br>
                                        '.tr('Forum').'
                                    </a>
                                </div>
                                <div class="col-xs-3 text-center">
                                    <a href="https://eepurl.com/8MFgH" target="_blank">
                                        <i class="fa fa-2x fa-envelope"></i><br>
                                        '.tr('Newsletter').'
                                    </a>
                                </div>
                                <div class="col-xs-3 text-center">
                                    <a href="https://www.facebook.com/openstamanager" target="_blank">
                                        <i class="fa fa-2x fa-facebook-square"></i><br>
                                        '.tr('Facebook').'
                                    </a>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="box box-warning">
                    <div class="box-header">
                        <h3 class="box-title text-uppercase"><i class="fa fa-download"></i> '.tr('Aggiornamenti e nuove versioni').'</h3>
                    </div>

                    <div class="box-body">
                        <p>'.tr("Tutti gli aggiornamenti e le nuove versioni sono disponibili all'indirizzo").':</p>
                        <a href="https://www.openstamanager.com/downloads/" target="_blank"><i class="fa fa-external-link"></i> www.openstamanager.com/downloads/</a>
                    </div>
                </div>
            </div>


            <div class="col-md-6">
                <div class="box box-success">
                    <div class="box-header">
                        <h3 class="box-title text-uppercase"><i class="fa fa-euro"></i> '.tr('Servizi a pagamento').'</h3>
                    </div>

                    <div class="box-body">
                        <p>'.tr('Per le aziende che hanno necessità di essere seguite da <b>supporto professionale</b> è disponibile un servizio di assistenza e supporto a pagamento').'.</p>

                        <p>'.tr("E' disponibile anche un <b>servizio cloud</b> su cui poter installare OpenSTAManager, in modo da non doverti più preoccupare di backup e gestione dei dati").'.</p>

                        <p><a href="https://www.openstamanager.com/per-le-aziende/" class="btn btn-lg btn-info btn-block" target="_blank"><i class="fa fa-briefcase"></i> '.tr('Ottieni supporto professionale').'</a></p>
                    </div>
                </div>
            </div>
        </div>';
}

echo '

	</div>
</div>';

include_once App::filepath('include|custom|', 'bottom.php');
