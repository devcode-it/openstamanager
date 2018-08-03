<?php

class AnagraficheCest
{
    public function addWorks(AcceptanceTester $t)
    {
        $t->login('admin', 'admin');

        $t->clickAndWait('Anagrafiche', '.sidebar');

        $t->clickAndWaitModal('.btn-primary', '#tabs');

        $t->fillField('Ragione sociale', 'TEST');
        $t->select2('#idtipoanagrafica', '1');

        $t->clickAndWait('Aggiungi', '#add-form');

        $t->see('Dati anagrafici');
    }
}
