<?php

use Helper\Common\RowHelper;

class PreventiviCest
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
     * Crea un nuovo preventivo.
     */
    public function testPreventivo(AcceptanceTester $t)
    {
        $this->addPreventivo($t, 'Preventivo di test', 2);

        $this->rowHelper->testImporti($t);

        //$t->click('Stampa');
    }

    protected function _inject(RowHelper $rowHelper)
    {
        $this->rowHelper = $rowHelper;
    }

    /**
     * Crea un nuovo preventivo.
     */
    protected function addPreventivo(AcceptanceTester $t, $name, $anagrafica, $tipo = 'GEN')
    {
        // Seleziona il modulo da aprire
        $t->expandSidebarLink('Vendite');
        $t->navigateTo('Preventivi');

        // Apre la schermata di nuovo elemento
        $t->clickAndWaitModal('.btn-primary', '#tabs');

        // Completa i campi per il nuovo elemento
        $t->fillField('Nome preventivo', $name);
        $t->select2ajax('#idanagrafica', $anagrafica);
        $t->select2ajax('#idtipointervento', $tipo);

        // Effettua il submit
        $t->clickAndWait('Aggiungi', '#add-form');

        // Controlla il salvataggio finale
        $t->see('Aggiunto preventivo');
    }

    /**
     * Crea una nuova anagrafica di tipo cliente e la elimina.
     */
    protected function addAndDeletePreventivo(AcceptanceTester $t, $name, $anagrafica = 2, $tipo = 'GEN')
    {
        $this->addAnag($t, $name, $anagrafica, $tipo);

        // Seleziona l'azione di eliminazione
        $t->clickAndWaitSwal('Elimina', '#tab_0');

        // Conferma l'eliminazione
        $t->clickSwalButton('Elimina');

        // Controlla eliminazione
        $t->see('Preventivo eliminato!', '.alert-success');
    }
}
