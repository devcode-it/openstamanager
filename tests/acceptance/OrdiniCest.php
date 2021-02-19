<?php

use Helper\Common\RowHelper;

class OrdiniCest
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
     * Crea un nuovo ordine.
     */
    public function testOrdineCliente(AcceptanceTester $t)
    {
        $this->addOrdine($t, true, 2);

        $this->rowHelper->testImporti($t);

        //$t->click('Stampa');
    }

    /**
     * Crea un nuovo ordine.
     */
    public function testOrdineFornitore(AcceptanceTester $t)
    {
        $this->addOrdine($t, false, 4);

        // Fix pagamento vuoto
        $t->select2('#idpagamento', 109);
        $t->clickAndWait('Salva');

        $this->rowHelper->testImporti($t, 'uscita');
    }

    protected function _inject(RowHelper $rowHelper)
    {
        $this->rowHelper = $rowHelper;
    }

    /**
     * Crea un nuovo ordine.
     */
    protected function addOrdine(AcceptanceTester $t, $entrata, $anagrafica)
    {
        // Seleziona il modulo da aprire
        $t->expandSidebarLink($entrata == true ? 'Vendite' : 'Acquisti');
        $t->navigateTo($entrata == true ? 'Ordini cliente' : 'Ordini fornitore');

        // Apre la schermata di nuovo elemento
        $t->clickAndWaitModal('.btn-primary', '#tabs');

        // Completa i campi per il nuovo elemento
        $t->select2ajax('#idanagrafica', $anagrafica);

        // Effettua il submit
        $t->clickAndWait('Aggiungi', '#add-form');

        // Controlla il salvataggio finale
        $t->see('Aggiunto ordine');
    }

    /**
     * Crea una nuova anagrafica di tipo cliente e la elimina.
     */
    protected function addAndDeleteOrdine(AcceptanceTester $t, $entrata, $anagrafica = 2)
    {
        $this->addAnag($t, $entrata, $anagrafica);

        // Seleziona l'azione di eliminazione
        $t->clickAndWaitSwal('Elimina', '#tab_0');

        // Conferma l'eliminazione
        $t->clickSwalButton('Elimina');

        // Controlla eliminazione
        $t->see('Ordine eliminato!', '.alert-success');
    }
}
