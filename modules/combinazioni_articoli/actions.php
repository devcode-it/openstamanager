<?php

use Modules\CombinazioniArticoli\Combinazione;

include_once __DIR__.'/../../core.php';

switch (filter('op')) {
    case 'add':
    case 'update':
        $nome = post('nome');

        // Ricerca combinazione con nome indicato
        $esistente = Combinazione::where('nome', '=', $nome);
        if (isset($combinazione)) {
            $esistente = $esistente->where('id', '!=', $combinazione->id);
        }
        $esistente = $esistente->count() !== 0;

        if (!$esistente) {
            $combinazione = $combinazione ?: Combinazione::build();
            $combinazione->nome = $nome;
            $combinazione->codice = post('codice');
            $combinazione->id_categoria = post('id_categoria');
            $combinazione->id_sottocategoria = post('id_sottocategoria');
            $combinazione->save();

            $id_record = $combinazione->id;

            // Selezione attributi per la combinazione
            if ($combinazione->articoli()->count() == 0) {
                $combinazione->attributi()->sync((array) post('attributi'));
            }

            flash()->info(tr('Combinazione aggiornata correttamente!'));
        } else {
            flash()->error(tr('Combinazione esistente con lo stesso nome!'));
        }

        break;

    case 'delete':
        $combinazione->delete();

        flash()->info(tr('Combinazione rimossa correttamente!'));

        break;

    case 'gestione-variante':
        $combinazione->generaVariante((array) filter('attributo'));

        flash()->info(tr('Variante aggiunta correttamente!'));

        break;

    case 'genera-varianti':
        $combinazione->generaTutto();

        flash()->info(tr('Varianti generate correttamente!'));

        break;
}
