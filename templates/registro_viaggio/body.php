<?php

include_once __DIR__.'/../../core.php';

use Models\Module;

echo '
<table class="table table-bordered table-sm">
    <thead>
        <tr>
            <th width="7%" class="text-center"><small>'.tr('ORARIO INIZIO', [], ['upper' => true]).'</small></th>
            <th width="7%" class="text-center"><small>'.tr('ORARIO FINE', [], ['upper' => true]).'</small></th>
            <th width="15%" class="text-center"><small>'.tr('AUTISTA', [], ['upper' => true]).'</small></th>
            <th width="8%" class="text-center"><small>'.tr('KM INIZIALI', [], ['upper' => true]).'</small></th>
            <th width="8%" class="text-center"><small>'.tr('KM FINALI', [], ['upper' => true]).'</small></th>
            <th width="20%" class="text-center"><small>'.tr('DESTINAZIONE', [], ['upper' => true]).'</small></th>
            <th width="20%" class="text-center"><small>'.tr('MOTIVAZIONI', [], ['upper' => true]).'</small></th>
            <th width="7%" class="text-center"><small>'.tr('FIRMA', [], ['upper' => true]).'</small></th>
        </tr>
    </thead>
    <tbody>';

if (!empty($records)) {
    foreach ($records as $viaggio) {
        // Recupero rifornimenti per questo viaggio
        $rifornimenti = $rifornimenti_per_viaggio[$viaggio['id']] ?? [];

        // Riga con dati viaggio
        echo '
        <tr>
            <td class="text-center"><small>'.Translator::timestampToLocale($viaggio['data_inizio']).'</small></td>
            <td class="text-center"><small>'.Translator::timestampToLocale($viaggio['data_fine']).'</small></td>
            <td><small>'.$viaggio['tecnico_nome'].'</small></td>
            <td class="text-center"><small>'.$viaggio['km_inizio'].'</small></td>
            <td class="text-center"><small>'.$viaggio['km_fine'].'</small></td>
            <td><small>'.$viaggio['destinazione'].'</small></td>
            <td><small>'.$viaggio['motivazione'].'</small></td>
            <td class="text-center">';

        // Mostra firma se presente
        if (!empty($viaggio['firma_data'])) {
            $module = Module::where('name', 'Automezzi')->first();
            $uploads = $module->files($id_record, true);
            // Cerca il primo file con key che inizia con 'signature'
            foreach ($uploads as $upload) {
                if ($upload->key == 'signature_viaggio:'.$viaggio['id']) {
                    $directory_firma = '/files/'.$module->directory.'/';
                    $image = $directory_firma.$upload->filename;
                }
            }
            $url = $image ? base_path_osm().$image : null;
            echo '<img src="'.$url.'" style="max-height: 15mm; max-width: 25mm;">';
        }

        echo '</td>
        </tr>';

        // Se ci sono rifornimenti, aggiungo una riga con sotto-tabella
        if (!empty($rifornimenti)) {
            echo '
        <tr>
            <td colspan="8" style="padding: 5px 10px; border-top: 0; background-color: #fafafa;">
                <table class="table table-sm" style="margin: 0; border: 1px solid #ddd;">
                    <thead>
                        <tr>
                            <td class="text-center" width="15%" style="border: 1px solid #ddd; padding: 2px 4px;"><small><i>'.tr('Data rif.').'</i></small></td>
                            <td class="text-center" width="20%" style="border: 1px solid #ddd; padding: 2px 4px;"><small><i>'.tr('Luogo').'</i></small></td>
                            <td class="text-center" width="15%" style="border: 1px solid #ddd; padding: 2px 4px;"><small><i>'.tr('Km rif.').'</i></small></td>
                            <td class="text-center" width="15%" style="border: 1px solid #ddd; padding: 2px 4px;"><small><i>'.tr('Quantità').'</i></small></td>
                            <td class="text-center" width="15%" style="border: 1px solid #ddd; padding: 2px 4px;"><small><i>'.tr('Importo').' €</i></small></td>
                            <td class="text-center" width="10%" style="border: 1px solid #ddd; padding: 2px 4px;"><small><i>'.tr('Gestore').'</i></small></td>
                            <td class="text-center" width="10%" style="border: 1px solid #ddd; padding: 2px 4px;"><small><i>'.tr('Tipo').'</i></small></td>
                        </tr>
                    </thead>
                    <tbody>';

            foreach ($rifornimenti as $rif) {
                $um = !empty($rif['id_carburante_um']) ? $rif['id_carburante_um'] : 'L';
                echo '
                        <tr>
                            <td class="text-center" style="border: 1px solid #ddd; padding: 2px 4px;"><small>'.Translator::dateToLocale($rif['data']).'</small></td>
                            <td style="border: 1px solid #ddd; padding: 2px 4px;"><small>'.$rif['luogo'].'</small></td>
                            <td class="text-center" style="border: 1px solid #ddd; padding: 2px 4px;"><small>'.$rif['km'].'</small></td>
                            <td class="text-center" style="border: 1px solid #ddd; padding: 2px 4px;"><small>'.Translator::numberToLocale($rif['quantita'], 2).' '.$um.'</small></td>
                            <td class="text-right" style="border: 1px solid #ddd; padding: 2px 4px;"><small>'.moneyFormat($rif['costo'], 2).'</small></td>
                            <td class="text-center" style="border: 1px solid #ddd; padding: 2px 4px;"><small>'.strtoupper($rif['gestore_desc']).'</small></td>
                            <td class="text-center" style="border: 1px solid #ddd; padding: 2px 4px;"><small>'.strtoupper($rif['id_carburante_desc']).'</small></td>
                        </tr>';
            }

            echo '
                    </tbody>
                </table>
            </td>
        </tr>';
        }
    }
} else {
    echo '
    <tr>
        <td colspan="8" class="text-center">
            <i>'.tr('Nessun viaggio registrato nel periodo selezionato').'</i>
        </td>
    </tr>';
}

echo '
    </tbody>
</table>';

