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

$resource = ($resource ?: $_GET['op']);

switch ($resource) {
    // Elenco e-mail
    case 'get_email':
        $indirizzi_proposti = $_GET['indirizzi_proposti'];
        $where = '';

        if ($indirizzi_proposti == 1) {
            $where .= 'AND an_tipianagrafiche_lang.title = "Cliente"';
        } elseif ($indirizzi_proposti == 2) {
            $where .= 'AND an_tipianagrafiche_lang.title = "Fornitore"';
        }

        $results = [];

        // Tutte le anagrafiche
        $q = "SELECT DISTINCT(an_anagrafiche.email), an_anagrafiche.idanagrafica, an_anagrafiche.ragione_sociale FROM an_anagrafiche INNER JOIN an_tipianagrafiche_anagrafiche ON an_tipianagrafiche_anagrafiche.idanagrafica=an_anagrafiche.idanagrafica INNER JOIN an_tipianagrafiche ON an_tipianagrafiche.id=an_tipianagrafiche_anagrafiche.idtipoanagrafica INNER JOIN an_tipianagrafiche_lang ON (an_tipianagrafiche_lang.id_lang=1 AND an_tipianagrafiche_lang.id_record=an_tipianagrafiche.id) WHERE email != '' ".$where.' ORDER BY ragione_sociale';
        $rs = $dbo->fetchArray($q);

        foreach ($rs as $r) {
            if (!empty($r['email'])) {
                $results[] = [
                    'value' => $r['email'],
                    'label' => $r['ragione_sociale'].' <'.$r['email'].'>',
                ];
            }
        }

        $q = "SELECT DISTINCT(an_sedi.email), an_sedi.idanagrafica, nomesede AS ragione_sociale FROM an_sedi INNER JOIN an_anagrafiche ON an_anagrafiche.idanagrafica=an_sedi.idanagrafica INNER JOIN an_tipianagrafiche_anagrafiche ON an_tipianagrafiche_anagrafiche.idanagrafica=an_anagrafiche.idanagrafica INNER JOIN an_tipianagrafiche ON an_tipianagrafiche.id=an_tipianagrafiche_anagrafiche.idtipoanagrafica INNER JOIN an_tipianagrafiche_lang ON (an_tipianagrafiche_lang.id_lang=1 AND an_tipianagrafiche_lang.id_record=an_tipianagrafiche.id) WHERE an_sedi.email != '' ".$where.' ORDER BY ragione_sociale';

        $sedi = $dbo->fetchArray($q);
        foreach ($sedi as $sede) {
            $results[] = [
                'value' => $sede['email'],
                'label' => $sede['ragione_sociale'].' <'.$sede['email'].'>',
            ];
        }

        $q = "SELECT DISTINCT(an_referenti.email), an_referenti.idanagrafica, an_referenti.nome AS ragione_sociale FROM an_referenti INNER JOIN an_anagrafiche ON an_anagrafiche.idanagrafica=an_referenti.idanagrafica INNER JOIN an_tipianagrafiche_anagrafiche ON an_tipianagrafiche_anagrafiche.idanagrafica=an_anagrafiche.idanagrafica INNER JOIN an_tipianagrafiche ON an_tipianagrafiche.id=an_tipianagrafiche_anagrafiche.idtipoanagrafica INNER JOIN an_tipianagrafiche_lang ON (an_tipianagrafiche_lang.id_lang=1 AND an_tipianagrafiche_lang.id_record=an_tipianagrafiche.id) WHERE an_referenti.email != '' ".$where.' ORDER BY ragione_sociale';

        $referenti = $dbo->fetchArray($q);
        foreach ($referenti as $referente) {
            $results[] = [
                'value' => $referente['email'],
                'label' => $referente['ragione_sociale'].' <'.$referente['email'].'>',
            ];
        }

        echo json_encode($results);

        break;
}
