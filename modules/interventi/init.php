<?php
/*
 * OpenSTAManager: il software gestionale open source per l'assistenza tecnica e la fatturazione
 * Copyright (C) DevCode s.n.c.
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

use Modules\Interventi\Intervento;

if (isset($id_record)) {
    $intervento = Intervento::find($id_record);

    $record = $dbo->fetchOne('SELECT *,
       (SELECT tipo FROM an_anagrafiche WHERE idanagrafica = in_interventi.idanagrafica) AS tipo_anagrafica,
       (SELECT is_completato FROM in_statiintervento WHERE idstatointervento=in_interventi.idstatointervento) AS flag_completato,
       IF((in_interventi.idsede_destinazione = 0), (SELECT idzona FROM an_anagrafiche WHERE idanagrafica = in_interventi.idanagrafica), (SELECT idzona FROM an_sedi WHERE id = in_interventi.idsede_destinazione)) AS idzona,
       (SELECT colore FROM in_statiintervento WHERE idstatointervento=in_interventi.idstatointervento) AS colore,
       in_interventi.id_preventivo as idpreventivo,
       in_interventi.id_contratto as idcontratto
    FROM in_interventi WHERE id='.prepare($id_record));
}
