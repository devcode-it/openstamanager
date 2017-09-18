<?php

include_once __DIR__.'/../../core.php';

$operazione = filter('op');

switch ($operazione) {
    case 'addreferente':
        $nome = filter('nome');
        $mansione = filter('mansione');
        $telefono = filter('telefono');
        $email = filter('email');
        $idsede = filter('idsede');

        if (isset($nome) && isset($idsede)) {
            $query = 'INSERT INTO `an_referenti` (`nome`, `mansione`, `telefono`, `email`, `idanagrafica`, `idsede`) VALUES ('.prepare($nome).', '.prepare($mansione).', '.prepare($telefono).', '.prepare($email).', '.prepare($id_record).', '.prepare($idsede).')';

            $dbo->query($query);
            $_SESSION['infos'][] = tr('Aggiunto nuovo referente!');
        }

        break;

    case 'updatereferente':
        $query = 'UPDATE `an_referenti` SET `nome`='.prepare($post['nome']).', `mansione`='.prepare($post['mansione']).', `telefono`='.prepare($post['telefono']).', `email`='.prepare($post['email']).', `idsede`='.prepare($post['idsede']).' WHERE `id`='.prepare($id_record);
        $dbo->query($query);

        $_SESSION['infos'][] = tr('Salvataggio completato!');

        break;

    case 'deletereferente':
        $dbo->query("DELETE FROM `an_referenti` WHERE `id`=".prepare($id_record));

        $_SESSION['infos'][] = tr('Referente eliminato!');

        break;
}
