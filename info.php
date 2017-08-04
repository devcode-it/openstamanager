<?php
include_once(__DIR__."/core.php");
$pageTitle = "Info";

if( file_exists($docroot."/include/custom/top.php") ){
	include($docroot."/include/custom/top.php");
}
else{
	include($docroot."/include/top.php");
}
?>
	<div class="box">
		<div class="box-header">
			<img src="<?php echo $img ?>/logo.png" alt="<?php echo _("OSM Logo"); ?>">
			<h3 class="box-title"><?php echo  _("OpenSTAManager"); ?></h3>
			<div class="pull-right">
				<i class="fa fa-info"></i> <?php echo _("Informazioni") ?>
			</div>
		</div>

		<div class="box-body">
<?php
if( file_exists("assistenza.php")) include("assistenza.php");
else{
?>
			<div class="row">
				<div class="col-xs-12 col-md-8">
					<p><?php echo _('<b>OpenSTAManager</b> è un <b>software libero</b> ideato e sviluppato da <a href="mailto:info@openstamanager.com">Fabio Lovato</a>') ?>.</p>
					<p><?php echo _('Il nome significa "Gestore di STA (<b>Servizio Tecnico Assistenza</b>) aperto" ed è stato creato per gestire e archiviare l\'assistenza tecnica fornita ai propri clienti') ?>.</p>
				</div>

				<div class="col-xs-12 col-md-4">
					<p><b><?php echo _("Sito web") ?>:</b> <a href="http://www.openstamanager.com" target="_blank">http://www.openstamanager.com</a></p>
					<p><b><?php echo _("Versione") ?>:</b> <?php echo $version.' <small class="text-muted">('.(!empty($revision) ? 'R'.$revision : _('In sviluppo')).')'; ?></small></p>
					<p><b><?php echo _("Licenza") ?>:</b> <a href="http://www.gnu.org/licenses/gpl-3.0.txt" target="_blank" title="<?php echo _("Vai al sito per leggere la licenza")?>">GPLv3</a></p>
				</div>
			</div>

			<hr>

			<div class="row">
				<div class="col-xs-12 col-md-6">
					<div class="box box-primary">
						<div class="box-header">
							<h3 class="box-title text-uppercase"><i class="fa fa-globe"></i> <?php echo _("Perchè software libero") ?></h3>
						</div>

						<div class="box-body">
							<p><?php echo _("Il progetto è software libero perchè permette a tutti di conoscere come funziona avendo il codice sorgente del programma e fornisce così la possibilità di studiare come funziona, modificarlo, adattarlo alle proprie esigenze e, in ambito commerciale, non obbliga l'utilizzatore ad essere legato allo stesso fornitore di assistenza") ?>.</p>
							<p><?php echo _("E' altrettanto importante sapere come funziona per conoscere come vengono trattati i VOSTRI dati, proteggendo così la vostra <b>privacy</b>") ?>.</p>

							<p><?php echo _("OpenSTAManager è inoltre stato progettato utilizzando altro software libero, tra cui principalmente") ?>:</p>
							<a href="http://www.php.net" target="_blank"><i class="fa fa-circle-o-notch"></i> PHP</a><br>
							<a href="http://www.mysql.com" target="_blank"><i class="fa fa-circle-o-notch"></i> MySQL</a><br>
							<a href="http://jquery.com" target="_blank"><i class="fa fa-circle-o-notch"></i> JQuery</a><br>
							<a href="http://getbootstrap.com" target="_blank"><i class="fa fa-circle-o-notch"></i> Bootstrap</a><br>
							<a href="http://fortawesome.github.io/Font-Awesome" target="_blank"><i class="fa fa-circle-o-notch"></i> FontAwesome</a><br>
							<a href="http://html2pdf.fr/it/default" target="_blank"><i class="fa fa-circle-o-notch"></i> HTML2PDF</a>
						</div>
					</div>
				</div>

				<div class="col-xs-12 col-md-6">
					<div class="box box-danger">
						<div class="box-header">
							<h3 class="box-title text-uppercase"><i class="fa fa-group"></i> <?php echo _("Community") ?></h3>
						</div>

						<div class="box-body">
							<p><?php echo _("La community è un componente importante in un progetto open source perchè mette in contatto le persone tra di loro, utenti e programmatori") ?>.</p>

							<p><?php echo _("Con OpenSTAManager siamo presenti su") ?>:</p>
							<div class="well">
								<div class="row">
									<div class="col-md-4 text-center">
										<a href="http://www.openstamanager.com/forum/" target="_blank"><i class="fa fa-2x fa-edit"></i><br><?php echo _("Forum") ?></b></a>
									</div>
									<div class="col-md-4 text-center">
										<a href="http://eepurl.com/8MFgH" target="_blank"><i class="fa fa-2x fa-envelope"></i><br><?php echo _("Mailing list") ?></a>
									</div>
									<div class="col-md-4 text-center">
										<a href="https://www.facebook.com/openstamanager" target="_blank"><i class="fa fa-2x fa-facebook-square"></i><br><?php echo _("Pagina Facebook") ?></a>
									</div>
								</div>
							</div>

						</div>
					</div>

					<div class="box box-default">
						<div class="box-header">
							<h3 class="box-title text-uppercase"><i class="fa fa-download"></i> <?php echo _("Aggiornamenti e nuove versioni") ?></h3>
						</div>

						<div class="box-body">
							<p><?php echo _("Tutti gli aggiornamenti e le nuove versioni sono disponibili all'indirizzo") ?>:</p>
							<a href="http://www.openstamanager.com/downloads/" target="_blank"><i class="fa fa-external-link"></i> www.openstamanager.com/downloads/</a>
						</div>
					</div>
				</div>
			</div>

			<div class="row">
				<div class="col-xs-12 col-md-6">
					<div class="box box-warning">
						<div class="box-header">
							<h3 class="box-title text-uppercase"><i class="fa fa-money"></i> <?php echo _("Supporta il progetto") ?></h3>
						</div>

						<div class="box-body">
							<p><?php echo _("OpenSTAManager è software libero ed è nato e cresciuto con il lavoro volontario di alcuni programmatori") ?>.</p>

							<p><?php echo _("La filosofia del software libero fa sì che il progetto sia <b>accessibile a tutti</b> e nel nostro caso specifico lo è, anche dal punto di vista della gratuità") ?>.</p>
							<p><?php echo _("Offriamo supporto a pagamento professionale a chi fosse interessato, ma a chi non interessa il supporto a pagamento e sta comunque utilizzando il software chiediamo una donazione per il lavoro svolto finora e per la possibilità di continuare questo progetto con lo stesso spirito con cui è nato. Con le donazioni non diventiamo ricchi, ma è un <b>grande
							simbolo di apprezzamento</b>") ?>.</p>

							<a href="http://sourceforge.net/donate/index.php?group_id=236538" class="btn btn-lg btn-success" target="_blank"><i class="fa fa-usd"></i> <?php echo _("Supporta questo progetto") ?></a>
						</div>
					</div>
				</div>


				<div class="col-xs-12 col-md-6">
					<div class="box box-success">
						<div class="box-header">
							<h3 class="box-title text-uppercase"><i class="fa fa-euro"></i> <?php echo _("Servizi a pagamento") ?></h3>
						</div>

						<div class="box-body">
							<p><?php echo _("Per le aziende che hanno necessità di essere seguite da <b>supporto professionale</b> è disponibile un servizio di assistenza e supporto a pagamento") ?>.</p>
							<p><?php echo _("E' disponibile anche un <b>servizio cloud</b> su cui poter installare OpenSTAManager, in modo da non doverti più preoccupare di backup e gestione dei dati") ?>.</p>

							<p><?php echo _("Tutte le informazioni su servizi e prezzi le potete trovare qui") ?>:</p>
							<p><a href="http://www.openstamanager.com/per-le-aziende/" class="btn btn-lg btn-success" target="_blank"><i class="fa fa-thumbs-up"></i> <?php echo _("Ottieni supporto professionale") ?></a></p>
						</div>
					</div>
				</div>
			</div>

<?php
}
?>
	</div>
</div>

<?php
if( file_exists($docroot."/include/custom/bottom.php") ){
	include($docroot."/include/custom/bottom.php");
}
else{
	include($docroot."/include/bottom.php");
}
?>

