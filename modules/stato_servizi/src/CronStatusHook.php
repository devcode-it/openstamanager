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

namespace Modules\StatoServizi;

use Carbon\Carbon;
use Hooks\Manager;
use Models\Cache;
use Models\Module;

/**
 * Hook specializzato per il controllo dello stato del cron.
 * Segnala quando il cron non è stato eseguito da più di un giorno.
 */
class CronStatusHook extends Manager
{
    public function response()
    {
        // Recupera l'ultima esecuzione del cron dalla cache
        $ultima_esecuzione = Cache::where('name', 'Ultima esecuzione del cron')->first();

        if (!$ultima_esecuzione || !$ultima_esecuzione->content) {
            $document_root = $_SERVER['DOCUMENT_ROOT'] ?? base_dir();
            $message = tr('Il cron non è stato configurato correttamente.', [
                '_DOCUMENT_ROOT_' => $document_root,
            ]);
            $show = true;
        } else {
            // Verifica che il contenuto sia una data valida
            try {
                // Converte la data dell'ultima esecuzione
                $data_ultima_esecuzione = Carbon::parse($ultima_esecuzione->content);
                $ora_attuale = Carbon::now();

                // Calcola la differenza in ore
                $ore_trascorse = $data_ultima_esecuzione->diffInHours($ora_attuale);

                if ($ore_trascorse > 1) {
                    $data_formattata = $data_ultima_esecuzione->format('d/m/Y H:i:s');
                    $document_root = $_SERVER['DOCUMENT_ROOT'] ?? base_dir();

                    $message = tr('Sembra che il cron di OpenSTAManager non sia in esecuzione (ultima esecuzione il _DATA_).', [
                        '_DATA_' => $data_formattata,
                        '_DOCUMENT_ROOT_' => $document_root,
                    ]);
                    $show = true;
                } else {
                    $message = tr('Il cron è attivo e funzionante');
                    $show = false;
                }
            } catch (\Exception $e) {
                // Se il contenuto non è una data valida
                $message = tr('Il formato della data dell\'ultima esecuzione del cron non è valido.', []);
                $show = true;
            }
        }

        // Ottiene il link al modulo aggiornamenti
        $aggiornamenti_module = Module::where('name', 'Aggiornamenti')->first();
        $link = $aggiornamenti_module ? base_path().'/controller.php?id_module=' . $aggiornamenti_module->id : base_path().'/controller.php?id_module=' . $this->getModuleId();

        return [
            'icon' => 'fa fa-clock-o text-danger',
            'message' => $message,
            'show' => $show,
            'link' => $link,
        ];
    }

    public function execute()
    {
        // Non è necessaria alcuna esecuzione per questo hook
        return false;
    }

    public function needsExecution()
    {
        // Questo hook non ha bisogno di esecuzione, serve solo per la notifica
        return false;
    }

    /**
     * Ottiene l'ID del modulo "Stato servizi"
     */
    private function getModuleId()
    {
        $module = \Models\Module::where('name', 'Stato servizi')->first();
        return $module?->id;
    }
}
