<?php

include_once __DIR__.'/init.php';

$varianti_esistenti = $database->fetchArray('SELECT GROUP_CONCAT(`variazioni`.`id_valore`) AS variante
FROM (SELECT `mg_articolo_attributo`.`id_valore`, `mg_articolo_attributo`.`id_articolo`
        FROM `mg_articolo_attributo`
            INNER JOIN `mg_valori_attributi` ON `mg_valori_attributi`.`id` = `mg_articolo_attributo`.`id_valore`
            INNER JOIN `mg_attributi` ON `mg_attributi`.`id` = `mg_valori_attributi`.`id_attributo`

            INNER JOIN `mg_articoli` ON `mg_articoli`.`id` = `mg_articolo_attributo`.`id_articolo`
            INNER JOIN `mg_combinazioni` ON `mg_combinazioni`.`id` = `mg_articoli`.`id_combinazione`
            INNER JOIN `mg_attributo_combinazione` ON `mg_attributo_combinazione`.`id_combinazione` = `mg_combinazioni`.`id` AND `mg_attributo_combinazione`.`id_attributo` = `mg_attributi`.`id`
        WHERE `mg_articoli`.`deleted_at` IS NULL AND `mg_articoli`.`id_combinazione` = '.prepare($combinazione->id).'
        ORDER BY `mg_attributo_combinazione`.`order`
    ) AS variazioni
GROUP BY `variazioni`.`id_articolo`');
$varianti_esistenti = array_column($varianti_esistenti, 'variante');

echo '
<form action="" method="post" id="form-variante">
    <input type="hidden" name="op" value="gestione-variante">
    <input type="hidden" name="backto" value="record-edit">

    <p>'.tr('Completa le informazioni dei diversi Attributi per generare una variante della Combinazione').'.</p>

    <div class="row">';

$attributi = $combinazione->attributi;
foreach ($attributi as $key => $attributo) {
    echo '
        <div class="col-md-4">
			{[ "type": "select", "label": "'.$attributo->nome.'", "name": "attributo['.$key.']", "values": "query=SELECT id, nome AS descrizione FROM mg_valori_attributi WHERE id_attributo = '.prepare($attributo->id).' AND deleted_at IS NULL", "required": 1 ]}
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
            <button type="submit" class="btn btn-primary">
                <i class="fa fa-plus"></i> '.tr('Aggiungi').'
            </button>
        </div>
    </div>
</form>

<script>
var varianti_esistenti = '.json_encode($varianti_esistenti).';
var form = $("#form-variante");

form.find("select").on("change", function () {
    const inputs = form.serializeArray();

    // Individuazione variante e completezza
    let incompleto = false;
    let valori = [];
    for (const {name, value} of inputs) {
        if (name.startsWith("attributo")){
            valori.push(value);
            incompleto |= !value;
        }
    }

    // Completamento informazioni
    const variante = valori.join(",");
    const variante_esistente = varianti_esistenti.includes(variante);

    // Disabilitazione/abilitazione pulsante di aggiunta
    const button = form.find("button");
    if (variante_esistente || incompleto){
        button.addClass("disabled").attr("disabled", true);
    } else {
        button.removeClass("disabled").attr("disabled", false);
    }

    // Messaggio informativo
    const info = form.find("#variante-esistente");
    if (variante_esistente) {
        info.removeClass("hidden");
    } else {
        info.addClass("hidden");
    }
});

$(document).ready(init);
</script>';
