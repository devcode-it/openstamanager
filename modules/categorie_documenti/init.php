<?php
	if( $docroot == '' ){
		die( _("Accesso negato!") );
	}
	
	$records = $dbo->fetchArray("SELECT *, (SELECT COUNT(id) FROM zz_documenti WHERE idcategoria = '".$id_record."' ) AS doc_associati FROM zz_documenti_categorie WHERE id=\"".$id_record."\"");
?>
