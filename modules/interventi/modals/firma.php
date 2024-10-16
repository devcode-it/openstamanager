<?php
/*
 * OpenSTAManager: il software gestionale open source per l'assistenza tecnica e la fatturazione
 * Copyright (C) DevCode s.r.l.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

include_once __DIR__.'/../../../core.php';

use Models\PrintTemplate;
use Modules\Interventi\Intervento;

$id_records = explode(';', get('id_records'));
$id_print = setting('Stampa per anteprima e firma');
$template = PrintTemplate::find($id_print)->getTranslation('title');

$interventi_completati = [];
$interventi_firmati = [];
$interventi_da_firmare = [];
$records = [];
foreach ($id_records as $id) {
    $intervento = Intervento::find($id);

    if ($intervento->stato->is_completato) {
        $interventi_completati[] = $id;
    } elseif ($intervento->firma_file) {
        $interventi_firmati[] = $id;
        $records[] = $id;
    } else {
        $interventi_da_firmare[] = $id;
        $records[] = $id;
    }
}

echo '
<div class="row">
    <div class="col-md-4">
        <div class="card card-success">
            <div class="card-header">
                <h3 class="card-title">'.tr('Interventi da firmare').'</h3>
            </div>
            <div class="card-body">';
if ($interventi_da_firmare) {
    echo '
                <table class="table table-hover table-bordered table-sm">
                    <thead>
                        <tr>
                            <th>'.tr('Interventi').'</th>
                            <th class="text-center">#</th>
                        </tr>
                    </thead>
                    <tbody>';
    foreach ($interventi_da_firmare as $id) {
        $intervento = Intervento::find($id);
        echo '
                        <tr>
                            <td>
                                '.Modules::link('Interventi', $intervento->id, tr('Intervento num. _NUM_ del _DATE_', [
            '_NUM_' => $intervento->codice,
            '_DATE_' => Translator::dateToLocale($intervento->inizio),
        ])).'
                            </td>
                            <td class="text-center">
                                '.Prints::getLink($template, $id, 'btn btn-xs btn-primary', '', 'fa fa-print').'
                            </td>
                        <tr>';
    }
    echo '
                    </tbody>
                </table>';
} else {
    echo tr('Nessun Intervento.');
}
echo '
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card card-warning">
            <div class="card-header">
                <h3 class="card-title">'.tr('Interventi firmati non completati').'</h3>
            </div>
            <div class="card-body">';
if ($interventi_firmati) {
    echo '
                <table class="table table-hover table-bordered table-sm">
                    <thead>
                        <tr>
                            <th>'.tr('Interventi').'</th>
                            <th class="text-center">#</th>
                        </tr>
                    </thead>
                    <tbody>';
    foreach ($interventi_firmati as $id) {
        $intervento = Intervento::find($id);
        echo '
                        <tr>
                            <td>
                                '.Modules::link('Interventi', $intervento->id, tr('Intervento num. _NUM_ del _DATE_', [
            '_NUM_' => $intervento->codice,
            '_DATE_' => Translator::dateToLocale($intervento->inizio),
        ])).'
                            </td>
                            <td class="text-center">
                                '.Prints::getLink($template, $id, 'btn btn-xs btn-primary', '', 'fa fa-print').'
                            </td>
                        <tr>';
    }
    echo '
                    </tbody>
                </table>';
} else {
    echo tr('Nessun Intervento.');
}
echo '
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card card-danger">
            <div class="card-header">
                <h3 class="card-title">'.tr('Interventi completati').'</h3>
            </div>
            <div class="card-body">';
if ($interventi_completati) {
    echo '
                <table class="table table-hover table-bordered table-sm">
                    <thead>
                        <tr>
                            <th>'.tr('Interventi').'</th>
                            <th class="text-center">#</th>
                        </tr>
                    </thead>
                    <tbody>';
    foreach ($interventi_completati as $id) {
        $intervento = Intervento::find($id);
        echo '
                        <tr>
                            <td>
                                '.Modules::link('Interventi', $intervento->id, tr('Intervento num. _NUM_ del _DATE_', [
            '_NUM_' => $intervento->codice,
            '_DATE_' => Translator::dateToLocale($intervento->inizio),
        ])).'
                            </td>
                            <td class="text-center">
                                '.Prints::getLink($template, $id, 'btn btn-xs btn-primary', '', 'fa fa-print').'
                            </td>
                        <tr>';
    }
    echo '
                    </tbody>
                </table>
                <br>
                <div class="alert alert-warning">
                    <i class="fa fa-warning"></i> '.tr('Questi interventi non verranno firmati').'
                </div>';
} else {
    echo tr('Nessun Intervento.');
}
echo '
            </div>
        </div>
    </div>
</div>';

// HTML per la visualizzazione
echo '
<div id="preview">
    <button type="button" class="btn btn-success btn-block btn-lg" id="firma">
        <i class="fa fa-pencil"></i> '.tr('Firma').'
    </button>
    <br>

    <div class="clearfix"></div>
</div>';

?>
<form action="" method="post" id="form-firma" class="hide">
    <input type="hidden" name="op" value="firma_bulk">
    <input type="hidden" name="backto" value="record-list">
    <input type="hidden" name="records" value="<?php echo implode(';', $records); ?>">

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
    $(document).ready( function() {
        $('#firma').on('click', function() {
            $('#preview').addClass('hide');

            $('#form-firma').removeClass('hide');
        })

        var wrapper = document.getElementById("signature-pad"),
            clearButton = document.querySelector("[data-action=clear]"),
            saveButton = document.querySelector("[data-action=save]"),
            canvas = document.getElementById("canvas");

        var signaturePad = new SignaturePad(canvas, {
            backgroundColor: 'rgb(255,255,255)'
        });

        function resizeCanvas() {
            image_data = signaturePad.toDataURL();

            var ratio =  Math.max(window.devicePixelRatio || 1, 1);
            canvas.width = canvas.offsetWidth * ratio;
            canvas.height = canvas.offsetHeight * ratio;
            canvas.getContext("2d").scale(ratio, ratio);
            signaturePad.clear();

            signaturePad.fromDataURL(image_data);
        }

        window.addEventListener("resize", resizeCanvas);
        $('#firma').click(resizeCanvas);

        clearButton.addEventListener("click", function (event) {
            signaturePad.clear();
        });

        saveButton.addEventListener("click", function (event) {
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
