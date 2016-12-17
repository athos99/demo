Feature: Manage a bank account
  In order to manage my account
  As a logged in user
  I need to be able to add or take money on my account

  Background:
    And I am logged in as "jeanfrancois"
    And I have "50" euro
    And I am on "/"

  Scenario: Check my bank account
    Then I should see "You have 50 euro on your account"

