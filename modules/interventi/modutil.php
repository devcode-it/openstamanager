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

use Modules\Anagrafiche\Anagrafica;
use Modules\Emails\Mail;
use Modules\Emails\Template;
use Modules\Fatture\Components\Descrizione;
use Modules\Fatture\Components\Riga;
use Modules\Fatture\Fattura;
use Modules\Interventi\Components\Sessione;
use Modules\Interventi\Intervento;
use Util\Ini;

/**
 * Recupera il totale delle ore spese per un intervento.
 *
 * @param int $id_intervento
 *
 * @deprecated
 */
function get_ore_intervento($id_intervento)
{
    $intervento = Intervento::find($id_intervento);

    return $intervento->ore_totali;
}

/**
 * Funzione per collegare gli articoli, usati in un intervento, ai rispettivi impianti.
 *
 * @param int $id_intervento
 * @param int $id_impianto
 * @param int $id_articolo
 * @param int $qta
 */
function link_componente_to_articolo($id_intervento, $id_impianto, $id_articolo, $qta)
{
    if (empty($id_impianto) || empty($id_intervento)) {
        return;
    }

    $dbo = database();
    $intervento = Intervento::find($id_intervento);

    // Data di inizio dell'intervento (data_richiesta in caso di assenza di sessioni)
    $data = $intervento->inizio ?: $intervento->data_richiesta;

    // Se l'articolo aggiunto è collegato a un componente, aggiungo il componente all'impianto selezionato
    $componente_articolo = $dbo->fetchOne('SELECT componente_filename, contenuto FROM mg_articoli WHERE id = '.prepare($id_articolo));
    if (!empty($componente_articolo) && !empty($componente_articolo['componente_filename'])) {
        $contenuto_ini = Ini::read($componente_articolo['contenuto']);
        $nome_componente = Ini::getValue($contenuto_ini, 'Nome');

        $dati = [
            'idimpianto' => $id_impianto,
            'idintervento' => $id_intervento,
            'nome' => $nome_componente,
            'data' => $data,
            'filename' => $componente_articolo['componente_filename'],
            'contenuto' => $componente_articolo['contenuto'],
        ];

        // Inserisco il componente tante volte quante la quantità degli articoli inseriti
        for ($q = 0; $q < $qta; ++$q) {
            $dbo->insert('my_impianto_componenti', $dati);
        }
    }
}

function add_tecnico($id_intervento, $idtecnico, $inizio, $fine, $idcontratto = null)
{
    $intervento = Intervento::find($id_intervento);
    $anagrafica = Anagrafica::find($idtecnico);

    $sessione = Sessione::build($intervento, $anagrafica, $inizio, $fine);

    // Notifica nuovo intervento al tecnico
    if (setting('Notifica al tecnico l\'assegnazione all\'attività')) {
        if (!empty($anagrafica['email'])) {
            $template = Template::pool('Notifica intervento');

            if (!empty($template)) {
                $mail = Mail::build(auth()->getUser(), $template, $id_intervento);
                $mail->addReceiver($anagrafica['email']);
                $mail->save();
            }
        }
    }

    return true;
}

/**
 * Calcola le ore presenti tra due date.
 *
 * @param string $orario_inizio
 * @param string $orario_fine
 *
 * @return float
 *
 * @deprecated
 */
function calcola_ore_intervento($orario_inizio, $orario_fine)
{
    $inizio = new DateTime($orario_inizio);
    $diff = $inizio->diff(new DateTime($orario_fine));

    $ore = $diff->i / 60 + $diff->h + ($diff->days * 24);

    return $ore;
}

function aggiungi_intervento_in_fattura($id_intervento, $id_fattura, $descrizione, $id_iva, $id_conto, $id_rivalsa_inps = false, $id_ritenuta_acconto = false, $calcolo_ritenuta_acconto = false)
{
    $dbo = database();

    $id_rivalsa_inps = $id_rivalsa_inps !== false ? $id_rivalsa_inps : setting('Percentuale rivalsa');
    $id_ritenuta_acconto = $id_ritenuta_acconto !== false ? $id_ritenuta_acconto : setting("Percentuale ritenuta d'acconto");
    $calcolo_ritenuta_acconto = $calcolo_ritenuta_acconto !== false ? $calcolo_ritenuta_acconto : setting("Metodologia calcolo ritenuta d'acconto predefinito");

    $fattura = Fattura::find($id_fattura);
    $intervento = Intervento::find($id_intervento);

    $data = $intervento->inizio;
    $codice = $intervento->codice;

    // Riga di descrizione
    $riga = Descrizione::build($fattura);
    $riga->descrizione = $descrizione;
    $riga->idintervento = $id_intervento;
    $riga->save();

    // Ore di lavoro raggruppate per costo orario
    $sessioni = $intervento->sessioni;

    if (empty($sessioni)) {
        flash()->warning(tr("L'intervento _NUM_ non ha sessioni di lavoro!", [
            '_NUM_' => $codice,
        ]));
    } else {
        $ore_di_lavoro = $sessioni->groupBy(function ($item, $key) {
            return $item['prezzo_orario'].'|'.$item['sconto_unitario'].'|'.$item['tipo_sconto'];
        });
        foreach ($ore_di_lavoro as $gruppo) {
            $sessione = $gruppo->first();
            $riga = Riga::build($fattura);

            $riga->descrizione = tr("Ore di lavoro dell'intervento _NUM_ del _DATE_", [
                '_NUM_' => $codice,
                '_DATE_' => dateFormat($data),
            ]);
            $riga->idintervento = $id_intervento;
            $riga->um = 'ore';

            $riga->id_iva = $id_iva;
            $riga->idconto = $id_conto;

            $riga->calcolo_ritenuta_acconto = $calcolo_ritenuta_acconto;
            $riga->id_ritenuta_acconto = $id_ritenuta_acconto;
            $riga->id_rivalsa_inps = $id_rivalsa_inps;

            $riga->prezzo_unitario = $sessione->prezzo_orario;
            $riga->setSconto($sessione->sconto_unitario, $sessione->tipo_sconto);

            $riga->qta = $gruppo->sum('ore');

            $riga->save();
        }

        // Diritti di chiamata raggruppati per costo
        $diritti_chiamata = $sessioni->where('prezzo_diritto_chiamata', '>', 0)->groupBy(function ($item, $key) {
            return $item['prezzo_diritto_chiamata'];
        });
        foreach ($diritti_chiamata as $gruppo) {
            $diritto_chiamata = $gruppo->first();
            $riga = Riga::build($fattura);

            $riga->descrizione = tr("Diritto di chiamata dell'intervento _NUM_ del _DATE_", [
                '_NUM_' => $codice,
                '_DATE_' => dateFormat($data),
            ]);
            $riga->idintervento = $id_intervento;
            //$riga->um = 'ore';

            $riga->id_iva = $id_iva;
            $riga->idconto = $id_conto;

            $riga->calcolo_ritenuta_acconto = $calcolo_ritenuta_acconto;
            $riga->id_ritenuta_acconto = $id_ritenuta_acconto;
            $riga->id_rivalsa_inps = $id_rivalsa_inps;

            $riga->prezzo_unitario = $diritto_chiamata->prezzo_diritto_chiamata;

            $riga->qta = $gruppo->count();

            $riga->save();
        }

        // Viaggi raggruppati per costo
        $viaggi = $sessioni->where('prezzo_km_unitario', '>', 0)->groupBy(function ($item, $key) {
            return $item['prezzo_km_unitario'].'|'.$item['scontokm_unitario'].'|'.$item['tipo_scontokm'];
        });
        foreach ($viaggi as $gruppo) {
            $viaggio = $gruppo->first();
            $riga = Riga::build($fattura);

            $riga->descrizione = tr("Trasferta dell'intervento _NUM_ del _DATE_", [
                '_NUM_' => $codice,
                '_DATE_' => dateFormat($data),
            ]);
            $riga->idintervento = $id_intervento;
            $riga->um = 'km';

            $riga->id_iva = $id_iva;
            $riga->idconto = $id_conto;

            $riga->calcolo_ritenuta_acconto = $calcolo_ritenuta_acconto;
            $riga->id_ritenuta_acconto = $id_ritenuta_acconto;
            $riga->id_rivalsa_inps = $id_rivalsa_inps;

            $riga->prezzo_unitario = $viaggio->prezzo_km_unitario;
            $riga->setSconto($viaggio->scontokm_unitario, $viaggio->tipo_scontokm);

            $riga->qta = $gruppo->sum('km');

            $riga->save();
        }
    }

    // Articoli, righe, sconti e descrizioni collegati all'intervento
    $righe = $intervento->getRighe();
    foreach ($righe as $riga) {
        $qta = $riga->qta;

        $copia = $riga->copiaIn($fattura, $qta);
        $copia->id_conto = $id_conto;

        $copia->calcolo_ritenuta_acconto = $calcolo_ritenuta_acconto;
        $copia->id_ritenuta_acconto = $id_ritenuta_acconto;
        $copia->id_rivalsa_inps = $id_rivalsa_inps;

        // Aggiornamento seriali dalla riga dell'ordine
        if ($copia->isArticolo()) {
            $copia->serials = $riga->serials;
        }

        $copia->save();
    }

    // Ricalcolo inps, ritenuta e bollo
    ricalcola_costiagg_fattura($id_fattura);

    // Metto l'intervento in stato "Fatturato"
    $dbo->query("UPDATE in_interventi SET idstatointervento=(SELECT idstatointervento FROM in_statiintervento WHERE codice='FAT') WHERE id=".prepare($id_intervento));
}
