<?php

include_once __DIR__.'/../../core.php';

/**
 * Funzione per inserire i movimenti di magazzino.
 */
function add_movimento_magazzino($idarticolo, $qta, $array = [], $descrizone = '')
{
    $dbo = Database::getConnection();

    if (empty($qta)) {
        return false;
    }

    // DDT
    if (!empty($array['idddt'])) {
        $rs = $dbo->fetchArray('SELECT numero, numero_esterno, dt_ddt.descrizione AS tipo, dt_ddt.dir FROM dt_ddt LEFT JOIN dt_tipiddt ON dt_tipiddt.id = dt_ddt.idtipoddt WHERE dt_ddt.id='.prepare($array['idddt']));
        $numero = (!empty($rs[0]['numero_esterno'])) ? $rs[0]['numero_esterno'] : $rs[0]['numero'];
        $tipo = strtolower($rs[0]['tipo']);
    }

    // Fattura
    elseif (!empty($array['iddocumento'])) {
        $rs = $dbo->fetchArray('SELECT numero, numero_esterno, co_tipidocumento.descrizione AS tipo, co_tipidocumento.dir FROM co_documenti LEFT JOIN co_tipidocumento ON co_tipidocumento.id = co_documenti.idtipodocumento WHERE co_documenti.id='.prepare($array['iddocumento']));
        $numero = (!empty($rs[0]['numero_esterno'])) ? $rs[0]['numero_esterno'] : $rs[0]['numero'];
        $tipo = strtolower($rs[0]['tipo']);
    }

    // Automezzo
    elseif (!empty($array['idautomezzo'])) {
        $rs = $dbo->fetchArray("SELECT CONCAT_WS( ' - ', nome, targa ) AS nome FROM dt_automezzi WHERE id=".prepare($array['idautomezzo']));
        $nome = $rs[0]['nome'];

        if (empty($array['idintervento'])) {
            $movimento = ($qta < 0) ? _("Carico dal magazzino sull'automezzo _NAME_") : _("Scarico nel magazzino dall'automezzo _NAME_");
        }
        // Automezzo legato a intervento
        else {
            $movimento = ($qta > 0) ? _("Carico sull'automezzo _NAME_") : _("Scarico dall'automezzo _NAME_");

            $qta = -$qta;
        }

        $new = ($qta < 0 ? '+' : '').-$qta;

        $dbo->query('UPDATE mg_articoli_automezzi SET qta = qta + '.$new.' WHERE idarticolo = '.prepare($idarticolo).' AND idautomezzo = '.prepare($array['idautomezzo']));
    }

    // Intervento
    elseif (!empty($array['idintervento'])) {
        $movimento = ($qta > 0) ? _('Ripristino articolo da intervento _NUM_') : _('Scarico magazzino per intervento _NUM_');
        $numero = $array['idintervento'];
    }

    // Manuale
    else {
        $movimento = !empty($descrizone) ? $descrizone : '';
        $descrizone = '';

        if (empty($movimento)) {
            $movimento = ($qta > 0) ? _('Carico magazzino') : _('Scarico magazzino');
        }
    }

    // Descrizione di default
    if (empty($movimento)) {
        $carico = (!empty($rs[0]['dir']) && $rs[0]['dir'] == 'entrata') ? _('Ripristino articolo da _TYPE_ _NUM_') : _('Carico magazzino da _TYPE_ numero _NUM_');
        $scarico = (!empty($rs[0]['dir']) && $rs[0]['dir'] == 'uscita') ? _('Rimozione articolo da _TYPE_ _NUM_') : _('Scarico magazzino per _TYPE_ numero _NUM_');

        $movimento = ($qta > 0) ? $carico : $scarico;
    }

    // Completamento della descrizione
    $movimento .= $descrizone;
    $movimento = str_replace(['_NAME_', '_TYPE_', '_NUM_'], [$nome, $tipo, $numero], $movimento);

    $new = ($qta > 0 ? '+' : '').$qta;

    // Movimentazione effettiva
    if (empty($array['idintervento']) || empty($array['idautomezzo'])) {
        $dbo->query('UPDATE mg_articoli SET qta = qta + '.$new.' WHERE id = '.prepare($idarticolo));
    }

    // Registrazione della movimentazione
    $dbo->insert('mg_movimenti', array_merge($array, [
        'idarticolo' => $idarticolo,
        'descrizione_articolo' => '#(SELECT descrizione FROM mg_articoli WHERE id='.prepare($idarticolo).')#',
        'qta' => $qta,
        'movimento' => $movimento,
        'data' => '#NOW()#',
    ]));

    return true;
}
