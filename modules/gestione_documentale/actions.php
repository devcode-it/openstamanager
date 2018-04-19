<?php
	if( $docroot == '' ){
		die( _("Accesso negato!") );
	}

	switch( post('op') ){
		
		case "add":
		
		
		$nome = save( $_POST['nome'] );
		$idcategoria = save( $_POST['idcategoria'] );
		$data = saveDate( $_POST['data'] );
		
		
		$dir_ok = true;
		$nome_modulo = 'Gestione documentale';
		$nome_allegato = save( $_POST['nome_allegato'] );
		$filename = $_FILES['blob']['name'];
		$src = $_FILES['blob']['tmp_name'];
		$f = pathinfo( $filename );
		
		//$q="SELECT idanagrafica FROM zz_utenti WHERE idutente=".$_SESSION['idutente'];
		//$rs=$dbo->fetchArray($q);
		//$idutente=$rs[0]['idanagrafica'];

		$dst_file = sanitizeFilename( $f['filename'].".".$f['extension'] );
		$dst_dir = $docroot."/files/gestione_documentale/";
		$dst_dir = strtolower($dst_dir);
		
		
		//Rinomino il file se non esiste già
		$f = pathinfo($dst_file);
		$i = 1;
		
		while( file_exists($dst_dir."/".$dst_file) ){
			$dst_file = sanitizeFilename($f['filename']."_".$i.".".$f['extension']);
			$i++;
		}

		//Se la destinazione non esiste la creo
		if( !is_dir($dst_dir) ){
			if( !mkdir($dst_dir) ){
				$dir_ok = false;
				array_push( $_SESSION['errors'], "Non hai i permessi per creare directory!" );
				header( "Location: ".$rootdir."/controller.php?id_module=".Modules::get('Gestione documentale')['id'] );
				exit;
			}
		}

		//Creazione file fisico
		if( $dir_ok ){
			if( move_uploaded_file( $src, $dst_dir."/".$dst_file) ){
				
				
				
				$rs = $dbo->query("INSERT INTO `zz_documenti`( nome, idcategoria, data ) VALUES( '".$nome."','".$idcategoria."','".$data."' )");
				
				
				$id_record = $dbo->last_inserted_id();
				
				
				$rs = $dbo->query("INSERT INTO `zz_files`( nome, filename, id_module, id_record ) VALUES( \"".$nome_allegato."\", \"".$dst_file."\", \"".Modules::get('Gestione documentale')['id']."\", \"".$id_record."\" )");
					
					
				array_push( $_SESSION['infos'], "File caricato correttamente!" );
				
				//header( "Location: ".$rootdir."/controller.php?id_module=".$modules_info[$modulo_permessi]['id'] );
				//exit;
			}

			else{
				array_push( $_SESSION['errors'], "Errore durante il caricamento del file!" );
				header( "Location: ".$rootdir."/controller.php?id_module=".Modules::get('Gestione documentale')['id'] );
				exit;
			}
		}
		break;

		case "update":
	
			if( $permessi[$module_name] == 'rw' ){

				//leggo tutti i valori passati dal POST e li salvo in un array
				$html_post = array();
				foreach ($_POST as $key => $value) {
					$html_post[$key] = save($value);
				}

				if( isset($_POST['id_record']) ){

					$query = "UPDATE zz_documenti SET ".
				 						"idcategoria=\"".$html_post['idcategoria']."\",".
				 						"nome=\"".$html_post['nome']."\",".
										"data=\"".saveDate($html_post['data'])."\"".
										"WHERE id = '$id_record' ".$additional_where['Gestione documenti'];

					$rs = $dbo->query( $query );

					array_push( $_SESSION['infos'], "Informazioni per la scheda ''".$html_post['id']."'' salvate correttamente!");
					
				}
				
			}
					
					
		break;
		
		
		case "delete":
			if( $permessi[$module_name] == 'rw' ){
				
				$rs =  $dbo->fetchArray("SELECT id FROM zz_files WHERE externalid = \"".$id_record."\" AND module = '".$module_name."' " );
				$n = sizeof($rs);

				//Per tutte le sessioni di lavoro trovate
				for($i=0; $i<$n; $i++){
					
					//Elimino fisicamente il file...
					$rs2 = $dbo->fetchArray("SELECT filename FROM zz_files WHERE id = \"".$rs[$i]['id']."\" ");
					unlink( $docroot."/files/".strtolower($module_name)."/".$rs2[0]['filename'] );
					//array_push( $_SESSION['warnings'], $rs2[0]['filename']." eliminato!");
				
				}
				
				//...e da db
				$dbo->query("DELETE FROM zz_files WHERE externalid = \"".$id_record."\" AND module = '".$module_name."' ");
				$dbo->query("DELETE FROM zz_documenti WHERE id = \"".$id_record."\" ");
		
				array_push( $_SESSION['infos'], "Scheda e relativi files eliminati!" );
				
			}

			break;
	}
?>
