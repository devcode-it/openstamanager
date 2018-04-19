<?php
	if( $docroot == '' ){
		die( _("Accesso negato!") );
	}

	include_once("../../core.php");
	include_once($docroot."/config.inc.php");
	include_once($docroot."/lib/user_check.php");
	include_once($docroot."/lib/permissions_check.php");
	

	switch( post('op') ){		

		case "update":
			if( $permessi[$module_name] == 'rw' ){

				$id_record = $_POST['id_record'];
				$descrizione = save($_POST['descrizione']);
				
				
				//Verifico che il nome non sia duplicato
				$q = "SELECT descrizione FROM zz_documenti_categorie WHERE descrizione=\"".$descrizione."\" AND deleted = 0 ";
				$rs = $dbo->fetchArray($q);
					
				if( sizeof($rs)>0 ){
					array_push( $_SESSION['errors'], "Categoria '".$descrizione."' già esistente!" );
				}

				//Nome ok
				else{
					
					$query = "UPDATE zz_documenti_categorie SET descrizione=\"$descrizione\" WHERE id = $id_record";
					$rs = $dbo->query( $query );
					array_push( $_SESSION['infos'], "Informazioni salvate correttamente!" );

				}

			}
			break;

		case "add":
			if( $permessi[$module_name] == 'rw' ){
				
				$descrizione = save( $_POST['descrizione'] );
			
				if( isset( $_POST['descrizione'] ) ){
					//Verifico che il nome non sia duplicato
					$q = "SELECT descrizione FROM zz_documenti_categorie WHERE descrizione=\"".$descrizione."\" AND deleted = 0 ";
					$rs = $dbo->fetchArray($q);
					
					if( sizeof($rs)>0 ){
						array_push( $_SESSION['errors'], "Categoria '".$descrizione."' già esistente!" );
					}else{
						$query = "INSERT INTO zz_documenti_categorie(descrizione) VALUES (\"$descrizione\")";
						$rs = $dbo->query($query);
						$id_record = $dbo->last_inserted_id();
						array_push( $_SESSION['infos'], "Nuova categoria documenti aggiunta!" );
					}
				}
			}
			break;

		case "delete":
			if( $permessi[$module_name] == 'rw' ){

				//$query="DELETE FROM zz_documenti_categorie WHERE id = \"$id_record\"";
				//$rs = $dbo->query($query);
				
				$dbo->query( "UPDATE zz_documenti_categorie SET deleted=1 WHERE id = $id_record");
				
					
					
				array_push( $_SESSION['infos'], "Categoria docimenti eliminata!" );
			}
			break;

	}


?>
