<?php
	$idddt = save($_GET['idddt']);
	$iva_generica = '';
	$n_rows = 0;
	$words4row = 45;

	//Lettura tipo ddt
	$q = "SELECT n_colli, (SELECT dir FROM dt_tipiddt WHERE id=idtipoddt) AS dir, (SELECT descrizione FROM dt_causalet WHERE id=idcausalet) AS causalet, (SELECT descrizione FROM dt_porto WHERE id=idporto) AS porto, (SELECT descrizione FROM dt_aspettobeni WHERE id=idaspettobeni) AS aspettobeni, (SELECT descrizione FROM dt_spedizione WHERE id=idspedizione) AS spedizione, (SELECT ragione_sociale FROM an_anagrafiche WHERE idanagrafica=idvettore) AS vettore FROM dt_ddt WHERE id=\"".$idddt."\"";
	$rs = $dbo->fetchArray($q);
	$aspettobeni	= $rs[0]['aspettobeni'];
	$causalet		= $rs[0]['causalet'];
	$porto			= $rs[0]['porto'];
	$n_colli		= $rs[0]['n_colli'];
	if($n_colli=="0") $n_colli = "";
	$spedizione		= $rs[0]['spedizione'];
	$vettore		= $rs[0]['vettore'];

	if( $rs[0]['dir']=='entrata' )
		$nome_modulo = "Ddt di vendita";
	else
		$nome_modulo = "Ddt di acquisto";
	include_once( $docroot."/lib/permissions_check.php" );
	include_once( $docroot."/modules/ddt/modutil.php" );

	$additional_where[$nome_modulo] = str_replace( "|idanagrafica|", "'".$user_idanagrafica."'", $additional_where[$nome_modulo] );

	$mostra_prezzi = get_var("Stampa i prezzi sui ddt");
	

	//Lettura info fattura
	$q = "SELECT *, (SELECT descrizione FROM dt_tipiddt WHERE id=idtipoddt) AS tipo_doc, (SELECT dir FROM dt_tipiddt WHERE id=idtipoddt) AS dir FROM dt_ddt WHERE id=\"".$idddt."\" ".$additional_where[$nome_modulo];
	$rs = $dbo->fetchArray($q);
	$tipo_doc = $rs[0]['tipo_doc'];
	$idcliente = $rs[0]['idanagrafica'];
	//$idsede = $rs[0]['idsede'];
	( $rs[0]['numero_esterno']!='' ) ? $numero=$rs[0]['numero_esterno'] : $numero=$rs[0]['numero'];

	if( $rs[0]['numero_esterno']=='' ){
		$numero = "pro-forma ".$numero;
		$tipo_doc = "DDT PRO-FORMA";
	}

	//Lettura righe ddt
	$q2 = "SELECT * FROM dt_righe_ddt INNER JOIN dt_ddt ON dt_righe_ddt.idddt=dt_ddt.id WHERE idddt='$idddt' ".$additional_where[$nome_modulo];
	$righe = $dbo->fetchArray( $q2 );

	//carica report html
	$report = file_get_contents ($docroot."/templates/ddt/ddt.html");
	$body = file_get_contents ($docroot."/templates/ddt/ddt_body.html");

	if( !($idcliente == $user_idanagrafica || $_SESSION['is_admin']) )
		die("Non hai i permessi per questa stampa!");

	include_once( "pdfgen_variables.php" );
	// $body = str_replace( "P.Iva: ".$c_piva, $c_piva, $body );
	// $body = str_replace( "P.Iva/C.F.:  ".$c_piva, $c_piva, $body );

	$body = str_replace( '$tipo_doc$', strtoupper($tipo_doc), $body );
	$body = str_replace( '$numero_doc$', $numero, $body );
	$body = str_replace( '$data$', date( "d/m/Y", strtotime($rs[0]['data']) ), $body );
	$body = str_replace( '$pagamento$', $rs[0]['tipo_pagamento'], $body );
	$body = str_replace( '$c_banca_appoggio$', "&nbsp;", $body );

	if($mostra_prezzi):
		$report = str_replace( '$img_sfondo$', "bg_ddt.jpg", $report );
	else:
		$report = str_replace( '$img_sfondo$', "bg_ddt_noprezzi.jpg", $report );
	endif;

	//Leggo i dati della destinazione (se 0=sede legale, se!=altra sede da leggere da tabella an_sedi)
	$destinazione = '';
	if( $rs[0]['idsede']==0 ){
		
		$destinazione = '';
		
	}	else {
		$queryd = "SELECT (SELECT codice FROM an_anagrafiche WHERE idanagrafica=an_sedi.idanagrafica) AS codice, (SELECT ragione_sociale FROM an_anagrafiche WHERE idanagrafica=an_sedi.idanagrafica) AS ragione_sociale, indirizzo, indirizzo2, cap, citta, provincia, piva, codice_fiscale FROM an_sedi WHERE idanagrafica='".$idcliente."' AND id='".$rs[0]['idsede']."'";
		$rsd = $dbo->fetchArray($queryd);

		if( $rsd[0]['indirizzo']!='' )
			$destinazione .= $rsd[0]['indirizzo']."<br/>\n";
		if( $rsd[0]['indirizzo2']!='' )
			$destinazione .= $rsd[0]['indirizzo2']."<br/>\n";
		if( $rsd[0]['cap']!='' )
			$destinazione .= $rsd[0]['cap']." ";
		if( $rsd[0]['citta']!='' )
			$destinazione .= $rsd[0]['citta'];
		if( $rsd[0]['provincia']!='' )
			$destinazione .= " (".$rsd[0]['provincia'].")\n";
	}

	$body = str_replace( '$c_destinazione$', $destinazione, $body );

	//Campi finali
	$body = str_replace( '$aspettobeni$', $aspettobeni, $body );
	$body = str_replace( '$causalet$', $causalet, $body );
	$body = str_replace( '$porto$', $porto, $body );
	$body = str_replace( '$n_colli$', $n_colli, $body );
	$body = str_replace( '$spedizione$', $spedizione, $body );
	$body = str_replace( '$vettore$', $vettore, $body );

	$v_iva = '';
	$v_totale = '';



	//Intestazione tabella per righe
	$body .= "<table class='table_values' cellspacing='0' style='table-layout:fixed;' id='contents'>\n";
	
	if($mostra_prezzi):
		$body .= "<col width='298'><col width='57'><col width='81'><col width='81'><col width='81'><col width='43'>\n";
	else:
		$body .= "<col width='635'><col width='70'>\n";
	endif;
	
	$body .= "<thead>\n";
	$body .= "<tr>\n";
	$body .= "<th class='b-top b-right b-bottom b-left'><small>DESCRIZIONE</small></th>\n";
	$body .= "<th align='center' class='b-top b-right b-bottom'><small>Q.T&Agrave;</small></th>\n";
	if($mostra_prezzi):
		$body .= "<th align='center' class='b-top b-right b-bottom'><small>PREZZO U.</small></th>\n";
		$body .= "<th align='center' class='b-top b-right b-bottom'><small>IMPORTO</small></th>\n";
		$body .= "<th align='center' class='b-top b-right b-bottom'><small>SCONTO</small></th>\n";
		$body .= "<th align='center' class='b-top b-right b-bottom'><small>IVA</small></th>\n";
	endif;
	$body .= "</tr>\n";
	$body .= "</thead>\n";

	$body .= "<tbody>\n";

	//Mostro le righe del ddt
	$totale_ddt = 0.00;
	$totale_imponibile = 0.00;
	$totale_iva = 0.00;
	$sconto = 0.00;
	$sconto_generico = 0.00;


	/*
		Righe
	*/
	$q_gen = "SELECT *, (SELECT percentuale FROM co_iva WHERE id=idiva) AS perc_iva FROM `dt_righe_ddt` WHERE idddt='$idddt'";
	$rs_gen = $dbo->fetchArray( $q_gen );
	$tot_gen = sizeof($rs_gen);
	$imponibile_gen = 0.0;
	$iva_gen = 0.0;

	if( $tot_gen>0 ){
		for( $i=0; $i<sizeof($rs_gen); $i++ ){
			$descrizione	= $rs_gen[$i]['descrizione'];
			$qta			= $rs_gen[$i]['qta'];
			$subtot			= $rs_gen[$i]['subtotale']/$rs_gen[$i]['qta'];
			$subtotale		= $rs_gen[$i]['subtotale'];
			$sconto			= $rs_gen[$i]['sconto'];
			$iva			= $rs_gen[$i]['iva'];

			//Se c'è un barcode provo a proseguire il loop per accorpare eventuali barcode dello stesso articolo
			if( $rs_gen[$i]['barcode'] != '' ){
				$descrizione .= "\n".$rs_gen[$i]['barcode'];

				while( $rs_gen[$i+1]['idarticolo'] == $rs_gen[$i]['idarticolo'] && $rs_gen[$i+1]['barcode'] != '' ){
					$i++;

					if( $rs_gen[$i]['barcode'] != '' ){
						$descrizione .= ", ".$rs_gen[$i]['barcode'];
					}

					$qta			+= $rs_gen[$i]['qta'];
					$subtot			+= $rs_gen[$i]['subtotale']/$rs_gen[$i]['qta'];
					$subtotale		+= $rs_gen[$i]['subtotale'];
					$sconto			+= $rs_gen[$i]['sconto'];
					$iva			+= $rs_gen[$i]['iva'];
				}
			}

			$descrizione = rtrim( $descrizione, "," );


			//Calcolo quanti a capo ci sono
			$righe = explode( "\n", $descrizione );

			for( $r=0; $r<sizeof($righe); $r++ ){
				if( $r == 0 ){
					$n_rows += ceil( ceil( strlen($righe[$r])/$words4row )*4.1 );
				} else {
					$n_rows += ceil( ceil( strlen($righe[$r])/$words4row )*2.9 );
				}

				$n++;
			}


			if( $rs_gen[$i]['descrizione'] == 'SCONTO' ){
				$sconto_generico = $rs_gen[$i]['subtotale'];
				$iva_gen += $rs_gen[$i]['iva'];
			}

			else{
				$body .= "<tr><td class='' valign='top'>\n";
				$body .= nl2br( $descrizione );

				//Aggiunta riferimento a ordine
				if( $rs_gen[$i]['idordine']!='0' ){
					$rso = $dbo->fetchArray("SELECT numero, numero_esterno, data FROM or_ordini WHERE id=\"".$rs_gen[$i]['idordine']."\"");
					( $rso[0]['numero_esterno']!='' ) ? $numero=$rso[0]['numero_esterno'] : $numero=$rso[0]['numero'];
					$body .= "<br/><small>Rif. ordine n<sup>o</sup>".$numero." del ".date("d/m/Y", strtotime($rso[0]['data']) )."</small>";
				}

				$body .= "</td>\n";

				$body .= "<td class='center' valign='top'>\n";
				$body .= number_format($qta, 2, ",", "")."\n";
				$body .= "</td>\n";

				/*
				$body .= "<td class='center b-right' valign='top'>\n";
				$body .= $rs_gen[$i]['um']."\n";
				$body .= "</td>\n";
				*/

				if($mostra_prezzi):
						$body .= "<td align='right' class='' valign='top'>\n";
						$body .= number_format( $subtotale/$qta, 2, ",", "" )." &euro;\n";
						$body .= "</td>\n";

						//Imponibile
						$body .= "<td align='right' class='' valign='top'>\n";
						$subtot = $subtotale;
						$body .= number_format( $subtot, 2, ",", "." )." &euro;\n";

						/*
						if( $rs_gen[$i]['sconto']>0 ){
							$body .= "<br/>\n<small style='color:#555;'>- sconto ".number_format( $rs_gen[$i]['sconto'], 2, ",", "." )." &euro;</small>\n";
							$tot_gen++;
						}*/

						$body .= "</td>\n";


						//Sconto
						$body .= "<td align='right' class='' valign='top'>\n";
						if( $rs_gen[$i]['scontoperc'] > 0 ){
							$body .= "(".$rs_gen[$i]['scontoperc']."%)	";
						}
						if( $rs_gen[$i]['sconto'] > 0 ){
							$body .= "	".number_format( $rs_gen[$i]['sconto'], 2, ",", "." )." &euro;\n";
						}

						$body .= "</td>\n";


						//Iva
						$body .= "<td align='center' valign='top'>\n";

						if( $rs_gen[$i]['perc_iva'] > 0 ){
							$body .= "	".intval($rs_gen[$i]['perc_iva'])."%\n";
						}

						$body .= "</td>\n";
				endif;
				$body .= "</tr>\n";



				$imponibile_gen += $subtotale;
				$iva_gen += $iva;
				$sconto += $sconto;

				if( $rs_gen[$i]['perc_iva'] > 0 ){
					$iva_generica = $rs_gen[$i]['perc_iva'];
				}
			}
		}
		$imponibile_ddt += $imponibile_gen;
		$totale_iva += $iva_gen;
		$totale_ddt += $imponibile_gen;
	}


	$body .= "</tbody>\n";
	$body .= "</table><br/>\n";



	/*
		NOTE
	*/
	/*	
	$body .= "<table style='margin-left:-0.5mm;'>\n";
	$body .= "	<tr><td style='width:193.5mm; height:20mm; border:1px solid #aaa;' valign='top'>\n";
	$body .= "		<small><b>NOTE</b></small><br/>\n";
	$body .= "		<div style='padding:3mm;'>\n";
	$body .= "			".nl2br( $rs[0]['note'] )."\n";
	$body .= "		</div>\n";
	$body .= "	</td></tr>\n";
	$body .= "</table>\n";
	*/

	$imponibile_ddt -= $sconto;
	$totale_ddt = $totale_ddt - $sconto + $totale_iva;



	/*
		SCADENZE  |  TOTALI
	*/
	//TABELLA PRINCIPALE

if($mostra_prezzi):
	//Riga 1
	$footer  = "	<tr><td rowspan='7' style='width:159mm;' valign='top' class='b-right'>\n";

	$footer .= "		<small><b>NOTE</b></small><br/>\n";
	$footer .= "		".nl2br( $rs[0]['note'] )."\n";	

	$footer .= "	</td>\n";
	$footer .= "	<td style='width:33mm;' valign='top' class='b-bottom'>\n";
	$footer .= "		<small><small><b>TOTALE IMPONIBILE</b></small></small>\n";
	$footer .= "	</td></tr>\n";

	//Dati riga 1
	$footer .= "	<tr>\n";

	$footer .= "	<td valign='top' style='text-align:right;' class='b-bottom cell-padded'>\n";
	$footer .= "		".number_format( $imponibile_ddt, 2, ",", "." )." &euro;\n";
	$footer .= "	</td></tr>\n";

	//Riga 2
	$footer .= "	<tr><td style='width:33mm;' valign='top' class='b-bottom'>\n";
	$footer .= "		<small><small><b>TOTALE IMPOSTE</b></small></small>\n";
	$footer .= "	</td></tr>\n";

	$footer .= "	<tr><td valign='top' style='text-align:right;' class='b-bottom cell-padded'>\n";
	$footer .= "		".number_format( $totale_iva, 2, ",", "." )." &euro;\n";
	$footer .= "	</td></tr>\n";

	//Riga 3
	$footer .= "	<tr><td valign='top' class='b-bottom'>\n";
	$footer .= "		<small><small><b>TOTALE DOCUMENTO</b></small></small>\n";
	$footer .= "	</td></tr>\n";

	$footer .= "	<tr><td valign='top'  class='b-bottom cell-padded' style='border-bottom:none;text-align:right;'>\n";
	$footer .= "		".number_format( $totale_ddt, 2, ",", "." )." &euro;\n";
	$footer .= "	</td></tr>\n";
	
	//Riga 4 (opzionale, solo se c'è la ritenuta d'acconto)
	if( $rs[0]['ritenutaacconto'] > 0 ):
		$rs2 = $dbo->fetchArray("SELECT percentuale FROM co_ritenutaacconto WHERE id='".$rs[0]['idritenutaacconto']."'");

		$footer .= "	<tr><td valign='top' class='b-bottom'>\n";
		$footer .= "		<small><small><b>RITENUTA D'ACCONTO ".intval( $rs2[0]['percentuale'] )."%</b></small></small>\n";
		$footer .= "	</td></tr>\n";

		$footer .= "	<tr><td valign='top' style='text-align:right;' class='b-bottom cell-padded'>\n";
		$footer .= "		".number_format( $rs[0]['ritenutaacconto'], 2, ",", "." )." &euro;\n";
		$footer .= "	</td></tr>\n";



		$footer .= "	<tr><td valign='top' class='b-bottom'>\n";
		$footer .= "		<small><small><b>NETTO A PAGARE</b></small></small>\n";
		$footer .= "	</td></tr>\n";

		$footer .= "	<tr><td valign='top' style='text-align:right;' class='cell-padded'>\n";
		$footer .= "		".number_format( $totale_ddt - $rs[0]['ritenutaacconto'], 2, ",", "." )." &euro;\n";
		$footer .= "	</td></tr>\n";
	endif;
	
else:
	//Riga 1
	$footer  = "	<tr><td style='width:193.5mm;height:30mm;' valign='top' class=''>\n";

	$footer .= "		<small><b>NOTE</b></small><br/>\n";
	$footer .= "		".nl2br( $rs[0]['note'] )."\n";	

	$footer .= "	</td></tr>\n";

	
endif;




	$report_name = "ddt_".$numero.".pdf";
?>
