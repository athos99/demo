<?php


use Behat\Behat\Tester\Exception\PendingException;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use Behat\MinkExtension\Context\MinkContext;
use Behat\Behat\Context\SnippetAcceptingContext;


require_once __DIR__.'/../../vendor/autoload.php';



/**
 * Defines application features from the specific context.
 */
class WebContext extends MinkContext implements SnippetAcceptingContext
{



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
