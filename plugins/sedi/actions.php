<?php

include_once __DIR__.'/../../core.php';

$operazione = filter('op');

switch ($operazione) {
    case 'addsede':
        $array = [
            'idanagrafica' => $id_parent,
            'nomesede' => $post['nomesede'],
            'indirizzo' => $post['indirizzo'],
            'indirizzo2' => $post['indirizzo2'],
            'citta' => $post['citta'],
            'cap' => $post['cap'],
            'km' => $post['km'],
            'cellulare' => $post['cellulare'],
            'telefono' => $post['telefono'],
            'email' => $post['email'],
            'idzona' => $post['idzona'],
        ];

        $dbo->insert('an_sedi', $array);

        $_SESSION['infos'][] = _('Aggiunta una nuova sede!');

        break;

    case 'updatesede':
        $array = [
            'nomesede' => $post['nomesede'],
            'indirizzo' => $post['indirizzo'],
            'indirizzo2' => $post['indirizzo2'],
            'piva' => $post['piva'],
            'codice_fiscale' => $post['codice_fiscale'],
            'citta' => $post['citta'],
            'cap' => $post['cap'],
            'provincia' => $post['provincia'],
            'km' => $post['km'],
            'cellulare' => $post['cellulare'],
            'telefono' => $post['telefono'],
            'email' => $post['email'],
            'fax' => $post['fax'],
            'id_nazione' => !empty($post['id_nazione']) ? $post['id_nazione'] : null,
            'idzona' => $post['idzona'],
        ];

        $dbo->update('an_sedi', $array, ['id' => $post['id']]);

        $_SESSION['infos'][] = _('Salvataggio completato!');

        break;

    case 'deletesede':
        $idsede = filter('id');
        $dbo->query("DELETE FROM `an_sedi` WHERE `id`=".prepare($idsede));

        $_SESSION['infos'][] = _('Sede eliminata!');

        break;
}
