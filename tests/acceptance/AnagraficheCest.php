<?php

class AnagraficheCest
{
    /**
     * Crea una nuova anagrafica di tipo Cliente.
     *
     * @param AcceptanceTester $t
     */
    public function addWorks(AcceptanceTester $t)
    {
        // Effettua l'accesso con le credenziali fornite
        $t->login('admin', 'admin');

        // Seleziona il modulo da aprire
        $t->clickAndWait('Anagrafiche', '.sidebar');

        // Apre la schermata di nuovo elemento
        $t->clickAndWaitModal('.btn-primary', '#tabs');

        // Completa i campi per il nuovo elemento
        $t->fillField('Ragione sociale', 'TEST');
        $t->select2('#idtipoanagrafica', '1');

        // Effettua il submit
        $t->clickAndWait('Aggiungi', '#add-form');

        // Controlla il salvataggio finale
        $t->see('Dati anagrafici');
    }
}
