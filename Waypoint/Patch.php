<?php

namespace Dorgflow\Waypoint;

class Patch {

  protected static $generator;

  /**
   * Indicates that the patch is not a viable object and should be destroyed.
   */
  public $cancel;

  protected $fid;

  protected $patchFile;

  function __construct(\Dorgflow\Situation $situation) {
    $this->situation = $situation;

    // The generator must be stored, otherwise getting it again takes us back
    // to the start of the iterator.
    if (!isset(static::$generator)) {
      static::$generator = $situation->DrupalOrgIssueNode->getNextIssueFile();
    }


    $file = static::$generator->current();
    dump($file);
    if (empty($file)) {
      // Cancel this.
      // (Would throwing an exception be cleaner?)
      $this->cancel = TRUE;
      $this->status = 'end';
      return;
    }

    // Advance the generator for the next use.
    static::$generator->next();

    // Skip a patch file that is set to not be displayed.
    if (!$file->display) {
      $this->cancel = TRUE;
      $this->status = 'skip';
      return;
    }

    // Set the file ID.
    $this->fid = $file->file->id;

    // Skip if it's not a patch file.
    // Unfortunately, we have to retrieve the file entity from d.org API to
    // know the file's URL and its extension.
    $file_entity = $this->getFileEntity();
    $file_url = $file_entity->url;
    if (pathinfo($file_url, PATHINFO_EXTENSION) != 'patch') {
      $this->cancel = TRUE;
      $this->status = 'skip';
      return;
    }

    // Try to find a commit.
    // TODO: can't do this until we have index numbers.
    // (well, we could use fids, but then we'd have a backwards compatibility
    // issue in the future...)
  }

  public function getFileEntity() {
    // Lazy fetch the patch file.
    if (empty($this->fileEntity)) {
      $this->fileEntity = $this->situation->DrupalOrgPatchFile->getFileEntity($this->fid);
    }
    return $this->fileEntity;
  }

  public function getPatchFile() {
    // Lazy fetch the patch file.
    if (empty($this->patchFile)) {
      $this->patchFile = $this->situation->DrupalOrgPatchFile->getPatchFile($this->fid);
    }
    return $this->patchFile;
  }

  public function commitPatch() {
    // Set the files back to the master branch, without changing the current
    // commit.
    $this->situation->masterBranch->checkOutFiles();

    $this->applyPatchFile();
    $this->makeGitCommit();
  }

  public function applyPatchFile() {
    // See https://www.sitepoint.com/proc-open-communicate-with-the-outside-world/
    $desc = [
      0 => array('pipe', 'r'), // 0 is STDIN for process
      1 => array('pipe', 'w'), // 1 is STDOUT for process
      2 => array('pipe', 'w'), // 2 is STDERR for process
    ];

    // The command.
    $cmd = "git apply --index --verbose -";

    // Spawn the process.
    $pipes = [];
    $process = proc_open($cmd, $desc, $pipes);

    // Send the patch to command as input, the close the input pipe so the
    // command knows to start processing.
    $patch_file = $this->getPatchFile();
    dump($patch_file);
    fwrite($pipes[0], $patch_file);
    fclose($pipes[0]);


    $out = stream_get_contents($pipes[1]);
    dump($out);

    $errors = stream_get_contents($pipes[2]);
    dump($errors);
    // TODO: check STDERR for errors!



    // all done! Clean up
    fclose($pipes[1]);
    fclose($pipes[2]);
    proc_close($process);
  }

  protected function getCommitMessage() {
    return "patch #??TODO; fid $this->fid. Automatic commit by dorgflow.";
  }

  protected function makeGitCommit() {
    $message = $this->getCommitMessage();
    shell_exec("git commit --message='$message'");
  }

}
