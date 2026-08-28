<?php

include_once __DIR__.'/../../core.php';

function noteSpeseTipologiaNormalizeDescription($value)
{
    return trim(strip_tags((string) $value));
}

function noteSpeseTipologiaStringLength($value)
{
    $value = (string) $value;

    if (function_exists('mb_strlen')) {
        return mb_strlen($value, 'UTF-8');
    }

    $count = preg_match_all('/./us', $value, $matches);

    return $count !== false ? $count : strlen($value);
}

function noteSpeseTipologiaDescriptionIsValid($value)
{
    $value = (string) $value;

    return $value !== '' && noteSpeseTipologiaStringLength($value) <= 100;
}

switch (post('op')) {
    case 'add':
        Permissions::check('rw');

        $descrizione = noteSpeseTipologiaNormalizeDescription(post('descrizione'));
        $ordine = max(0, (int) post('ordine'));
        $id_lang = (int) Models\Locale::getDefault()->id;

        if (!noteSpeseTipologiaDescriptionIsValid($descrizione)) {
            flash()->error(tr('Inserire una descrizione valida di massimo 100 caratteri.'));
            break;
        }

        $duplicate = $dbo->fetchOne(
            'SELECT t.`id` FROM `co_note_spese_tipologie` t '
            .'LEFT JOIN `co_note_spese_tipologie_lang` l ON l.`id_record` = t.`id` '
            .'WHERE LOWER(t.`descrizione`) = LOWER('.prepare($descrizione).') OR LOWER(l.`title`) = LOWER('.prepare($descrizione).') LIMIT 1'
        );
        if (!empty($duplicate)) {
            flash()->error(tr('Esiste già una tipologia con questa descrizione.'));
            break;
        }

        $dbo->insert('co_note_spese_tipologie', [
            'codice' => null,
            'descrizione' => $descrizione,
            'ordine' => $ordine,
            'enabled' => 1,
            'can_delete' => 1,
        ]);
        $id_record = $dbo->lastInsertedID();

        $dbo->insert('co_note_spese_tipologie_lang', [
            'id_lang' => $id_lang,
            'id_record' => $id_record,
            'title' => $descrizione,
        ]);

        flash()->info(tr('Tipologia aggiunta correttamente.'));
        break;

    case 'update':
        Permissions::check('rw');

        if (empty($id_record)) {
            break;
        }

        $descrizione = noteSpeseTipologiaNormalizeDescription(post('descrizione'));
        $ordine = max(0, (int) post('ordine'));
        $enabled = (int) post('enabled');
        $id_lang = (int) Models\Locale::getDefault()->id;

        if (!noteSpeseTipologiaDescriptionIsValid($descrizione)) {
            flash()->error(tr('Inserire una descrizione valida di massimo 100 caratteri.'));
            break;
        }

        $duplicate = $dbo->fetchOne(
            'SELECT t.`id` FROM `co_note_spese_tipologie` t '
            .'LEFT JOIN `co_note_spese_tipologie_lang` l ON l.`id_record` = t.`id` '
            .'WHERE t.`id` != '.prepare($id_record).' AND (LOWER(t.`descrizione`) = LOWER('.prepare($descrizione).') '
            .'OR LOWER(l.`title`) = LOWER('.prepare($descrizione).')) LIMIT 1'
        );
        if (!empty($duplicate)) {
            flash()->error(tr('Esiste già una tipologia con questa descrizione.'));
            break;
        }

        $category_data = [
            'ordine' => $ordine,
            'enabled' => $enabled ? 1 : 0,
        ];
        if (Models\Locale::getDefault()->id == Models\Locale::getPredefined()->id) {
            $category_data['descrizione'] = $descrizione;
        }
        $dbo->update('co_note_spese_tipologie', $category_data, ['id' => $id_record]);

        $translation = $dbo->fetchOne(
            'SELECT `id` FROM `co_note_spese_tipologie_lang` WHERE `id_lang` = '.prepare($id_lang).' AND `id_record` = '.prepare($id_record).' LIMIT 1'
        );
        if (!empty($translation)) {
            $dbo->update('co_note_spese_tipologie_lang', ['title' => $descrizione], ['id' => $translation['id']]);
        } else {
            $dbo->insert('co_note_spese_tipologie_lang', [
                'id_lang' => $id_lang,
                'id_record' => $id_record,
                'title' => $descrizione,
            ]);
        }

        flash()->info(tr('Tipologia aggiornata correttamente.'));
        break;

    case 'delete':
        Permissions::check('rw');

        if (empty($id_record)) {
            break;
        }

        $used = $dbo->fetchNum('SELECT `id` FROM `co_note_spese` WHERE `id_tipologia` = '.prepare($id_record));
        $record = $dbo->fetchOne('SELECT `can_delete` FROM `co_note_spese_tipologie` WHERE `id` = '.prepare($id_record));

        if (empty($used) && !empty($record['can_delete'])) {
            $dbo->delete('co_note_spese_tipologie', ['id' => $id_record]);
            flash()->info(tr('Tipologia eliminata correttamente.'));
        } else {
            flash()->error(tr('La tipologia non può essere eliminata perché è predefinita o già utilizzata.'));
        }
        break;
}
