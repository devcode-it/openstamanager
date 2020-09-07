<?php
/*
 * OpenSTAManager: il software gestionale open source per l'assistenza tecnica e la fatturazione
 * Copyright (C) DevCode s.n.c.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

include_once __DIR__.'/../../core.php';

$idconto = $_GET['idconto'];
$module_name = 'Piano dei conti';

$date_start = $_SESSION['period_start'];
$date_end = $_SESSION['period_end'];

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
$body = str_replace('|period_start|', Translator::dateToLocale($date_start), $body);
$body = str_replace('|period_end|', Translator::dateToLocale($date_end), $body);

// Stampa da livello 3
if ($_GET['lev'] == '3') {
    $body .= "<table style='table-layout:fixed; border-bottom:1px solid #777; border-right:1px solid #777; border-left:1px solid #777;' cellpadding='0' cellspacing='0'>
                    <col width='80'><col width='452'><col width='80'><col width='80'>
                    <tbody>\n";

    // Inizializzo saldo finale
    $saldo_finale = [];

    // Calcolo saldo iniziale
    $saldo_iniziale = 0;
    $saldo_finale = $saldo_iniziale;

    $rs = $dbo->fetchArray('SELECT *, SUM(totale) AS totale
    FROM co_movimenti
    WHERE idconto='.prepare($idconto).' AND
        data >= '.prepare($date_start).' AND
        data <= '.prepare($date_end).'
    GROUP BY idmastrino
    ORDER BY data ASC');

    // Inizializzo saldo finale
    $saldo_finale2 = [];
    for ($i = 0; $i < sizeof($rs); ++$i) {
        if ($rs[$i]['totale'] >= 0) {
            $dare = moneyFormat(abs($rs[$i]['totale']), 2);
            $avere = '';
        } else {
            $dare = '';
            $avere = moneyFormat(abs($rs[$i]['totale']), 2);
        }

        $body .= "		<tr><td class='br bb padded text-center'>".Translator::dateToLocale($rs[$i]['data'])."</td><td class='br bb padded'>".$rs[$i]['descrizione']."</td><td class='br bb padded text-right'>".$dare."</td><td class='bb padded text-right'>".$avere."</td></tr>\n";

        $saldo_finale2[] = $rs[$i]['totale'];
    }

    if (sum($saldo_finale) < 0) {
        $dare = '';
        $avere = abs(sum($saldo_finale) + sum($saldo_finale2));
    } else {
        $dare = abs(sum($saldo_finale) + sum($saldo_finale2));
        $avere = '';
    }

    // Mostro il saldo finale
    $body .= "		<tr><td class='br bb padded'></td><td class='br bb padded'><b>SALDO FINALE</b></td><td class='br bb padded text-right'><b>".moneyFormat(abs(sum($dare)), 2)."</b></td><td class='bb padded text-right'><b>".moneyFormat(abs(sum($avere)), 2)."</b></td></tr>\n";

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
        $saldo_iniziale = 0;
        $saldo_finale[] = $saldo_iniziale;

        $rs = $dbo->fetchArray('SELECT * FROM co_movimenti WHERE idconto="'.$rs3[$z]['id'].'" AND data >= '.prepare($date_start).' AND data <= '.prepare($date_end).' ORDER BY data ASC');

        for ($i = 0; $i < sizeof($rs); ++$i) {
            if ($rs[$i]['totale'] >= 0) {
                $v_dare[] = abs($rs[$i]['totale']);
            } else {
                $v_avere[] = abs($rs[$i]['totale']);
            }
        }

        $totale = sum($v_dare) - sum($v_avere);

        if ($totale >= 0) {
            $dare = moneyFormat(abs($totale), 2);
            $avere = '';
        } else {
            $dare = '';
            $avere = moneyFormat(abs($totale), 2);
        }

        // Mostro il saldo finale del conto di livello 3
        if (sizeof($rs) > 0) {
            $body .= "		<tr><td class='br bb padded'></td><td class='br bb padded'>".$rs3[$z]['numero'].' '.$rs3[$z]['descrizione']."</td><td class='br bb padded text-right'>".$dare."</td><td class='bb padded text-right'>".$avere."</td></tr>\n";
        }
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
            // Ciclo fra i sotto-conti di livello 2
            $rs3 = $dbo->fetchArray('SELECT id, numero, descrizione, movimenti.totale FROM co_pianodeiconti3 LEFT JOIN (SELECT SUM(totale) AS totale, idconto FROM co_movimenti GROUP BY idconto) AS movimenti ON co_pianodeiconti3.id=movimenti.idconto WHERE idpianodeiconti2="'.$rs2[$y]['id'].'" AND movimenti.totale != 0');

            if (!empty($rs3)) {
                $body .= "		<tr><th class='bb padded' colspan='4'><b>".$rs2[$y]['numero'].' '.$rs2[$y]['descrizione']."</b></th></tr>\n";
            }

            for ($z = 0; $z < sizeof($rs3); ++$z) {
                $v_dare = [];
                $v_avere = [];

                if ($rs3[$z]['totale'] >= 0) {
                    $v_dare[] = abs($rs3[$z]['totale']);
                } else {
                    $v_avere[] = abs($rs3[$z]['totale']);
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
                $body .= "		<tr><td class='br bb padded'></td><td class='br bb padded'>".$rs3[$z]['numero'].' '.$rs3[$z]['descrizione']."</td><td class='br bb padded text-right'>".moneyFormat(abs($dare), 2)."</td><td class='bb padded text-right'>".moneyFormat(abs($avere), 2)."</td></tr>\n";
            }
        }
    }

    // Stampa "Costi/Ricavi" se conto economico
    if ($rs1[0]['descrizione'] == 'Economico') {
        $body .= "		<tr><th colspan='3' class='br bb padded'>RICAVI</th><th align='right' class='bb padded'>".moneyFormat(sum($ricavi), 2)."</th></tr>\n";
        $body .= "		<tr><th colspan='3' class='br bb padded'>COSTI</th><th align='right' class='bb padded'>".moneyFormat(sum($costi), 2)."</th></tr>\n";
        $body .= "		<tr><th colspan='3' class='br padded'>UTILE</th><th class='padded' align='right'>".moneyFormat(sum($ricavi) - sum($costi), 2)."</th></tr>\n";
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
                    $rs = $dbo->fetchArray('SELECT SUM(totale) AS totale FROM co_movimenti WHERE idconto="'.$rs2[$y]['id'].'" AND data < '.prepare($date_start).'');
                    $dare = [];
                    $avere = [];

                    $rs = $dbo->fetchArray('SELECT * FROM co_movimenti WHERE idconto="'.$rs3[$z]['id'].'" AND data >= '.prepare($date_start).' AND data <= '.prepare($date_end).' ORDER BY data ASC');

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
        $totale_attivita = abs(sum($totale_attivita));
        $totale_passivita = abs(sum($totale_passivita));
        $utile_perdita = abs(sum($ricavi)) - abs(sum($costi));

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
        $body .= "		<tr><th class='br bb padded'>TOTALE ATTIVIT&Agrave;</th><th align='right' class='bb br padded'>".moneyFormat($totale_attivita, 2)."</th>\n";

        // Passività
        $body .= "		<th class='br bb padded'>PASSIVIT&Agrave;</th><th align='right' class='bb padded'>".moneyFormat($totale_passivita, 2)."</th></tr>\n";

        if ($utile_perdita < 0) {
            // Perdita d'esercizio
            $body .= "		<tr><th class='br bb padded'>PERDITA D'ESERCIZIO</th><th align='right' class='bb br padded'>".moneyFormat(abs($utile_perdita), 2)."</th>\n";

            // Utile
            $body .= "		<th class='br bb padded'>&nbsp;</th><th align='right' class='bb padded'>&nbsp;</th></tr>\n";
        } else {
            // Perdita d'esercizio
            $body .= "		<tr><th class='br bb padded'>&nbsp;</th><th align='right' class='bb br padded'>&nbsp;</th>\n";

            // Utile
            $body .= "		<th class='br bb padded'>UTILE</th><th align='right' class='bb padded'>".moneyFormat(abs($utile_perdita), 2)."</th></tr>\n";
        }

        // PAREGGIO 1
        $body .= "		<tr><th class='br padded'>TOTALE A PAREGGIO</th><th align='right' class='br padded'>".moneyFormat($pareggio1, 2)."</th>\n";

        // PAREGGIO 2
        $body .= "		<th class='br padded'>TOTALE A PAREGGIO</th><th align='right' class='padded'>".moneyFormat($pareggio2, 2)."</th></tr>\n";
    }

    $body .= "		</tbody>
                </table>\n";
}
