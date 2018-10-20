<?php

use \Helper\Common\RowHelper;

class FattureCest
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
     * Crea una nuova anagrafica.
     *
     * @param AcceptanceTester $t
     */
    protected function addFattura(AcceptanceTester $t, $entrata, $tipo, $anagrafica)
    {
        // Seleziona il modulo da aprire
        $t->expandSidebarLink($entrata == true ? 'Vendite' : 'Acquisti');
        $t->navigateTo($entrata == true ? 'Fatture di vendita' : 'Fatture di acquisto');

        // Apre la schermata di nuovo elemento
        $t->clickAndWaitModal('.btn-primary', '#tabs');

        // Completa i campi per il nuovo elemento
        $t->select2ajax('#idanagrafica_add', $anagrafica);
        $t->select2('#idtipodocumento', $tipo);

        // Effettua il submit
        $t->clickAndWait('Aggiungi', '#add-form');

        // Controlla il salvataggio finale
        $t->see('Aggiunta fattura numero');
    }

    /**
     * Crea una nuova anagrafica di tipo cliente e la elimina.
     *
     * @param AcceptanceTester $t
     */
    protected function addAndDeleteFattura(AcceptanceTester $t, $cliente = 2, $tipo)
    {
        $this->addAnag($t, $cliente, $tipo);

        // Seleziona l'azione di eliminazione
        $t->clickAndWaitSwal('Elimina', '#tab_0');

        // Conferma l'eliminazione
        $t->clickSwalButton('Elimina');

        // Controlla eliminazione
        $t->see('Anagrafica eliminata!', '.alert-success');
    }

    /**
    * Crea una nuova fattura di vendita.
    *
    * @param AcceptanceTester $t
    */
    public function testFatturaDiVendita(AcceptanceTester $t)
    {
        $this->addFattura($t, true, 2, 2);

        $this->rowHelper->testImporti($t);
    }

    /**
    * Crea una nuova fattura di acquisto.
    *
    * @param AcceptanceTester $t
    */
    public function testFatturaDiAcquisto(AcceptanceTester $t)
    {
        $this->addFattura($t, false, 1, 3);

        $this->rowHelper->testImporti($t);
    }
}
