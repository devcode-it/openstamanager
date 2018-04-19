<?php
	if( $docroot == '' ){
		die( _("Accesso negato!") );
	}
	/* LEFT OUTER JOIN  zz_files ON idfile = zz_files.id */
	$records = $dbo->fetchArray("SELECT *, zz_documenti.`id`as id, zz_documenti.nome AS nome, zz_documenti.`data` AS `data` FROM zz_documenti WHERE zz_documenti.id = '".$id_record."' ");
?>
