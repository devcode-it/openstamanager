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

use API\Services;
use Carbon\Carbon;
use Models\Module;
use Modules\Anagrafiche\Anagrafica;
use Modules\Anagrafiche\Referente;
use Modules\Anagrafiche\Sede;

$servizio_abilitato = Services::isEnabled() && Services::verificaRisorsaAttiva('Servizio Newsletter');

if (!empty($is_title_request)) {
    echo tr('Notifiche interne');

    return;
}

if (!empty($is_number_request)) {
    echo '<small>
    '.(
        $servizio_abilitato ?
        tr('Clicca qui per avviare la sincronizzazione con il servizio esterno la gestione dei disiscritti') :
        tr('Servizio non abilitato')
    ).'
    </small>';

    return;
}

// Avviso di servizio non abilitato
if (!$servizio_abilitato){
    echo '
<div class="alert alert-info">
    <i class="fa fa-info-circle"></i> '.tr('Servizio non abilitato: contatta gli sviluppatori ufficiali per la gestione delle Newsletter tramite servizio esterno').'.
</div>';

    return;
}

$response = Services::request('GET', 'opt-out');
$response = Services::responseBody($response);

// Individuazione email interessate
$email_disiscritte = collect($response['emails']);

// Ricerca dei riferimenti locali collegati alle email
$anagrafiche = Anagrafica::whereIn('email', $email_disiscritte)
    ->where('enable_newsletter', '=', true)
    ->get();

$sedi = Sede::whereIn('email', $email_disiscritte)
    ->where('enable_newsletter', '=', true)
    ->get();

$referenti = Referente::whereIn('email', $email_disiscritte)
    ->where('enable_newsletter', '=', true)
    ->get();

$destinatari = $anagrafiche
    ->concat($sedi)
    ->concat($referenti);

// Messaggio informativo di nessun utente disiscritto rispetto alla sincronizzazione precedente
if ($destinatari->count() == 0) {
    echo '
<p>'.tr('Non ci sono nuovi utenti disiscritti dal servizio di newsletter').'.</p>';

    return;
}

// Elenco dei nuovi utenti disiscritti
echo '
<p>'.tr("I seguenti utenti si sono disiscritti dalla newsletter dall'ultima sincronizzazione").'</p>
<table class="table table-hover">
    <tr>
        <th width="25%">'.tr('Anagrafica').'</th>
        <th>'.tr('Email').'</th>
    </tr>';

    foreach ($destinatari as $destinatario) {
        // Aggiornamento iscrizione locale
        $destinatario->enable_newsletter = false;
        $destinatario->save();

        $anagrafica = $destinatario instanceof Anagrafica ? $destinatario : $destinatario->anagrafica;
        $descrizione = $anagrafica->ragione_sociale;

        if ($destinatario instanceof Sede) {
            $descrizione .= ' ['.$destinatario->nomesede.']';
        } elseif ($destinatario instanceof Referente) {
            $descrizione .= ' ['.$destinatario->nome.']';
        }

        echo '
    <tr>
        <td>'.$descrizione.'</td>
        <td>'.$destinatario->email.'</td>
    </tr>';
}

echo '
</table>';
