<?php

namespace Helper;

// here you can define custom actions
// all public methods declared in helper class will be available in $t

class Acceptance extends \Codeception\Module
{
    /**
     * Effettua il login dalla pagina principale.
     *
     * @param string $username
     * @param string $password
     */
    public function login($username, $password)
    {
        $t = $this->getAcceptanceModule();

        $t->amOnPage('/');

        $t->fillField('username', $username);
        $t->fillField('password', $password);

        $this->clickAndWait('Accedi');
    }

    /**
     * Clicca sul pulsante e attende la conclusione del caricamento.
     *
     * @param $link
     * @param $context
     */
    public function clickAndWait($link, $context = null)
    {
        $t = $this->getAcceptanceModule();

        $t->click($link, $context);

        $t->waitForElementNotVisible('#main_loading');
    }

    /**
     * Clicca sul pulsante e attende la conclusione del caricamento del modal.
     *
     * @param $link
     * @param $context
     */
    public function clickAndWaitModal($link, $context = null)
    {
        $t = $this->getAcceptanceModule();

        $this->clickAndWait($link, $context);

        $t->waitForElementVisible('.modal');
    }

    /**
     * Imposta il valore di un select gestito dal framework Select2.
     *
     * @param $selector
     * @param $option
     * @param int $timeout seconds. Default to 1
     */
    public function select2($selector, $option, $timeout = 5)
    {
        $select2 = $this->getModule('\Helper\Select2');

        $select2->openSelect2($selector);
        $select2->selectOptionForSelect2($selector, $option, $timeout);
        $select2->closeSelect2($selector);
    }

    protected function getAcceptanceModule()
    {
        if (!$this->hasModule('WebDriver')) {
            throw new \Exception('You must enable the WebDriver module', 1);
        }

        return $this->getModule('WebDriver');
    }
}
