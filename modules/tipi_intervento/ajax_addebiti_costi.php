<?php

if (file_exists(__DIR__.'/../../../core.php')) {
    include_once __DIR__.'/../../../core.php';
} else {
    include_once __DIR__.'/../../core.php';
}

// Fasce orarie per il tipo di attività
$fasce_orarie = $dbo->fetchArray("SELECT *, CONCAT (`in_fasceorarie_lang`.`title`, ' (', DATE_FORMAT(`ora_inizio`, '%H:%i'), '-', DATE_FORMAT(`ora_fine`, '%H:%i'), ')') AS descrizione FROM `in_fasceorarie_tipiintervento` INNER JOIN `in_fasceorarie` ON `in_fasceorarie_tipiintervento`.`idfasciaoraria` = `in_fasceorarie`.`id` LEFT JOIN `in_fasceorarie_lang` ON (`in_fasceorarie_lang`.`id_record` = `in_fasceorarie`.`id` AND `in_fasceorarie_lang`.`id_lang` = ".prepare(Models\Locale::getDefault()->id).') WHERE `idtipointervento`='.prepare($id_record));

echo '
    <table class="table table-striped table-condensed table-hover table-bordered">
        <tr>
            <th>'.tr('Fascia oraria').'</th>
            <th width="12%">'.tr('Addebito orario').'</th>
            <th width="12%">'.tr('Addebito km').'</th>
            <th width="12%">'.tr('Addebito diritto ch.').'</th>
            <th width="12%">'.tr('Costo orario').'</th>
            <th width="12%">'.tr('Costo km ').'</th>
            <th width="12%">'.tr('Costo diritto ch.').'</th>
        </tr>';
$i = 0;
foreach ($fasce_orarie as $fascia_oraria) {
    $descrizione = $fascia_oraria['descrizione'];
    $giorni = '';

    if (!empty($fascia_oraria['giorni'])) {
        if ($fascia_oraria['giorni'] == '1,2,3,4,5') {
            $giorni .= 'Lun-Ven';
        } elseif ($fascia_oraria['giorni'] == '6,7') {
            $giorni .= 'Sab-Dom';
        } elseif ($fascia_oraria['giorni'] == '6') {
            $giorni .= 'Sab';
        }

        $descrizione .= ' ('.$giorni.')';
    }

    if (!empty($fascia_oraria['include_bank_holidays'])) {
        $descrizione .= ' (Festivi)';
    }

    echo '
            <tr>
                <td class="text-left">'.$descrizione.'</td>
                
                <td class="text-right">
                    {[ "type": "number", "name": "fascia_ore['.$fascia_oraria['idfasciaoraria'].']", "value": "'.number_format($fascia_oraria['costo_orario'], 2, ',', '.').'", "decimals": "2", "icon-after": "<i class=\'fa fa-euro\'></i>" ]} </td>
                <td class="text-right">
                    {[ "type": "number", "name": "fascia_km['.$fascia_oraria['idfasciaoraria'].']", "value": "'.number_format($fascia_oraria['costo_km'], 2, ',', '.').'", "decimals": "2", "icon-after": "<i class=\'fa fa-euro\'></i>" ]}
                </td>
                <td class="text-right">
                  {[ "type": "number", "name": "fascia_diritto_chiamata['.$fascia_oraria['idfasciaoraria'].']", "value": "'.number_format($fascia_oraria['costo_diritto_chiamata'], 2, ',', '.').'", "decimals": "2", "icon-after": "<i class=\'fa fa-euro\'></i>" ]}
                </td>
                <td class="text-right">
                    {[ "type": "number", "name": "fascia_orario_tecnico['.$fascia_oraria['idfasciaoraria'].']", "value": "'.number_format($fascia_oraria['costo_orario_tecnico'], 2, ',', '.').'", "decimals": "2", "icon-after": "<i class=\'fa fa-euro\'></i>" ]}
                </td>
                <td class="text-right">
                    {[ "type": "number", "name": "fascia_km_tecnico['.$fascia_oraria['idfasciaoraria'].']", "value": "'.number_format($fascia_oraria['costo_km_tecnico'], 2, ',', '.').'", "decimals": "2", "icon-after": "<i class=\'fa fa-euro\'></i>" ]}
                </td>
                <td class="text-right">
                    {[ "type": "number", "name": "fascia_diritto_chiamata_tecnico['.$fascia_oraria['idfasciaoraria'].']", "value": "'.number_format($fascia_oraria['costo_diritto_chiamata_tecnico'], 2, ',', '.').'", "decimals": "2", "icon-after": "<i class=\'fa fa-euro\'></i>" ]}
                </td>
            </tr>';

    ++$i;
}

echo '
    </table>';
