<?php

include_once __DIR__.'/../../core.php';

$idconto = $_GET['idconto'];
$module_name = 'Piano dei conti';

// carica report html
$report = file_get_contents($docroot.'/templates/partitario_mastrino/partitario.html');
$body = file_get_contents($docroot.'/templates/partitario_mastrino/partitario_body.html');
include_once $docroot.'/templates/pdfgen_variables.php';

// Calcolo il percorso piano dei conti
if ($_GET['lev'] == '3') {
    $rs = $dbo->fetchArray("SELECT idpianodeiconti2, CONCAT_WS(' ', numero, descrizione ) AS descrizione FROM co_pianodeiconti3 WHERE id=\"".$idconto.'"');
    $percorso = $rs[0]['descrizione'];
    $idpianodeiconti2 = $rs[0]['idpianodeiconti2'];

    $rs = $dbo->fetchArray("SELECT idpianodeiconti1, CONCAT_WS(' ', numero, descrizione ) AS descrizione FROM co_pianodeiconti2 WHERE id=\"".$idpianodeiconti2.'"');
    $percorso = $rs[0]['descrizione'].'<br>&nbsp;&nbsp;&nbsp;&nbsp;'.$percorso;
    $idpianodeiconti1 = $rs[0]['idpianodeiconti1'];

    $rs = $dbo->fetchArray("SELECT CONCAT_WS(' ', numero, descrizione ) AS descrizione FROM co_pianodeiconti1 WHERE id=\"".$idpianodeiconti1.'"');

    ($rs[0]['descrizione'] == '01 Patrimoniale') ? $descrizione = 'Stato patrimoniale' : $descrizione = 'Conto economico';
    $percorso = $descrizione.'<br>&nbsp;&nbsp;'.$percorso;
} elseif ($_GET['lev'] == '2') {
    $rs = $dbo->fetchArray("SELECT idpianodeiconti1, CONCAT_WS(' ', numero, descrizione ) AS descrizione FROM co_pianodeiconti2 WHERE id=\"".$idconto.'"');
    $percorso = $rs[0]['descrizione'].'<br>&nbsp;&nbsp;&nbsp;&nbsp;'.$percorso;
    $idpianodeiconti1 = $rs[0]['idpianodeiconti1'];

    $rs = $dbo->fetchArray("SELECT CONCAT_WS(' ', numero, descrizione ) AS descrizione FROM co_pianodeiconti1 WHERE id=\"".$idpianodeiconti1.'"');

    ($rs[0]['descrizione'] == '01 Patrimoniale') ? $descrizione = 'Stato patrimoniale' : $descrizione = 'Conto economico';
    $percorso = $descrizione.'<br>&nbsp;&nbsp;'.$percorso;
} elseif ($_GET['lev'] == '1') {
    $rs = $dbo->fetchArray("SELECT CONCAT_WS(' ', numero, descrizione ) AS descrizione FROM co_pianodeiconti1 WHERE id=\"".$idconto.'"');

    ($rs[0]['descrizione'] == '01 Patrimoniale') ? $descrizione = 'Stato patrimoniale' : $descrizione = 'Conto economico';
    $percorso = $descrizione.'<br>&nbsp;<br>&nbsp;';
}

$body = str_replace('|percorso|', $percorso, $body);
$body = str_replace('|info_fornitore|', $f_ragionesociale.'<br>'.$f_indirizzo.'<br>'.$f_citta, $body);
$body = str_replace('|period_start|', Translator::dateToLocale($_SESSION['period_start']), $body);
$body = str_replace('|period_end|', Translator::dateToLocale($_SESSION['period_end']), $body);

// Stampa da livello 3
if ($_GET['lev'] == '3') {
    $body .= "<table style='table-layout:fixed; border-bottom:1px solid #777; border-right:1px solid #777; border-left:1px solid #777;' cellpadding='0' cellspacing='0'>
                    <col width='80'><col width='452'><col width='80'><col width='80'>
                    <tbody>\n";

    // Inizializzo saldo finale
    $saldo_finale = [];

    // Calcolo saldo iniziale
    $rs = $dbo->fetchArray('SELECT SUM(totale) AS totale FROM co_movimenti WHERE idconto="'.$idconto.'" AND data < "'.$_SESSION['period_start'].'"');
    $saldo_iniziale = $rs[0]['totale'];
    $saldo_finale = $saldo_iniziale;

    if ($saldo_iniziale < 0) {
        $dare = '';
        $avere = abs($saldo_iniziale);
    } else {
        $dare = abs($saldo_iniziale);
        $avere = '';
    }

    $body .= "		<tr><td class='br bb padded'></td><td class='br bb padded'><b>SALDO INIZIALE</b></td><td class='br bb padded text-right'><b>".Translator::numberToLocale(abs($dare))."</b></td><td class='bb padded text-right'><b>".Translator::numberToLocale(abs($avere))."</b></td></tr>\n";

    $rs = $dbo->fetchArray('SELECT * FROM co_movimenti WHERE idconto="'.$idconto.'" AND data >= "'.$_SESSION['period_start'].'" AND data <= "'.$_SESSION['period_end'].'" ORDER BY data ASC');

    for ($i = 0; $i < sizeof($rs); ++$i) {
        if ($rs[$i]['totale'] >= 0) {
            $dare = Translator::numberToLocale(abs($rs[$i]['totale']));
            $avere = '';
        } else {
            $dare = '';
            $avere = Translator::numberToLocale(abs($rs[$i]['totale']));
        }

        $body .= "		<tr><td class='br bb padded text-center'>".Translator::dateToLocale($rs[$i]['data'])."</td><td class='br bb padded'>".$rs[$i]['descrizione']."</td><td class='br bb padded text-right'>".$dare."</td><td class='bb padded text-right'>".$avere."</td></tr>\n";

        $saldo_finale[] = $rs[$i]['totale'];
    }

    if ( sum($saldo_finale) < 0) {
        $dare = '';
        $avere = abs( sum($saldo_finale) );
    } else {
        $dare = abs( sum($saldo_finale) );
        $avere = '';
    }

    // Mostro il saldo finale
    $body .= "		<tr><td class='br bb padded'></td><td class='br bb padded'><b>SALDO FINALE</b></td><td class='br bb padded text-right'><b>".Translator::numberToLocale( abs( sum($dare) ) )."</b></td><td class='bb padded text-right'><b>".Translator::numberToLocale( abs( sum($avere) ) )."</b></td></tr>\n";

    $body .= "		</tbody>
                </table>\n";
}

// Stampa da livello 2
elseif ($_GET['lev'] == '2') {
    $body .= "<table style='table-layout:fixed; border-bottom:1px solid #777; border-right:1px solid #777; border-left:1px solid #777;' cellpadding='0' cellspacing='0'>
                    <col width='80'><col width='452'><col width='80'><col width='80'>
                    <tbody>\n";

    // Ciclo fra i sotto-conti di livello 2
    $rs3 = $dbo->fetchArray('SELECT id, numero, descrizione FROM co_pianodeiconti3 WHERE idpianodeiconti2="'.$idconto.'"');

    for ($z = 0; $z < sizeof($rs3); ++$z) {
        $v_dare = [];
        $v_avere = [];

        // Inizializzo saldo finale
        $saldo_finale = [];

        // Calcolo saldo iniziale
        $rs = $dbo->fetchArray('SELECT SUM(totale) AS totale FROM co_movimenti WHERE idconto="'.$rs3[$z]['id'].'" AND data < "'.$_SESSION['period_start'].'"');
        $saldo_iniziale = $rs[0]['totale'];
        $saldo_finale[] = $saldo_iniziale;

        if ( $saldo_iniziale < 0) {
            $v_avere[] = abs( $saldo_iniziale );
        } else {
            $v_dare[] = abs( $saldo_iniziale );
        }

        $rs = $dbo->fetchArray('SELECT * FROM co_movimenti WHERE idconto="'.$rs3[$z]['id'].'" AND data >= "'.$_SESSION['period_start'].'" AND data <= "'.$_SESSION['period_end'].'" ORDER BY data ASC');

        for ($i = 0; $i < sizeof($rs); ++$i) {
            if ($rs[$i]['totale'] >= 0) {
                $v_dare[] = abs($rs[$i]['totale']);
            } else {
                $v_avere[] = abs($rs[$i]['totale']);
            }
        }

        $totale = sum($v_dare) - sum($v_avere);

        if ($totale >= 0) {
            $dare = Translator::numberToLocale(abs($totale));
            $avere = '';
        } else {
            $dare = '';
            $avere = Translator::numberToLocale(abs($totale));
        }

        // Mostro il saldo finale del conto di livello 3
        $body .= "		<tr><td class='br bb padded'></td><td class='br bb padded'>".$rs3[$z]['numero'].' '.$rs3[$z]['descrizione']."</td><td class='br bb padded text-right'>".$dare."</td><td class='bb padded text-right'>".$avere."</td></tr>\n";
    }

    $body .= "		</tbody>
                </table>\n";
}

// Stampa completa bilancio
elseif (get('lev') == '1') {
    $ricavi = [];
    $costi = [];
    $totale_attivita = [];
    $totale_passivita = [];

    $body .= "<table style='table-layout:fixed; border-bottom:1px solid #777; border-right:1px solid #777; border-left:1px solid #777;' cellpadding='0' cellspacing='0'>
                    <col width='80'><col width='452'><col width='80'><col width='80'>
                    <tbody>\n";

    // Ciclo fra il conto principale scelto (Economico o Patrimoniale)
    $rs1 = $dbo->fetchArray('SELECT id, numero, descrizione FROM co_pianodeiconti1 WHERE id="'.$idconto.'" ORDER BY numero DESC');

    for ($x = 0; $x < sizeof($rs1); ++$x) {
        // Ciclo fra i sotto-conti di livello 1
        $rs2 = $dbo->fetchArray('SELECT id, numero, descrizione FROM co_pianodeiconti2 WHERE idpianodeiconti1="'.$rs1[$x]['id'].'"');

        for ($y = 0; $y < sizeof($rs2); ++$y) {
            $body .= "		<tr><th class='bb padded' colspan='4'><b>".$rs2[$y]['numero'].' '.$rs2[$y]['descrizione']."</b></th></tr>\n";

            // Ciclo fra i sotto-conti di livello 2
            $rs3 = $dbo->fetchArray('SELECT id, numero, descrizione FROM co_pianodeiconti3 WHERE idpianodeiconti2="'.$rs2[$y]['id'].'"');

            for ($z = 0; $z < sizeof($rs3); ++$z) {
                $v_dare = [];
                $v_avere = [];

                $rs = $dbo->fetchArray('SELECT * FROM co_movimenti WHERE idconto="'.$rs3[$z]['id'].'" AND data >= "'.$_SESSION['period_start'].'" AND data <= "'.$_SESSION['period_end'].'" ORDER BY data ASC');

                for ($i = 0; $i < sizeof($rs); ++$i) {
                    if ($rs[$i]['totale'] >= 0) {
                        $v_dare[] = abs($rs[$i]['totale']);
                    } else {
                        $v_avere[] = abs($rs[$i]['totale']);
                    }
                }

                $totale = sum($v_dare) - sum($v_avere);

                if ($totale >= 0) {
                    $dare = abs($totale);
                    $avere = 0;
                    $totale_attivita[] = $dare;
                    $costi[] = abs($dare);
                } else {
                    $dare = 0;
                    $avere = abs($totale);
                    $totale_passivita[] = $avere;
                    $ricavi[] = abs($avere);
                }

                // Mostro il saldo finale del conto di livello 3
                $body .= "		<tr><td class='br bb padded'></td><td class='br bb padded'>".$rs3[$z]['numero'].' '.$rs3[$z]['descrizione']."</td><td class='br bb padded text-right'>".Translator::numberToLocale(abs($dare))."</td><td class='bb padded text-right'>".Translator::numberToLocale(abs($avere))."</td></tr>\n";
            }
        }
    }

    // Stampa "Costi/Ricavi" se conto economico
    if ($rs1[0]['descrizione'] == 'Economico') {
        $body .= "		<tr><th colspan='3' class='br bb padded'>RICAVI</th><th align='right' class='bb padded'>".Translator::numberToLocale( sum($ricavi) )."</th></tr>\n";
        $body .= "		<tr><th colspan='3' class='br bb padded'>COSTI</th><th align='right' class='bb padded'>".Translator::numberToLocale( sum($costi) )."</th></tr>\n";
        $body .= "		<tr><th colspan='3' class='br padded'>UTILE</th><th class='padded' align='right'>".Translator::numberToLocale( sum($ricavi) - sum($costi) )."</th></tr>\n";
    }

    // Stampa "Attività/Passività" se stato patrimoniale
    else {
        $costi = [];
        $ricavi = [];

        // Ciclo fra il conto economico per calcolare l'utile o la perdita
        $rs1 = $dbo->fetchArray('SELECT id, numero, descrizione FROM co_pianodeiconti1 WHERE NOT id="'.$idconto.'" ORDER BY numero DESC');

        for ($x = 0; $x < sizeof($rs1); ++$x) {
            // Ciclo fra i sotto-conti di livello 1
            $rs2 = $dbo->fetchArray('SELECT id, numero, descrizione FROM co_pianodeiconti2 WHERE idpianodeiconti1="'.$rs1[$x]['id'].'"');

            for ($y = 0; $y < sizeof($rs2); ++$y) {
                // Ciclo fra i sotto-conti di livello 2
                $rs3 = $dbo->fetchArray('SELECT id, numero, descrizione FROM co_pianodeiconti3 WHERE idpianodeiconti2="'.$rs2[$y]['id'].'"');

                for ($z = 0; $z < sizeof($rs3); ++$z) {
                    // Inizializzo saldo finale
                    $saldo_finale = [];

                    // Calcolo saldo iniziale
                    $rs = $dbo->fetchArray('SELECT SUM(totale) AS totale FROM co_movimenti WHERE idconto="'.$rs2[$y]['id'].'" AND data < "'.$_SESSION['period_start'].'"');
                    $dare = [];
                    $avere = [];

                    $rs = $dbo->fetchArray('SELECT * FROM co_movimenti WHERE idconto="'.$rs3[$z]['id'].'" AND data >= "'.$_SESSION['period_start'].'" AND data <= "'.$_SESSION['period_end'].'" ORDER BY data ASC');

                    for ($i = 0; $i < sizeof($rs); ++$i) {
                        if ($rs[$i]['totale'] >= 0) {
                            $dare[] = abs($rs[$i]['totale']);
                        } else {
                            $avere[] = abs($rs[$i]['totale']);
                        }
                    }

                    $totale = sum($dare) - sum($avere);

                    if ($totale >= 0) {
                        $costi[] = abs($totale);
                    } else {
                        $ricavi[] = abs($totale);
                    }
                }
            }
        }

        $body .= "		</tbody>\n";
        $body .= "	</table>\n";

        // Tabella di riepilogo finale
        $totale_attivita = abs( sum($totale_attivita) );
        $totale_passivita = abs( sum($totale_passivita) );
        $utile_perdita = abs( sum($ricavi) ) - abs( sum($costi) );

        if ($utile_perdita < 0) {
            $pareggio1 = $totale_attivita + abs($utile_perdita);
            $pareggio2 = abs($totale_passivita);
        } else {
            $pareggio1 = $totale_attivita;
            $pareggio2 = abs($totale_passivita) + abs($utile_perdita);
        }

        $body .= "<table style='table-layout:fixed; border-bottom:1px solid #777; border-right:1px solid #777; border-left:1px solid #777;' cellpadding='0' cellspacing='0'>
                    <col width='173'><col width='173'><col width='173'><col width='173'>
                    <tbody>\n";

        // Attività
        $body .= "		<tr><th class='br bb padded'>TOTALE ATTIVIT&Agrave;</th><th align='right' class='bb br padded'>".Translator::numberToLocale($totale_attivita)."</th>\n";

        // Passività
        $body .= "		<th class='br bb padded'>PASSIVIT&Agrave;</th><th align='right' class='bb padded'>".Translator::numberToLocale($totale_passivita)."</th></tr>\n";

        if ($utile_perdita < 0) {
            // Perdita d'esercizio
            $body .= "		<tr><th class='br bb padded'>PERDITA D'ESERCIZIO</th><th align='right' class='bb br padded'>".Translator::numberToLocale(abs($utile_perdita))."</th>\n";

            // Utile
            $body .= "		<th class='br bb padded'>&nbsp;</th><th align='right' class='bb padded'>&nbsp;</th></tr>\n";
        } else {
            // Perdita d'esercizio
            $body .= "		<tr><th class='br bb padded'>&nbsp;</th><th align='right' class='bb br padded'>&nbsp;</th>\n";

            // Utile
            $body .= "		<th class='br bb padded'>UTILE</th><th align='right' class='bb padded'>".Translator::numberToLocale(abs($utile_perdita))."</th></tr>\n";
        }

        // PAREGGIO 1
        $body .= "		<tr><th class='br padded'>TOTALE A PAREGGIO</th><th align='right' class='br padded'>".Translator::numberToLocale($pareggio1)."</th>\n";

        // PAREGGIO 2
        $body .= "		<th class='br padded'>TOTALE A PAREGGIO</th><th align='right' class='padded'>".Translator::numberToLocale($pareggio2)."</th></tr>\n";
    }

    $body .= "		</tbody>
                </table>\n";
}

$report_name = 'mastrino.pdf';
