<?php

use Illuminate\Database\Schema\Blueprint;

$connessione = database()->table('in_interventi')->getConnection();
$schema = $connessione->getSchemaBuilder();
$nome_blocco = 'osm_interventi_impegno_'.sha1($connessione->getDatabaseName());
$blocco = $connessione->selectOne('SELECT GET_LOCK(?, 15) AS acquisito', [$nome_blocco], false);

if ((int) ($blocco->acquisito ?? 0) !== 1) {
    throw new RuntimeException('Aggiornamento dell’impegno materiali già in corso. Riprovare.');
}

try {
    if ($schema->hasColumn('in_interventi', 'posticipa_scarico_materiali')
        || $schema->hasColumn('in_righe_interventi', 'impegna_magazzino')) {
        throw new RuntimeException('È presente uno schema precedente di impegno materiali. Verificare i dati prima di aggiornare Attività.');
    }

    $nome = 'Impegna i materiali nelle nuove attività';
    $impostazione = $connessione->table('zz_settings')->where('nome', $nome)->first();
    if ($impostazione && ($impostazione->tipo !== 'boolean' || $impostazione->sezione !== 'Attività'
        || !$impostazione->editable || $impostazione->is_user_setting)) {
        throw new RuntimeException('Esiste già un’impostazione incompatibile per l’impegno materiali nelle Attività.');
    }

    $colonne = [
        'in_interventi' => 'impegna_materiali',
        'in_righe_interventi' => 'impegnato_magazzino',
    ];
    $mancanti = [];
    foreach ($colonne as $tabella => $campo) {
        $definizione = $connessione->selectOne(
            'SELECT DATA_TYPE AS tipo, IS_NULLABLE AS nullable, COLUMN_DEFAULT AS valore_predefinito
             FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            [$tabella, $campo],
            false,
        );
        if (!$definizione) {
            $mancanti[$tabella] = $campo;
        } elseif ($definizione->tipo !== 'tinyint' || $definizione->nullable !== 'NO'
            || trim((string) $definizione->valore_predefinito, "'") !== '0') {
            throw new RuntimeException('Colonna incompatibile con l’impegno materiali nelle Attività: '.$tabella.'.'.$campo);
        }
    }

    foreach ($mancanti as $tabella => $campo) {
        $schema->table($tabella, static function (Blueprint $struttura) use ($campo) {
            $struttura->boolean($campo)->default(false);
        });
    }

    $connessione->transaction(static function () use ($connessione, $impostazione, $nome) {
        $id = $impostazione->id ?? $connessione->table('zz_settings')->insertGetId([
            'nome' => $nome,
            'valore' => '0',
            'tipo' => 'boolean',
            'sezione' => 'Attività',
            'editable' => true,
            'is_user_setting' => false,
        ]);

        foreach ($connessione->table('zz_langs')->get(['id', 'language_code']) as $lingua) {
            $riferimento = ['id_record' => $id, 'id_lang' => $lingua->id];
            if ($connessione->table('zz_settings_lang')->where($riferimento)->exists()) {
                continue;
            }

            $italiano = $lingua->language_code === 'it_IT';
            $connessione->table('zz_settings_lang')->insert($riferimento + [
                'title' => $italiano ? $nome : 'Reserve materials in new activities',
                'help' => $italiano
                    ? 'I materiali aggiunti alle nuove attività vengono impegnati e restano nel saldo dell’inventario.'
                    : 'Materials added to new activities are reserved and remain in the inventory balance.',
            ]);
        }
    });
} finally {
    $connessione->selectOne('SELECT RELEASE_LOCK(?)', [$nome_blocco], false);
}
