Feature: Manage a bank account 1
  In order to manage my account
  As a logged user
  I need to be able to take money on my account

  Scenario: Open a new bank account
    Given I am a new customer
    Then My sold is "0" euros on my account

  Scenario: Get money from my bank account
    Given I am a customer
    And I have "50" euros on my account
    When I take "10" euros
    Then My sold is "40" euros on my account


  Scenario Outline: Add money on my account
    Given I am a customer
    And I have "<initialAmount>" euros on my account
    When I take "<amount>" euros
    Then My sold is "<finalAmount>" euros on my account

  Examples:
  | initialAmount | amount    | finalAmount   |
  | 50            | 0         | 50            |
  | 50            | 10        | 40            |
  | 50            | 20        | 30            |
  | 50            | 30        | 20            |

  Scenario: Overdrafts are not allowed
    Given I am a customer
    And I have "10" euros on my account
    When I take "20" euros
    Then I have a error message "Overdrafts are not allowed"

