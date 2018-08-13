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

        if ($t->loadSessionSnapshot('login')) {
            return;
        }

        // Operazioni di login
        $t->amOnPage('/');

        $t->fillField('username', $username);
        $t->fillField('password', $password);

        $this->clickAndWait('Accedi');

        // Controlla il completamento del login
        $t->see($username, '.user-panel');

        $t->saveSessionSnapshot('login');

        // Rimozione barra di debug
        $t->executeJS('$(".phpdebugbar-close-btn").click()');
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
     * Clicca sul pulsante dentro il modal.
     *
     * @param $link
     */
    public function clickModalButton($link)
    {
        $t = $this->getAcceptanceModule();

        $this->clickAndWait($link, '.modal-content');
    }

    /**
     * Clicca sul pulsante e attende la conclusione del caricamento del modal SWAL.
     *
     * @param $link
     * @param $context
     */
    public function clickAndWaitSwal($link, $context = null)
    {
        $t = $this->getAcceptanceModule();

        $this->clickAndWait($link, $context);

        $t->waitForElementVisible('.swal2-modal');
    }

    /**
     * Clicca sul pulsante dentro il modal SWAL.
     *
     * @param $link
     */
    public function clickSwalButton($link)
    {
        $t = $this->getAcceptanceModule();

        $this->clickAndWait($link, '.swal2-buttonswrapper');
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
