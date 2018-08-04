<?php

class AnagraficheCest
{
    /**
     * Crea una nuova anagrafica di tipo Cliente.
     *
     * @param AcceptanceTester $t
     */
    public function addWorks(AcceptanceTester $t, $name = 'TEST', $tipo = 1)
    {
        // Effettua l'accesso con le credenziali fornite
        $t->login('admin', 'admin');

        // Seleziona il modulo da aprire
        $t->clickAndWait('Anagrafiche', '.sidebar');

        // Apre la schermata di nuovo elemento
        $t->clickAndWaitModal('.btn-primary', '#tabs');

        // Completa i campi per il nuovo elemento
        $t->fillField('Ragione sociale', $name);
        $t->select2('#idtipoanagrafica', $tipo);

        // Effettua il submit
        $t->clickAndWait('Aggiungi', '#add-form');

        // Controlla il salvataggio finale
        $t->see('Dati anagrafici');
    }

    /**
     * Crea una nuova anagrafica di tipo Cliente.
     *
     * @param AcceptanceTester $t
     */
    public function addAndDeleteWorks(AcceptanceTester $t)
    {
        $this->addWorks($t, 'TEST DELETE');

        // Seleziona l'azione di eliminazione
        $t->clickAndWaitSwal('Elimina', '#tab_0');

        // Conferma l'eliminazione
        $t->clickSwalButton('Elimina');

        $t->see('Anagrafica eliminata!');
    }
}
