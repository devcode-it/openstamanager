<?php

/*
 * OpenSTAManager: stampa Etichetta spedizione per DDT
 */

include_once __DIR__.'/../../core.php';

use Modules\Anagrafiche\Nazione;
use Modules\DDT\DDT;

$documento = DDT::find($id_record);

if (!$documento) {
    exit(tr('Documento non trovato!'));
}

$id_cliente = $documento['idanagrafica'];
$id_azienda = setting('Azienda predefinita');

/**
 * Restituisce i dati essenziali di un'anagrafica o di una sua sede.
 */
function getShippingAddress($dbo, $idanagrafica, $idsede = 0)
{
    if (!empty($idsede)) {
        $result = $dbo->fetchOne(
            'SELECT an_anagrafiche.ragione_sociale, an_sedi.nomesede, an_sedi.indirizzo, an_sedi.indirizzo2, an_sedi.cap, an_sedi.citta, an_sedi.provincia, an_sedi.id_nazione, an_sedi.telefono, an_sedi.cellulare
            FROM an_sedi
            INNER JOIN an_anagrafiche ON an_anagrafiche.idanagrafica = an_sedi.idanagrafica
            WHERE an_sedi.idanagrafica = '.prepare($idanagrafica).' AND an_sedi.id = '.prepare($idsede)
        );
    } else {
        $result = $dbo->fetchOne(
            'SELECT ragione_sociale, NULL AS nomesede, indirizzo, indirizzo2, cap, citta, provincia, id_nazione, telefono, cellulare
            FROM an_anagrafiche
            WHERE idanagrafica = '.prepare($idanagrafica)
        );
    }

    return $result ?: [];
}

/**
 * Compone un indirizzo HTML compatto adatto all'etichetta.
 */
function formatShippingAddress($data)
{
    $lines = [];

    if (!empty($data['ragione_sociale'])) {
        $lines[] = '<strong>'.htmlspecialchars((string) $data['ragione_sociale']).'</strong>';
    }
    if (!empty($data['nomesede'])) {
        $lines[] = htmlspecialchars((string) $data['nomesede']);
    }
    if (!empty($data['indirizzo'])) {
        $lines[] = htmlspecialchars((string) $data['indirizzo']);
    }
    if (!empty($data['indirizzo2'])) {
        $lines[] = htmlspecialchars((string) $data['indirizzo2']);
    }

    $city = trim((string) ($data['cap'] ?? '').' '.(string) ($data['citta'] ?? ''));
    if (!empty($data['provincia'])) {
        $city .= ' ('.htmlspecialchars((string) $data['provincia']).')';
    }
    if ($city !== '') {
        $lines[] = htmlspecialchars($city);
    }

    if (!empty($data['id_nazione'])) {
        $nazione = Nazione::find($data['id_nazione']);
        if ($nazione && $nazione['iso2'] !== 'IT') {
            $lines[] = htmlspecialchars((string) $nazione->getTranslation('title'));
        }
    }

    $contacts = array_filter([
        !empty($data['telefono']) ? tr('Tel').': '.htmlspecialchars((string) $data['telefono']) : '',
        !empty($data['cellulare']) ? tr('Cell').': '.htmlspecialchars((string) $data['cellulare']) : '',
    ]);
    if (!empty($contacts)) {
        $lines[] = implode(' - ', $contacts);
    }

    return implode('<br>', $lines);
}

$tipo_doc = $documento->tipo ? $documento->tipo->getTranslation('title') : tr('DDT');
$tipo_doc_normalized = mb_strtolower(trim((string) $tipo_doc));
$is_outgoing = $tipo_doc_normalized === mb_strtolower('Ddt in uscita');

// Logica coerente con la stampa DDT standard OSM:
// - DDT in uscita: partenza azienda, destinazione cliente
// - altri DDT: partenza cliente, destinazione azienda
if ($is_outgoing) {
    $sender_data = getShippingAddress($dbo, $id_azienda, $documento['idsede_partenza']);
    $recipient_data = getShippingAddress($dbo, $id_cliente, $documento['idsede_destinazione']);
} else {
    $sender_data = getShippingAddress($dbo, $id_cliente, $documento['idsede_partenza']);
    $recipient_data = getShippingAddress($dbo, $id_azienda, $documento['idsede_destinazione']);
}

$numero = !empty($documento['numero_esterno']) ? $documento['numero_esterno'] : $documento['numero'];

$notes = [];
if (!empty($documento['note'])) {
    $notes[] = nl2br(htmlspecialchars((string) $documento['note']));
}
if (!empty($documento['note_aggiuntive'])) {
    $notes[] = '<strong>'.tr('Note aggiuntive').':</strong><br>'.nl2br(htmlspecialchars((string) $documento['note_aggiuntive']));
}
$notes_block = '';
if (!empty($notes)) {
    $notes_block = '<div class="notes"><div class="section-title">'.tr('Note').'</div>'.implode('<br><br>', $notes).'</div>';
}

$custom = [
    'mittente' => formatShippingAddress($sender_data),
    'destinatario' => formatShippingAddress($recipient_data),
    'tipo_doc' => $tipo_doc,
    'numero' => $numero,
    'data' => Translator::dateToLocale($documento['data']),
    'n_colli' => !empty($documento['n_colli']) ? $documento['n_colli'] : '',
    'notes_block' => $notes_block,
];

// Accesso coerente con la stampa DDT standard.
if ((auth_osm()->getUser()['gruppo'] === 'Clienti' && $id_cliente != auth_osm()->getUser()['idanagrafica'] && !AuthOSM::admin()) || Modules::getPermission($documento->module) === '-') {
    exit(tr('Non hai i permessi per questa stampa!'));
}
