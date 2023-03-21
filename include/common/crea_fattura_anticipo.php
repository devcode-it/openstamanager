<?php
include_once __DIR__.'/../../core.php';
use Modules\Ordini\Ordine;

$documento = Ordine::find($options['id_documento']);
$dir = $documento->direzione;

//$name = !empty($documento) ? $documento->module : $options['module'];

$name = 'Fatture di vendita'; //entrata

$final_module = Modules::get($name);

$descrizione = tr('Acconto sull\'ordine ') . $documento->numero_esterno . tr(' del ') . Translator::dateToLocale($documento->data);
$valore_anticipo = $options['value'];

//tipo documento predefinito
$tipologia = $dbo->fetchOne(
    'SELECT id, CONCAT(codice_tipo_documento_fe, \' - \', descrizione) AS descrizione
    FROM co_tipidocumento
    WHERE enabled = 1 AND dir = '.prepare($dir).' AND descrizione = "Acconto/anticipo su fattura"
    ORDER BY codice_tipo_documento_fe'
);

//piano predefinito
$piano = $dbo->fetchOne(
    'SELECT id
    FROM co_pianodeiconti3
    WHERE descrizione = '.prepare(($dir == 'entrata') ? 'Anticipo clienti' : 'Anticipo fornitori')
);

//sezione predefinita
$id_segment = $dbo->fetchOne(
    'SELECT id
    FROM zz_segments
    WHERE is_sezionale = 1 AND name = "Fatture differite"'
);

//zz_settings iva_predefinita
$iva_predefinita = setting('Iva predefinita');
$iva = $dbo->fetchOne(
    'SELECT id, descrizione, percentuale
    FROM co_iva
    WHERE id = '.prepare($iva_predefinita)
);
?>

<input type="hidden" name="id_documento" value="<?php echo $options['id_documento']; ?>">
<input type="hidden" name="type" value="<?php echo $options['type']; ?>">
<input type="hidden" name="class" value="<?php echo get_class($documento); ?>">

<div class="box box-warning">
    <div class="box-header with-border">
        <h3 class="box-title"><?php echo tr('Nuovo documento'); ?></h3>
    </div>
    <div class="box-body">

        <div class="row">
            <input type="hidden" name="create_document" value="on" />

            <div class="col-md-6">
                {[ "type": "date", "label": "<?php echo tr('Data del documento'); ?>", "name": "data", "required": 0, "value": "-now-" ]}
            </div>

            <div class="col-md-6">
                {[ "type": "text", "label": "<?php echo tr('Tipo documento'); ?>", "name": "tipologia", "required": 1, "readonly": 1, "value": "<?php echo $tipologia['descrizione']; ?>" ]}
                {[ "type": "hidden", "name": "idtipodocumento", "value": "<?php echo $tipologia['id']; ?>" ]}
            </div>

            <div class="col-md-6">
                {[ "type": "select", "label": "<?php echo tr('Sezionale'); ?>", "name": "id_segment", "required": 1, "ajax-source": "segmenti", "select-options": <?php echo json_encode(['id_module' => $final_module['id'], 'is_sezionale' => 1]); ?>, "value": "<?php echo $id_segment['id']; ?>" ]}
            </div>

            <div class="col-md-6">
                {[ "type": "select", "label": "<?php echo tr('Conto'); ?>", "name": "id_conto", "required": 1, "value": "<?php echo $piano['id']; ?>", "ajax-source": "<?php echo ($dir == 'entrata' ? 'conti-vendite' : 'conti-acquisti-totali'); ?>" ]}
            </div>

            <div class="col-md-6">
                {[ "type": "select", "label": "<?php echo tr('Iva'); ?>", "name": "id_iva", "required": 1, "value": "<?php echo $iva['id']; ?>", "ajax-source": "iva" ]}
            </div>

            <div class="col-md-12">
                {[ "type": "textarea", "label": "<?php echo tr('Note della fattura'); ?>", "name": "note", "required": 0 ]}
            </div>
        </div>
    </div>
</div>

<!-- Righe del documento -->
<div class="box box-success">
    <div class="box-header with-border">
        <h3 class="box-title"><?php echo tr('Righe prviste'); ?></h3>
    </div>

    <table class="box-body table table-striped table-hover table-condensed">
        <thead>
            <tr>
                <th><?php echo tr('Descrizione'); ?></th>
                <th width="10%" class="text-center"><?php echo tr('Q.tà'); ?></th>
                <th width="15%"><?php echo tr('Prezzo unitario'); ?></th>
            </tr>
        </thead>
        <tbody id="righe_previste">
            <?php
                //replace in valore_anticipo , with .
                $anticipo = str_replace('.', '', $valore_anticipo);
                $anticipo = str_replace(',', '.', $anticipo);
            ?>
            <tr>
                {[ "type": "hidden", "name": "descrizione", "value": "<?php echo $descrizione; ?>" ]}
                {[ "type": "hidden", "name": "anticipo", "value": "<?php echo $anticipo; ?>" ]}
                {[ "type": "hidden", "name": "id_acconto", "value": "<?php echo $options['id_acconto']; ?> ]"]}

                <td style="vertical-align:middle">
                    <?php echo $descrizione; ?>
                </td>
                <td class="text-center" style="vertical-align:middle">
                    1
                </td>
                <td class="text-center" style="vertical-align:middle">
                    <?php echo $valore_anticipo . ' ' . currency(); ?>
                </td>
            </tr>
        </tbody>
    </table>
</div>
