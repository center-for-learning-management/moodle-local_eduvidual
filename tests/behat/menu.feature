@local @local_eduvidual
Feature: Resource catalogue menu item is always available
  In order to make OER available to everyone
  As a guest or not logged user
  I want to see the menu item resource catalogue

  Background:
    Given the following config values are set as admin:
      | autologinguests | Yes |

  Scenario: Menu item resource catalogue exists
    Given I am on the homepage logged in as guest
    Then I should see "Resource catalogue" in the ".moremenu" "css_element"
    And I should not see "Create course" in the ".moremenu" "css_element"
    And I should not see "My schools" in the ".moremenu" "css_element"