<?php

include_once __DIR__.'/../../core.php';

$idconto = get('id');
$lvl = get('lvl');

// Info conto
if ($lvl == 2) {
    $query = 'SELECT *, idpianodeiconti1 AS idpianodeiconti FROM co_pianodeiconti2 WHERE id='.prepare($idconto);
} else {
    $query = 'SELECT *, idpianodeiconti2 AS idpianodeiconti FROM co_pianodeiconti3 WHERE id='.prepare($idconto);
}

$info = $dbo->fetchOne($query);

?><form action="<?php echo $rootdir; ?>/editor.php?id_module=<?php echo Modules::get('Piano dei conti')['id']; ?>" method="post">
    <input type="hidden" name="op" value="edit">
    <input type="hidden" name="backto" value="record-list">
    <input type="hidden" name="lvl" value="<?php echo $lvl; ?>">

    <input type="hidden" name="idpianodeiconti" value="<?php echo $info['idpianodeiconti']; ?>">
    <input type="hidden" name="idconto" value="<?php echo $info['id']; ?>">

    <div class="row">
        <div class="col-md-4">
            {[ "type": "text", "label": "<?php echo tr('Numero'); ?>", "name": "numero", "required": 1, "class": "text-center", "value": "<?php echo $info['numero']; ?>", "extra": "maxlength=\"6\"" ]}
        </div>

        <div class="col-md-8">
            {[ "type": "text", "label": "<?php echo tr('Descrizione'); ?>", "name": "descrizione", "required": 1, "value": <?php echo json_encode($info['descrizione']); ?> ]}
        </div>
    </div>
    <br>

    <div class="pull-right">
        <button type="submit" class="btn btn-primary">
            <i class="fa fa-edit"></i> <?php echo tr('Modifica'); ?>
        </button>
    </div>
    <div class="clearfix"></div>
</form>

