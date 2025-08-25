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

//use Database;
//use Modules\Ubicazioni;

include_once __DIR__.'/../../core.php';

echo '<style>
.barcode {
    padding: 0;
    margin: 0;
    vertical-align: top;
}
.barcode-cell {
    text-align: center;
    vertical-align: middle;
}
</style>';

if (!empty($_SESSION['superselect']['id_ubicazione_barcode'])) {
    //$records = Ubicazioni::whereIn('id', $_SESSION['superselect']['id_ubicazione_barcode'])->get();
    //unset($_SESSION['superselect']['id_ubicazione_barcode']);
} else {
    $records = $dbo->fetchOne('SELECT id FROM `mg_ubicazioni` WHERE id='.prepare($id_record));
}

$pages = count($records);
$page = 0;
//$prezzi_ivati = setting('Utilizza prezzi di vendita comprensivi di IVA');

foreach ($records as $record) {
	//$row = $dbo->fetchOne('SELECT * FROM `mg_ubicazioni` WHERE id='.prepare($id_record));
	//adeguamento a mg_ubicazioni_lang
	$row = $dbo->fetchOne('SELECT `mg_ubicazioni`.*, `mg_ubicazioni_lang`.`title` AS title, `mg_ubicazioni_lang`.`notes` FROM `mg_ubicazioni` LEFT JOIN `mg_ubicazioni_lang` ON (`mg_ubicazioni`.`id`=`mg_ubicazioni_lang`.`id_record` AND `mg_ubicazioni_lang`.`id_lang`='.prepare(parameter: Models\Locale::getDefault()->id).') WHERE `mg_ubicazioni`.`id`='.prepare($id_record));
	$u_label = $row['u_label'];
	$u_title = $row['title'];
	if ($u_title == NULL) {
		$u_title = "--";
	} 	
    //$barcode = strtolower(trim($row['u_label']).trim($u_label_info));

    echo '
    <div class="barcode-cell">
        <p style="font-size:30pt;"><b>'.$u_label.'</b></p>
        <p style="font-size:25pt;">'.$u_title.'</p><br>
        <!--<p style="font-size:15pt;"><b>'.moneyFormat($prezzi_ivati ? $articolo->prezzo_vendita_ivato : $articolo->prezzo_vendita).'</b></p>-->
        <!--<barcode code="'.$barcode.'" type="C39" height="2" size="0.65" class="barcode" />-->
        <!--<p><b>'.$barcode.'</b></p>-->
    </div>';

    ++$page;

    if ($page < $pages) {
        echo '<pagebreak>';
    }
}

