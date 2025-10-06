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

namespace API\App\v1;

use API\Interfaces\RetrieveInterface;
use API\Resource;
use Carbon\Carbon;

/**
 * Risorsa API per verificare se ci sono aggiornamenti disponibili per tutte le risorse principali.
 * Permette all'app di fare una sola chiamata per sapere se ci sono record modificati da sincronizzare.
 */
class VerificaAggiornamenti extends Resource implements RetrieveInterface
{
    /**
     * Mappa delle risorse principali da controllare.
     * Ogni risorsa è associata alla sua classe per poter istanziarla e controllare gli aggiornamenti.
     */
    private const RISORSE_DA_CONTROLLARE = [
        'clienti' => 'API\\App\\v1\\Clienti',
        'tecnici' => 'API\\App\\v1\\Tecnici',
        'interventi' => 'API\\App\\v1\\Interventi',
        'articoli' => 'API\\App\\v1\\Articoli',
        'impianti' => 'API\\App\\v1\\Impianti',
        'contratti' => 'API\\App\\v1\\Contratti',
        'preventivi' => 'API\\App\\v1\\Preventivi',
        'sedi' => 'API\\App\\v1\\Sedi',
        'sedi-azienda' => 'API\\App\\v1\\SediAzienda',
        'referenti' => 'API\\App\\v1\\Referenti',
        'seriali' => 'API\\App\\v1\\Seriali',
        'sessioni' => 'API\\App\\v1\\SessioniInterventi',
        'righe-interventi' => 'API\\App\\v1\\RigheInterventi',
        'stati-intervento' => 'API\\App\\v1\\StatiIntervento',
        'tipi-intervento' => 'API\\App\\v1\\TipiIntervento',
        'tariffe-tecnici' => 'API\\App\\v1\\TariffeTecnici',
        'tariffe-contratti' => 'API\\App\\v1\\TariffeContratti',
        'aliquote-iva' => 'API\\App\\v1\\AliquoteIva',
        'campi-personalizzati' => 'API\\App\\v1\\CampiPersonalizzati',
        'campi-personalizzati-valori' => 'API\\App\\v1\\CampiPersonalizzatiValori',
        'checklists' => 'API\\App\\v1\\Checklists',
        'pagamenti' => 'API\\App\\v1\\Pagamenti',
        'allegati-interventi' => 'API\\App\\v1\\AllegatiInterventi',
    ];

    /**
     * Verifica se ci sono record modificati per tutte le risorse principali.
     *
     * @param array $request Richiesta contenente last_sync_at
     * @return array Risultato con informazioni sui record modificati disponibili
     */
    public function retrieve($request)
    {
        $last_sync_at = $request['last_sync_at'] && $request['last_sync_at'] != 'undefined' 
            ? new Carbon($request['last_sync_at']) 
            : null;

        $aggiornamenti_disponibili = false;
        $dettagli_risorse = [];
        $errori = [];

        foreach (self::RISORSE_DA_CONTROLLARE as $nome_risorsa => $classe_risorsa) {
            try {
                // Verifica se la classe esiste
                if (!class_exists($classe_risorsa)) {
                    $errori[] = "Classe non trovata: {$classe_risorsa}";
                    continue;
                }

                // Istanzia la risorsa
                $risorsa = new $classe_risorsa();

                // Controlla se ci sono record modificati
                $record_modificati = $risorsa->getModifiedRecords($last_sync_at);
                $ha_modifiche = !empty($record_modificati);

                // Se ci sono modifiche, segna che ci sono aggiornamenti
                if ($ha_modifiche) {
                    $aggiornamenti_disponibili = true;
                }

                // Salva i dettagli per questa risorsa
                $dettagli_risorse[$nome_risorsa] = [
                    'ha_modifiche' => $ha_modifiche,
                    'numero_modifiche' => count($record_modificati),
                    'richiede_sincronizzazione' => $ha_modifiche,
                ];

            } catch (\Exception $e) {
                // Log dell'errore e continua con le altre risorse
                $errori[] = "Errore nel controllare {$nome_risorsa}: " . $e->getMessage();
                
                // Log dell'errore per debugging
                $logger = logger();
                $logger->addRecord(\Monolog\Level::Error, "Errore in VerificaAggiornamenti per {$nome_risorsa}: " . $e->getMessage());
            }
        }

        // Calcola statistiche generali
        $risorse_con_aggiornamenti = array_filter($dettagli_risorse, function($dettagli) {
            return $dettagli['richiede_sincronizzazione'];
        });

        $totale_modifiche = array_sum(array_column($dettagli_risorse, 'numero_modifiche'));

        return [
            'aggiornamenti_disponibili' => $aggiornamenti_disponibili,
            'last_sync_at' => $last_sync_at ? $last_sync_at->toISOString() : null,
            'timestamp_controllo' => (new Carbon())->toISOString(),
            'statistiche' => [
                'totale_risorse_controllate' => count(self::RISORSE_DA_CONTROLLARE),
                'risorse_con_aggiornamenti' => count($risorse_con_aggiornamenti),
                'totale_record_modificati' => $totale_modifiche,
            ],
            'risorse' => $dettagli_risorse,
            'errori' => $errori,
        ];
    }
}
