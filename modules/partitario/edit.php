<?php

include_once __DIR__.'/../../core.php';

// Verifico se è già stata eseguita l'apertura bilancio
$bilancio_gia_aperto = $dbo->fetchNum('SELECT id FROM co_movimenti WHERE is_apertura=1 AND data BETWEEN '.prepare($_SESSION['period_start']).' AND '.prepare($_SESSION['period_end']));

$msg = tr('Sei sicuro di voler aprire il bilancio?');
$btn_class = 'btn-info';

if ($bilancio_gia_aperto) {
    $msg .= ' '.tr('I movimenti di apertura già esistenti verranno annullati e ricreati').'.';
    $btn_class = 'btn-default';
}
?>

<div class="text-right">
    <button type="button" class="btn btn-lg <?php echo $btn_class; ?>" data-op="apri-bilancio" data-title="<?php echo tr('Apertura bilancio'); ?>" data-backto="record-list" data-msg="<?php echo $msg; ?>" data-button="<?php echo tr('Riprendi saldi'); ?>" data-class="btn btn-lg btn-warning" onclick="message( this );"><i class="fa fa-folder-open"></i> <?php echo tr('Apertura bilancio'); ?></button>
</div>

<?php
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
        <div class="pull-right">
            '.Prints::getLink('Mastrino', $conto_secondo['id'], 'btn-info btn-xs', '', null, 'lev=2').'

            <button type="button" class="btn btn-warning btn-xs" data-toggle="tooltip" title="Modifica questo conto..." onclick="launch_modal(\'Modifica conto\', \''.$structure->fileurl('edit_conto.php').'?id='.$conto_secondo['id'].'&lvl=2\');">
                <i class="fa fa-edit"></i>
            </button>
        </div>

        <h5><b>'.$conto_secondo['numero'].' '.$conto_secondo['descrizione'].'</b></h5>

        <div style="padding-left:10px;">
            <table class="table table-striped table-hover table-condensed" style="margin-bottom:0;">';

        // Livello 3
        $query3 = 'SELECT `co_pianodeiconti3`.*, movimenti.numero_movimenti, movimenti.totale, anagrafica.idanagrafica, anagrafica.deleted_at FROM `co_pianodeiconti3` LEFT OUTER JOIN (SELECT idanagrafica, idconto_cliente, idconto_fornitore, deleted_at FROM an_anagrafiche) AS anagrafica ON co_pianodeiconti3.id IN (anagrafica.idconto_cliente, idconto_fornitore) LEFT OUTER JOIN (SELECT COUNT(idconto) AS numero_movimenti, idconto, SUM( ROUND(totale,2)) AS totale FROM co_movimenti  WHERE data BETWEEN '.prepare($_SESSION['period_start']).' AND '.prepare($_SESSION['period_end']).' GROUP BY idconto) movimenti ON co_pianodeiconti3.id=movimenti.idconto WHERE `idpianodeiconti2` = '.prepare($conto_secondo['id']).' ORDER BY numero ASC';
        $terzo_livello = $dbo->fetchArray($query3);

        foreach ($terzo_livello as $conto_terzo) {
            // Se il conto non ha documenti collegati posso eliminarlo
            $movimenti = $conto_terzo['numero_movimenti'];

            $totale_conto = $conto_terzo['totale'];
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
                <tr>
                    <td>';

            // Possibilità di esplodere i movimenti del conto
            if (!empty($movimenti)) {
                echo '
                    <a href="javascript:;" class="btn btn-primary btn-xs plus-btn"><i class="fa fa-plus"></i></a>';
            }

            // Span con i pulsanti
            echo '
                    <span class="hide tools pull-right">';

            //  Possibilità di visionare l'anagrafica
            $id_anagrafica = $conto_terzo['idanagrafica'];
            $anagrafica_deleted = $conto_terzo['deleted_at'];
            echo    isset($id_anagrafica) ? Modules::link('Anagrafiche', $id_anagrafica, ' <i title="'.(isset($anagrafica_deleted) ? 'Anagrafica eliminata' : 'Visualizza anagrafica').'" class="btn btn-'.(isset($anagrafica_deleted) ? 'danger' : 'primary').' btn-xs fa fa-user" ></i>', null) : '';

            // Stampa mastrino
            if (!empty($movimenti)) {
                echo '
                        '.Prints::getLink('Mastrino', $conto_terzo['id'], 'btn-info btn-xs', '', null, 'lev=3');
            }

            // Possibilità di modificare il nome del conto livello3
            echo '
                        <button type="button" class="btn btn-warning btn-xs" data-toggle="tooltip" title="Modifica questo conto..." onclick="launch_modal(\'Modifica conto\', \''.$structure->fileurl('edit_conto.php').'?id='.$conto_terzo['id'].'\');">
                            <i class="fa fa-edit"></i>
                        </button>';

            // Possibilità di eliminare il conto se non ci sono movimenti collegati
            if ($numero_movimenti <= 0) {
                echo '
                        <a class="btn btn-danger btn-xs ask" data-toggle="tooltip" title="'.tr('Elimina').'" data-backto="record-list" data-op="del" data-idconto="'.$conto_terzo['id'].'">
                            <i class="fa fa-trash"></i>
                        </a>';
            }

            echo  ' </span>';

            // Span con info del conto
            echo '
                    <span  style="'.(!empty($movimenti) ? '' : 'opacity: 0.5;').'" class="clickable" id="movimenti-'.$conto_terzo['id'].'">';

            echo  '
                            &nbsp;'.$conto_secondo['numero'].'.'.$conto_terzo['numero'].' '.$conto_terzo['descrizione'];

            echo '  </span>';

            echo '
                        <div id="conto_'.$conto_terzo['id'].'" style="display:none;"></div>
                    </td>

                    <td width="100" class="text-right" valign="top"  style="'.(!empty($movimenti) ? '' : 'opacity: 0.5;').'">
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

// Verifico se è già stata eseguita l'apertura bilancio
$bilancio_gia_chiuso = $dbo->fetchNum('SELECT id FROM co_movimenti WHERE is_chiusura=1 AND data BETWEEN '.prepare($_SESSION['period_start']).' AND '.prepare($_SESSION['period_end']));

$msg = tr('Sei sicuro di voler aprire il bilancio?');
$btn_class = 'btn-info';

if ($bilancio_gia_chiuso) {
    $msg .= ' '.tr('I movimenti di apertura già esistenti verranno annullati e ricreati').'.';
    $btn_class = 'btn-default';
}
?>

<div class="text-right">
    <button type="button" class="btn btn-lg <?php echo $btn_class; ?>" data-op="chiudi-bilancio" data-title="<?php echo tr('Chiusura bilancio'); ?>" data-backto="record-list" data-msg="<?php echo $msg; ?>" data-button="<?php echo tr('Chiudi bilancio'); ?>" data-class="btn btn-lg btn-primary" onclick="message( this );"><i class="fa fa-folder"></i> <?php echo tr('Chiusura bilancio'); ?></button>
</div>

<script>
var tr = '';
$(document).ready(function(){
    $("tr").each(function() {
        $(this).on("mouseover", function() {
            $(this).find(".tools").removeClass("hide");
        });

        $(this).on("mouseleave", function() {
            $(this).find(".tools").addClass("hide");
        });
    });

    $("span[id^=movimenti-]").each(function() {
        $(this).on("click", function() {
            var movimenti = $(this).parent().find("div[id^=conto_]");

            if(!movimenti.html()) {
                var id_conto = $(this).attr("id").split("-").pop();

                load_movimenti(movimenti.attr("id"), id_conto);
            } else {
                movimenti.slideToggle();
            }

            $(this).find(".plus-btn i").toggleClass("fa-plus").toggleClass("fa-minus");
        });
    })
});

function add_conto(id) {
    launch_modal("<?php echo tr('Nuovo conto'); ?>",  "<?php echo $structure->fileurl('add_conto.php'); ?>?id=" + id);
}

function load_movimenti(selector, id_conto) {
	$("#main_loading").show();

    $.ajax({
        url: "<?php echo $structure->fileurl('dettagli_conto.php'); ?>",
        type: "get",
        data: {
            id_module: globals.id_module,
            id_conto: id_conto,
        },
        success: function(data){
           $("#" + selector).html(data);
           $("#" + selector).slideToggle();

           $("#main_loading").fadeOut();
        }
	});
}
</script>
