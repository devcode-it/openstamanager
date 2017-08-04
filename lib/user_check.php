<?php

trigger_error(_('Procedura deprecata!'), E_USER_DEPRECATED);

	if ( isset ($_GET['op'])  )
		$op = $_GET['op'];
	else
		$op = '';

	if( $op == 'logout' )
		$_SESSION['idutente']='';

	if( $_SESSION['idutente'] == '' ){
		redirect($rootdir."/index.php","php");
		exit;
	}
?>
