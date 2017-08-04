<?php

include_once __DIR__.'/../../core.php';

$idconto = get('id');

?><form action="<?php echo $rootdir ?>/editor.php?id_module=<?php echo Modules::getModule('Piano dei conti')['id'] ?>" method="post">
    <input type="hidden" name="op" value="add">
    <input type="hidden" name="backto" value="record-list">

    <input type="hidden" name="idpianodeiconti2" value="<?php echo $idconto ?>">

    <div class="row">

        <div class="col-md-4">
            {[ "type": "text", "label": "<?php echo _('Numero'); ?>", "name": "numero", "required": 1, "class": "text-center", "value": "000000", "extra": "maxlength=\"6\"" ]}
        </div>

        <div class="col-md-8">
            {[ "type": "text", "label": "<?php echo _('Descrizione'); ?>", "name": "descrizione", "required": 1 ]}
        </div>
    </div>
    <br>

    <div class="pull-right">
        <button type="submit" class="btn btn-primary"><i class="fa fa-plus"></i> <?php echo _('Aggiungi'); ?></button>
    </div>
    <div class="clearfix"></div>
</form>

