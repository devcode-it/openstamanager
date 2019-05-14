<?php

include_once __DIR__.'/../../core.php';

/**
 * Elenco conti.
 */
$query1 = 'SELECT * FROM `co_pianodeiconti1` ORDER BY id DESC';
$rs1 = $dbo->fetchArray($query1);
$n1 = sizeof($rs1);

// Livello 1
for ($x = 0; $x < $n1; ++$x) {
    $totale_attivita = [];
    $totale_passivita = [];

    $costi = [];
    $ricavi = [];

    if ($rs1[$x]['descrizione'] == 'Economico') {
        echo "<hr><h2 class=\"pull-left\">Conto economico</h2>\n";
    } else {
        echo "<hr><h2 class=\"pull-left\">Stato patrimoniale</h2>\n";
    }

    echo "<div class=\"pull-right\"><br>\n";
    echo Prints::getLink('Mastrino', $rs1[$x]['id'], null, tr('Stampa'), null, 'lev=1');
    echo "</div>\n";
    echo "<div class=\"clearfix\"></div>\n";

    // Livello 2
    $query2 = "SELECT * FROM `co_pianodeiconti2` WHERE idpianodeiconti1='".$rs1[$x]['id']."' ORDER BY numero ASC";
    $rs2 = $dbo->fetchArray($query2);
    $n2 = sizeof($rs2);

    echo "<div style='padding-left:10px;'>\n";

    for ($y = 0; $y < $n2; ++$y) {
        // Livello 2
        echo "	<div>\n";

        // Stampa mastrino
        echo Prints::getLink('Mastrino', $rs2[$y]['id'], 'btn-info btn-xs', '', null, 'lev=2');

        echo '		<b>'.$rs2[$y]['numero'].' '.htmlentities($rs2[$y]['descrizione'], ENT_QUOTES, 'ISO-8859-1')."</b><br>\n";

        echo "	</div>\n";

        // Livello 3
        $query3 = 'SELECT `co_pianodeiconti3`.*, `clienti`.`idanagrafica` AS id_cliente, `fornitori`.`idanagrafica` AS id_fornitore FROM `co_pianodeiconti3` LEFT OUTER JOIN `an_anagrafiche` `clienti` ON `clienti`.`idconto_cliente` = `co_pianodeiconti3`.`id` LEFT OUTER JOIN `an_anagrafiche` `fornitori` ON `fornitori`.`idconto_fornitore` = `co_pianodeiconti3`.`id` WHERE `idpianodeiconti2` = '.prepare($rs2[$y]['id']).' ORDER BY numero ASC';
        $rs3 = $dbo->fetchArray($query3);
        $n3 = sizeof($rs3);

        echo "	<div style='padding-left:10px;'>\n";
        echo "		<table class='table table-striped table-hover table-condensed' style='margin-bottom:0;'>\n";

        for ($z = 0; $z < $n3; ++$z) {
            $totale_conto_liv3 = [];

            echo "		<tr><td>\n";

            // Se il conto non ha documenti collegati posso eliminarlo
            $query = "SELECT id FROM co_movimenti WHERE idconto='".$rs3[$z]['id']."'";
            $nr = $dbo->fetchNum($query);

            // Calcolo totale conto da elenco movimenti di questo conto
            $query = "SELECT co_movimenti.*, dir FROM (co_movimenti LEFT OUTER JOIN co_documenti ON co_movimenti.iddocumento=co_documenti.id) LEFT OUTER JOIN co_tipidocumento ON co_documenti.idtipodocumento=co_tipidocumento.id WHERE co_movimenti.idconto='".$rs3[$z]['id']."' AND co_movimenti.data >= '".$_SESSION['period_start']."' AND co_movimenti.data <= '".$_SESSION['period_end']."' ORDER BY co_movimenti.data ASC";
            $rs = $dbo->fetchArray($query);

            $tools = "			<span class='hide tools'>\n";

            // Stampa mastrino
            if (!empty($rs)) {
                $tools .= Prints::getLink('Mastrino', $rs3[$z]['id'], 'btn-info btn-xs', '', null, 'lev=3');
            }

            if ($nr <= 0 && $rs3[$z]['can_delete'] == '1') {
                $tools .= '
                                    <a class="btn btn-danger btn-xs ask" data-toggle="tooltip" title="'.tr('Elimina').'" data-backto="record-list" data-op="del" data-idconto="'.$rs3[$z]['id'].'">
                                        <i class="fa fa-trash"></i>
                                    </a>';
            }

            // Possibilità di modificare il nome del conto livello3
            if ($rs3[$z]['can_edit'] == '1') {
                $tools .= "			<button type='button' class='btn btn-warning btn-xs' data-toggle='tooltip' title='Modifica questo conto...' onclick=\"launch_modal( 'Modifica conto', '".$rootdir.'/modules/partitario/edit_conto.php?id='.$rs3[$z]['id']."', 1 );\"><i class='fa fa-edit'></i></button>\n";
            }

            $tools .= "			</span>\n";

            echo "
                <span class='clickable' onmouseover=\"$(this).find('.tools').removeClass('hide');\" onmouseleave=\"$(this).find('.tools').addClass('hide');\" onclick=\"$('#conto_".$rs3[$z]['id']."').slideToggle(); $(this).find('.plus-btn i').toggleClass('fa-plus').toggleClass('fa-minus');\">";

            if (!empty($rs)) {
                echo '
                    <a href="javascript:;" class="btn btn-primary btn-xs plus-btn"><i class="fa fa-plus"></i></a>';
            }

            $id_anagrafica = $rs3[$z]['id_cliente'] ?: $rs3[$z]['id_fornitore'];

            echo '
                    '.$tools.'&nbsp;'.$rs2[$y]['numero'].'.'.$rs3[$z]['numero'].' '.$rs3[$z]['descrizione'].' '.(isset($id_anagrafica) ? Modules::link('Anagrafiche', $id_anagrafica, 'Anagrafica', null) : '').'
                </span>';

            echo '			<div id="conto_'.$rs3[$z]['id']."\" style=\"display:none;\">\n";

            if (sizeof($rs) > 0) {
                $totale_conto_liv3 = [];

                echo "				<table class='table table-bordered table-hover table-condensed table-striped'>\n";
                echo "				<tr><th>Causale</th>\n";
                echo "				<th width='100'>Data</th>\n";
                echo "				<th width='100'>Dare</th>\n";
                echo "				<th width='100'>Avere</th></tr>\n";

                // Elenco righe del partitario
                for ($i = 0; $i < sizeof($rs); ++$i) {
                    echo "				<tr><td>\n";

                    if (!empty($rs[$i]['iddocumento'])) {
                        $module = ($rs[$i]['dir'] == 'entrata') ? Modules::get('Fatture di vendita')['id'] : Modules::get('Fatture di acquisto')['id'];
                        echo "<a data-toggle='modal' data-title='Dettagli movimento...' data-target='#bs-popup' class='clickable' data-href='".$rootdir.'/modules/partitario/dettagli_movimento.php?id_movimento='.$rs[$i]['id'].'&id_conto='.$rs[$i]['idconto'].'&id_module='.$module."' >".$rs[$i]['descrizione']."</a>\n";
                    // echo "					<a href='".$rootdir.'/editor.php?id_module='.$module.'&id_record='.$rs[$i]['iddocumento']."'>".$rs[$i]['descrizione']."</a>\n";
                    } else {
                        echo '					<span>'.$rs[$i]['descrizione']."</span>\n";
                    }

                    echo "				</td>\n";

                    // Data
                    echo "				<td>\n";
                    echo date('d/m/Y', strtotime($rs[$i]['data']));
                    echo                "</td>\n";

                    // Dare
                    if ($rs[$i]['totale'] > 0) {
                        echo "				<td align='right'>\n";
                        echo moneyFormat(abs($rs[$i]['totale']), 2)."\n";
                        echo "				</td>\n";
                        echo "				<td></td></tr>\n";

                        if ($rs1[$x]['descrizione'] == 'Patrimoniale') {
                            $totale_conto_liv3[] = $rs[$i]['totale'];
                        } else {
                            $totale_conto_liv3[] = -$rs[$i]['totale'];
                        }
                    }

                    // Avere
                    else {
                        echo "				<td></td><td  align='right'>\n";
                        echo moneyFormat(abs($rs[$i]['totale']), 2)."\n";
                        echo "				</td>\n";

                        if ($rs1[$x]['descrizione'] == 'Patrimoniale') {
                            $totale_conto_liv3[] = $rs[$i]['totale'];
                        } else {
                            $totale_conto_liv3[] = -$rs[$i]['totale'];
                        }
                    }
                    echo "				</td></tr>\n";
                }

                // Somma dei totali
                if ($rs1[$x]['descrizione'] == 'Patrimoniale') {
                    if (sum($totale_conto_liv3) > 0) {
                        $totale_attivita[] = abs(sum($totale_conto_liv3));
                    } else {
                        $totale_passivita[] = abs(sum($totale_conto_liv3));
                    }
                } else {
                    if (sum($totale_conto_liv3) > 0) {
                        $totale_ricavi[] = abs(sum($totale_conto_liv3));
                    } else {
                        $totale_costi[] = abs(sum($totale_conto_liv3));
                    }
                }
                echo "				</table>\n";
            }
            echo "			</div>\n";
            echo "		</td>\n";

            echo "		<td width='100' align='right' valign='top'>\n";
            echo moneyFormat(sum($totale_conto_liv3), 2)."\n";
            echo "		</td></tr>\n";
        } // Fine livello3

        echo "	</table>\n";

        // Possibilità di inserire un nuovo conto
        echo "	<button type='button' class='btn btn-xs btn-primary' data-toggle='tooltip'  title='".tr('Aggiungi un nuovo conto...')."' onclick=\"launch_modal( '".tr('Nuovo conto')."', '".$rootdir.'/modules/partitario/add_conto.php?id='.$rs2[$y]['id']."', 1 );\"><i class='fa fa-plus-circle'></i></button><br><br>\n";
        echo "</div>\n";
    } // Fine livello 2

    echo "</div>\n";

    if ($rs1[$x]['descrizione'] == 'Patrimoniale') {
        // Riepilogo
        $attivita = abs(sum($totale_attivita));
        $passivita = abs(sum($totale_passivita));
        $utile_perdita = abs(sum($totale_ricavi)) - abs(sum($totale_costi));
        if ($utile_perdita < 0) {
            $pareggio1 = $attivita + abs($utile_perdita);
            $pareggio2 = abs($passivita);
        } else {
            $pareggio1 = $attivita;
            $pareggio2 = abs($passivita) + abs($utile_perdita);
        }

        echo "<table class='table table-condensed table-hover'>\n";

        // Attività
        echo "<tr><th>\n";
        echo "	<p align='right'><big>Totale attività:</big></p>\n";
        echo "</th>\n";

        echo "<td width='150' align='right'>\n";
        echo "	<p align='right'><big>".moneyFormat($attivita, 2)."</big></p>\n";
        echo "</td>\n";
        echo "<td width='50'></td>\n";

        // Passività
        echo "<th align='right'>\n";
        echo "	<p align='right'><big>Passività:</big></p>\n";
        echo "</th>\n";
        echo "<td width='150' align='right'>\n";
        echo "	<p align='right'><big>".moneyFormat($passivita, 2)."</big></p>\n";
        echo "</td></tr>\n";

        // Perdita d'esercizio
        if ($utile_perdita < 0) {
            echo "<tr><th align='right'>\n";
            echo "	<p align='right'><big>Perdita d'esercizio:</big></p>\n";
            echo "</th>\n";
            echo "<td align='right'>\n";
            echo "	<p align='right'><big>".moneyFormat(sum($utile_perdita), 2)."</big></p>\n";
            echo "</td>\n";
            echo "<td></td>\n";
            echo "<td></td><td></td></tr>\n";
        } else {
            echo "<tr><td></td><td></td><td></td><th align='right'>\n";
            echo "	<p align='right'><big>Utile:</big></p>\n";
            echo "</th>\n";
            echo "<td align='right'>\n";
            echo "	<p align='right'><big>".moneyFormat(sum($utile_perdita), 2)."</big></p>\n";
            echo "</td></tr>\n";
        }

        // Totale a pareggio
        echo "<tr><th align='right'>\n";
        echo "	<p align='right'><big>Totale a pareggio:</big></p>\n";
        echo "</th>\n";
        echo "<td align='right'>\n";
        echo "	<p align='right'><big>".moneyFormat(sum($pareggio1), 2)."</big></p>\n";
        echo "</td>\n";
        echo "<td></td>\n";

        // Totale a pareggio
        echo "<th align='right'>\n";
        echo "	<p align='right'><big>Totale a pareggio:</big></p>\n";
        echo "</th>\n";
        echo "<td align='right'>\n";
        echo "	<p align='right'><big>".moneyFormat(sum($pareggio2), 2)."</big></p>\n";
        echo "</td></tr>\n";

        echo '</table>';
    } else {
        echo "<p align='right'><big><b>RICAVI:</b> ".moneyFormat(sum($totale_ricavi), 2)."</big></p>\n";
        echo "<p align='right'><big><b>COSTI:</b> ".moneyFormat(abs(sum($totale_costi)), 2)."</big></p>\n";
        echo "<p align='right'><big><b>UTILE/PERDITA:</b> ".moneyFormat(sum($totale_ricavi) - abs(sum($totale_costi)), 2)."</big></p>\n";
    }
}
