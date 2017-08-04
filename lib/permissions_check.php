<?php

trigger_error(_('Procedura deprecata!'), E_USER_DEPRECATED);

	/*
		Controllo permessi modulo su utente (se non è is_admin)
	*/

	if( $module_name == '' ){
		$rs = $dbo->fetchArray("SELECT name FROM zz_modules WHERE id='".$html->form('id_module')."'");
		$module_name = $rs[0]['name'];
	}


	//Il modulo "Info" è visibile da tutti
	if( $module_name == 'Info' ){
		$permessi[$module_name] = 'r';
	}

	else{
		$rs = $dbo->fetchArray("SELECT idanagrafica FROM zz_users WHERE idutente='".$_SESSION['idutente']."'");
		$user_idanagrafica = $rs[0]['idanagrafica'];

		$query = "SELECT *, (SELECT idanagrafica FROM zz_users WHERE idutente='".$_SESSION['idutente']."') AS idanagrafica FROM zz_permissions WHERE idgruppo=(SELECT idgruppo FROM zz_users WHERE idutente='".$_SESSION['idutente']."') AND idmodule=(SELECT id FROM zz_modules WHERE name='".$module_name."')";
		$rs = $dbo->fetchArray($query);


		if( sizeof($rs)<=0 ){
			//Ultimo tentativo: se non ci sono i permessi ma sono l'amministratore posso comunque leggere il modulo
			if( $_SESSION['is_admin']==1 ){
				$permessi[$module_name] = 'rw';
			}
			else{
				echo "<div style='clear:both;'></div><br/><br/><br/><br/><br/><p>Non hai i permessi per accedere a questo modulo.</p>\n";
				$permessi[$module_name] = '-';
				exit;
			}
		}
		else{
			if( $rs[0]['permessi']=='-' ){
				echo "<div style='clear:both;'></div><br/><br/><br/><br/><br/><p>Non hai i permessi per accedere a questo modulo.</p>\n";
				$permessi[$module_name] = '-';
				exit;
			}
			else if( $rs[0]['permessi']=='r' ){
				$permessi[$module_name] = 'r';
			}
			else if( $rs[0]['permessi']=='rw' ){
				$permessi[$module_name] = 'rw';
			}
		}


		//Carico i filtri dei WHERE in base al modulo e all'utente loggato
		$qp = "SELECT *, (SELECT idanagrafica FROM zz_users WHERE idutente='".$_SESSION['idutente']."') AS idanagrafica, (SELECT name FROM zz_modules WHERE id=idmodule) AS nome_modulo FROM zz_group_module WHERE idgruppo=(SELECT idgruppo FROM zz_users WHERE idutente='".$_SESSION['idutente']."')";
		$rsp = $dbo->fetchArray($qp);
		for( $i=0; $i<sizeof($rsp); $i++ ){
			$additional_where[ $rsp[$i]['nome_modulo'] ] = $rsp[$i]['clause'];
		}
	}
?>
