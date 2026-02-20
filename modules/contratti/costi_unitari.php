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

include_once __DIR__.'/../../core.php';

$block_edit = $record['is_bloccato'];

$idtipiintervento = ['-1'];

// Loop fra i tipi di attività e i relativi costi del tipo intervento (tutti quelli abilitati)
$rs = $dbo->fetchArray('SELECT `co_contratti_tipiintervento`.*, `in_tipiintervento`.`costo_orario`, `in_tipiintervento`.`costo_km` AS `costo_km_standard`, `in_tipiintervento`.`costo_diritto_chiamata`, `in_tipiintervento_lang`.`title` FROM `co_contratti_tipiintervento` INNER JOIN `in_tipiintervento` ON `in_tipiintervento`.`id` = `co_contratti_tipiintervento`.`idtipointervento` LEFT JOIN `in_tipiintervento_lang` ON `in_tipiintervento_lang`.`id_record` = `in_tipiintervento`.`id` AND `in_tipiintervento_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).' WHERE `idcontratto`='.prepare($id_record).' AND `co_contratti_tipiintervento`.`is_abilitato` = 1 ORDER BY `in_tipiintervento_lang`.`title`');

if (!empty($rs)) {
    echo '
                    <table class="table table-striped table-sm table-bordered">
                        <tr>
                            <th width="300">'.tr('Tipo attività').'</th>

                            <th>'.tr('Addebito orario').' <span class="tip" title="'.tr('Addebito al cliente').'"><i class="fa fa-question-circle-o"></i></span></th>
                            <th>'.tr('Addebito km').' <span class="tip" title="'.tr('Addebito al cliente').'"><i class="fa fa-question-circle-o"></i></span></th>
                            <th>'.tr('Addebito diritto ch.').' <span class="tip" title="'.tr('Addebito al cliente').'"><i class="fa fa-question-circle-o"></i></span></th>

                            <th width="120" '.($block_edit ? 'style="display:none;"' : '').'></th>
                        </tr>';

    for ($i = 0; $i < sizeof($rs); ++$i) {
        $is_abilitato = !empty($rs[$i]['is_abilitato']);
        $costo_ore = !empty($rs[$i]['costo_ore']) ? $rs[$i]['costo_ore'] : $rs[$i]['costo_orario'];
        $costo_km = !empty($rs[$i]['costo_km']) ? $rs[$i]['costo_km'] : $rs[$i]['costo_km_standard'];
        $costo_dirittochiamata = !empty($rs[$i]['costo_dirittochiamata']) ? $rs[$i]['costo_dirittochiamata'] : $rs[$i]['costo_diritto_chiamata'];
        echo '
                            <tr>
                                <td>'.$rs[$i]['title'].'</td>

                                <td>
                                    {[ "type": "number", "name": "costo_ore['.$rs[$i]['idtipointervento'].']", "value": "'.$costo_ore.'", "disabled": '.(!$is_abilitato ? '1' : '0').' ]}
                                </td>

                                <td>
                                    {[ "type": "number", "name": "costo_km['.$rs[$i]['idtipointervento'].']", "value": "'.$costo_km.'", "disabled": '.(!$is_abilitato ? '1' : '0').' ]}
                                </td>

                                <td>
                                    {[ "type": "number", "name": "costo_dirittochiamata['.$rs[$i]['idtipointervento'].']", "value": "'.$costo_dirittochiamata.'", "disabled": '.(!$is_abilitato ? '1' : '0').' ]}
                                </td>

                                <td class="text-center" '.($block_edit ? 'style="display:none;"' : '').'>
                                    <button type="button" class="btn btn-warning btn-xs" data-card-widget="tooltip" title="Importa valori da tariffe standard" onclick="if( confirm(\'Importare i valori dalle tariffe standard?\') ){ $.post( \''.base_path_osm().'/modules/contratti/actions.php\', { op: \'import\', idcontratto: \''.$id_record.'\', idtipointervento: \''.$rs[$i]['idtipointervento'].'\' }, function(data){ location.href=\''.base_path_osm().'/editor.php?id_module='.$id_module.'&id_record='.$id_record.'\'; } ); }">
                                    <i class="fa fa-download"></i>
                                    </button>
                                    <button type="button" class="btn btn-'.($is_abilitato ? 'danger' : 'success').' btn-xs" data-card-widget="tooltip" title="'.($is_abilitato ? tr('Disabilita') : tr('Abilita')).'" onclick="toggleTipoAttivita('.$rs[$i]['idtipointervento'].', this)">
                                    <i class="fa fa-'.($is_abilitato ? 'times' : 'check').'"></i>
                                    </button>
                                    <button type="button" class="btn btn-info btn-xs" data-card-widget="tooltip" title="'.tr('Aggiungi riga ore').'" onclick="aggiungiRigaOre('.$rs[$i]['idtipointervento'].', \''.$rs[$i]['title'].'\')" '.(!$is_abilitato ? 'style="display:none;"' : '').'>
                                    <i class="fa fa-plus"></i>
                                    </button>
                                </td>

                            </tr>';

        $idtipiintervento[] = prepare($rs[$i]['idtipointervento']);
    }
    echo '
                    </table>';
}

echo '
                    <button type="button" onclick="$(this).next().toggleClass(\'hide\');" class="btn btn-info btn-sm" '.($block_edit ? 'style="display:none;"' : '').'><i class="fa fa-th-list"></i> '.tr('Mostra tipi di attività disabilitati').'</button>
					<div class="hide">';

// Loop fra i tipi di attività e i relativi costi del tipo intervento (quelli disabilitati)
$rs = $dbo->fetchArray('SELECT `co_contratti_tipiintervento`.*, `in_tipiintervento`.`costo_orario`, `in_tipiintervento`.`costo_km` AS `costo_km_standard`, `in_tipiintervento`.`costo_diritto_chiamata`, `in_tipiintervento_lang`.`title` FROM `co_contratti_tipiintervento` INNER JOIN `in_tipiintervento` ON `in_tipiintervento`.`id` = `co_contratti_tipiintervento`.`idtipointervento` LEFT JOIN `in_tipiintervento_lang` ON (`in_tipiintervento`.`id`=`in_tipiintervento_lang`.`id_record` AND `in_tipiintervento_lang`.`id_lang` = '.prepare(Models\Locale::getDefault()->id).') WHERE `co_contratti_tipiintervento`.`idtipointervento` NOT IN('.implode(',', array_map(prepare(...), $idtipiintervento)).') AND `idcontratto`='.prepare($id_record).' AND `co_contratti_tipiintervento`.`is_abilitato` = 0 ORDER BY `title`');

if (!empty($rs)) {
    echo '
                        <div class="clearfix">&nbsp;</div>
						<table class="table table-striped table-sm table-bordered">
							<tr>
								<th width="300">'.tr('Tipo attività').'</th>

								<th>'.tr('Addebito orario').' <span class="tip" title="'.tr('Addebito al cliente').'"><i class="fa fa-question-circle-o"></i></span></th>
								<th>'.tr('Addebito km').' <span class="tip" title="'.tr('Addebito al cliente').'"><i class="fa fa-question-circle-o"></i></span></th>
								<th>'.tr('Addebito diritto ch.').' <span class="tip" title="'.tr('Addebito al cliente').'"><i class="fa fa-question-circle-o"></i></span></th>

                                <th width="120" '.($block_edit ? 'style="display:none;"' : '').'></th>
							</tr>';

    for ($i = 0; $i < sizeof($rs); ++$i) {
        $is_abilitato = !empty($rs[$i]['is_abilitato']);
        echo '
                            <tr>
                                <td>'.$rs[$i]['title'].'</td>

                                <td>
                                    {[ "type": "number", "name": "costo_ore['.$rs[$i]['idtipointervento'].']", "value": "'.$rs[$i]['costo_orario'].'", "icon-after": "<i class=\'fa fa-euro\'></i>", "disabled": '.(!$is_abilitato ? '1' : '0').' ]}
                                </td>

                                <td>
                                    {[ "type": "number", "name": "costo_km['.$rs[$i]['idtipointervento'].']", "value": "'.$rs[$i]['costo_km_standard'].'", "icon-after": "<i class=\'fa fa-euro\'></i>", "disabled": '.(!$is_abilitato ? '1' : '0').' ]}
                                </td>

                                <td>
                                    {[ "type": "number", "name": "costo_dirittochiamata['.$rs[$i]['idtipointervento'].']", "value": "'.$rs[$i]['costo_diritto_chiamata'].'", "icon-after": "<i class=\'fa fa-euro\'></i>", "disabled": '.(!$is_abilitato ? '1' : '0').' ]}
                                </td>

                                <td class="text-center" '.($block_edit ? 'style="display:none;"' : '').'>
                                <button type="button" class="btn btn-'.($is_abilitato ? 'danger' : 'success').' btn-xs" data-card-widget="tooltip" title="'.($is_abilitato ? tr('Disabilita') : tr('Abilita')).'" onclick="toggleTipoAttivita('.$rs[$i]['idtipointervento'].', this)">
                                    <i class="fa fa-'.($is_abilitato ? 'times' : 'check').'"></i>
                                    </button>
                                    <button type="button" class="btn btn-info btn-xs" data-card-widget="tooltip" title="'.tr('Aggiungi riga ore').'" onclick="aggiungiRigaOre('.$rs[$i]['idtipointervento'].', \''.$rs[$i]['title'].'\')" '.(!$is_abilitato ? 'style="display:none;"' : '').'>
                                    <i class="fa fa-plus"></i>
                                    </button>
                                </td>

                            </tr>';
    }
    echo '
                        </table>';
}
echo '

					</div>';
