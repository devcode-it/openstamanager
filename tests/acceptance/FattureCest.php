<?php

use Helper\Common\RowHelper;

class FattureCest
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
     * Crea una nuova fattura di vendita.
     */
    public function testFatturaDiVendita(AcceptanceTester $t)
    {
        $this->addFattura($t, true, 2, 2);

        $this->rowHelper->testImporti($t);
    }

    /**
     * Crea una nuova fattura di acquisto.
     */
    public function testFatturaDiAcquisto(AcceptanceTester $t)
    {
        $this->addFattura($t, false, 1, 4);

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
     * Crea una nuova fattura.
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
        $t->see('Aggiunta fattura');
    }

    /**
     * Crea una nuova fattura e la elimina.
     */
    protected function addAndDeleteFattura(AcceptanceTester $t, $cliente, $tipo)
    {
        $this->addAnag($t, $cliente, $tipo);

        // Seleziona l'azione di eliminazione
        $t->clickAndWaitSwal('Elimina', '#tab_0');

        // Conferma l'eliminazione
        $t->clickSwalButton('Elimina');

        // Controlla eliminazione
        $t->see('Fattura eliminata!', '.alert-success');
    }
}
