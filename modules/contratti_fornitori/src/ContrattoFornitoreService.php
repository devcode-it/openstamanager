<?php

/*
 * OpenSTAManager: il software gestionale open source per l'assistenza tecnica e la fatturazione
 * Copyright (C) DevCode s.r.l.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

namespace Modules\ContrattiFornitori;

use DateTime;
use DateTimeImmutable;
use Models\Upload;
use RuntimeException;
use Throwable;

class ContrattoFornitoreService
{
    public const STATO_BOZZA = 'Bozza';
    public const STATO_ATTIVO = 'Attivo';
    public const STATO_DISDETTO = 'Disdetto';
    public const STATO_TERMINATO = 'Terminato';
    public const MAX_BULK_RECORDS = 100;

    private int $savepointCounter = 0;

    private const STATE_TRANSITIONS = [
        self::STATO_BOZZA => [self::STATO_ATTIVO, self::STATO_DISDETTO],
        self::STATO_ATTIVO => [self::STATO_DISDETTO, self::STATO_TERMINATO],
        self::STATO_DISDETTO => [self::STATO_ATTIVO, self::STATO_TERMINATO],
        self::STATO_TERMINATO => [],
    ];

    private const PERIODICITA_AMMESSE = [
        'una_tantum', 'mensile', 'bimestrale', 'trimestrale', 'semestrale', 'annuale',
    ];

    public function __construct(private \Database $dbo, private int $idModulo)
    {
    }

    public function validateText(?string $value, int $maxLength, string $label, bool $required = false): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            if ($required) {
                throw new RuntimeException(tr('Il campo _FIELD_ è obbligatorio.', ['_FIELD_' => $label]));
            }

            return null;
        }

        if (mb_strlen($value) > $maxLength) {
            throw new RuntimeException(tr('Il campo _FIELD_ non può superare _MAX_ caratteri.', [
                '_FIELD_' => $label,
                '_MAX_' => $maxLength,
            ]));
        }

        return $value;
    }

    public function validateName(?string $name): string
    {
        return (string) $this->validateText($name, 255, tr('Descrizione contratto'), true);
    }

    public function validateDate(?string $date, bool $required = false): ?string
    {
        $date = trim((string) $date);
        if ($date === '') {
            if ($required) {
                throw new RuntimeException(tr('La data richiesta non è stata indicata.'));
            }

            return null;
        }

        $parsed = DateTime::createFromFormat('Y-m-d', $date);
        $errors = DateTime::getLastErrors();
        if (!$parsed || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0)) || $parsed->format('Y-m-d') !== $date) {
            throw new RuntimeException(tr('Formato data non valido.'));
        }

        return $date;
    }

    public function normalizeAmount($amount): float
    {
        if ($amount === null || $amount === '') {
            return 0.0;
        }

        if (is_int($amount) || is_float($amount)) {
            $normalized = round((float) $amount, 2);
        } else {
            $value = str_replace(["\xc2\xa0", ' ', '€'], '', trim((string) $amount));
            if (!preg_match('/^-?[0-9.,]+$/', $value)) {
                throw new RuntimeException(tr('Importo non valido.'));
            }

            $comma = strrpos($value, ',');
            $dot = strrpos($value, '.');
            if ($comma !== false && $dot !== false) {
                $value = $comma > $dot
                    ? str_replace(',', '.', str_replace('.', '', $value))
                    : str_replace(',', '', $value);
            } elseif ($comma !== false) {
                $value = str_replace(',', '.', str_replace('.', '', $value));
            } elseif (substr_count($value, '.') > 1) {
                $value = str_replace('.', '', $value);
            }

            if (!is_numeric($value)) {
                throw new RuntimeException(tr('Importo non valido.'));
            }

            $normalized = round((float) $value, 2);
        }

        if ($normalized < 0) {
            throw new RuntimeException(tr('L\'importo non può essere negativo.'));
        }

        return $normalized;
    }

    public function normalizeTipoValidita(?string $tipo): ?string
    {
        $tipo = trim((string) $tipo);
        $map = ['manuale' => 'manual', 'giorni' => 'days', 'mesi' => 'months', 'anni' => 'years'];

        return $map[$tipo] ?? ($tipo !== '' ? $tipo : null);
    }

    public function calculateExpiry(?string $inizio, $validita, ?string $tipo): ?string
    {
        $tipo = $this->normalizeTipoValidita($tipo);
        $validita = (int) $validita;
        if (empty($inizio) || $validita <= 0 || empty($tipo) || $tipo === 'manual') {
            return null;
        }

        if (!in_array($tipo, ['days', 'months', 'years'], true)) {
            throw new RuntimeException(tr('Tipo di validità non valido.'));
        }

        $start = new DateTimeImmutable($this->validateDate($inizio, true));
        if ($tipo === 'days') {
            return $start->modify('+'.($validita - 1).' days')->format('Y-m-d');
        }

        $months = $tipo === 'years' ? $validita * 12 : $validita;
        $startDay = (int) $start->format('d');
        $targetMonth = $start->modify('first day of this month')->modify('+'.$months.' months');
        $daysInTargetMonth = (int) $targetMonth->format('t');

        if ($startDay > $daysInTargetMonth) {
            $expiry = $targetMonth->setDate((int) $targetMonth->format('Y'), (int) $targetMonth->format('m'), $daysInTargetMonth);
        } else {
            $expiry = $targetMonth
                ->setDate((int) $targetMonth->format('Y'), (int) $targetMonth->format('m'), $startDay)
                ->modify('-1 day');
        }

        return $expiry->format('Y-m-d');
    }

    public function calculateCancellationDeadline(?string $scadenza, $giorni): ?string
    {
        if (empty($scadenza)) {
            return null;
        }

        return (new DateTimeImmutable($this->validateDate($scadenza, true)))
            ->modify('-'.max(0, (int) $giorni).' days')
            ->format('Y-m-d');
    }

    public function getStateId(string $name): int
    {
        $row = $this->dbo->fetchOne('SELECT `id` FROM `ac_stati_contratti_fornitori` WHERE `nome` = '.prepare($name).' LIMIT 1');
        if (empty($row['id'])) {
            throw new RuntimeException(tr('Stato contratto non configurato: _STATE_.', ['_STATE_' => $name]));
        }

        return (int) $row['id'];
    }

    public function getStateName(int $idState): string
    {
        $row = $this->dbo->fetchOne('SELECT `nome` FROM `ac_stati_contratti_fornitori` WHERE `id` = '.prepare($idState).' LIMIT 1');
        if (empty($row['nome'])) {
            throw new RuntimeException(tr('Stato contratto non valido.'));
        }

        return (string) $row['nome'];
    }

    public function validateSupplier($idSupplier): int
    {
        $idSupplier = (int) $idSupplier;
        $row = $this->dbo->fetchOne(
            'SELECT `an_anagrafiche`.`idanagrafica`
            FROM `an_anagrafiche`
            INNER JOIN `an_tipianagrafiche_anagrafiche`
                ON `an_tipianagrafiche_anagrafiche`.`idanagrafica` = `an_anagrafiche`.`idanagrafica`
            INNER JOIN `an_tipianagrafiche`
                ON `an_tipianagrafiche`.`idtipoanagrafica` = `an_tipianagrafiche_anagrafiche`.`idtipoanagrafica`
            WHERE `an_anagrafiche`.`idanagrafica` = '.prepare($idSupplier).'
              AND `an_tipianagrafiche`.`descrizione` = '.prepare('Fornitore').'
            LIMIT 1'
        );
        if (empty($row)) {
            throw new RuntimeException(tr('Selezionare un\'anagrafica di tipo Fornitore.'));
        }

        return $idSupplier;
    }

    public function validateCategory($idCategory): ?int
    {
        $idCategory = (int) $idCategory;
        if ($idCategory <= 0) {
            return null;
        }

        $row = $this->dbo->fetchOne('SELECT `id` FROM `ac_categorie_contratti_fornitori` WHERE `id` = '.prepare($idCategory).' LIMIT 1');
        if (empty($row)) {
            throw new RuntimeException(tr('Categoria non valida.'));
        }

        return $idCategory;
    }

    public function validateReferent($idReferent, int $idSupplier): ?int
    {
        $idReferent = (int) $idReferent;
        if ($idReferent <= 0) {
            return null;
        }

        $row = $this->dbo->fetchOne('SELECT `id` FROM `an_referenti` WHERE `id` = '.prepare($idReferent).' AND `idanagrafica` = '.prepare($idSupplier).' LIMIT 1');
        if (empty($row)) {
            throw new RuntimeException(tr('Referente del fornitore non valido.'));
        }

        return $idReferent;
    }

    public function validateInternalReferent($idAgent): ?int
    {
        $idAgent = (int) $idAgent;
        if ($idAgent <= 0) {
            return null;
        }

        $row = $this->dbo->fetchOne('SELECT `idanagrafica` FROM `an_anagrafiche` WHERE `idanagrafica` = '.prepare($idAgent).' LIMIT 1');
        if (empty($row)) {
            throw new RuntimeException(tr('Referente interno non valido.'));
        }

        return $idAgent;
    }

    public function validatePeriodicity(?string $periodicity): ?string
    {
        $periodicity = trim((string) $periodicity);
        if ($periodicity === '') {
            return null;
        }
        if (!in_array($periodicity, self::PERIODICITA_AMMESSE, true)) {
            throw new RuntimeException(tr('Periodicità non valida.'));
        }

        return $periodicity;
    }

    public function nextNumber(int $idSegment, ?string $creationDate = null): string
    {
        $creationDate = $this->validateDate($creationDate ?: date('Y-m-d'), true);
        $year = substr($creationDate, 2, 2);
        $rows = $this->dbo->fetchArray('SELECT `numero` FROM `ac_contratti_fornitori` WHERE `id_segment` = '.prepare($idSegment).' FOR UPDATE');
        $max = 0;
        foreach ($rows as $row) {
            if (preg_match('/^(\d+)\/'.$year.'$/', (string) $row['numero'], $matches)) {
                $max = max($max, (int) $matches[1]);
            }
        }

        return str_pad((string) ($max + 1), 4, '0', STR_PAD_LEFT).'/'.$year;
    }

    public function validateNumber(string $number, int $idSegment, ?int $excludeId = null): string
    {
        $number = trim($number);
        if ($number === '') {
            throw new RuntimeException(tr('Numero contratto obbligatorio.'));
        }

        $query = 'SELECT `id` FROM `ac_contratti_fornitori` WHERE `id_segment` = '.prepare($idSegment).' AND `numero` = '.prepare($number);
        if ($excludeId) {
            $query .= ' AND `id` != '.prepare($excludeId);
        }
        if (!empty($this->dbo->fetchOne($query.' LIMIT 1'))) {
            throw new RuntimeException(tr('Numero contratto già utilizzato nel sezionale selezionato.'));
        }

        return $number;
    }

    public function create(array $input): int
    {
        $scope = $this->beginTransaction();
        try {
            $idSegment = (int) ($input['id_segment'] ?? 0);
            if ($idSegment <= 0) {
                throw new RuntimeException(tr('Sezionale non valido.'));
            }
            $start = $this->validateDate($input['data_inizio'] ?? null, true);
            $type = $this->normalizeTipoValidita($input['tipo_validita'] ?? null);
            $validity = max(0, (int) ($input['validita'] ?? 0));
            $data = [
                'numero' => $this->nextNumber($idSegment),
                'id_segment' => $idSegment,
                'id_fornitore' => $this->validateSupplier($input['id_fornitore'] ?? 0),
                'id_stato' => $this->getStateId(self::STATO_BOZZA),
                'id_categoria' => $this->validateCategory($input['id_categoria'] ?? null),
                'nome' => $this->validateName($input['nome'] ?? null),
                'data_inizio' => $start,
                'validita' => $validity ?: null,
                'tipo_validita' => $type,
                'data_scadenza' => $this->calculateExpiry($start, $validity, $type),
                'giorni_preavviso' => 0,
                'rinnovo_automatico' => 0,
                'mesi_rinnovo' => 0,
                'importo' => 0,
                'note' => '',
            ];
            $this->dbo->insert('ac_contratti_fornitori', $data);
            $id = (int) $this->dbo->lastInsertedID();
            $this->commitTransaction($scope);

            return $id;
        } catch (Throwable $e) {
            $this->rollbackTransaction($scope);
            throw $e;
        }
    }

    public function update(int $id, array $input, bool $forceStateTransition = false): void
    {
        $scope = $this->beginTransaction();
        try {
            $old = $this->findForUpdate($id);
            $supplier = $this->validateSupplier($input['id_fornitore'] ?? $old['id_fornitore']);
            $state = (int) ($input['id_stato'] ?? $old['id_stato']);
            $oldState = $this->getStateName((int) $old['id_stato']);
            $newState = $this->getStateName($state);
            if (!$forceStateTransition && $oldState !== $newState && !in_array($newState, self::STATE_TRANSITIONS[$oldState] ?? [], true)) {
                throw new RuntimeException(tr('Passaggio di stato non standard. Confermare per procedere.'));
            }

            $start = $this->validateDate($input['data_inizio'] ?? $old['data_inizio'], true);
            $type = $this->normalizeTipoValidita($input['tipo_validita'] ?? $old['tipo_validita']);
            $validity = max(0, (int) ($input['validita'] ?? $old['validita']));
            $expiry = $type === 'manual'
                ? $this->validateDate($input['data_scadenza'] ?? $old['data_scadenza'])
                : $this->calculateExpiry($start, $validity, $type);
            $notice = max(0, (int) ($input['giorni_preavviso'] ?? $old['giorni_preavviso']));

            $data = [
                'numero' => $this->validateNumber((string) ($input['numero'] ?? $old['numero']), (int) $old['id_segment'], $id),
                'nome' => $this->validateName($input['nome'] ?? $old['nome']),
                'id_fornitore' => $supplier,
                'id_referente' => $this->validateReferent($input['id_referente'] ?? null, $supplier),
                'idagente' => $this->validateInternalReferent($input['idagente'] ?? null),
                'id_stato' => $state,
                'id_categoria' => $this->validateCategory($input['id_categoria'] ?? null),
                'numero_fornitore' => $this->validateText($input['numero_fornitore'] ?? null, 100, tr('Numero fornitore')),
                'data_stipula' => $this->validateDate($input['data_stipula'] ?? null),
                'data_inizio' => $start,
                'validita' => $validity ?: null,
                'tipo_validita' => $type,
                'data_scadenza' => $expiry,
                'giorni_preavviso' => $notice,
                'data_limite_disdetta' => $this->calculateCancellationDeadline($expiry, $notice),
                'rinnovo_automatico' => empty($input['rinnovo_automatico']) ? 0 : 1,
                'mesi_rinnovo' => max(0, (int) ($input['mesi_rinnovo'] ?? 0)),
                'condizioni_rinnovo' => $this->validateText($input['condizioni_rinnovo'] ?? null, 255, tr('Condizioni di rinnovo')),
                'importo' => $this->normalizeAmount($input['importo'] ?? 0),
                'periodicita' => $this->validatePeriodicity($input['periodicita'] ?? null),
                'note_economiche' => $this->validateText($input['note_economiche'] ?? null, 255, tr('Note economiche')),
                'note' => trim((string) ($input['note'] ?? '')),
            ];
            $this->dbo->update('ac_contratti_fornitori', $data, ['id' => $id]);
            $this->commitTransaction($scope);
        } catch (Throwable $e) {
            $this->rollbackTransaction($scope);
            throw $e;
        }
    }

    public function changeState(int $id, int $idState, bool $force = false): void
    {
        $old = $this->find($id);
        $from = $this->getStateName((int) $old['id_stato']);
        $to = $this->getStateName($idState);
        if (!$force && $from !== $to && !in_array($to, self::STATE_TRANSITIONS[$from] ?? [], true)) {
            throw new RuntimeException(tr('Passaggio di stato non standard. Confermare per procedere.'));
        }
        $this->dbo->update('ac_contratti_fornitori', ['id_stato' => $idState], ['id' => $id]);
    }

    public function duplicate(int $id): int
    {
        return $this->copyRecord($id, false);
    }

    public function renew(int $id): int
    {
        return $this->copyRecord($id, true);
    }

    public function deleteDraft(int $id): void
    {
        $scope = $this->beginTransaction();
        try {
            $record = $this->findForUpdate($id);
            if ($this->getStateName((int) $record['id_stato']) !== self::STATO_BOZZA) {
                throw new RuntimeException(tr('È possibile eliminare soltanto i contratti in stato Bozza.'));
            }
            if (Upload::where('id_module', $this->idModulo)->where('id_record', $id)->exists()) {
                throw new RuntimeException(tr('Eliminare prima gli allegati associati al contratto.'));
            }
            if (!empty($record['id_contratto_precedente'])) {
                $this->dbo->update('ac_contratti_fornitori', ['id_contratto_successivo' => null], ['id' => $record['id_contratto_precedente']]);
            }
            if (!empty($record['id_contratto_successivo'])) {
                $this->dbo->update('ac_contratti_fornitori', ['id_contratto_precedente' => null], ['id' => $record['id_contratto_successivo']]);
            }
            $this->dbo->delete('ac_contratti_fornitori', ['id' => $id]);
            $this->commitTransaction($scope);
        } catch (Throwable $e) {
            $this->rollbackTransaction($scope);
            throw $e;
        }
    }

    private function copyRecord(int $id, bool $renew): int
    {
        $scope = $this->beginTransaction();
        try {
            $old = $this->findForUpdate($id);
            if ($renew) {
                if ($this->getStateName((int) $old['id_stato']) !== self::STATO_ATTIVO) {
                    throw new RuntimeException(tr('È possibile rinnovare soltanto i contratti Attivi.'));
                }
                if (!empty($old['id_contratto_successivo']) || empty($old['data_scadenza'])) {
                    throw new RuntimeException(tr('Il contratto non può essere rinnovato.'));
                }
            }

            $data = $old;
            unset($data['id'], $data['created_at'], $data['updated_at']);
            $data['numero'] = $this->nextNumber((int) $old['id_segment']);
            $data['id_stato'] = $this->getStateId(self::STATO_BOZZA);
            $data['data_stipula'] = null;
            $data['id_contratto_successivo'] = null;
            $data['id_contratto_precedente'] = $renew ? $id : null;

            if ($renew) {
                $start = date('Y-m-d', strtotime($old['data_scadenza'].' +1 day'));
                $type = $this->normalizeTipoValidita($old['tipo_validita']);
                $validity = $type === 'manual' ? max(1, (int) $old['mesi_rinnovo']) : (int) $old['validita'];
                $newType = $type === 'manual' ? 'months' : $type;
                $data['data_inizio'] = $start;
                $data['validita'] = $validity;
                $data['tipo_validita'] = $newType;
                $data['data_scadenza'] = $this->calculateExpiry($start, $validity, $newType);
                $data['data_limite_disdetta'] = $this->calculateCancellationDeadline($data['data_scadenza'], $old['giorni_preavviso']);
            }

            $this->dbo->insert('ac_contratti_fornitori', $data);
            $newId = (int) $this->dbo->lastInsertedID();
            if ($renew) {
                $update = ['id_contratto_successivo' => $newId];
                if ($old['data_scadenza'] <= date('Y-m-d')) {
                    $update['id_stato'] = $this->getStateId(self::STATO_TERMINATO);
                }
                $this->dbo->update('ac_contratti_fornitori', $update, ['id' => $id]);
            }
            $this->commitTransaction($scope);

            return $newId;
        } catch (Throwable $e) {
            $this->rollbackTransaction($scope);
            throw $e;
        }
    }

    private function beginTransaction(): array
    {
        $pdo = $this->dbo->getPDO();
        if (!$pdo->inTransaction()) {
            $pdo->beginTransaction();

            return ['own' => true, 'savepoint' => null];
        }

        $savepoint = 'cf_'.(++$this->savepointCounter);
        $pdo->exec('SAVEPOINT '.$savepoint);

        return ['own' => false, 'savepoint' => $savepoint];
    }

    private function commitTransaction(array $scope): void
    {
        $pdo = $this->dbo->getPDO();
        if ($scope['own']) {
            if ($pdo->inTransaction()) {
                $pdo->commit();
            }
        } elseif ($pdo->inTransaction()) {
            $pdo->exec('RELEASE SAVEPOINT '.$scope['savepoint']);
        }
    }

    private function rollbackTransaction(array $scope): void
    {
        $pdo = $this->dbo->getPDO();
        if (!$pdo->inTransaction()) {
            return;
        }
        if ($scope['own']) {
            $pdo->rollBack();
        } else {
            $pdo->exec('ROLLBACK TO SAVEPOINT '.$scope['savepoint']);
            $pdo->exec('RELEASE SAVEPOINT '.$scope['savepoint']);
        }
    }

    private function find(int $id): array
    {
        $row = $this->dbo->fetchOne('SELECT * FROM `ac_contratti_fornitori` WHERE `id` = '.prepare($id).' LIMIT 1');
        if (empty($row)) {
            throw new RuntimeException(tr('Contratto fornitore non trovato.'));
        }

        return $row;
    }

    private function findForUpdate(int $id): array
    {
        $row = $this->dbo->fetchOne('SELECT * FROM `ac_contratti_fornitori` WHERE `id` = '.prepare($id).' LIMIT 1 FOR UPDATE');
        if (empty($row)) {
            throw new RuntimeException(tr('Contratto fornitore non trovato.'));
        }

        return $row;
    }
}
