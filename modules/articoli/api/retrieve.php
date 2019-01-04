<?php

switch ($resource) {
    case 'articoli':
        $query = 'SELECT *, (SELECT nome FROM mg_categorie WHERE id = mg_articoli.id_categoria) AS categoria, (SELECT nome FROM mg_categorie WHERE id = mg_articoli.id_sottocategoria) AS sottocategoria FROM mg_articoli WHERE attivo = 1';

        break;
}

return [
    'articoli',
];
