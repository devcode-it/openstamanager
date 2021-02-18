<?php

use Helper\Common\RowHelper;

class ContrattiCest
{
    /**
     * @var Helper\SignUp
     */
    protected $rowHelper;

    public function _before(AcceptanceTester $t)
    {
        // Effettua l'accesso con le credenziali fornite
        $t->login('admin', 'admin');
    }

    /**
     * Crea un nuovo contratto.
     */
    public function testContratto(AcceptanceTester $t)
    {
        $this->addContratto($t, 'Contratto di test', 2);

        $this->rowHelper->testImporti($t);

        //$t->click('Stampa');
    }

    protected function _inject(RowHelper $rowHelper)
    {
        $this->rowHelper = $rowHelper;
    }

    /**
     * Crea un nuovo contratto.
     */
    protected function addContratto(AcceptanceTester $t, $name, $anagrafica)
    {
        // Seleziona il modulo da aprire
        $t->expandSidebarLink('Vendite');
        $t->navigateTo('Contratti');

        // Apre la schermata di nuovo elemento
        $t->clickAndWaitModal('.btn-primary', '#tabs');

        // Completa i campi per il nuovo elemento
        $t->fillField('Nome', $name);
        $t->select2ajax('#idanagrafica', $anagrafica);

        // Effettua il submit
        $t->clickAndWait('Aggiungi', '#add-form');

        // Controlla il salvataggio finale
        $t->see('Aggiunto contratto');
    }

    /**
     * Crea una nuova anagrafica di tipo cliente e la elimina.
     */
    protected function addAndDeleteContratto(AcceptanceTester $t, $name, $anagrafica = 2)
    {
        $this->addAnag($t, $name, $anagrafica);

        // Seleziona l'azione di eliminazione
        $t->clickAndWaitSwal('Elimina', '#tab_0');

        // Conferma l'eliminazione
        $t->clickSwalButton('Elimina');

        // Controlla eliminazione
        $t->see('Contratto eliminato!', '.alert-success');
    }
}
