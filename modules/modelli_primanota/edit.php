<?php

include_once __DIR__.'/../../core.php';

?><form action="" method="post" id="edit-form">
	<input type="hidden" name="op" value="editriga">
	<input type="hidden" name="backto" value="record-edit">
	<input type="hidden" name="id_record" value="<?php echo $id_record; ?>">
	<input type="hidden" name="idmastrino" value="<?php echo $records[0]['idmastrino']; ?>">
	<input type="hidden" name="iddocumento" value="<?php echo $records[0]['iddocumento']; ?>">


    <div class="row">
		<div class="col-md-12">
			{[ "type": "text", "label": "<?php echo tr('Causale predefinita'); ?>", "name": "descrizione", "required": 1, "value": "$descrizione$" ]}
		</div>
	</div>


	<?php
    $conti3 = [];    // contenitore conti di terzo livello
    $idmastrino = $records[0]['idmastrino'];

    // Salvo l'elenco conti in un array (per non fare il ciclo ad ogni riga)
    $query2 = 'SELECT * FROM co_pianodeiconti2';
    $conti2 = $dbo->fetchArray($query2);
    for ($x = 0; $x < sizeof($conti2); ++$x) {
        $query3 = 'SELECT * FROM co_pianodeiconti3 WHERE idpianodeiconti2='.prepare($conti2[$x]['id']);
        $rs3 = $dbo->fetchArray($query3);
        for ($y = 0; $y < sizeof($rs3); ++$y) {
            // Creo un array con le descrizioni dei conti di livello 3 che ha come indice l'id del livello2 e del livello3
            $conti3[$rs3[$y]['idpianodeiconti2']][$y]['id'] = $rs3[$y]['id'];
            $conti3[$rs3[$y]['idpianodeiconti2']][$y]['descrizione'] = $conti2[$x]['numero'].'.'.$rs3[$y]['numero'].' '.$rs3[$y]['descrizione'];
        }
    }

    /*
        Form di modifica riga movimento
    */
    // Lettura movimenti del mastrino selezionato
    $query = 'SELECT * FROM co_movimenti_modelli WHERE idmastrino='.prepare($records[0]['idmastrino']);
    $rs = $dbo->fetchArray($query);
    $n = sizeof($rs);

    echo '
    <table class="table table-striped table-condensed table-hover table-bordered"
        <tr>
            <th>'.tr('Conto').'</th>
        </tr>';

    for ($i = 0; $i < 10; ++$i) {
		
		($i<=1) ? $required = 1 : $required = 0;
			
        // Conto
        echo '
			<tr>
				<td>
					{[ "type": "select", "name": "idconto['.$i.']", "value": "'.$rs[$i]['idconto'].'", "ajax-source": "conti", "required": "'.$required.'" ]}
				</td>';

        echo '
			</tr>';
    }

    // Totale per controllare sbilancio
    
    //  Verifica sbilancio
    echo '
        </table>';
    ?>


	<script type="text/javascript">
		$(document).ready( function(){
			$('select').on('change', function(){
                if($(this).parent().parent().find('input[disabled]').length != 1){
                    if($(this).val()) {
                        $(this).parent().parent().find('input').prop("disabled", false);
                    }
                    else{
                        $(this).parent().parent().find('input').prop("disabled", true);
                        $(this).parent().parent().find('input').val("");
                    }
                }
            });
		});
	</script>
	
</form>

<a class="btn btn-danger ask" data-backto="record-list" data-idmastrino="<?php echo $records[0]['idmastrino']; ?>">
    <i class="fa fa-trash"></i> <?php echo tr('Elimina'); ?>
</a>
