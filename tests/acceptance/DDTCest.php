<?php

use Helper\Common\RowHelper;

class DDTCest
{
    /**
     * @var Helper\SignUp
     */
    protected $rowHelper;

    protected function _inject(RowHelper $rowHelper)
    {
        $this->rowHelper = $rowHelper;
    }

    public function _before(\AcceptanceTester $t)
    {
        // Effettua l'accesso con le credenziali fornite
        $t->login('admin', 'admin');
    }

    /**
     * Crea un nuovo ddt.
     *
     * @param AcceptanceTester $t
     */
    protected function addDdt(AcceptanceTester $t, $entrata, $anagrafica, $tipo)
    {
        // Seleziona il modulo da aprire
        $t->expandSidebarLink('Magazzino');
        $t->navigateTo($entrata == true ? 'Ddt di vendita' : 'Ddt di acquisto');

        // Apre la schermata di nuovo elemento
        $t->clickAndWaitModal('.btn-primary', '#tabs');

        // Completa i campi per il nuovo elemento
        $t->select2ajax('#idanagrafica_add', $anagrafica);
        $t->select2('#idtipoddt', $tipo);

        // Effettua il submit
        $t->clickAndWait('Aggiungi', '#add-form');

        // Controlla il salvataggio finale
        $t->see('Aggiunto ddt');
    }

    /**
     * Crea una nuova anagrafica di tipo cliente e la elimina.
     *
     * @param AcceptanceTester $t
     */
    protected function addAndDeleteDdt(AcceptanceTester $t, $entrata, $anagrafica, $tipo)
    {
        $this->addAnag($t, $entrata, $anagrafica, $tipo);

        // Seleziona l'azione di eliminazione
        $t->clickAndWaitSwal('Elimina', '#tab_0');

        // Conferma l'eliminazione
        $t->clickSwalButton('Elimina');

        // Controlla eliminazione
        $t->see('Ddt eliminato!', '.alert-success');
    }

    /**
     * Crea un nuovo ddt.
     *
     * @param AcceptanceTester $t
     */
    public function testDdtDiVendita(AcceptanceTester $t)
    {
        $this->addDdt($t, true, 2, 2);

        $this->rowHelper->testImporti($t);

        //$t->click('Stampa');
    }

    /**
     * Crea un nuovo ddt.
     *
     * @param AcceptanceTester $t
     */
    public function testDdtDiAcquisto(AcceptanceTester $t)
    {
        $this->addDdt($t, false, 3, 1);

        $this->rowHelper->testImporti($t);
    }
}
