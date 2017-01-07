<?php

namespace Dorgflow\Service;

/**
 * Handles user input.
 */
class UserInput {

  protected $issueNumber;

  public function getIssueNumber() {
    if (!isset($this->issueNumber)) {
      global $argv;

      if (!empty($argv[1])) {
        if (is_numeric($argv[1])) {
          $this->issueNumber = $argv[1];
        }
        else {
          // If the param is a URL, get the node ID from the end of it.
          // Allow an #anchor at the end of the URL so users can copy and paste it
          // when it has a #new or #ID link.
          $matches = [];
          if (preg_match("@www\.drupal\.org/node/(?P<number>\d+)(#.*)?$@", $argv[1], $matches)) {
            $this->issueNumber = $matches['number'];
          }
        }
      }

      // If nothing worked, set it to FALSE so we don't repeat the work here
      // another time.
      if (!isset($this->issueNumber)) {
        $this->issueNumber = FALSE;
      }
    }

    return $this->issueNumber;
  }

}
