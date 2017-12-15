<?php
	if( $docroot == '' ){
		die( _("Accesso negato!") );
	}
	$records = $dbo->fetchArray("SELECT * FROM co_sezionali WHERE id='$id_record'");
?>
