<?php

namespace Helper;

// here you can define custom actions
// all public methods declared in helper class will be available in $t

class Acceptance extends \Codeception\Module
{
    /**
     * Imposta il valore di un select gestito dal framework Select2.
     *
     * @param $selector
     * @param $option
     * @param int $timeout seconds. Default to 1
     */
    public function select2($selector, $option, $timeout = null)
    {
        $select2 = $this->getModule('\Helper\Select2');

        $select2->openSelect2($selector);
        $select2->selectOptionForSelect2($selector, $option, $timeout);
        $select2->closeSelect2($selector);
    }

    /**
     * Imposta il valore di un select gestito dal framework Select2.
     *
     * @param $selector
     * @param $option
     * @param int $timeout seconds. Default to 1
     */
    public function select2ajax($selector, $option, $timeout = null)
    {
        $select2 = $this->getModule('\Helper\Select2Ajax');
        $t = $this->getAcceptanceModule();

        $select2->openSelect2($selector);
        $t->wait(1);
        $select2->selectByPosition($selector, $option, $timeout);
        $select2->closeSelect2($selector);
    }

    public function seePageHasElement($element)
    {
        try {
            $this->getAcceptanceModule()->seeElement($element);
        } catch (\PHPUnit_Framework_AssertionFailedError $f) {
            return false;
        }

        return true;
    }

    public function expandSidebarLink($link)
    {
        $t = $this->getAcceptanceModule();

        if (!$this->seePageHasElement("descendant-or-self::*[@class and contains(concat(' ', normalize-space(@class), ' '), ' sidebar ')]/descendant-or-self::*/li[contains(., '".$link."') and @class and contains(concat(' ', normalize-space(@class), ' '), ' menu-open ')]")) {
            $t->click($link, '.sidebar');
            $t->wait(1);
        }
    }

    protected function getAcceptanceModule()
    {
        if (!$this->hasModule('WebDriver')) {
            throw new \Exception('You must enable the WebDriver module', 1);
        }

        return $this->getModule('WebDriver');
    }
}
