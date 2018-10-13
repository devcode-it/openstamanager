<?php

class FattureCest
{
    /**
     * Crea una nuova anagrafica.
     *
     * @param AcceptanceTester $t
     */
    protected function addFattura(AcceptanceTester $t, $entrata, $tipo, $anagrafica)
    {
        // Effettua l'accesso con le credenziali fornite
        $t->login('admin', 'admin');

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

    protected function addRow(AcceptanceTester $t, $descrizione, $qta, $prezzo, $sconto = 0, $tipo_sconto = 'UNT', $id_iva = null, $id_rivalsa_inps = null, $id_ritenuta_acconto = null)
    {
        // Apre il modal
        $t->clickAndWaitModal('Riga', '#tab_0');

        // Completa le informazioni
        $t->fillField('Descrizione', $descrizione);
        $t->fillField('Q.tà', $qta);
        $t->fillField('Costo unitario', $prezzo);

        if (!empty($sconto)) {
            $t->fillField('Sconto unitario', $sconto);

            if (in_array($tipo_sconto, ['PRC', 'UNT'])) {
                $t->select2ajax('#tipo_sconto', $tipo_sconto);
            }
        }

        if ($id_iva) {
            $t->select2('#idiva', $id_iva);
        }

        if ($id_rivalsa_inps) {
            $t->select2('#id_rivalsa_inps', $id_rivalsa_inps);
        }
        if ($id_ritenuta_acconto) {
            $t->select2('#id_ritenuta_acconto', $id_ritenuta_acconto);
        }

        // Effettua il submit
        $t->clickAndWait('Aggiungi', '.modal');

        // Controlla il salvataggio finale
        $t->see('Riga aggiunta');
    }

    protected function getFinalValue($type)
    {
        return "//div[@class='panel-heading' and contains(string(), 'Righe')]/parent::*//table//tr[contains(string(), '".strtoupper($type)."')]//td[2]";
    }

    protected function checkImporti(AcceptanceTester $t)
    {
        $this->addRow($t, 'Riga 1', 1, 34);
        $this->addRow($t, 'Riga 2', 1, 17.44);
        $this->addRow($t, 'Riga 3', 48, 0.52);
        $this->addRow($t, 'Riga 4', 66, 0.44);
        $this->addRow($t, 'Riga 5', 1, 104.90);
        $this->addRow($t, 'Riga 6', 1, 2);

        $t->see("212,34", $this->getFinalValue('Imponibile'));
        $t->see("46,71", $this->getFinalValue('IVA'));
        $t->see("259,05", $this->getFinalValue('Totale'));

        $this->addRow($t, 'Riga 7 con sconto in euro', 15, 12, 2);
        $this->addRow($t, 'Riga 8 con sconto percentuale', 15, 10, 20, 'PRC');

        $t->see("542,34", $this->getFinalValue('Imponibile'));
        $t->see("60,00", $this->getFinalValue('Sconto'));
        $t->see("482,34", $this->getFinalValue('Imponibile scontato'));
        $t->see("106,11", $this->getFinalValue('IVA'));
        $t->see("588,45", $this->getFinalValue('Totale'));
    }

    /**
    * Crea una nuova fattura di vendita.
    *
    * @param AcceptanceTester $t
    */
    public function testFatturaDiVendita(AcceptanceTester $t)
    {
        $this->addFattura($t, true, 2, 2);

        $this->checkImporti($t);
    }

    /**
    * Crea una nuova fattura di acquisto.
    *
    * @param AcceptanceTester $t
    */
    public function testFatturaDiAcquisto(AcceptanceTester $t)
    {
        $this->addFattura($t, false, 1, 3);

        $this->checkImporti($t);
    }
}
