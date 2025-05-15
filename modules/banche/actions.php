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

use GuzzleHttp\Client;
use Modules\Anagrafiche\Anagrafica;
use Modules\Anagrafiche\Nazione;
use Modules\Banche\Banca;
use Modules\Banche\IBAN;

include_once __DIR__.'/../../core.php';

switch (filter('op')) {
    case 'add':
        $id_anagrafica = filter('id_anagrafica');
        $anagrafica = Anagrafica::find($id_anagrafica);

        $nome = filter('nome');

        $banca = Banca::build($anagrafica, $nome, filter('iban'), filter('bic'));
        $id_record = $banca->id;

        if (isAjaxRequest()) {
            echo json_encode([
                'id' => $id_record,
                'text' => $nome,
            ]);
        }

        flash()->info(tr('Aggiunta nuova _TYPE_', [
            '_TYPE_' => 'banca',
        ]));

        break;

    case 'update':
        $nome = filter('nome');

        $banca->nome = post('nome');
        $banca->iban = post('iban');
        $banca->bic = post('bic');

        $banca->note = post('note');
        $banca->id_pianodeiconti3 = post('id_pianodeiconti3');
        $banca->filiale = post('filiale');
        $banca->creditor_id = post('creditor_id');
        $banca->codice_sia = post('codice_sia');

        $banca->predefined = post('predefined');

        $banca->save();

        flash()->info(tr('Salvataggio completato'));

        break;

    case 'delete':
        $banca->delete();

        flash()->info(tr('_TYPE_ eliminata con successo!', [
            '_TYPE_' => 'Banca',
        ]));

        break;

    case 'compose':
        $nazione = Nazione::find(filter('id_nazione'));

        $iban = IBAN::generate([
            'nation' => $nazione->iso2,
            'bank_code' => filter('bank_code'),
            'branch_code' => filter('branch_code'),
            'account_number' => filter('account_number'),
            'check_digits' => filter('check_digits'),
            'national_check_digits' => filter('national_check_digits'),
        ]);

        echo json_encode([
            'iban' => $iban->getIban(),
        ]);

        break;

    case 'decompose':
        $iban = new IBAN(filter('iban'));
        $nazione = Nazione::where('iso2', '=', $iban->getNation())->first();

        echo json_encode([
            'id_nazione' => [
                'id' => $nazione->id,
                'iso2' => $nazione->iso2,
                'text' => $nazione->iso2.' - '.$nazione->getTranslation('title'),
            ],
            'bank_code' => $iban->getBankCode(),
            'branch_code' => $iban->getBranchCode(),
            'account_number' => $iban->getAccountNumber(),
            'check_digits' => $iban->getCheckDigits(),
            'national_check_digits' => $iban->getNationalCheckDigits(),
        ]);

        break;

    case 'check_balance':
        $api_key = filter('api_key');

        // Verifica il credito residuo su ibanapi.com
        try {
            $client = new Client();
            $response = $client->request('GET', setting('Endpoint ibanapi.com').'/v1/balance', [
                'query' => ['api_key' => $api_key],
                'http_errors' => false,
            ]);

            echo $response->getBody()->getContents();
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Errore durante la connessione a ibanapi.com: '.$e->getMessage()]);
            exit;
        }
        break;

    case 'verify_iban':
        $iban = filter('iban');
        $type = filter('type');
        $api_key = filter('api_key');

        // Verifica l'IBAN tramite ibanapi.com
        try {
            $client = new Client();
            $endpoint = ($type === 'bank') ? setting('Endpoint ibanapi.com').'/v1/validate' : setting('Endpoint ibanapi.com').'/v1/validate-basic';
            $response = $client->request('POST', $endpoint, [
                'form_params' => [
                    'iban' => $iban,
                    'api_key' => $api_key,
                ],
                'headers' => [
                    'Accept' => 'application/json',
                ],
                'http_errors' => false,
            ]);

            echo $response->getBody()->getContents();
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Errore durante la connessione a ibanapi.com: '.$e->getMessage()]);
            exit;
        }
        break;
}
