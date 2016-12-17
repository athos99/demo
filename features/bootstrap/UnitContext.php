<?php


use Behat\Behat\Tester\Exception\PendingException;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use Behat\MinkExtension\Context\MinkContext;
use Behat\Behat\Context\SnippetAcceptingContext;


require_once __DIR__.'/../../vendor/autoload.php';
require_once __DIR__.'/../../app/Account.php';


/**
 * Defines application features from the specific context.
 */
class UnitContext extends MinkContext implements SnippetAcceptingContext
{


    private $_account;
    private $_lastException;

    /**
     * Initializes context.
     *
     * Every scenario gets its own context instance.
     * You can also pass arbitrary arguments to the
     * context constructor through behat.yml.
     */
    public function __construct()
    {
    }

    /**
     * @Given I am a new customer
     */
    public function iAmANewCustomer()
    {
        $this->_account = new Account;
    }


    /**
     * @Then My sold is :arg1 euros on my account
     */
    public function mySoldIsEurosOnMyAccount($balance)
    {

        PHPUnit_Framework_Assert::assertEquals($balance, $this->_account->getBalance());
    }

    /**
     * @Given I am a customer
     */
    public function iAmACustomer()
    {
        $this->_account = new Account;
    }

    /**
     * @Given I have :arg1 euros on my account
     */
    public function iHaveEurosOnMyAccount($amount)
    {
        $this->_account->setBalance($amount);
    }


    /**
     * @Then I have a error message :arg1
     */
    public function iHaveAErrorMessage($message)
    {
        PHPUnit_Framework_Assert::assertEquals($message, $this->_lastException->getMessage());
    }

    /**
     * @When I take :arg1 euros
     */
    public function iTakeEuros($amount)
    {
        try {
            $this->_account->takeMoney($amount);
        } catch (\Exception $e) {
            $this->_lastException = $e;
        }
    }

    /**
     * @Given I am logged in as :username
     */
    public function iAmLoggedInAs($username)
    {
        $this->visit('login.php');
        $this->fillField('My name', $username);
        $this->pressButton('Login');
    }

    /**
     * @Given I have :arg1 euro
     */
    public function iHaveEuro($balance)
    {
        $this->visit('/');
        $this->fillField('New balance', $balance);
        $this->pressButton('Reset');


    }
}
