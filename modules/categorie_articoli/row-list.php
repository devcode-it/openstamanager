<?php

$subcategorie = $dbo->fetchArray('SELECT * FROM `mg_categorie` WHERE `parent`='.prepare($id_record));

foreach ($subcategorie as $sub) {
    echo '
				<tr>
					<td>'.$sub['nome'].'</td>
					<td>'.$sub['colore'].'</td>
					<td>'.$sub['nota'].'</td>
					<td>
						<a class="btn btn-warning btn-sm" title="Modifica riga" onclick="launch_modal(\''.tr('Modifica sottocategoria').'\', \''.$rootdir.'/add.php?id_module='.$id_module.'&id_record='.$sub['id'].'&id_original='.$id_record.'\', 1 );"><i class="fa fa-edit"></i></a>
                        <a class="btn btn-sm btn-danger ask" data-backto="record-edit" data-id="'.$sub['id'].'">
                            <i class="fa fa-trash"></i>
                        </a>
					</td>
				</tr>';
}
