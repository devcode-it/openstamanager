<?php

use Modules\CombinazioniArticoli\Combinazione;

include_once __DIR__.'/../../core.php';

switch (filter('op')) {
    case 'add':
    case 'update':
        $nome = post('nome');

        // Ricerca combinazione con nome indicato
        $combinazione_new = (new Combinazione())->getByField('name', $nome);

        if (!empty($combinazione_new) && !empty($id_record) && $combinazione_new != $id_record) {
            flash()->error(tr('Questo nome è già stato utilizzato per un altra combinazione.'));
        } else {
            if (empty($combinazione)) {
                $combinazione = Combinazione::build();
                $id_record = $dbo->lastInsertedID();
            }
            $combinazione->setTranslation('name', $nome);
            $combinazione->codice = post('codice');
            $combinazione->id_categoria = post('id_categoria')?: null;
            $combinazione->id_sottocategoria = post('id_sottocategoria')?: null;
            $combinazione->save();

            $id_record = $combinazione->id;

            $database->query('INSERT INTO `mg_combinazioni_lang` (`id_record`, `id_lang`, `name`) VALUES ('.$id_record.', '.prepare(Models\Locale::getDefault()->id).', \''.post('nome').'\')');

            // Selezione attributi per la combinazione
            $combinazione->attributi()->sync((array) post('attributi'));

            flash()->info(tr('Combinazione aggiornata correttamente!'));
        }

        break;

    case 'delete':
        $combinazione->delete();

        flash()->info(tr('Combinazione rimossa correttamente!'));

        break;

    case 'edit-variante':
        $valori = (array) filter('attributo');
        $id_articolo = filter('id_articolo');

        $database->delete('mg_articolo_attributo', ['id_articolo' => $id_articolo]);

        foreach ($valori as $valore) {
            $database->insert('mg_articolo_attributo', [
                'id_articolo' => $id_articolo,
                'id_valore' => $valore,
            ]);
        }

        flash()->info(tr('Variante modificata correttamente!'));

        break;

    case 'add-variante':
        $combinazione->generaVariante((array) filter('attributo'), filter('id_articolo'));

        flash()->info(tr('Variante aggiunta correttamente!'));

        break;

    case 'remove-variante':
        $id_articolo = filter('id_articolo');

        $database->delete('mg_articolo_attributo', ['id_articolo' => $id_articolo]);
        $database->update('mg_articoli', ['id_combinazione' => null], ['id' => $id_articolo]);

        flash()->info(tr('Variante rimossa correttamente!'));

        break;

    case 'genera-varianti':
        $combinazione->generaTutto();

        flash()->info(tr('Varianti generate correttamente!'));

        break;
}
