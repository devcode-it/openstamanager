<?php

include_once __DIR__.'/core.php';

$pageTitle = tr('Informazioni');

$paths = App::getPaths();

include_once App::filepath('resources\views|custom|\layout', 'top.php');

echo '
<div class="box">
    <div class="box-header">
        <img src="'.$paths['img'].'/logo.png" alt="'.tr('OSM Logo').'">
        <h3 class="box-title">'.tr('OpenSTAManager').'</h3>
        <div class="pull-right">
            <i class="fa fa-info"></i> '.tr('Informazioni').'
        </div>
    </div>

    <div class="box-body">';

if (file_exists($docroot.'/assistenza.php')) {
    include $docroot.'/assistenza.php';
} else {
    echo '
        <div class="row">
            <div class="col-md-8">
                <p>'.tr('<b>OpenSTAManager</b> è un <b>software libero</b> ideato e sviluppato da <a href="mailto:info@openstamanager.com">Fabio Lovato</a>').'.</p>

                <p>'.tr('Il nome significa "Gestore di STA (<b>Servizio Tecnico Assistenza</b>) aperto" ed è stato creato per gestire e archiviare l\'assistenza tecnica fornita ai propri clienti').'.</p>
            </div>

            <div class="col-md-4">
                <p><b>'.tr('Sito web').':</b> <a href="https://www.openstamanager.com" target="_blank">www.openstamanager.com</a></p>

                <p><b>'.tr('Versione').':</b> '.$version.' <small class="text-muted">('.(!empty($revision) ? 'R'.$revision : tr('In sviluppo')).')</small></p>

                <p><b>'.tr('Licenza').':</b> <a href="http://www.gnu.org/licenses/gpl-3.0.txt" target="_blank" title="'.tr('Vai al sito per leggere la licenza').'">GPLv3</a></p>
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
                            <li><a href="http://www.php.net" target="_blank"><i class="fa fa-circle-o-notch"></i> PHP</a></li>
                            <li><a href="http://www.mysql.com" target="_blank"><i class="fa fa-circle-o-notch"></i> MySQL</a></li>
                            <li><a href="http://jquery.com" target="_blank"><i class="fa fa-circle-o-notch"></i> JQuery</a></li>
                            <li><a href="http://getbootstrap.com" target="_blank"><i class="fa fa-circle-o-notch"></i> Bootstrap</a></li>
                            <li><a href="http://fortawesome.github.io/Font-Awesome" target="_blank"><i class="fa fa-circle-o-notch"></i> FontAwesome</a></li>
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
                                    <a href="http://www.openstamanager.com/forum/" target="_blank">
                                        <i class="fa fa-2x fa-edit"></i><br>
                                        '.tr('Forum').'
                                    </a>
                                </div>
                                <div class="col-xs-3 text-center">
                                    <a href="http://eepurl.com/8MFgH" target="_blank">
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

                <div class="box box-default">
                    <div class="box-header">
                        <h3 class="box-title text-uppercase"><i class="fa fa-download"></i> '.tr('Aggiornamenti e nuove versioni').'</h3>
                    </div>

                    <div class="box-body">
                        <p>'.tr("Tutti gli aggiornamenti e le nuove versioni sono disponibili all'indirizzo").':</p>
                        <a href="http://www.openstamanager.com/downloads/" target="_blank"><i class="fa fa-external-link"></i> www.openstamanager.com/downloads/</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="box box-warning">
                    <div class="box-header">
                        <h3 class="box-title text-uppercase"><i class="fa fa-money"></i> '.tr('Supporta il progetto').'</h3>
                    </div>

                    <div class="box-body">
                        <p>'.tr('OpenSTAManager è software libero ed è nato e cresciuto con il lavoro volontario di alcuni programmatori').'.</p>

                        <p>'.tr('La filosofia del software libero fa sì che il progetto sia <b>accessibile a tutti</b> e nel nostro caso specifico lo è, anche dal punto di vista della gratuità').'.</p>

                        <p>'.tr('Offriamo supporto a pagamento professionale a chi fosse interessato, ma a chi non interessa il supporto a pagamento e sta comunque utilizzando il software chiediamo una donazione per il lavoro svolto finora e per la possibilità di continuare questo progetto con lo stesso spirito con cui è nato').'. '.tr('Le donazioni non ci rendono ricchi, ma sono un <b>grande simbolo di apprezzamento</b>').'.</p>

                        <a href="http://sourceforge.net/donate/index.php?group_id=236538" class="btn btn-lg btn-success btn-block" target="_blank"><i class="fa fa-usd"></i> '.tr('Supporta questo progetto').'</a>
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

                        <p><a href="http://www.openstamanager.com/per-le-aziende/" class="btn btn-lg btn-info btn-block" target="_blank"><i class="fa fa-thumbs-up"></i> '.tr('Ottieni supporto professionale').'</a></p>
                    </div>
                </div>
            </div>
        </div>';
}

echo '

	</div>
</div>';

include_once App::filepath('resources\views|custom|\layout', 'bottom.php');
