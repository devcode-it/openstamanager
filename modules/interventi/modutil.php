<?php

include_once __DIR__.'/../../core.php';

use Modules\Anagrafiche\Anagrafica;
use Modules\Fatture\Components\Descrizione;
use Modules\Fatture\Components\Riga;
use Modules\Fatture\Fattura;
use Modules\Interventi\Components\Sessione;
use Modules\Interventi\Intervento;

/**
 * Recupera il totale delle ore spese per un intervento.
 *
 * @param int $id_intervento
 */
function get_ore_intervento($id_intervento)
{
    $intervento = Intervento::find($id_intervento);

    return $intervento->ore_totali;
}

/**
 * Funzione per collegare gli articoli, usati in un intervento, ai rispettivi impianti.
 *
 * @param int $idintervento
 * @param int $idimpianto
 * @param int $idarticolo
 * @param int $qta
 */
function link_componente_to_articolo($idintervento, $idimpianto, $idarticolo, $qta)
{
    $dbo = database();

    if (!empty($idimpianto) && !empty($idintervento)) {
        //Leggo la data dell'intervento
        $rs = $dbo->fetchArray("SELECT DATE_FORMAT(MIN(orario_inizio),'%Y-%m-%d') AS data FROM in_interventi_tecnici WHERE idintervento=".prepare($idintervento));
        $data = $rs[0]['data'];

        $rs = $dbo->fetchArray('SELECT componente_filename, contenuto FROM mg_articoli WHERE id='.prepare($idarticolo));

        //Se l'articolo aggiunto è collegato a un file .ini, aggiungo il componente all'impianto selezionato
        if (count($rs) == 1 && $rs[0]['componente_filename'] != '') {
            //Inserisco il componente tante volte quante la quantità degli articoli inseriti
            for ($q = 0; $q < $qta; ++$q) {
                $dbo->query('INSERT INTO my_impianto_componenti(idimpianto, idintervento, nome, data, filename, contenuto) VALUES ('.prepare($idimpianto).', '.prepare($idintervento).', '.prepare(\Util\Ini::getValue($rs[0]['componente_filename'], 'Nome')).', '.prepare($data).', '.prepare($rs[0]['componente_filename']).', '.prepare($rs[0]['contenuto']).')');
            }
        }
    }
}

function add_tecnico($idintervento, $idtecnico, $inizio, $fine, $idcontratto = null)
{
    $dbo = database();

    $intervento = Intervento::find($idintervento);
    $anagrafica = Anagrafica::find($idtecnico);

    $sessione = Sessione::build($intervento, $anagrafica, $inizio, $fine);

    // Notifica nuovo intervento al tecnico
    if (!empty($tecnico['email'])) {
        $n = new Notifications\EmailNotification();

        $n->setTemplate('Notifica intervento', $idintervento);
        $n->setReceivers($anagrafica['email']);

        $n->send();
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

            $riga->prezzo_unitario_vendita = $sessione->prezzo_orario;
            $riga->sconto_unitario = $sessione->sconto_unitario;
            $riga->tipo_sconto = $sessione->tipo_sconto;

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
            $riga->um = 'ore';

            $riga->id_iva = $id_iva;
            $riga->idconto = $id_conto;

            $riga->calcolo_ritenuta_acconto = $calcolo_ritenuta_acconto;
            $riga->id_ritenuta_acconto = $id_ritenuta_acconto;
            $riga->id_rivalsa_inps = $id_rivalsa_inps;

            $riga->prezzo_unitario_vendita = $diritto_chiamata->prezzo_diritto_chiamata;

            $riga->qta = $gruppo->count();

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

    // Aggiunta km come "Trasferta" (se c'è)
    if ($intervento->prezzo_viaggio > 0) {
        $riga = Riga::build($fattura);

        $riga->descrizione = tr("Trasferta dell'intervento _NUM_ del _DATE_", [
            '_NUM_' => $codice,
            '_DATE_' => dateFormat($data),
        ]);
        $riga->idintervento = $id_intervento;

        $riga->id_iva = $id_iva;
        $riga->idconto = $id_conto;

        $riga->calcolo_ritenuta_acconto = $calcolo_ritenuta_acconto;
        $riga->id_ritenuta_acconto = $id_ritenuta_acconto;
        $riga->id_rivalsa_inps = $id_rivalsa_inps;

        $riga->prezzo_unitario_vendita = $intervento->prezzo_viaggio;
        $riga->sconto_unitario = $intervento->sconto_totale_viaggio;
        $riga->tipo_sconto = 'UNT';

        $riga->qta = 1;

        $riga->save();
    }

    // Ricalcolo inps, ritenuta e bollo
    ricalcola_costiagg_fattura($id_fattura);

    // Metto l'intervento in stato "Fatturato"
    $dbo->query("UPDATE in_interventi SET idstatointervento=(SELECT idstatointervento FROM in_statiintervento WHERE descrizione='Fatturato') WHERE id=".prepare($id_intervento));
}
