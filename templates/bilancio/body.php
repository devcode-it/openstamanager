<?php

/*
 * OpenSTAManager: il software gestionale open source per l'assistenza tecnica e la fatturazione
 * Copyright (C) DevCode s.r.l.
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

// Stato Patrimoniale
echo '
<h4>Stato Patrimoniale</h3>

<div class="row">
      <div class="col-md-6 pull-left" style="width:49%;" >
            <table class="table table-striped table-bordered" style="overflow:hidden;" id="contents">
                <thead>
                    <tr>
                        <th colspan="3"><h5>Attività</h5></th>
                    </tr>
                    <tr>
                        <th width="15%">CONTO</th>
                        <th width="60%">DESCRIZIONE</th>
                        <th width="25%">SALDO</th>

                    </tr>
                </thead>
                <tbody>';
// Mostra le righe delle attività
foreach ($liv2_patrimoniale as $liv2_p) {
    $livello3 = '';
    $totale_livello3 = 0;
    foreach ($liv3_patrimoniale as $liv3_p) {
        if ($liv3_p['totale'] > 0) {
            // Visualizzo solo i conti di livello 3 relativi al conto di livello 2
            if ($liv2_p['id'] == $liv3_p['idpianodeiconti2'] && $liv3_p['totale'] != 0) {
                $totale_attivita += $liv3_p['totale'];
                $totale_livello3 += $liv3_p['totale'];
                $livello3 .= '
                                    <tr>
                                        <td>'.$liv3_p['numero'].'</td>
                                        <td>'.$liv3_p['descrizione'].'</td>
                                        <td class="text-right">'.numberFormat($liv3_p['totale'], 2).'</td>
                                    </tr>';
            }
        }
    }

    if (empty(get('elenco_analitico'))) {
        if ($liv2_p['id'] == setting('Conto di secondo livello per i crediti clienti') && $crediti_clienti > 0) {
            $totale_attivita += $crediti_clienti;
            $totale_livello3 += $crediti_clienti;
            $livello3 .= '
                                    <tr>
                                        <td></td>
                                        <td>Clienti</td>
                                        <td class="text-right">'.numberFormat($crediti_clienti, 2).'</td>
                                    </tr>';
        } elseif ($liv2_p['id'] == setting('Conto di secondo livello per i debiti fornitori') && $debiti_fornitori > 0) {
            $totale_attivita += $debiti_fornitori;
            $totale_livello3 += $debiti_fornitori;
            $livello3 .= '
                                <tr>
                                    <td></td>
                                    <td>Fornitori</td>
                                    <td class="text-right">'.numberFormat($debiti_fornitori, 2).'</td>
                                </tr>';
        }
    }

    if ($totale_livello3 != 0) {
        echo '
                            <tr>
                                <td><b>'.$liv2_p['numero'].'</b></td>
                                <td><b>'.$liv2_p['descrizione'].'</b></td>
                                <td class="text-right"><b>'.numberFormat($totale_livello3, 2).'</b></td>
                            </tr>';
        echo $livello3;
    }
}
echo '
                    <tr>
                        <td colspan="2"><h6><b>Totale Attività</b></h6></td>
                        <td class="text-right" style="font-size:8pt;"><b>'.($totale_attivita ? numberFormat(abs($totale_attivita), 2) : '').'</b></td>
                    </tr>';
if ($utile_perdita > 0) {
    echo '
                        <tr>
                            <td colspan="2"><h6><b>Perdita</b></h6></td>
                            <td class="text-right" style="font-size:8pt;"><b>'.numberFormat(abs($utile_perdita), 2).'</b></td>
                        </tr>
                        <tr>
                            <td colspan="2"><h6><b>Totale a pareggio</b></h6></td>
                            <td class="text-right" style="font-size:8pt;"><b>'.numberFormat(abs($totale_attivita) + abs($utile_perdita), 2).'</b></td>
                        </tr>';
}
echo '
                </tbody>
            </table>
        </div>

        <div class="col-md-6 pull-right"  style="width:49%;" >
            <table class="table table-striped table-bordered" style="overflow:hidden;" id="contents">
                <thead>
                    <tr>
                    
                        <th colspan="3"><h5>Passività</h5></th>
                    </tr>
                    <tr>
                        <th width="20%">CONTO</th>
                        <th width="55%">DESCRIZIONE</th>
                        <th width="25%">SALDO</th>

                    </tr>
                </thead>
                <tbody>';
$i = 0;
// Mostra le righe delle passività
foreach ($liv2_patrimoniale as $liv2_p) {
    $livello3 = '';
    $totale_livello3 = 0;
    foreach ($liv3_patrimoniale as $liv3_p) {
        if ($liv3_p['totale'] < 0) {
            // Visualizzo solo i conti di livello 3 relativi al conto di livello 2
            if ($liv2_p['id'] == $liv3_p['idpianodeiconti2'] && $liv3_p['totale'] != 0) {
                $totale_passivita += $liv3_p['totale'];
                $totale_livello3 += $liv3_p['totale'];
                $livello3 .= '
                                    <tr>
                                        <td>'.$liv3_p['numero'].'</td>
                                        <td>'.$liv3_p['descrizione'].'</td>
                                        <td class="text-right">'.numberFormat(abs($liv3_p['totale']), 2).'</td>
                                    </tr>';
            }
        }
    }

    if (empty(get('elenco_analitico'))) {
        if ($liv2_p['id'] == setting('Conto di secondo livello per i crediti clienti') && $crediti_clienti < 0) {
            $totale_passivita += $crediti_clienti;
            $totale_livello3 += $crediti_clienti;
            $livello3 .= '
                                    <tr>
                                        <td></td>
                                        <td>Clienti</td>
                                        <td class="text-right">'.numberFormat(abs($crediti_clienti), 2).'</td>
                                    </tr>';
        } elseif ($liv2_p['id'] == setting('Conto di secondo livello per i debiti fornitori') && $debiti_fornitori < 0) {
            $totale_passivita += $debiti_fornitori;
            $totale_livello3 += $debiti_fornitori;
            $livello3 .= '
                                <tr>
                                    <td></td>
                                    <td>Fornitori</td>
                                    <td class="text-right">'.numberFormat(abs($debiti_fornitori), 2).'</td>
                                </tr>';
        }
    }

    if ($totale_livello3 != 0) {
        echo '
                            <tr>
                                <td><b>'.$liv2_p['numero'].'</b></td>
                                <td><b>'.$liv2_p['descrizione'].'</b></td>
                                <td class="text-right"><b>'.numberFormat(abs($totale_livello3), 2).'</b></td>
                            </tr>';
        echo $livello3;
    }
}
echo '
                    <tr>
                        <td colspan="2"><h6><b>Totale Passività</b></h6></td>
                        <td class="text-right" style="font-size:8pt;"><b>'.($totale_passivita ? numberFormat(abs($totale_passivita), 2) : '').'</b></td>
                    </tr>';
if ($utile_perdita < 0) {
    echo '
                        <tr>
                            <td colspan="2"><h6><b>Utile</b></h6></td>
                            <td class="text-right" style="font-size:8pt;"><b>'.numberFormat(abs($utile_perdita), 2).'</b></td>
                        </tr>
                        <tr>
                            <td colspan="2"><h6><b>Totale a pareggio</b></h6></td>
                            <td class="text-right" style="font-size:8pt;"><b>'.($totale_passivita ? numberFormat(abs($totale_passivita), 2) : '').'</b></td>
                        </tr>';
}
echo '
                </tbody>
            </table>
        
    </div>
</div>';

// Conto economico
echo '
<pagebreak>
<h4>Conto Economico</h4>

<div class="row">

      <div class="col-md-6 pull-left" style="width:49%;" >

            <table class="table table-striped table-bordered" style=" overflow:hidden;" id="contents">
                <thead>
                    <tr>
                        <th colspan="4"><h5>Costi</h5></th>
                    </tr>
                    <tr>
                        <th width="12%">CONTO</th>
                        <th>DESCRIZIONE</th>
                        <th width="21%">SALDO</th>
                        <th width="21%">REDDITO</th>
                    </tr>
                </thead>
                <tbody>';
// Mostra le righe dei costi
foreach ($liv2_economico as $liv2_e) {
    $livello3 = '';
    $totale_livello3 = 0;
    $totale_reddito_livello3 = 0;
    foreach ($liv3_economico as $liv3_e) {
        if ($liv3_e['totale'] > 0) {
            // Visualizzo solo i conti di livello 3 relativi al conto di livello 2
            if ($liv2_e['id'] == $liv3_e['idpianodeiconti2'] && $liv3_e['totale'] != 0) {
                $totale_costi += $liv3_e['totale'];
                $totale_livello3 += $liv3_e['totale'];
                $totale_reddito_livello3 += $liv3_e['totale_reddito'];
                $livello3 .= '
                                    <tr>
                                        <td>'.$liv3_e['numero'].'</td>
                                        <td>'.$liv3_e['descrizione'].'</td>
                                        <td class="text-right">'.numberFormat(abs($liv3_e['totale']), 2).'</td>
                                        <td class="text-right">'.numberFormat(abs($liv3_e['totale_reddito']), 2).'</td>
                                    </tr>';
            }
        }
    }

    if ($totale_livello3 != 0) {
        echo '
                            <tr>
                                <td><b>'.$liv2_e['numero'].'</b></td>
                                <td><b>'.$liv2_e['descrizione'].'</b></td>
                                <td class="text-right"><b>'.numberFormat(abs($totale_livello3), 2).'</b></td>
                                <td class="text-right"><b>'.numberFormat(abs($totale_reddito_livello3), 2).'</b></td>
                            </tr>';
        echo $livello3;
    }
}

echo '
                    <tr>
                        <td colspan="2"><h6><b>Totale costi</b></h6></td>
                        <td class="text-right" style="font-size:8pt;"><b>'.($totale_costi ? numberFormat(abs($totale_costi), 2) : '').'</b></td>
                        <td></td>
                    </tr>';
if ($utile_perdita < 0) {
    echo '
                        <tr>
                            <td colspan="2"><h6><b>Utile</b></h6></td>
                            <td class="text-right" style="font-size:8pt;"><b>'.numberFormat(abs($utile_perdita), 2).'</b></td>
                            <td></td>
                        </tr>
                        <tr>
                            <td colspan="2"><h6><b>Totale a pareggio</b></h6></td>
                            <td class="text-right" style="font-size:8pt;"><b>'.($totale_costi ? numberFormat(abs($totale_costi)) : 0) + ($utile_perdita ? numberFormat(abs($utile_perdita), 2) : 0).'</b></td>
                            <td></td>
                        </tr>';
}
echo '
                </tbody>
            </table>
        </div>
      
      <div class="col-md-6 pull-right" style="width:49%;" >

            <table class="table table-striped table-bordered" style=" overflow:hidden;" id="contents">
                <thead>
                    <tr>
                        <th colspan="4"><h5>Ricavi</h5></th>
                    </tr>
                    <tr>
                        <th width="12%">CONTO</th>
                        <th>DESCRIZIONE</th>
                        <th width="21%">SALDO</th>
                        <th width="21%">REDDITO</th>
                    </tr>
                </thead>
                <tbody>';
// Mostra le righe dei ricavi
foreach ($liv2_economico as $liv2_e) {
    $livello3 = '';
    $totale_livello3 = 0;
    $totale_reddito_livello3 = 0;
    foreach ($liv3_economico as $liv3_e) {
        if ($liv3_e['totale'] < 0) {
            // Visualizzo solo i conti di livello 3 relativi al conto di livello 2
            if ($liv2_e['id'] == $liv3_e['idpianodeiconti2'] && $liv3_e['totale'] != 0) {
                $totale_ricavi += $liv3_e['totale'];
                $totale_livello3 += $liv3_e['totale'];
                $totale_reddito_livello3 += $liv3_e['totale_reddito'];
                $livello3 .= '
                                <tr>
                                    <td>'.$liv3_e['numero'].'</td>
                                    <td>'.$liv3_e['descrizione'].'</td>
                                    <td class="text-right">'.numberFormat(abs($liv3_e['totale']), 2).'</td>
                                    <td class="text-right">'.numberFormat(abs($liv3_e['totale_reddito']), 2).'</td>
                                </tr>';
            }
        }
    }

    if ($totale_livello3 != 0) {
        echo '
                        <tr>
                            <td><b>'.$liv2_e['numero'].'</b></td>
                            <td><b>'.$liv2_e['descrizione'].'</b></td>
                            <td class="text-right"><b>'.numberFormat(abs($totale_livello3), 2).'</b></td>
                            <td class="text-right"><b>'.numberFormat(abs($totale_reddito_livello3), 2).'</b></td>
                        </tr>';
        echo $livello3;
    }
}
echo '
                    <tr>
                        <td colspan="2"><h6><b>Totale ricavi</b></h6></td>
                        <td style="font-size:8pt;" class="text-right"><b>'.($totale_ricavi ? numberFormat(abs($totale_ricavi), 2) : '').'</b></td>
                        <td></td>
                    </tr>';
if ($utile_perdita > 0) {
    echo '
                        <tr>
                            <td colspan="2"><h6><b>Perdita</b></h6></td>
                            <td class="text-right" style="font-size:8pt;"><b>'.numberFormat(abs($utile_perdita), 2).'</b></td>
                        </tr>
                        <tr>
                            <td colspan="2"><h6><b>Totale a pareggio</b></td>
                            <td class="text-right" style="font-size:8pt;"><b>'.$utile_perdita ? (numberFormat(abs($utile_perdita), 2)) : ''.'</b></td>
                            <td></td>
                        </tr>';
}
echo '
                </tbody>
            </table>
        
    </div>
</div>';
