<?php


class LoginCest
{
    public function _before(AcceptanceTester $I)
    {
    }

    public function _after(AcceptanceTester $I)
    {
    }

    public function frontpageWorks(AcceptanceTester $I)
    {
        $I->amOnPage('/');

        $I->fillField('username', 'admin');
        $I->fillField('password', 'admin');

        $I->click('Accedi');

        $I->see('OpenSTAManager');
    }
}
