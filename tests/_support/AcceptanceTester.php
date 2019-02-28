<?php

/**
 * Inherited Methods.
 *
 * @method void                    wantToTest($text)
 * @method void                    wantTo($text)
 * @method void                    execute($callable)
 * @method void                    expectTo($prediction)
 * @method void                    expect($prediction)
 * @method void                    amGoingTo($argumentation)
 * @method void                    am($role)
 * @method void                    lookForwardTo($achieveValue)
 * @method void                    comment($description)
 * @method \Codeception\Lib\Friend haveFriend($name, $actorClass = NULL)
 *
 * @SuppressWarnings(PHPMD)
 */
class AcceptanceTester extends \Codeception\Actor
{
    use _generated\AcceptanceTesterActions;

    /*
     * Define custom actions here
     */

    /**
     * Clicca sul pulsante e attende la conclusione del caricamento.
     *
     * @param $link
     * @param $context
     */
    public function clickAndWait($link, $context = null)
    {
        $t = $this;

        $t->click($link, $context);

        $t->waitForElementNotVisible('#main_loading');
        $t->waitForElementNotVisible('#mini-loader');
    }

    /**
     * Clicca sul pulsante e attende la conclusione del caricamento del modal.
     *
     * @param $link
     * @param $context
     */
    public function clickAndWaitModal($link, $context = null)
    {
        $t = $this;

        $t->clickAndWait($link, $context);

        $t->waitForElementVisible('.modal');
        $t->wait(1);
    }

    /**
     * Clicca sul pulsante dentro il modal.
     *
     * @param $link
     */
    public function clickModalButton($link)
    {
        $t = $this;

        $t->clickAndWait($link, '.modal-content');
    }

    /**
     * Clicca sul pulsante e attende la conclusione del caricamento del modal SWAL.
     *
     * @param $link
     * @param $context
     */
    public function clickAndWaitSwal($link, $context = null)
    {
        $t = $this;

        $t->clickAndWait($link, $context);

        $t->waitForElementVisible('.swal2-modal');
    }

    /**
     * Clicca sul pulsante dentro il modal SWAL.
     *
     * @param $link
     */
    public function clickSwalButton($link)
    {
        $t = $this;

        $t->clickAndWait($link, '.swal2-buttonswrapper');
    }

    public function navigateTo($link)
    {
        $this->clickAndWait($link, '.sidebar');
    }

    /**
     * Effettua il login dalla pagina principale.
     *
     * @param string $username
     * @param string $password
     */
    public function login($username, $password)
    {
        $t = $this;

        // Operazioni di login
        $t->amOnPage('/');

        $t->fillField('username', $username);
        $t->fillField('password', $password);

        $t->clickAndWait('Accedi');

        // Controlla il completamento del login
        $t->see($username, '.user-panel');

        // Rimozione barra di debug
        $t->executeJS('$(".phpdebugbar-close-btn").click()');
    }
}
