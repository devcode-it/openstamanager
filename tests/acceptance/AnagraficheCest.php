<?php

class AnagraficheCest
{
    /**
     * Crea una nuova anagrafica.
     *
     * @param AcceptanceTester $t
     */
    public function addAnag(AcceptanceTester $t, $name = 'ANAGRAFICA DI PROVA', $tipo = 1, $partita_iva = '')
    {
        // Effettua l'accesso con le credenziali fornite
        $t->login('admin', 'admin');

        // Seleziona il modulo da aprire
        $t->clickAndWait('Anagrafiche', '.sidebar');

        // Apre la schermata di nuovo elemento
        $t->clickAndWaitModal('.btn-primary', '#tabs');

        // Completa i campi per il nuovo elemento
        $t->fillField('Ragione sociale', $name);
        $t->select2('#id_tipo_anagrafica', $tipo);
        $t->click('.btn-box-tool');
        $t->waitForElementVisible('#piva', 3);
        $t->fillField('Partita IVA', $partita_iva);

        // Effettua il submit
        $t->clickAndWait('Aggiungi', '#add-form');

        // Controlla il salvataggio finale
        $t->see('Aggiunta nuova anagrafica');
    }

    /**
     * Crea una nuova anagrafica di tipo cliente e la elimina.
     *
     * @param AcceptanceTester $t
     */
    public function addAndDeleteAnag(AcceptanceTester $t)
    {
        $this->addAnag($t, 'ANAGRAFICA CLIENTE DI PROVA', 1, '05024030289');

        // Seleziona l'azione di eliminazione
        $t->clickAndWaitSwal('Elimina', '#tab_0');

        // Conferma l'eliminazione
        $t->clickSwalButton('Elimina');

        // Controlla eliminazione
        $t->see('Anagrafica eliminata!', '.alert-success');
    }
}
