<?php

include_once __DIR__.'/init.php';

echo '
<form action="" method="post" id="form-variante">
    <input type="hidden" name="op" value="edit-variante">
    <input type="hidden" name="backto" value="record-edit">
    <input type="hidden" name="id_articolo" value="'.get('id_articolo').'">

    <div class="row">';

$attributi = $combinazione->attributi;
foreach ($attributi as $key => $attributo) {
    $value = $dbo->fetchOne('SELECT mg_valori_attributi.id AS valore FROM mg_valori_attributi LEFT JOIN mg_articolo_attributo ON mg_articolo_attributo.id_valore=mg_valori_attributi.id WHERE id_articolo='.prepare(get('id_articolo')).' AND id_attributo='.prepare($attributo->id).'  AND deleted_at IS NULL')['valore'];
    echo '
        <div class="col-md-4">
            {[ "type": "select", "label": "'.$attributo->getTranslation('title').'", "name": "attributo['.$key.']", "values": "query=SELECT id, nome AS descrizione FROM mg_valori_attributi WHERE id_attributo = '.prepare($attributo->id).' AND deleted_at IS NULL", "value": "'.$value.'", "required": 1 ]}
		</div>';
}
echo '
    </div>

    <div class="alert alert-info hidden" id="variante-esistente">
        <i class="fa fa-info-circle"></i> '.tr('La variante indicata è già presente per la combinazione corrente').'.
    </div>

    <!-- PULSANTI -->
    <div class="row">
        <div class="col-md-12 text-right">
            <button type="submit" class="btn btn-success">
                <i class="fa fa-check"></i> '.tr('Salva').'
            </button>
        </div>
    </div>
</form>

<script>
    $(document).ready(init);
</script>';
