@api @smokeTest @public_link_share-feature-required @files_sharing-app-required @issue-ocis-reva-172 @notToImplementOnOCIS
Feature: set timeouts of LOCKS on shares

  Background:
    Given these users have been created with default attributes and without skeleton files:
      | username |
      | Alice    |
      | Brian    |
    And user "Alice" has created folder "PARENT"
    And user "Alice" has created folder "PARENT/CHILD"
    And user "Alice" has uploaded file "filesForUpload/textfile.txt" to "PARENT/parent.txt"
    And user "Brian" has created folder "PARENT"
    And user "Brian" has created folder "PARENT/CHILD"
    And user "Brian" has uploaded file "filesForUpload/textfile.txt" to "PARENT/parent.txt"


  Scenario Outline: as owner set timeout on folder as receiver check it
    Given using <dav-path> DAV path
    And user "Alice" has shared folder "PARENT" with user "Brian"
    When user "Alice" locks folder "PARENT" using the WebDAV API setting the following properties
      | lockscope | shared    |
      | timeout   | <timeout> |
    Then the HTTP status code should be "200"
    And the lock property item "//d:timeout" of folder "PARENT (2)" in response to user "Brian" should match "<result>"
    And the lock property item "//d:timeout" of folder "PARENT (2)/CHILD" in response to user "Brian" should match "<result>"
    And the lock property item "//d:timeout" of folder "PARENT (2)/parent.txt" in response to user "Brian" should match "<result>"
    Examples:
      | dav-path | timeout         | result          |
      | old      | second-999      | /Second-\d{3}$/ |
      | old      | second-99999999 | /Second-\d{5}$/ |
      | old      | infinite        | /Second-\d{5}$/ |
      | old      | second--1       | /Second-\d{5}$/ |
      | old      | second-0        | /Second-\d{4}$/ |
      | new      | second-999      | /Second-\d{3}$/ |
      | new      | second-99999999 | /Second-\d{5}$/ |
      | new      | infinite        | /Second-\d{5}$/ |
      | new      | second--1       | /Second-\d{5}$/ |
      | new      | second-0        | /Second-\d{4}$/ |


  Scenario Outline: as share receiver set timeout on folder as owner check it
    Given using <dav-path> DAV path
    And user "Alice" has shared folder "PARENT" with user "Brian"
    When user "Brian" locks folder "PARENT (2)" using the WebDAV API setting the following properties
      | lockscope | shared    |
      | timeout   | <timeout> |
    Then the HTTP status code should be "200"
    And the lock property item "//d:timeout" of folder "PARENT" in response to user "Alice" should match "<result>"
    And the lock property item "//d:timeout" of folder "PARENT/CHILD" in response to user "Alice" should match "<result>"
    And the lock property item "//d:timeout" of folder "PARENT/parent.txt" in response to user "Alice" should match "<result>"
    Examples:
      | dav-path | timeout         | result          |
      | old      | second-999      | /Second-\d{3}$/ |
      | old      | second-99999999 | /Second-\d{5}$/ |
      | old      | infinite        | /Second-\d{5}$/ |
      | old      | second--1       | /Second-\d{5}$/ |
      | old      | second-0        | /Second-\d{4}$/ |
      | new      | second-999      | /Second-\d{3}$/ |
      | new      | second-99999999 | /Second-\d{5}$/ |
      | new      | infinite        | /Second-\d{5}$/ |
      | new      | second--1       | /Second-\d{5}$/ |
      | new      | second-0        | /Second-\d{4}$/ |
