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

use Modules\Anagrafiche\Anagrafica;
use Modules\Articoli\Articolo as ArticoloOriginale;
use Modules\Emails\Mail;
use Modules\Emails\Template;
use Modules\Fatture\Components\Descrizione;
use Modules\Fatture\Components\Riga;
use Modules\Fatture\Fattura;
use Modules\Interventi\Components\Riga as RigaIntervento;
use Modules\Interventi\Components\Sessione;
use Modules\Interventi\Intervento;
use Modules\Iva\Aliquota;
use Util\Generator;
use Util\Ini;

/*
 * Recupera il totale delle ore spese per un intervento.
 *
 * @param int $id_intervento
 *
 * @deprecated
 */

if (!function_exists('get_ore_intervento')) {
    function get_ore_intervento($id_intervento)
    {
        $intervento = Intervento::find($id_intervento);

        return $intervento->ore_totali;
    }
}

/*
 * Funzione per collegare gli articoli, usati in un intervento, ai rispettivi impianti.
 *
 * @param int $id_intervento
 * @param int $id_impianto
 * @param int $id_articolo
 * @param int $qta
 *
 * @deprecated 2.4.25
 */
if (!function_exists('link_componente_to_articolo')) {
    function link_componente_to_articolo($id_intervento, $id_impianto, $id_articolo, $qta)
    {
        if (empty($id_impianto) || empty($id_intervento)) {
            return;
        }

        $dbo = database();
        $intervento = Intervento::find($id_intervento);

        // Data di inizio dell'intervento (data_richiesta in caso di assenza di sessioni)
        $data = $intervento->inizio;

        // Se l'articolo aggiunto è collegato a un componente, aggiungo il componente all'impianto selezionato
        $componente_articolo = $dbo->fetchOne('SELECT `componente_filename`, `contenuto` FROM `mg_articoli` WHERE `id` = '.prepare($id_articolo));
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
}

if (!function_exists('add_tecnico')) {
    function add_tecnico($id_intervento, $idtecnico, $inizio, $fine, $idcontratto = null)
    {
        $intervento = Intervento::find($id_intervento);
        $anagrafica = Anagrafica::find($idtecnico);

        $sessione = Sessione::build($intervento, $anagrafica, $inizio, $fine);

        // Notifica nuovo intervento al tecnico
        if (setting('Notifica al tecnico l\'aggiunta della sessione nell\'attività')) {
            if (!empty($anagrafica['email'])) {
                $template = Template::where('name', 'Notifica intervento')->first();

                if (!empty($template)) {
                    $mail = Mail::build(auth()->getUser(), $template, $id_intervento);
                    $mail->addReceiver($anagrafica['email']);
                    $mail->save();
                    flash()->info(tr('Notifica al tecnico aggiunta correttamente.'));
                }
            }
        }

        // Inserisco le righe aggiuntive previste dal tipo di intervento
        $righe_aggiuntive = database()->fetchArray('SELECT * FROM in_righe_tipiinterventi WHERE id_tipointervento='.prepare($sessione->idtipointervento));

        foreach ($righe_aggiuntive as $riga_aggiuntiva) {
            $riga = RigaIntervento::build($intervento);

            $riga->descrizione = $riga_aggiuntiva['descrizione'];
            $riga->um = $riga_aggiuntiva['um'];

            $riga->costo_unitario = $riga_aggiuntiva['prezzo_acquisto'];
            $riga->setPrezzoUnitario($riga_aggiuntiva['prezzo_vendita'], $riga_aggiuntiva['idiva']);
            $riga->qta = $riga_aggiuntiva['qta'];

            $riga->save();
        }

        // Trigger aggiornamento intervento
        $intervento->updated_at = date('Y-m-d H:i:s');
        $intervento->save();

        return true;
    }
}

/*
 * Calcola le ore presenti tra due date.
 *
 * @param string $orario_inizio
 * @param string $orario_fine
 *
 * @return float
 *
 * @deprecated
 */
if (!function_exists('calcola_ore_intervento')) {
    function calcola_ore_intervento($orario_inizio, $orario_fine)
    {
        $inizio = new DateTime($orario_inizio);
        $diff = $inizio->diff(new DateTime($orario_fine));

        $ore = $diff->i / 60 + $diff->h + ($diff->days * 24);

        return $ore;
    }
}

if (!function_exists('aggiungi_intervento_in_fattura')) {
    function aggiungi_intervento_in_fattura($id_intervento, $id_fattura, $descrizione, $id_iva, $id_conto, $id_rivalsa_inps = false, $id_ritenuta_acconto = false, $calcolo_ritenuta_acconto = false)
    {
        $dbo = database();

        $id_rivalsa_inps = $id_rivalsa_inps !== false ? $id_rivalsa_inps : setting('Cassa previdenziale predefinita');
        $id_ritenuta_acconto = $id_ritenuta_acconto !== false ? $id_ritenuta_acconto : setting("Ritenuta d'acconto predefinita");
        $calcolo_ritenuta_acconto = $calcolo_ritenuta_acconto !== false ? $calcolo_ritenuta_acconto : setting("Metodologia calcolo ritenuta d'acconto predefinito");

        $fattura = Fattura::find($id_fattura);
        $intervento = Intervento::find($id_intervento);
        $codice = $intervento->codice;

        // Riga di descrizione
        $riga = Descrizione::build($fattura);
        $riga->descrizione = $descrizione;
        $riga->idintervento = $id_intervento;
        $riga->save();

        // Ore di lavoro raggruppate per costo orario
        $sessioni = $intervento->sessioni;

        if (empty($sessioni)) {
            flash()->warning(tr("L'attività _NUM_ non ha sessioni di lavoro!", [
                '_NUM_' => $codice,
            ]));
        } else {
            aggiungi_sessioni_in_fattura($id_intervento, $id_fattura, $id_iva, $id_conto, $id_rivalsa_inps, $id_ritenuta_acconto, $calcolo_ritenuta_acconto);
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
                $articolo = ArticoloOriginale::find($copia->idarticolo);
                $copia->id_conto = ($articolo->idconto_vendita ?: $id_conto);
            }

            $copia->save();
        }

        // Ricalcolo inps, ritenuta e bollo
        ricalcola_costiagg_fattura($id_fattura);

        // Metto l'intervento in stato "Fatturato"
        if (setting('Cambia automaticamente stato attività fatturate')) {
            $dbo->query("UPDATE `in_interventi` SET `idstatointervento`=(SELECT `id` FROM `in_statiintervento` WHERE `codice`='FAT') WHERE `id`=".prepare($id_intervento));
        }
    }
}

if (!function_exists('aggiungi_sessioni_in_fattura')) {
    function aggiungi_sessioni_in_fattura($id_intervento, $id_fattura, $id_iva, $id_conto, $id_rivalsa_inps, $id_ritenuta_acconto, $calcolo_ritenuta_acconto)
    {
        $fattura = Fattura::find($id_fattura);
        $intervento = Intervento::find($id_intervento);
        $sessioni = $intervento->sessioni;

        $decimals = setting('Cifre decimali per quantità');

        $ore_di_lavoro = $sessioni->groupBy(fn ($item, $key) => $item['prezzo_orario'].'|'.$item['sconto_unitario'].'|'.$item['tipo_sconto']);
        foreach ($ore_di_lavoro as $gruppo) {
            $date = [];
            $sessione = $gruppo->first();
            $riga = Riga::build($fattura);

            foreach ($gruppo as $sessione) {
                $dateValue = date('d/m/Y', strtotime($sessione->orario_fine));
                if (!in_array($dateValue, $date)) {
                    $date[] = $dateValue;
                }
            }

            $riga->descrizione = tr("Ore di lavoro dell'attività _NUM_ del _DATE_", [
                '_NUM_' => $intervento->codice,
                '_DATE_' => implode(', ', $date),
            ]);
            $riga->idintervento = $id_intervento;
            $riga->um = 'ore';

            $riga->id_iva = $id_iva;
            $riga->idconto = $id_conto;

            $riga->calcolo_ritenuta_acconto = $calcolo_ritenuta_acconto;
            $riga->id_ritenuta_acconto = $id_ritenuta_acconto;
            $riga->id_rivalsa_inps = $id_rivalsa_inps;

            $riga->prezzo_unitario = $sessione->prezzo_orario;
            $riga->costo_unitario = $sessione->prezzo_ore_unitario_tecnico;
            // Calcolo lo sconto unitario della sessione in base all'impostazione sui prezzi ivati
            $iva = Aliquota::find($sessione->id_iva);
            if ($sessione->tipo_sconto == 'UNT' && setting('Utilizza prezzi di vendita comprensivi di IVA')) {
                $sconto_unitario = $sessione->sconto_unitario + (($sessione->sconto_unitario * $iva->percentuale) / 100);
            } else {
                $sconto_unitario = $sessione->sconto_unitario;
            }

            $riga->setSconto($sconto_unitario, $sessione->tipo_sconto);

            $qta_gruppo = $gruppo->sum('ore');
            $riga->qta = round($qta_gruppo, $decimals);

            // Riferimento al documento di origine
            $riga->original_document_type = $intervento::class;
            $riga->original_document_id = $intervento->id;

            $riga->save();
        }

        // Diritti di chiamata raggruppati per costo
        $diritti_chiamata = $sessioni->where('prezzo_diritto_chiamata', '>', 0)->groupBy(fn ($item, $key) => $item['prezzo_diritto_chiamata']);
        foreach ($diritti_chiamata as $gruppo) {
            $date = [];
            $diritto_chiamata = $gruppo->first();
            $riga = Riga::build($fattura);

            foreach ($gruppo as $sessione) {
                $dateValue = date('d/m/Y', strtotime($sessione->orario_fine));
                if (!in_array($dateValue, $date)) {
                    $date[] = $dateValue;
                }
            }

            $riga->descrizione = tr("Diritto di chiamata dell'attività _NUM_ del _DATE_", [
                '_NUM_' => $intervento->codice,
                '_DATE_' => implode(', ', $date),
            ]);
            $riga->idintervento = $id_intervento;
            // $riga->um = 'ore';

            $riga->id_iva = $id_iva;
            $riga->idconto = $id_conto;

            $riga->calcolo_ritenuta_acconto = $calcolo_ritenuta_acconto;
            $riga->id_ritenuta_acconto = $id_ritenuta_acconto;
            $riga->id_rivalsa_inps = $id_rivalsa_inps;

            $riga->prezzo_unitario = $diritto_chiamata->prezzo_diritto_chiamata;
            $riga->costo_unitario = $sessione->prezzo_dirittochiamata_tecnico;
            $riga->qta = $gruppo->count();

            // Riferimento al documento di origine
            $riga->original_document_type = $intervento::class;
            $riga->original_document_id = $intervento->id;

            $riga->save();
        }

        // Viaggi raggruppati per costo
        $viaggi = $sessioni->where('prezzo_km_unitario', '>', 0)->groupBy(fn ($item, $key) => $item['prezzo_km_unitario'].'|'.$item['scontokm_unitario'].'|'.$item['tipo_scontokm']);
        foreach ($viaggi as $gruppo) {
            $date = [];
            $qta_trasferta = $gruppo->sum('km');
            if ($qta_trasferta == 0) {
                continue;
            }

            foreach ($gruppo as $sessione) {
                $dateValue = date('d/m/Y', strtotime($sessione->orario_fine));
                if (!in_array($dateValue, $date)) {
                    $date[] = $dateValue;
                }
            }

            $viaggio = $gruppo->first();
            $riga = Riga::build($fattura);

            $riga->descrizione = tr("Trasferta dell'attività _NUM_ del _DATE_", [
                '_NUM_' => $intervento->codice,
                '_DATE_' => implode(', ', $date),
            ]);
            $riga->idintervento = $id_intervento;
            $riga->um = 'km';

            $riga->id_iva = $id_iva;
            $riga->idconto = $id_conto;

            $riga->calcolo_ritenuta_acconto = $calcolo_ritenuta_acconto;
            $riga->id_ritenuta_acconto = $id_ritenuta_acconto;
            $riga->id_rivalsa_inps = $id_rivalsa_inps;

            $riga->prezzo_unitario = $viaggio->prezzo_km_unitario;
            $riga->costo_unitario = $sessione->prezzo_km_unitario_tecnico;
            $riga->setSconto($viaggio->scontokm_unitario, $viaggio->tipo_scontokm);

            // Riferimento al documento di origine
            $riga->original_document_type = $intervento::class;
            $riga->original_document_id = $intervento->id;

            $riga->qta = $qta_trasferta;

            $riga->save();
        }
    }
}

/*
 * Verifica che il numero_esterno della fattura indicata sia correttamente impostato, a partire dai valori delle fatture ai giorni precedenti.
 * Restituisce il numero_esterno mancante in caso di numero errato.
 *
 * @return bool|string
 */
if (!function_exists('verifica_numero_intervento')) {
    function verifica_numero_intervento(Intervento $intervento, $id_segment)
    {
        if (empty($intervento->codice)) {
            return null;
        }

        $data = $intervento->data_richiesta;
        $documenti = Intervento::whereDate('data_richiesta', '=', $data->format('Y-m-d'))
            ->get();

        // Recupero maschera per questo segmento
        $maschera = Generator::getMaschera($id_segment);

        if ((!str_contains($maschera, 'YYYY')) or (!str_contains($maschera, 'yy'))) {
            $ultimo = Generator::getPreviousFrom($maschera, 'in_interventi', 'codice', [
                'DATE(data_richiesta) < '.prepare($data->format('Y-m-d')),
                'YEAR(data_richiesta) = '.prepare($data->format('Y')),
            ], $data);
        } else {
            $ultimo = Generator::getPreviousFrom($maschera, 'in_interventi', 'codice', [
                'DATE(data_richiesta) < '.prepare($data->format('Y-m-d')),
            ]);
        }

        do {
            $numero = Generator::generate($maschera, $ultimo, 1, Generator::dateToPattern($data), $data);

            $filtered = $documenti->reject(fn ($item, $key) => $item->codice == $numero);

            if ($documenti->count() == $filtered->count()) {
                return $numero;
            }

            $documenti = $filtered;
            $ultimo = $numero;
        } while ($numero != $intervento->codice);

        return null;
    }
}
