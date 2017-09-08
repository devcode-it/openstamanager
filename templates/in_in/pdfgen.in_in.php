<?php
	$module_name = "Interventi";

    include_once( $docroot."/modules/interventi/modutil.php" );
	$additional_where['Interventi'] = str_replace( "|idtecnico|", "'".$user_idanagrafica."'", $additional_where['Interventi'] );
	$additional_where['Interventi'] = str_replace( "|idanagrafica|", "'".$user_idanagrafica."'", $additional_where['Interventi'] );


	#############mostro o nascondo i costi dell'intervento..#################
	//true o false
	$visualizza_costi = get_var("Visualizza i costi sulle stampe degli interventi");
	########################################################################

	//carica intervento
	//TIME_TO_SEC(TIMEDIFF(ora_al,ora_dal)) AS `totale_tempo`,
	$idintervento = save($_GET['idintervento']);
	$query = "SELECT *, (SELECT numero FROM co_contratti WHERE id=(SELECT idcontratto FROM co_righe_contratti WHERE idintervento=in_interventi.id)) AS numero_contratto, (SELECT numero FROM co_preventivi WHERE id=(SELECT idpreventivo FROM co_preventivi_interventi WHERE idintervento=in_interventi.id)) AS numero_preventivo, (SELECT SUM(prezzo_dirittochiamata) FROM in_interventi_tecnici GROUP BY idintervento HAVING idintervento=in_interventi.id) AS `tot_dirittochiamata`, (SELECT SUM(km) FROM in_interventi_tecnici GROUP BY idintervento HAVING idintervento=in_interventi.id) AS `tot_km`, (SELECT SUM(prezzo_ore_consuntivo) FROM in_interventi_tecnici GROUP BY idintervento HAVING idintervento=in_interventi.id) AS `tot_ore_consuntivo`, (SELECT SUM(prezzo_km_consuntivo) FROM in_interventi_tecnici GROUP BY idintervento HAVING idintervento=in_interventi.id) AS `tot_km_consuntivo`, in_interventi.descrizione AS `descrizione_intervento`, richiesta FROM in_interventi INNER JOIN in_tipiintervento ON in_interventi.idtipointervento=in_tipiintervento.idtipointervento WHERE id=\"$idintervento\" ".$additional_where['Interventi'];
	$records = $dbo->fetchArray( $query );
	$idcliente = $records[0]['idanagrafica'];
	$idsede = $records[0]['idsede'];
	$str_cash = 0.00;

	//carica report html
	$report = file_get_contents ($docroot."/templates/in_in/intervento.html");
	$body = file_get_contents ($docroot."/templates/in_in/intervento_body.html");

	include_once( __DIR__."/../pdfgen_variables.php" );



	/*
		Dati intervento
	*/
	$body .= "<table class=\"table_values\" cellspacing=\"0\" border=\"0\" cellpadding=\"0\" style=\"table-layout:fixed; border-color:#aaa;\">\n";
	$body .= "<col width=\"167.5\"><col width=\"167.5\"><col width=\"167.5\"><col width=\"167.5\">\n";

	$body .= "<tr>\n";
	$body .= "<td align=\"center\" colspan=\"4\" valign=\"middle\" style=\"height:5mm; font-size:14pt;\" bgcolor=\"#dddddd\"><b>RAPPORTO OPERAZIONI E INTERVENTI</b></td>\n";
	$body .= "</tr>\n";

	$body .= "<tr>\n";
	$body .= "<td align=\"center\">Intervento numero: <b>".$records[0]['idintervento']."</b></td>\n";
	$body .= "<td align=\"center\">Data: <b>".date( "d/m/Y", strtotime($records[0]['data_richiesta']) )."</b></td>\n";
	$body .= "<td align=\"center\">Preventivo N<sup>o</sup>: <b>".$records[0]['numero_preventivo']."</b></td>\n";
	$body .= "<td align=\"center\">Contratto N<sup>o</sup>: <b>".$records[0]['numero_contratto']."</b></td>\n";
	$body .= "</tr>\n";

	$body .= "</table>\n";



	//dati cliente
	$body .= "<table class=\"table_values\" cellspacing=\"0\" border=\"0\" cellpadding=\"0\" style=\"table-layout:fixed; border-color:#aaa;\">\n";
	$body .= "<col width=\"543\"><col width=\"167\">\n";

	//riga 1
	$body .= "<tr>\n";
	$body .= "<td align=\"left\">";
	$body .= "Cliente: <b>".$c_ragionesociale."</b>\n";
	$body .= "</td>";

	//Codice fiscale
	$body .= "<td align=\"left\">";
	$body .= "P.iva: <b>".strtoupper($c_piva)."</b>\n";
	$body .= "</td>";

	$body .= "</tr>\n";

	//riga 2
	$body .= "<tr>\n";
	$body .= "	<td colspan=\"2\">";
	$body .= "		Via: <b>".$c_indirizzo."</b> - \n";
	$body .= "		Cap: <b>".$c_cap."</b> - \n";
	$body .= "		Comune: <b>".$c_citta." (".strtoupper ($c_provincia).")</b>\n";
	$body .= "	</td>\n";
	$body .= "</tr>\n";

	$body .= "<tr>\n";
	$body .= "	<td colspan=\"2\">";
	$body .= "		Telefono: <b>".$c_telefono."</b>\n";
	if( $c_cellulare!='' ) $body .= " - Cellulare: <b>".$c_cellulare."</b>\n";
	$body .= "	</td>\n";
	$body .= "</tr>\n";


	//riga 3
	//Elenco impianti su cui è stato fatto l'intervento
	$rs2 = $dbo->fetchArray("SELECT *, (SELECT nome FROM my_impianti WHERE id=my_impianti_interventi.idimpianto) AS nome FROM my_impianti_interventi WHERE idintervento=\"".$idintervento."\"");
	$impianti = array();
	for( $j=0; $j<sizeof($rs2); $j++ ){
		if( $rs2[$j]['nome']!='' )
			array_push( $impianti, "<b>".$rs2[$j]['nome']."</b> <small style='color:#777;'>(".$rs2[$j]['idimpianto'].")</small>" );
	}

	$body .= "<tr><td align=\"left\" colspan=\"4\">";
	$body .= "Impianti: ".implode( ', ', $impianti )."\n";
	$body .= "</td>";

	$body .= "</tr>\n";
	$body .= "</table>\n";


	if ($records[0]['richiesta']!=""){
		//Richiesta
		$body .= "<table class=\"table_values\" cellspacing=\"0\" border=\"0\" cellpadding=\"0\" style=\"table-layout:fixed; border-color:#aaa;\">\n";
		$body .= "<col width=\"730\">\n";
		$body .= "<tr><td align=\"left\"  valign=\"top\" style=\"border-top:0px solid #fff; border-bottom:0px solid #fff; font-size:8pt;\"><b>Richiesta:</b></td></tr>\n";
		$body .= "<tr><td valign=\"top\" align=\"left\" style=\"height:5mm;\">".str_replace( "\n", "<br/>", $records[0]['richiesta'] )."</td></tr>\n";
	}

	if ($records[0]['descrizione_intervento']!=""){

		//descrizione
		$body .= "<tr><td align=\"left\"  valign=\"top\" style=\"border-top:0px solid #fff; border-bottom:0px solid #fff; font-size:8pt;\"><b>Descrizione:</b></td></tr>\n";
		$body .= "<tr><td valign=\"top\" align=\"left\" style=\"height:5mm;\">".str_replace( "\n", "<br/>", $records[0]['descrizione_intervento'] )."</td></tr>\n";

	}

	$body .= "</table>\n";

	$body .= "<table class=\"table_values\" cellspacing=\"0\" border=\"0\" cellpadding=\"0\" style=\"table-layout:fixed; border-color:#aaa;\">\n";



	//Conteggio prezzi
	$costo_orario = $records[0]['costo_orario'];
	$totale_ore_consuntivo = $records[0]['tot_ore_consuntivo'] - $records[0]['tot_dirittochiamata'];
	$totale_km_consuntivo = $records[0]['tot_km_consuntivo'];
	$totale_dirittochiamata = $records[0]['tot_dirittochiamata'];
	$totale_intervento = $totale_ore_consuntivo + $totale_km_consuntivo + $totale_dirittochiamata;

	//visualizzo costi?
	if(( $totale_intervento != 0.00 ) and ($visualizza_costi==true)){
		$colspan = 2;
		//$body .= "<tr>\n";
		if( $totale_ore_consuntivo != 0 ){
			//$body .= "<td align=\"right\" >Costi ore lavorate: <b>".number_format( $totale_ore_consuntivo+$totale_dirittochiamata, 2, ",", "" )." &euro;</b></td>\n";
			$colspan--;
		}

		if( $totale_km_consuntivo != 0 ){
			//$body .= "<td align=\"right\">Costi km: <b>".number_format( $totale_km_consuntivo, 2, ",", "" )." &euro;</b></td>\n";
			$colspan--;
		}

		//$body .= "<td align=\"right\" colspan=\"".$colspan."\" bgcolor=\"#dddddd\"> Costi totali: <b>".number_format($totale_intervento, 2, ",", "." )." &euro;</b></td></tr>\n";
		//$body .= "<br/><br/>\n";
	}


	$body .= "</table>\n";



	//MATERIALE UTILIZZATO
	//Conteggio articoli utilizzati
	$query = "SELECT *, (SELECT codice FROM mg_articoli WHERE id=idarticolo) AS codice_art, '' AS codice, SUM(qta) AS sumqta FROM `mg_articoli_interventi` GROUP BY idarticolo, idintervento HAVING idintervento=\"".$idintervento."\" AND NOT idarticolo='0' ORDER BY idarticolo ASC";
	$rs2 = $dbo->fetchArray($query);
	if( sizeof($rs2)>0 ){
		$body .= "<br/><table class=\"table_values\" cellspacing=\"0\" border=\"0\" cellpadding=\"0\" style=\"table-layout:fixed; border-color:#aaa;\">\n";
		$body .= "<col width=\"100\"><col width=\"390\"><col width=\"50\"><col width=\"130\">\n";
		$body .= "<tr><td align=\"center\" colspan=\"4\" valign=\"middle\" style=\"font-size:11pt;\" bgcolor=\"#cccccc\"><b>MATERIALE UTILIZZATO</b></td></tr>\n";

		$body .= "<tr><td style=\"font-size:8pt;\" align=\"center\" bgcolor='#dedede'>\n";
		$body .= "<b>Codifica</b>\n";
		$body .= "</td>\n";

		$body .= "<td style=\"font-size:8pt;\" align=\"center\" bgcolor='#dedede'>\n";
		$body .= "<b>Descrizione</b>\n";
		$body .= "</td>\n";

		$body .= "<td style=\"font-size:8pt;\" align=\"center\" bgcolor='#dedede'>\n";
		$body .= "<b>Q.t&agrave;</b>\n";
		$body .= "</td>\n";

		$body .= "<td style=\"font-size:8pt;\" align=\"center\" bgcolor='#dedede'>\n";
		$body .= "<b>Prezzo unitario</b>\n";
		$body .= "</td></tr>\n";


		$totale_articoli = 0.00;

		for( $i=0; $i<sizeof($rs2); $i++ ){
			$body .= "<tr>\n";


			//Codifica
			$body .= "<td class='first_cell' valign='top'>\n";
			$body .= "<span>".$rs2[$i]['codice_art']."</span>\n";
			$body .= "</td>\n";


			//Descrizione
			$body .= "<td class='first_cell' valign='top'>\n";
			$body .= "<span>".$rs2[$i]['descrizione']."</span>\n";
			if( $rs2[$i]['codice']!='' && $rs2[$i]['codice']!='Lotto: , SN: , Altro: ' ){ $body .= "<br/><small>".$rs2[$i]['codice']."</small>\n"; }
			$body .= "</td>\n";


			//Quantità
			$qta = $rs2[$i]['sumqta'];
			$body .= "<td class='table_cell' align='center' valign='top'>\n";
			$body .= "<span>".$rs2[$i]['sumqta']."</span>\n";
			$body .= "</td>\n";


			//Prezzo unitario
			$netto = $rs2[$i]['prezzo_vendita'];
			$netto = $netto + $netto/100*$rs2[$i]['prc_guadagno'];
			$iva = $netto/100*$rs2[$i]['prciva_vendita'];


			$body .= "<td class='table_cell' align='center' valign='top'>\n";

			if ($visualizza_costi==true){
				$body .= "<span>".number_format( $netto, 2, ",", "" )." &euro;</span>\n";
			}else{

				$body .= "<span> - </span>\n";
			}

			$body .= "</td>\n";


			//Totale
			$totale_articoli += $netto*$qta;

			$body .= "</tr>\n";
		}


		//TOTALE MATERIALE UTILIZZATO
		if ($visualizza_costi==true){
			//Totale spesa articoli
			$body .= "<tr><td colspan=\"2\" align=\"right\">\n";
			$body .= "<b>TOTALE MATERIALE UTILIZZATO:</b>\n";
			$body .= "</td>\n";

			$body .= "<td align=\"center\" colspan=\"2\" bgcolor=\"#dddddd\">\n";
			$body .= "<b>".number_format( $totale_articoli, 2, ",", ".")." &euro;</b>\n";
			$body .= "</td></tr>\n";
		}

		$body .= "</table>\n";
	}

	//FINE MATERIALE UTILIZZATO





	//Conteggio SPESE AGGIUNTIVE
	$query = "SELECT * FROM in_righe_interventi WHERE idintervento='".$idintervento."' ORDER BY id ASC";
	$rs2 = $dbo->fetchArray($query);
	if( sizeof($rs2)>0 ){
		$body .= "<br/><table class=\"table_values\" cellspacing=\"0\" cellpadding=\"0\" style=\"table-layout:fixed; border-color:#aaa;\">\n";
		$body .= "<col width=\"440\"><col width=\"50\"><col width=\"50\"><col width=\"130\">\n";
		$body .= "<tr><td align=\"center\" colspan=\"4\" valign=\"middle\" style=\"font-size:11pt;\" bgcolor=\"#cccccc\"><b>SPESE AGGIUNTIVE</b></td></tr>\n";

		$body .= "<tr><td align=\"center\" style=\"font-size:8pt;\" bgcolor='#dedede'>\n";
		$body .= "<b>Descrizione</b>\n";
		$body .= "</td>\n";

		$body .= "<td style=\"font-size:8pt;\" align=\"center\" bgcolor='#dedede'>\n";
		$body .= "<b>Q.t&agrave;</b>\n";
		$body .= "</td>\n";

		$body .= "<td style=\"font-size:8pt;\" align=\"center\" bgcolor='#dedede'>\n";
		$body .= "<b>Prezzo unitario</b>\n";
		$body .= "</td>\n";

		$body .= "<td style=\"font-size:8pt;\" align=\"center\" bgcolor='#dedede'>\n";
		$body .= "<b>Subtot</b>\n";
		$body .= "</td></tr>\n";


		$totale_righe = 0.00;

		for( $i=0; $i<sizeof($rs2); $i++ ){
			//Articolo
			$body .= "<tr><td class='first_cell'>\n";
			$body .= "<span>".$rs2[$i]['descrizione']."</span>\n";
			$body .= "</td>\n";


			//Quantità
			$body .= "<td class='table_cell' align='center'>\n";
			$body .= "<span>".number_format( $rs2[$i]['qta'], 2, ".", "" )."</span>\n";
			$body .= "</td>\n";


			//Prezzo unitario
			$body .= "<td class='table_cell' align='center'>\n";
			$netto = $rs2[$i]['prezzo'];
			$iva = $rs2[$i]['prezzo'];
			if ($visualizza_costi==true){
				$body .= "<span>".number_format( $netto, 2, ",", "" )." &euro;</span>\n";
			}else{
				$body .= "<span> - </span>\n";
			}
			$body .= "</td>\n";


			//Subtot
			$body .= "<td class='table_cell' align='center'>\n";
			$subtot = $rs2[$i]['prezzo']*$rs2[$i]['qta'];
			if ($visualizza_costi==true){
				$body .= "<span><span>".number_format( $subtot, 2, ",", "" )."</span> &euro;</span>\n";
			}else{
				$body .= "<span> - </span>\n";
			}
			$body .= "</td></tr>\n";
			$totale_righe += $subtot;
		}


		if ($visualizza_costi==true){
			//Totale spese aggiuntive
			$body .= "<tr><td colspan=\"3\" align=\"right\">\n";
			$body .= "<b>TOTALE SPESE AGGIUNTIVE:</b>\n";
			$body .= "</td>\n";

			$body .= "<td align=\"center\" bgcolor=\"#dddddd\">\n";
			$body .= "<b>".number_format( $totale_righe, 2, ",", ".")." &euro;</b>\n";
			$body .= "</td></tr>\n";
		}


		$body .= "</table>\n";
	}

	//FINE SPESE AGGIUNTIVE

	//ORE TECNICI + FIRMA


	$body .= "<br/><table class=\"table_values\" cellspacing=\"0\" border=\"0\" cellpadding=\"0\" style=\"table-layout:fixed; border-color:#aaa;\">\n";
	$body .= "<col width=\"180\"><col width=\"115\"><col width=\"50\"><col width=\"50\"><col width=\"255\">\n";
	$body .= "<tr><td align=\"center\" colspan=\"6\" valign=\"middle\" style=\"font-size:11pt;\" bgcolor=\"#cccccc\"><b>ORE TECNICI</b></td></tr>\n";

	//INTESTAZIONE ELENCO TECNICI
	$body .= "<tr><td align=\"center\" style=\"font-size:8pt;\" bgcolor='#cccccc'>";
	$body .= "<b>Tecnico</b>";
	$body .= "</td>";

	$body .= "<td align=\"center\" style=\"font-size:8pt;\" bgcolor='#cccccc'>";
	$body .= "<b>Data</b>";
	$body .= "</td>";

	$body .= "<td align=\"center\" style=\"font-size:8pt;\" bgcolor='#cccccc'>";
	$body .= "<b>Dalle</b>";
	$body .= "</td>";

	$body .= "<td align=\"center\" style=\"font-size:8pt;\" bgcolor='#cccccc'>";
	$body .= "<b>Alle</b>";
	$body .= "</td>";

	$body .= "<td align=\"center\" valign=\"middle\" style=\"font-size:6pt;\" >";
	$body .= "I dati del ricevente verrano trattati in base al D.lgs n. 196/2003.";
	$body .= "</td></tr>";



	// sessioni di lavoro dei tecnici
	$qt = "SELECT * FROM in_interventi INNER JOIN (in_interventi_tecnici INNER JOIN an_anagrafiche ON in_interventi_tecnici.idtecnico=an_anagrafiche.idanagrafica) ON in_interventi.id=in_interventi_tecnici.idintervento WHERE in_interventi.id='$idintervento' ORDER BY in_interventi_tecnici.orario_inizio ";
	$nt = $dbo->fetchNum( $qt );
	$rst = $dbo->fetchArray( $qt );


	for( $t=0; $t<$nt; $t++ ){

		//nome tecnico
		$body .= "<tr><td style=\"height:5mm;\" align=\"left\">\n";
		$body .= "".$rst[$t]['ragione_sociale']."";
		$body .= "</td>";

		//data
		$body .= "<td align=\"center\">";
		if ($rst[$t]['orario_inizio']!='00:00:00'){
			$body .= "".readDateTimePrint($rst[$t]['orario_inizio'], 'date')."";
		}else{
			$body .= " - ";
		}
		$body .= "</td>";

		//ora inizio
		$body .= "<td align=\"center\">";
		if ($rst[$t]['orario_inizio']!='00:00:00'){
			$body .= "".readDateTimePrint($rst[$t]['orario_inizio'], 'time')."";
		}else{
			$body .= " - ";
		}
		$body .= "</td>";

		//ora fine
		$body .= "<td align=\"center\">";
		if ($rst[$t]['orario_fine']!='00:00:00'){
			$body .= "".readDateTimePrint($rst[$t]['orario_fine'], 'time')."";
		}else{
			$body .= " - ";
		}
		$body .= "</td>";

		if ($t==0){
			$body .= "<td align=\"center\" valign=\"middle\" rowspan=\"1\" style=\"font-size:8pt;\" >";
			$body .= "<b>Si dichiara che i lavori sono stati eseguiti<br/> ed i materiali installati.</b>";
			$body .= "</td>";
		}
		else{
			$body .= "<td style=\"border-bottom:0px;border-top:0px;\" >";
			$body .= "</td>";
		}
		$body .= "</tr>";
	}






	//ore lavorate
	if( $visualizza_costi ){
		$q_interventi = "SELECT * FROM in_interventi_tecnici WHERE idintervento = '".$idintervento."' ";
		$rs2 = $dbo->fetchArray( $q_interventi );
		$n2 = sizeof($rs2);

		for($i=0; $i<$n2; $i++){
			$tt = get_ore_intervento( $rs2[$i]['idintervento'] );
			$tt = floatval (round($tt,2));
		}

		$body .= "<tr><td align=\"center\" colspan=\"1\">Ore lavorate:<br/><b>".number_format( $tt, 2, ',', '.' )."</b></td>\n";


		//costo orario
		$body .= "<td align=\"center\" colspan=\"1\">Costo orario:<br/><b>".number_format( ($totale_ore_consuntivo/$tt), 2, ",", "." )."</b>";

		if ($totale_dirittochiamata!=0){

			$body .= "<small><small > + ".number_format($totale_dirittochiamata, 2, ",", "." )." d.c.</small></small>";
		}

		$body .= "</td>\n";


		//costo totale manodopera
		$body .= "<td align=\"center\" colspan=\"2\">Manodopera:<br/><b>".number_format($totale_intervento, 2, ",", "." )."</b>";
		$body .= "</td>\n";
	}

	else{
		$body .= "<tr><td colspan='4'></td>\n";
	}



	// timbro e firma
	if ($records[0]['firma_file']!=""):
		$body .= "<td align=\"center\"  rowspan=\"".($nt-$n2)."\" style=\"height:20mm; font-size:6pt;border-top:0px;\" valign=\"bottom\" >";
		$body .= "	<img width='260' src=".$docroot."/files/interventi/".$records[0]['firma_file'].">\n";
	else:
		$body .= "	<td align=\"center\" valign=\"bottom\" style=\"border:1px solid #888; height:30mm; font-size:8pt;\">";
		$body .= "		<i>(Timbro e firma leggibile.)</i>";
	endif;
	$body .= "</td>";
	$body .= "</tr>\n";






	//IMPONIBILE
	if( $visualizza_costi ){
		//Totale intervento
		$body .= "<tr><td colspan=\"4\" valign=\"middle\" align=\"right\">\n";
		$body .= "<big><b>IMPONIBILE:</b></big>\n";
		$body .= "</td>\n";

		$body .= "<td align=\"center\" colspan=\"1\" bgcolor=\"#cccccc\">\n";
		$body .= "<b>".number_format( $totale_articoli + $totale_intervento + $totale_righe, 2, ",", ".")." &euro;</b>\n";
		$body .= "</td></tr>\n";

		//Leggo iva da applicare
		$q = "SELECT * FROM co_iva INNER JOIN zz_impostazioni WHERE co_iva.id = zz_impostazioni.valore AND zz_impostazioni.nome = 'Iva predefinita' ";
		$records = $dbo->fetchArray($q);
		$percentuale_iva = $records[0]['percentuale'];



		//IVA
		//Totale intervento
		$body .= "<tr><td colspan=\"4\" valign=\"middle\" align=\"right\">\n";
		$body .= "<big><b>IVA (".number_format($percentuale_iva,0)."%):</b></big>\n";
		$body .= "</td>\n";

		$body .= "<td align=\"center\" colspan=\"1\" bgcolor=\"#cccccc\">\n";
		$body .= "<b>".number_format( (($totale_articoli + $totale_intervento + $totale_righe)/100*$percentuale_iva), 2, ",", ".")." &euro;</b>\n";
		$body .= "</td></tr>\n";


		//TOTALE INTERVENTO
		$body .= "<tr><td colspan=\"4\" valign=\"middle\" style=\"\" align=\"right\">\n";
		$body .= "<big><b>TOTALE INTERVENTO:</b></big>\n";
		$body .= "</td>\n";

		$body .= "<td align=\"center\" colspan=\"1\" bgcolor=\"#cccccc\">\n";
		$body .= "<b>".number_format( (($totale_articoli + $totale_intervento + $totale_righe)/100*$percentuale_iva)+$totale_articoli + $totale_intervento + $totale_righe, 2, ",", ".")." &euro;</b>\n";
		$body .= "</td></tr>\n";
	}

	$body .= "</table>";


	$report_name = "intervento_".$idintervento.".pdf";
?>
