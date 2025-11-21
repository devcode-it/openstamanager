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

namespace Modules\Aggiornamenti\Controlli;

use Modules\Anagrafiche\Anagrafica;

class PianoContiRagioneSociale extends Controllo
{
    public function getName()
    {
        return tr('Conti collegati alle anagrafiche non corrispondenti alle ragioni sociali');
    }

    public function getType($record)
    {
        return 'warning';
    }

    public function getOptions($record)
    {
        return [
            [
                'name' => tr('Correggi'),
                'icon' => 'fa fa-check',
                'color' => 'primary',
                'params' => [],
            ],
        ];
    }

    /**
     * Indica se questo controllo supporta azioni globali.
     */
    public function hasGlobalActions()
    {
        return true;
    }

    public function check()
    {
        $database = database();

        /**
         * Verifico se serve creare un conto per eventuali nuovi clienti o fornitori.
         */
        $anagrafiche_interessate = $database->fetchArray('
        SELECT
            `an_anagrafiche`.`idanagrafica` AS id,
            `an_anagrafiche`.`ragione_sociale`,
            `co_pianodeiconti3`.`descrizione` as nome_conto,
            `idconto_cliente`,
            `idconto_fornitore`
        FROM
            `an_anagrafiche`
            INNER JOIN `co_pianodeiconti3` ON (`an_anagrafiche`.`idconto_cliente` = `co_pianodeiconti3`.`id` OR `an_anagrafiche`.`idconto_fornitore` = `co_pianodeiconti3`.`id`)
        WHERE
            `deleted_at` IS NULL
        GROUP BY `idanagrafica`, `co_pianodeiconti3`.`descrizione`');

        foreach ($anagrafiche_interessate as $anagrafica) {
            if ($anagrafica['nome_conto'] != $anagrafica['ragione_sociale']) {
                $descrizione = tr("Il conto collegato all'anagrafica corrente (_NOME_) non corrisponde alla ragione sociale dell'anagrafica", [
                    '_NOME_' => $anagrafica['nome_conto'],
                ]);

                $this->addResult([
                    'id' => $anagrafica['id'],
                    'nome' => \Modules::link('Anagrafiche', $anagrafica['id'], $anagrafica['ragione_sociale']),
                    'descrizione' => $descrizione,
                ]);
            }
        }
    }

    public function execute($record, $params = [])
    {
        $anagrafica = Anagrafica::find($record['id']);
        $conti_da_verificare = [];

        // Raccogli i conti che potrebbero diventare vuoti
        if (!empty($anagrafica->idconto_cliente)) {
            $conti_da_verificare[] = $anagrafica->idconto_cliente;
        }
        if (!empty($anagrafica->idconto_fornitore)) {
            $conti_da_verificare[] = $anagrafica->idconto_fornitore;
        }

        // Gestione conto cliente
        if (!empty($anagrafica->idconto_cliente)) {
            $this->gestisciConto($anagrafica, 'idconto_cliente');
        }

        // Gestione conto fornitore
        if (!empty($anagrafica->idconto_fornitore)) {
            $this->gestisciConto($anagrafica, 'idconto_fornitore');
        }

        // Elimina i conti vuoti rimasti
        $conti_da_verificare = array_unique($conti_da_verificare);
        foreach ($conti_da_verificare as $id_conto) {
            $this->eliminaContoSeVuoto($id_conto);
        }

        return true;
    }

    /**
     * Override del metodo solveGlobal per gestire l'eliminazione dei conti vuoti.
     */
    public function solveGlobal($params = [])
    {
        $database = database();
        $conti_da_verificare = [];

        // Raccogli tutti i conti che potrebbero diventare vuoti
        foreach ($this->results as $record) {
            $anagrafica = Anagrafica::find($record['id']);

            if (!empty($anagrafica->idconto_cliente)) {
                $conti_da_verificare[] = $anagrafica->idconto_cliente;
            }
            if (!empty($anagrafica->idconto_fornitore)) {
                $conti_da_verificare[] = $anagrafica->idconto_fornitore;
            }
        }

        // Esegui la risoluzione globale direttamente
        $results = [];
        foreach ($this->results as $record) {
            $anagrafica = Anagrafica::find($record['id']);

            // Gestione conto cliente
            if (!empty($anagrafica->idconto_cliente)) {
                $this->gestisciConto($anagrafica, 'idconto_cliente');
            }

            // Gestione conto fornitore
            if (!empty($anagrafica->idconto_fornitore)) {
                $this->gestisciConto($anagrafica, 'idconto_fornitore');
            }

            $results[$record['id']] = true;
        }

        // Elimina i conti vuoti rimasti
        $conti_da_verificare = array_unique($conti_da_verificare);
        foreach ($conti_da_verificare as $id_conto) {
            $this->eliminaContoSeVuoto($id_conto);
        }

        return $results;
    }

    /**
     * Gestisce la risoluzione del conto per un'anagrafica specifica.
     *
     * @param Anagrafica $anagrafica
     * @param string     $campo_conto ('idconto_cliente' o 'idconto_fornitore')
     */
    private function gestisciConto($anagrafica, $campo_conto)
    {
        $database = database();
        $id_conto = $anagrafica->$campo_conto;

        // Conta quante anagrafiche sono collegate a questo conto
        $anagrafiche_collegate_cliente = $database->fetchOne('SELECT COUNT(*) as count FROM an_anagrafiche WHERE idconto_cliente = '.prepare($id_conto).' AND deleted_at IS NULL')['count'];
        $anagrafiche_collegate_fornitore = $database->fetchOne('SELECT COUNT(*) as count FROM an_anagrafiche WHERE idconto_fornitore = '.prepare($id_conto).' AND deleted_at IS NULL')['count'];
        $totale_anagrafiche_collegate = $anagrafiche_collegate_cliente + $anagrafiche_collegate_fornitore;

        if ($totale_anagrafiche_collegate == 1) {
            // Solo un'anagrafica collegata: aggiorna il nome del conto
            $database->update('co_pianodeiconti3', [
                'descrizione' => $anagrafica->ragione_sociale,
            ], [
                'id' => $id_conto,
            ]);
        } else {
            // Più anagrafiche collegate: crea un nuovo conto e aggiorna i movimenti
            $this->creaECollegaNuovoConto($anagrafica, $campo_conto, $id_conto);
        }
    }

    /**
     * Crea un nuovo conto per l'anagrafica e aggiorna i movimenti contabili.
     *
     * @param Anagrafica $anagrafica
     * @param string     $campo_conto
     * @param int        $vecchio_id_conto
     */
    private function creaECollegaNuovoConto($anagrafica, $campo_conto, $vecchio_id_conto)
    {
        $database = database();

        // Determina la categoria del conto
        $categoria_conto_id = null;
        if ($campo_conto == 'idconto_cliente') {
            $categoria_conto_id = setting('Conto di secondo livello per i crediti clienti');
        } else {
            $categoria_conto_id = setting('Conto di secondo livello per i debiti fornitori');
        }

        // Calcola il prossimo numero per il nuovo conto
        $numero = $database->table('co_pianodeiconti3')
            ->where('idpianodeiconti2', '=', $categoria_conto_id)
            ->selectRaw('MAX(CAST(numero AS UNSIGNED)) AS max_numero')
            ->first();
        $new_numero = $numero->max_numero + 1;
        $new_numero = str_pad($new_numero, 6, '0', STR_PAD_LEFT);

        // Crea il nuovo conto
        $nuovo_id_conto = $database->table('co_pianodeiconti3')
            ->insertGetId([
                'numero' => $new_numero,
                'descrizione' => $anagrafica->ragione_sociale,
                'idpianodeiconti2' => $categoria_conto_id,
            ]);

        // Aggiorna l'anagrafica con il nuovo conto
        $anagrafica->$campo_conto = $nuovo_id_conto;
        $anagrafica->save();

        // Aggiorna tutti i movimenti contabili collegati a questa anagrafica
        $this->aggiornaMovimentiContabili($anagrafica->idanagrafica, $vecchio_id_conto, $nuovo_id_conto);

        // Verifica se il vecchio conto è rimasto vuoto e lo elimina
        $this->eliminaContoSeVuoto($vecchio_id_conto);
    }

    /**
     * Aggiorna i movimenti contabili sostituendo il vecchio conto con il nuovo
     * per una specifica anagrafica.
     *
     * @param int $id_anagrafica
     * @param int $vecchio_id_conto
     * @param int $nuovo_id_conto
     */
    private function aggiornaMovimentiContabili($id_anagrafica, $vecchio_id_conto, $nuovo_id_conto)
    {
        $database = database();

        // Aggiorna i movimenti contabili collegati all'anagrafica e al vecchio conto
        $database->update('co_movimenti', [
            'idconto' => $nuovo_id_conto,
        ], [
            'id_anagrafica' => $id_anagrafica,
            'idconto' => $vecchio_id_conto,
        ]);

        // Aggiorna anche i movimenti collegati ai documenti dell'anagrafica
        $documenti_anagrafica = $database->fetchArray('
            SELECT DISTINCT iddocumento
            FROM co_movimenti
            WHERE id_anagrafica = '.prepare($id_anagrafica).'
            AND iddocumento > 0
        ');

        foreach ($documenti_anagrafica as $documento) {
            $database->update('co_movimenti', [
                'idconto' => $nuovo_id_conto,
            ], [
                'iddocumento' => $documento['iddocumento'],
                'idconto' => $vecchio_id_conto,
            ]);
        }
    }

    /**
     * Elimina un conto se non è più collegato ad alcuna anagrafica e non ha movimenti.
     *
     * @param int $id_conto
     */
    private function eliminaContoSeVuoto($id_conto)
    {
        $database = database();

        // Verifica se ci sono ancora anagrafiche collegate a questo conto
        $anagrafiche_collegate_cliente = $database->fetchOne('SELECT COUNT(*) as count FROM an_anagrafiche WHERE idconto_cliente = '.prepare($id_conto).' AND deleted_at IS NULL')['count'];
        $anagrafiche_collegate_fornitore = $database->fetchOne('SELECT COUNT(*) as count FROM an_anagrafiche WHERE idconto_fornitore = '.prepare($id_conto).' AND deleted_at IS NULL')['count'];
        $totale_anagrafiche_collegate = $anagrafiche_collegate_cliente + $anagrafiche_collegate_fornitore;

        // Verifica se ci sono movimenti contabili collegati a questo conto
        $movimenti_collegati = $database->fetchOne('SELECT COUNT(*) as count FROM co_movimenti WHERE idconto = '.prepare($id_conto))['count'];

        // Se non ci sono anagrafiche e movimenti collegati, elimina il conto
        if ($totale_anagrafiche_collegate == 0 && $movimenti_collegati == 0) {
            $database->delete('co_pianodeiconti3', ['id' => $id_conto]);
        }
    }
}
