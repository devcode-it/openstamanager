<?php

class BackupCest
{
    public function _before(AcceptanceTester $t)
    {
        // Effettua l'accesso con le credenziali fornite
        $t->login('admin', 'admin');
    }

    /**
     * Crea un nuovo backup.
     */
    protected function createBackup(AcceptanceTester $t)
    {
        // Seleziona il modulo da aprire
        $t->expandSidebarLink('Strumenti');
        $t->navigateTo('Backup');

        $t->clickAndWaitSwal('Crea backup');

        // Conferma la creazione
        $t->clickSwalButton('Crea');

        // Controlla il salvataggio finale
        $t->see('Nuovo backup creato correttamente!');
    }

    /**
     * Ripristina un backup specifico.
     */
    protected function restoreBackup(AcceptanceTester $t, $name = null)
    {
        // Seleziona il modulo da aprire
        $t->expandSidebarLink('Strumenti');
        $t->navigateTo('Backup');
    }

    /**
     * Crea una nuova anagrafica di tipo Cliente.
     */
    protected function testBackup(AcceptanceTester $t)
    {
        $name = $this->createBackup($t);
    }
}
