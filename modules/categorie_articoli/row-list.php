<?php

include_once __DIR__.'/../../core.php';

$subcategorie = $dbo->fetchArray('SELECT * FROM `mg_categorie` WHERE `parent`='.prepare($id_record).' ORDER BY nome ASC ');

foreach ($subcategorie as $sub) {
    $n_articoli = $dbo->fetchNum('SELECT * FROM `mg_articoli` WHERE `id_sottocategoria`='.prepare($sub['id']));
    echo '
				<tr>
					<td>'.$sub['nome'].'</td>
					<td>'.$sub['colore'].'</td>
					<td>'.$sub['nota'].'</td>
					<td>
						<a class="btn btn-warning btn-sm" title="Modifica riga" onclick="launch_modal(\''.tr('Modifica sottocategoria').'\', \''.$rootdir.'/add.php?id_module='.$id_module.'&id_record='.$sub['id'].'&id_original='.$id_record.'\');"><i class="fa fa-edit"></i></a>
                        <a class="btn btn-sm btn-danger ask '.(($n_articoli > 0) ? 'disabled tip' : '').'" data-backto="record-edit" data-id="'.$sub['id'].'" title="'.(($n_articoli > 0) ? 'Sottocategoria collegata a '.$n_articoli.' articoli' : '').'">
                            <i class="fa fa-trash"></i>
                        </a>
					</td>
				</tr>';
}
