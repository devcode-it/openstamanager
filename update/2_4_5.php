<?php
	//Fix per province caricate a gestionale in minuscolo 
	$dbo->query("UPDATE an_anagrafiche SET provincia=UPPER(provincia)");
	$dbo->query("UPDATE an_sedi SET provincia=UPPER(provincia)");
?>
