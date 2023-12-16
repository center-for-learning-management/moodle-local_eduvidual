@local @local_eduvidual @javascript
Feature: Resource catalogue menu item is always available
  In order to make OER available to everyone
  As a guest or not logged user
  I want to see the menu item resource catalogue

  Background:
    Given the following config values are set as admin:
      | autologinguests | Yes |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
      | student1 | Student   | 1        | student1@example.com |
      | manager1 | Manager   | 1        | manager1@example.com |
      | traverst | Thomas    | Travers  | traverst@example.com |

  Scenario: Non-logged user is presented with resource catalogue menu item
    When I am on site homepage
    Then I should see "Resource catalogue" "link"
    But I should not see "Create course" "link"
    But I should not see "My schools" "link"

  Scenario: Guest user is presented with resource catalogue menu item
    Given I am on site homepage
    And I press "Resource catalogue" "link"
    Then I should see "You are currently using guest access"
    And I should see "Resource catalogue" "link"
    But I should not see "Create course" "link"
    But I should not see "My schools" "link"

  Scenario: Logged in teacher is presented with resource catalogue menu item
    Given I am on site homepage
    And I log in as "teacher1"
    Then I should see "Resource catalogue" "link"
    And I should see "Create course" "link"
    And I should see "My schools" "link"

  Scenario: Logged in manager is presented with resource catalogue menu item
    Given I am on site homepage
    And I log in as "manager1"
    Then I should see "Resource catalogue" "link"
    And I should see "Create course"  "link"
    And I should see "My schools" "link"

  Scenario: Logged in student is presented with resource catalogue menu item
    Given I am on site homepage
    And I log in as "student"
    Then I should see "Resource catalogue" "link"
    And I should see "My schools" "link"
    But I should not see "Create course" "link"
    