<?php
include_once __DIR__.'/../../core.php';

$idconto = get('id');

//Info conto
$q = 'SELECT * FROM co_pianodeiconti3 WHERE id='.prepare($idconto);
$rs = $dbo->fetchArray($q);
$numero = $rs[0]['numero'];
$descrizione = $rs[0]['descrizione'];
$idpianodeiconti2 = $rs[0]['idpianodeiconti2'];

?><form action="<?php echo $rootdir ?>/editor.php?id_module=<?php echo Modules::getModule('Piano dei conti')['id'] ?>" method="post">
    <input type="hidden" name="op" value="edit">
    <input type="hidden" name="backto" value="record-list">
    <input type="hidden" name="idpianodeiconti2" value="<?php echo $idpianodeiconti2 ?>">
    <input type="hidden" name="idconto" value="<?php echo $idconto ?>">

    <div class="row">
        <div class="col-md-4">
            {[ "type": "text", "label": "<?php echo _('Numero'); ?>", "name": "numero", "required": 1, "class": "text-center", "value": "<?php echo $numero ?>", "extra": "maxlength=\"6\"" ]}
        </div>

        <div class="col-md-8">
            {[ "type": "text", "label": "<?php echo _('Descrizione'); ?>", "name": "descrizione", "required": 1, "value": "<?php echo $descrizione ?>" ]}
        </div>
    </div>
    <br>

    <div class="pull-right">
        <button type="submit" class="btn btn-primary"><i class="fa fa-edit"></i> Modifica</button>
    </div>
    <div class="clearfix"></div>
</form>

