<?php

namespace Dorgflow\Service;

/**
 * Provides log data from git.
 */
class GitLog {

  protected $feature_branch_log;

  function __construct($waypoint_manager_branches) {
    $this->waypoint_manager_branches = $waypoint_manager_branches;
  }

  /**
   * Get the log data of the feature branch from the branch point with master.
   *
   * @return
   *  An array keyed by SHA, whose items are arrays with 'sha' and 'message'.
   *  The items are arranged in progressing order, that is, older commits first.
   */
  public function getFeatureBranchLog() {
    if (!isset($this->feature_branch_log)) {
      $master_branch_name = $this->waypoint_manager_branches->getMasterBranch()->getBranchName();
      // TODO! Complain if $feature_branch_name doesn't exist yet!
      $feature_branch_name = $this->waypoint_manager_branches->getFeatureBranch()->getBranchName();

      $log = $this->getLog($master_branch_name, $feature_branch_name);
      $this->parseLog($log);
    }

    return $this->feature_branch_log;
  }

  /**
   * Gets the raw git log from one commit to another.
   *
   * @param $old
   *  The older commit. This is not included in the log.
   * @param $new
   *  The recent commit. This is included in the log.
   *
   * @return
   *  The raw output from git rev-list.
   */
  protected function getLog($old, $new) {
    $git_log = shell_exec("git rev-list {$new} ^{$old} --pretty=oneline --reverse");

    return $git_log;
  }

  protected function parseLog($log) {
    $feature_branch_log = [];

    if (!empty($log)) {
      $git_log_lines = explode("\n", rtrim($log));
      foreach ($git_log_lines as $line) {
        list($sha, $message) = explode(' ', $line, 2);
        //dump("$sha ::: $message");

        // This gets used with array_shift(), so the key is mostly pointless.
        $feature_branch_log[$sha] = [
          'sha' => $sha,
          'message' => $message,
        ];
      }
    }

    $this->feature_branch_log = $feature_branch_log;
  }

}
