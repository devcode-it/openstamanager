<?php

include_once __DIR__.'/../../core.php';

// Livello 1
$query1 = 'SELECT * FROM `co_pianodeiconti1` ORDER BY id DESC';
$primo_livello = $dbo->fetchArray($query1);

foreach ($primo_livello as $conto_primo) {
    $totale_attivita = [];
    $totale_passivita = [];

    $costi = [];
    $ricavi = [];

    $titolo = $conto_primo['descrizione'] == 'Economico' ? tr('Conto economico') : tr('Stato patrimoniale');

    echo '
<hr>
<h2>'.$titolo.'</h2>
    <div class="pull-right">
        <br>'.Prints::getLink('Mastrino', $conto_primo['id'], null, tr('Stampa'), null, 'lev=1').'
    </div>
    <div class="clearfix"></div>
    
    <div style="padding-left:10px;">';

    // Livello 2
    $query2 = "SELECT * FROM `co_pianodeiconti2` WHERE idpianodeiconti1='".$conto_primo['id']."' ORDER BY numero ASC";
    $secondo_livello = $dbo->fetchArray($query2);

    foreach ($secondo_livello as $conto_secondo) {
        // Livello 2
        echo '
        <div>
            '.Prints::getLink('Mastrino', $conto_secondo['id'], 'btn-info btn-xs', '', null, 'lev=2').'
            <b>'.$conto_secondo['numero'].' '.$conto_secondo['descrizione'].'</b><br>
        </div>
        
        <div style="padding-left:10px;">
            <table class="table table-striped table-hover table-condensed" style="margin-bottom:0;">';

        // Livello 3
        $query3 = 'SELECT `co_pianodeiconti3`.*, `clienti`.`idanagrafica` AS id_cliente, `fornitori`.`idanagrafica` AS id_fornitore FROM `co_pianodeiconti3` LEFT OUTER JOIN `an_anagrafiche` `clienti` ON `clienti`.`idconto_cliente` = `co_pianodeiconti3`.`id` LEFT OUTER JOIN `an_anagrafiche` `fornitori` ON `fornitori`.`idconto_fornitore` = `co_pianodeiconti3`.`id` WHERE `idpianodeiconti2` = '.prepare($conto_secondo['id']).' ORDER BY numero ASC';
        $terzo_livello = $dbo->fetchArray($query3);

        foreach ($terzo_livello as $conto_terzo) {
            // Se il conto non ha documenti collegati posso eliminarlo
            $numero_movimenti = $dbo->fetchNum('SELECT id FROM co_movimenti WHERE idconto = '.prepare($conto_terzo['id']));

            // Calcolo totale conto da elenco movimenti di questo conto
            $query = 'SELECT co_movimenti.*, dir FROM co_movimenti
                LEFT OUTER JOIN co_documenti ON co_movimenti.iddocumento = co_documenti.id
                LEFT OUTER JOIN co_tipidocumento ON co_documenti.idtipodocumento = co_tipidocumento.id
            WHERE co_movimenti.idconto='.prepare($conto_terzo['id']).' AND co_movimenti.data >= '.prepare($_SESSION['period_start']).' AND co_movimenti.data <= '.prepare($_SESSION['period_end']).' ORDER BY co_movimenti.data DESC';
            $movimenti = $dbo->fetchArray($query);

            $totale_conto = sum(array_column($movimenti, 'totale'));
            $totale_conto = ($conto_primo['descrizione'] == 'Patrimoniale') ? $totale_conto : -$totale_conto;

            // Somma dei totali
            if ($conto_primo['descrizione'] == 'Patrimoniale') {
                if ($totale_conto > 0) {
                    $totale_attivita[] = abs($totale_conto);
                } else {
                    $totale_passivita[] = abs($totale_conto);
                }
            } else {
                if ($totale_conto > 0) {
                    $totale_ricavi[] = abs($totale_conto);
                } else {
                    $totale_costi[] = abs($totale_conto);
                }
            }

            echo '
                <tr style="'.(!empty($movimenti) ? '' : 'opacity: 0.5;').'">
                    <td><span class="clickable" id="movimenti-'.$conto_terzo['id'].'">';

            if (!empty($movimenti)) {
                echo '
                    <a href="javascript:;" class="btn btn-primary btn-xs plus-btn"><i class="fa fa-plus"></i></a>';
            }

            $id_anagrafica = $conto_terzo['id_cliente'] ?: $conto_terzo['id_fornitore'];

            echo '
                    <span class="hide tools">';

            // Stampa mastrino
            if (!empty($movimenti)) {
                echo '
                        '.Prints::getLink('Mastrino', $conto_terzo['id'], 'btn-info btn-xs', '', null, 'lev=3');
            }

            if ($numero_movimenti <= 0 && !empty($conto_terzo['can_delete'])) {
                echo '
                        <a class="btn btn-danger btn-xs ask" data-toggle="tooltip" title="'.tr('Elimina').'" data-backto="record-list" data-op="del" data-idconto="'.$conto_terzo['id'].'">
                            <i class="fa fa-trash"></i>
                        </a>';
            }

            // Possibilità di modificare il nome del conto livello3
            if (!empty($conto_terzo['can_edit'])) {
                echo '
                        <button type="button" class="btn btn-warning btn-xs" data-toggle="tooltip" title="Modifica questo conto..." onclick="launch_modal(\'Modifica conto\', \''.$structure->fileurl('edit_conto.php').'?id='.$conto_terzo['id'].'\', 1);">
                            <i class="fa fa-edit"></i>
                        </button>';
            }

            echo  '
                        </span>
                            &nbsp;'.$conto_secondo['numero'].'.'.$conto_terzo['numero'].' '.$conto_terzo['descrizione'].' '.(isset($id_anagrafica) ? Modules::link('Anagrafiche', $id_anagrafica, 'Anagrafica', null) : '').'
                        </span>
                
                        <div id="conto_'.$conto_terzo['id'].'" style="display:none;"></div>
                    </td>
                    
                    <td width="100" align="right" valign="top">
                        '.moneyFormat(sum($totale_conto), 2).'
                    </td>
                </tr>';
        }

        echo '
            </table>';

        // Possibilità di inserire un nuovo conto
        echo '
            <button type="button" class="btn btn-xs btn-primary" data-toggle="tooltip" title="'.tr('Aggiungi un nuovo conto...').'" onclick="add_conto('.$conto_secondo['id'].')">
                <i class="fa fa-plus-circle"></i>
            </button>
    
            <br><br>
        </div>';
    }

    echo '
    </div>
    
    <table class="table table-condensed table-hover">'
    ;
    // Riepiloghi
    if ($conto_primo['descrizione'] == 'Patrimoniale') {
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

        // Attività
        echo '
        <tr>
            <th class="text-right">
                <big>'.tr('Totale attività').':</big>
            </th>
            <td class="text-right" width="150">
                <big>'.moneyFormat($attivita, 2).'</big>
            </td>
            <td width="50"></td>';

        // Passività
        echo '
            <th class="text-right">
                <big>'.tr('Passività').':</big>
            </th>
            <td class="text-right" width="150">
                <big>'.moneyFormat($passivita, 2).'</big>
            </td>
        </tr>';

        // Perdita d'esercizio
        if ($utile_perdita < 0) {
            echo '
        <tr>
            <th class="text-right">
                <big>'.tr("Perdita d'esercizio").':</big>
            </th>
            <td class="text-right">
                <big>'.moneyFormat(sum($utile_perdita), 2).'</big>
            </td>
            <td></td>
            <td></td>
            <td></td>
        </tr>';
        } else {
            echo '
        <tr>
            <td></td>
            <td></td>
            <td></td>
            <th class="text-right">
                <big>'.tr('Utile').':</big>
            </th>
            <td class="text-right">
                <big>'.moneyFormat(sum($utile_perdita), 2).'</big>
            </td>
        </tr>';
        }

        // Totale a pareggio
        echo '
        <tr>
            <th class="text-right">
                <big>'.tr('Totale a pareggio').':</big>
            </th>
            <td class="text-right" width="150">
                <big>'.moneyFormat(sum($pareggio1), 2).'</big>
            </td>
            <td width="50"></td>
            
            <th class="text-right">
                <big>'.tr('Totale a pareggio').':</big>
            </th>
            <td class="text-right" width="150">
                <big>'.moneyFormat(sum($pareggio2), 2).'</big>
            </td>
        </tr>';
    } else {
        echo '
        <tr>
            <th class="text-right">
                <big>'.tr('Ricavi').':</big>
            </th>
            <td class="text-right" width="150">
                <big>'.moneyFormat(sum($totale_ricavi), 2).'</big>
            </td>
        </tr>
        
        <tr>
            <th class="text-right">
                <big>'.tr('Costi').':</big>
            </th>
            <td class="text-right" width="150">
                <big>'.moneyFormat(sum($totale_costi), 2).'</big>
            </td>
        </tr>
        
        <tr>
            <th class="text-right">
                <big>'.tr('Utile/perdita').':</big>
            </th>
            <td class="text-right" width="150">
                <big>'.moneyFormat(sum($totale_ricavi) - abs(sum($totale_costi)), 2).'</big>
            </td>
        </tr>';
    }

    echo '
    </table>';
}

echo '
<script>
$(document).ready(function(){
    $("span[id^=movimenti-]").each(function() {
        $(this).on("mouseover", function() {
            $(this).find(".tools").removeClass("hide");
        });
        
        $(this).on("mouseleave", function() {
            $(this).find(".tools").addClass("hide");
        });
        
        $(this).on("click", function() {
            var movimenti = $(this).parent().find("div[id^=conto_]");            

            if(!movimenti.html()) {
                var id_conto = movimenti.attr("id").split("_").pop();

                load_movimenti(movimenti.attr("id"), id_conto);
            } else {
                movimenti.slideToggle();
            }
            
            $(this).find(".plus-btn i").toggleClass("fa-plus").toggleClass("fa-minus");
        });
    })
});

function add_conto(id) {
    launch_modal("'.tr('Nuovo conto').'",  "'.$structure->fileurl('add_conto.php').'?id=" + id, 1 );
}

function load_movimenti(selector, id_conto) {
    $.ajax({
        url: "'.$structure->fileurl('dettagli_conto.php').'",
        type: "get",
        data: {
            id_module: globals.id_module,
            id_conto: id_conto,
        },
        success: function(data){
           $("#" + selector).html(data);
           
           $("#" + selector).slideToggle();
        }
	});
}
</script>';
