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

use Modules\Articoli\Articolo;

/**
 * Funzione per inserire i movimenti di magazzino.
 *
 * @deprecated 2.4.11
 */
function add_movimento_magazzino($id_articolo, $qta, $array = [], $descrizone = '', $data = '')
{
    $dbo = database();

    if (empty($qta)) {
        return false;
    }

    $nome = null;
    $tipo = null;
    $numero = null;

    // Informazioni articolo
    $manuale = 0;

    // Ddt
    if (!empty($array['idddt'])) {
        $rs = $dbo->fetchArray('SELECT numero, numero_esterno, dt_tipiddt.descrizione AS tipo, dt_tipiddt.dir FROM dt_ddt LEFT JOIN dt_tipiddt ON dt_tipiddt.id = dt_ddt.idtipoddt WHERE dt_ddt.id='.prepare($array['idddt']));
        $numero = (!empty($rs[0]['numero_esterno'])) ? $rs[0]['numero_esterno'] : $rs[0]['numero'];
        $tipo = strtolower($rs[0]['tipo']);

        $rs_data = $dbo->fetchArray("SELECT data FROM dt_ddt WHERE id='".$array['idddt']."'");
        $data = $rs_data[0]['data'];
    }

    // Fattura
    elseif (!empty($array['iddocumento'])) {
        $rs = $dbo->fetchArray('SELECT numero, numero_esterno, co_tipidocumento.descrizione AS tipo, co_tipidocumento.dir FROM co_documenti LEFT JOIN co_tipidocumento ON co_tipidocumento.id = co_documenti.idtipodocumento WHERE co_documenti.id='.prepare($array['iddocumento']));
        $numero = (!empty($rs[0]['numero_esterno'])) ? $rs[0]['numero_esterno'] : $rs[0]['numero'];
        $tipo = strtolower($rs[0]['tipo']);

        $rs_data = $dbo->fetchArray("SELECT data FROM co_documenti WHERE id='".$array['iddocumento']."'");
        $data = $rs_data[0]['data'];
    }

    // Intervento
    elseif (!empty($array['idintervento'])) {
        $rs_data = $dbo->fetchArray('SELECT IFNULL(MAX(orario_fine), data_richiesta) AS data, codice FROM in_interventi LEFT JOIN in_interventi_tecnici ON in_interventi.id=in_interventi_tecnici.idintervento WHERE in_interventi.id = '.prepare($array['idintervento']));
        $data = $rs_data[0]['data'];
        $codice_intervento = $rs_data[0]['codice'];

        $movimento = ($qta > 0) ? tr('Ripristino articolo da intervento _NUM_') : tr('Scarico magazzino per intervento _NUM_');
        $numero = $codice_intervento;
    }

    // Manuale
    else {
        $manuale = 1;
        $movimento = !empty($descrizone) ? $descrizone : '';
        $descrizone = '';

        if (empty($movimento)) {
            $movimento = ($qta > 0) ? tr('Carico magazzino') : tr('Scarico magazzino');
        }

        if ($data == '') {
            $data = date('Y-m-d');
        }
    }

    // Descrizione di default
    if (empty($movimento)) {
        $carico = (!empty($rs[0]['dir']) && $rs[0]['dir'] == 'entrata') ? tr('Ripristino articolo da _TYPE_ numero _NUM_') : tr('Carico magazzino da _TYPE_ numero _NUM_');
        $scarico = (!empty($rs[0]['dir']) && $rs[0]['dir'] == 'uscita') ? tr('Rimozione articolo da _TYPE_ numero _NUM_') : tr('Scarico magazzino per _TYPE_ numero _NUM_');

        $movimento = ($qta > 0) ? $carico : $scarico;
    }

    // Completamento della descrizione
    $movimento .= $descrizone;
    $movimento = str_replace(['_NAME_', '_TYPE_', '_NUM_'], [$nome, $tipo, $numero], $movimento);

    // Movimento il magazzino solo se l'articolo non è un servizio
    $articolo = Articolo::find($id_articolo);

    // Movimentazione effettiva
    if (empty($array['idintervento'])) {
        return $articolo->movimenta($qta, $movimento, $data, $manuale, $array);
    } else {
        return $articolo->registra($qta, $movimento, $data, $manuale, $array);
    }

    return true;
}

/**
 * Funzione per aggiornare le sedi nei movimenti di magazzino.
 */
function aggiorna_sedi_movimenti($module, $id)
{
    $dbo = database();

    if ($module == 'ddt') {
        $rs = $dbo->fetchArray('SELECT idsede_partenza, idsede_destinazione, dir FROM dt_ddt INNER JOIN dt_tipiddt ON dt_tipiddt.id = dt_ddt.idtipoddt WHERE dt_ddt.id='.prepare($id));

        $idsede_azienda = ($rs[0]['dir'] == 'uscita') ? $rs[0]['idsede_destinazione'] : $rs[0]['idsede_partenza'];
        $idsede_controparte = ($rs[0]['dir'] == 'uscita') ? $rs[0]['idsede_partenza'] : $rs[0]['idsede_destinazione'];

        $dbo->query('UPDATE mg_movimenti SET idsede_azienda='.prepare($idsede_azienda).', idsede_controparte='.prepare($idsede_controparte).' WHERE idddt='.prepare($id));
    } elseif ($module == 'documenti') {
        $rs = $dbo->fetchArray('SELECT idsede_partenza, idsede_destinazione, dir FROM co_documenti INNER JOIN co_tipidocumento ON co_tipidocumento.id = co_documenti.idtipodocumento WHERE co_documenti.id='.prepare($id));

        $idsede_azienda = ($rs[0]['dir'] == 'uscita') ? $rs[0]['idsede_destinazione'] : $rs[0]['idsede_partenza'];
        $idsede_controparte = ($rs[0]['dir'] == 'uscita') ? $rs[0]['idsede_partenza'] : $rs[0]['idsede_destinazione'];

        $dbo->query('UPDATE mg_movimenti SET idsede_azienda='.prepare($idsede_azienda).', idsede_controparte='.prepare($idsede_controparte).' WHERE iddocumento='.prepare($id));
    } elseif ($module == 'interventi') {
        $rs = $dbo->fetchArray('SELECT idsede_partenza, idsede_destinazione FROM in_interventi WHERE in_interventi.id='.prepare($id));

        $idsede_azienda = $rs[0]['idsede_partenza'];
        $idsede_controparte = $rs[0]['idsede_destinazione'];

        $dbo->query('UPDATE mg_movimenti SET idsede_azienda='.prepare($idsede_azienda).', idsede_controparte='.prepare($idsede_controparte).' WHERE idintervento='.prepare($id));
    }
}
