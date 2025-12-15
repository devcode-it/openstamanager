<?php

include_once __DIR__.'/../../../core.php';

use Models\Module;

$idautomezzo = get('id_record');
$idviaggio = get('idviaggio');

// Recupero i dati del viaggio
$viaggio = $dbo->fetchOne('SELECT * FROM an_automezzi_viaggi WHERE id='.prepare($idviaggio));

if (empty($viaggio)) {
    echo '<p>'.tr('Viaggio non trovato').'</p>';
    exit;
}

// Verifico se è già firmato
if (!empty($viaggio['firma_data'])) {
    $module = Module::where('name', 'Automezzi')->first();
    $uploads = $module->files($idautomezzo, true);
    // Cerca il primo file con key che inizia con 'signature'
    foreach ($uploads as $upload) {
        if ($upload->key == 'signature_viaggio:'.$viaggio['id']) {
            $directory_firma = '/files/'.$module->directory.'/';
            $image = $directory_firma.$upload->filename;
        }
    }
    $url = $image ? base_path_osm().$image : null;
    echo '
    <div class="alert alert-info">
        <i class="fa fa-info-circle"></i> '.tr('Questo viaggio è già stato firmato da _NOME_ in data _DATA_', [
            '_NOME_' => $viaggio['firma_nome'],
            '_DATA_' => Translator::timestampToLocale($viaggio['firma_data']),
        ]).'
    </div>
    <div class="text-center">
        <img src="'.$url.'" style="max-width: 100%; border: 1px solid #ddd; padding: 10px;">
    </div>';
    exit;
}

// HTML per la visualizzazione anteprima
echo '
<div id="preview">
    <div class="card card-info">
        <div class="card-header">
            <h3 class="card-title">'.tr('Dettagli viaggio').'</h3>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <p><strong>'.tr('Data inizio').':</strong> '.Translator::timestampToLocale($viaggio['data_inizio']).'</p>
                    <p><strong>'.tr('Data fine').':</strong> '.Translator::timestampToLocale($viaggio['data_fine']).'</p>
                    <p><strong>'.tr('Destinazione').':</strong> '.$viaggio['destinazione'].'</p>
                </div>
                <div class="col-md-6">
                    <p><strong>'.tr('KM Inizio').':</strong> '.$viaggio['km_inizio'].'</p>
                    <p><strong>'.tr('KM Fine').':</strong> '.$viaggio['km_fine'].'</p>
                    <p><strong>'.tr('Motivazione').':</strong> '.$viaggio['motivazione'].'</p>
                </div>
            </div>
        </div>
    </div>

    <button type="button" class="btn btn-success btn-block btn-lg" id="firma">
        <i class="fa fa-pencil"></i> '.tr('Firma').'
    </button>
    <br>

    <div class="clearfix"></div>
</div>';

?>

<form action="" method="post" id="form-firma" class="hide">
    <input type="hidden" name="op" value="firma_viaggio">
    <input type="hidden" name="backto" value="record-edit">
    <input type="hidden" name="id_module" value="<?php echo $id_module; ?>">
    <input type="hidden" name="id_record" value="<?php echo $idautomezzo; ?>">
    <input type="hidden" name="idviaggio" value="<?php echo $idviaggio; ?>">

    <div class="row">
        <div class="col-md-12">
            {[ "type": "text", "label": "<?php echo tr('Nome e cognome'); ?>", "name": "firma_nome", "required": 1 ]}
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div id="signature-pad" class="signature-pad">
                <canvas id="canvas" onselectstart="return false"></canvas>
                <input type="hidden" name="firma_base64" id="firma_base64" value="">
            </div>
        </div>
    </div>

    <br>

    <div class="row">
        <div class="col-md-6">
            <button type="button" class="btn btn-danger" data-action="clear">
                <i class="fa fa-eraser"></i> <?php echo tr('Cancella firma'); ?>
            </button>
        </div>
        <div class="col-md-6">
            <button type="submit" class="btn btn-success pull-right" data-action="save">
                <i class="fa fa-check"></i> <?php echo tr('Salva firma'); ?>
            </button>
        </div>
    </div>

</form>
<div class="clearfix"></div>

<script type="text/javascript">
    $(document).ready(function() {
        $('#firma').on('click', function() {
            $('#firma').addClass('hide');
            $('#form-firma').removeClass('hide');
        });

        var wrapper = document.getElementById("signature-pad"),
            clearButton = document.querySelector("[data-action=clear]"),
            saveButton = document.querySelector("[data-action=save]"),
            canvas = document.getElementById("canvas");

        var signaturePad = new SignaturePad(canvas, {
            backgroundColor: 'rgb(255,255,255)'
        });

        function resizeCanvas() {
            image_data = signaturePad.toDataURL();

            var ratio = Math.max(window.devicePixelRatio || 1, 1);
            canvas.width = canvas.offsetWidth * ratio;
            canvas.height = canvas.offsetHeight * ratio;
            canvas.getContext("2d").scale(ratio, ratio);
            signaturePad.clear();

            signaturePad.fromDataURL(image_data);
        }

        window.addEventListener("resize", resizeCanvas);
        $('#firma').click(resizeCanvas);

        clearButton.addEventListener("click", function(event) {
            signaturePad.clear();
        });

        saveButton.addEventListener("click", function(event) {
            if (signaturePad.isEmpty()) {
                alert(globals.translations.signatureMissing);
                event.preventDefault();
                return;
            } else {
                image_data = signaturePad.toDataURL("image/jpeg", 100);
                $('#firma_base64').val(image_data);
            }
        });
    });
</script>

