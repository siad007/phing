<?php
/**
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the LGPL. For more information please see
 * <http://phing.info>.
 */

require_once 'phing/tasks/system/CopyTask.php';
include_once 'phing/system/io/PhingFile.php';
include_once 'phing/system/io/IOException.php';

/**
 * Moves a file or directory to a new file or directory.
 *
 * By default, the destination file is overwritten if it
 * already exists.  When overwrite is turned off, then files
 * are only moved if the source file is newer than the
 * destination file, or when the destination file does not
 * exist.
 *
 * Source files and directories are only deleted when the file or
 * directory has been copied to the destination successfully.
 *
 * @package phing.tasks.system
 */
class MoveTask extends CopyTask
{

    /**
     *
     */
    public function __construct()
    {
        parent::__construct();
        $this->forceOverwrite = true;
    }

    /**
     * Validates attributes coming in from XML
     *
     * @return void
     *
     * @throws BuildException
     */
    protected function validateAttributes()
    {
        if ($this->file !== null && $this->file->isDirectory()) {
            if (($this->destFile !== null && $this->destDir !== null)
                || ($this->destFile === null && $this->destDir === null)
            ) {
                throw new BuildException("One and only one of tofile and todir must be set.");
            }

            if ($this->destFile === null) {
                $this->destFile = new PhingFile($this->destDir, $this->file->getName());
            }

            if ($this->destDir === null) {
                $this->destDir = $this->destFile->getParentFile();
            }

            $this->completeDirMap[$this->file->getAbsolutePath()] = $this->destFile->getAbsolutePath();

            $this->file = null;
        } else {
            parent::validateAttributes();
        }
    }

    protected function doWork()
    {
        //Attempt complete directory renames, if any, first.
        if (count($this->completeDirMap) > 0) {
            foreach ($this->completeDirMap as $fromDir => $toDir) {
                $fromDir = new PhingFile($fromDir);
                $toDir = new PhingFile($toDir);
                $renamed = false;
                try {
                    $this->log(
                        'Attempting to rename dir: ' . (string) $fromDir
                            . ' to ' . (string) $toDir,
                        $this->verbosity
                    );
                    $renamed = $this->renameFile($fromDir, $toDir);
                } catch (IOException $ioe) {
                    $msg = 'Failed to rename dir ' . (string) $fromDir
                        . ' to ' . (string) $toDir . ' due to ' . $ioe->getMessage();
                    throw new BuildException($msg, $ioe, $this->getLocation());
                }
                if (!$renamed) {
                    $fs = new FileSet();
                    $fs->setProject($this->getProject());
                    $fs->setDir($fromDir);
                    $this->addFileSet($fs);
                    $ds = $fs->getDirectoryScanner($this->getProject());
                    $files = $ds->getIncludedFiles();
                    $dirs = $ds->getIncludedDirectories();
                    $this->_scan($fromDir, $toDir, $files, $dirs);
                }
            }
        }

        $moveCount = count($this->fileCopyMap);
        if ($moveCount > 0) {   // files to move
            $this->log("Moving " . $moveCount . " file" . (($moveCount === 1) ? "" : "s")
                . " to " . (string) $this->destDir->getAbsolutePath());

            foreach ($this->fileCopyMap as $fromFile => $toFile) {
                $f = new PhingFile($fromFile);
                $selfMove = false;
                if ($f->exists()) { //Is this file still available to be moved?
                    if ($fromFile === $toFile) {
                        $this->log("Skipping self-move of " . $fromFile, $this->verbosity);
                        $selfMove = true;

                        // if this is the last time through the loop then
                        // move will not occur, but that's what we want
                        continue;
                    }
                    $d = new PhingFile($toFile);
                    if (!$selfMove) {
                        $this->moveFile($f, $d);
                    } else {
                        $this->copyFile($f, $d);
                    }
                }
            }
        }

        if ($this->includeEmpty) {
            $createCount = 0;
            foreach ($this->dirCopyMap as $fromDirName => $toDirName) {
                $selfMove = false;
                if ($fromDirName === $toDirName) {
                    $this->log("Skipping self-move of " . $fromDirName, $this->verbosity);
                    $selfMove = true;
                    continue;
                }
                $d = new PhingFile($toDirName);
                if (!$d->exists()) {
                    if (!($d->mkdirs() || $d->exists())) {
                        $this->log("Unable to create directory "
                            . $d->getAbsolutePath(), Project::MSG_ERR);
                    } else {
                        $createCount++;
                    }
                }
                $fromDir = new PhingFile($fromDirName);
                if (!$selfMove && $this->okToDelete($fromDir)) {
                    $this->deleteDir($fromDir);
                }
            }
            if ($createCount > 0) {
                $this->log("Moved " . count($this->dirCopyMap)
                    . " empty director"
                    . (count($this->dirCopyMap) === 1 ? "y" : "ies")
                    . " to " . $createCount
                    . " empty director"
                    . ($createCount === 1 ? "y" : "ies") . " under "
                    . $this->destDir->getAbsolutePath());
            }
        }
    }

    /**
     * Try to move the file via a rename, but if this fails or filtering
     * is enabled, copy the file then delete the sourceFile.
     *
     * @param PhingFile $fromFile
     * @param PhingFile $toFile
     *
     * @throws BuildException
     * @throws Exception
     */
    private function moveFile(PhingFile $fromFile, PhingFile $toFile)
    {
        $moved = false;
        try {
            $this->log("Attempting to rename: " . $fromFile . " to " . $toFile, $this->verbosity);
            $moved = $this->renameFile($fromFile, $toFile);
        } catch (IOException $ioe) {
            $msg = "Failed to rename " . $fromFile
                . " to " . $toFile . " due to " . $ioe->getMessage();
            throw new BuildException($msg, $ioe, $this->getLocation());
        }

        if (!$moved) {
            $this->copyFile($fromFile, $toFile);
            try {
                if (!$fromFile->delete()) {
                    throw new BuildException("Unable to delete " . "file "
                        . $fromFile->getAbsolutePath());
                }
            } catch (IOException $e) {
                throw new BuildException("Unable to delete " . "file "
                    . $fromFile->getAbsolutePath());
            }
        }
    }

    /**
     * Copy fromFile to toFile.
     *
     * @param PhingFile $fromFile
     * @param PhingFile $toFile
     *
     * @throws BuildException
     * @throws Exception
     */
    private function copyFile(PhingFile $fromFile, PhingFile $toFile)
    {
        try {
            $this->log("Copying " . $fromFile . " to " . $toFile, $this->verbosity);

            $this->fileUtils->copyFile(
                $fromFile,
                $toFile,
                $this->forceOverwrite,
                $this->preserveLMT,
                $this->filterChains,
                $this->getProject(),
                $this->mode
            );
        } catch (IOException $ioe) {
            $msg = "Failed to copy " . $fromFile
                . " to " . $toFile . " due to " . $ioe->getMessage();
            throw new BuildException($msg, $ioe, $this->getLocation());
        }
    }

    /**
     * Its only ok to delete a dir tree if there are no files in it.
     *
     * @param PhingFile $d
     *
     * @throws IOException
     *
     * @return bool
     */
    private function okToDelete($d)
    {
        $list = $d->listDir();
        if ($list === null) {
            return false; // maybe io error?
        }

        foreach ($list as $s) {
            $f = new PhingFile($d, $s);
            if ($f->isDirectory()) {
                if (!$this->okToDelete($f)) {
                    return false;
                }
            } else {
                // found a file
                return false;
            }
        }

        return true;
    }

    /**
     * Go and delete the directory tree.
     *
     * @param PhingFile $d
     *
     * @throws BuildException
     * @throws IOException
     */
    private function deleteDir($d)
    {

        $list = $d->listDir();
        if ($list === null) {
            return; // on an io error list() can return null
        }

        foreach ($list as $fname) {
            $f = new PhingFile($d, $fname);
            if ($f->isDirectory()) {
                $this->deleteDir($f);
            } else {
                throw new BuildException("UNEXPECTED ERROR - The file " . $f->getAbsolutePath() . " should not exist!");
            }
        }

        $this->log("Deleting directory " . $d->getPath(), $this->verbosity);
        try {
            $d->delete();
        } catch (Exception $e) {
            $this->logError("Unable to delete directory " . (string) $d . ": " . $e->getMessage());
        }
    }

    /**
     * Attempts to rename a file from a source to a destination.
     * If overwrite is set to true, this method overwrites existing file
     * even if the destination file is newer.  Otherwise, the source file is
     * renamed only if the destination file is older than it.
     * Method then checks if token filtering is used.  If it is, this method
     * returns false assuming it is the responsibility to the copyFile method.
     *
     * @param PhingFile $sourceFile the file to rename
     * @param PhingFile $destFile the destination file
     *
     * @return bool true if the file was renamed
     *
     * @throws BuildException
     * @throws IOException
     */
    protected function renameFile(PhingFile $sourceFile, PhingFile $destFile)
    {
        if ($destFile->isDirectory() || count($this->filterChains) > 0) {
            return false;
        }

        if ($destFile->isFile() && !$destFile->canWrite()) {
            if (!$this->forceOverwrite) {
                throw new IOException("can't replace read-only destination "
                    . "file " . $destFile);
            } elseif (!$destFile->delete(true)) {
                throw new IOException("failed to delete read-only "
                    . "destination file " . $destFile);
            }
        }

        /** @var PhingFile $parent */
        $parent = $destFile->getParentFile();
        if ($parent != null && !$parent->exists()) {
            $parent->mkdirs();
        } else if ($destFile->isFile()) {
            /** @var PhingFile $sourceFile */
            $sourceFile = $this->fileUtils->normalize($sourceFile->getAbsolutePath());
            /** @var PhingFile $destFile */
            $destFile = $this->fileUtils->normalize($destFile->getAbsolutePath());
            if ($destFile->getAbsolutePath() === $sourceFile->getAbsolutePath()) {
                //no point in renaming a file to its own canonical version...
                $this->log("Rename of " . $sourceFile . " to " . $destFile
                    . " is a no-op.", Project::MSG_VERBOSE);
                return true;
            }
            if (!($this->fileUtils->contentEquals($sourceFile, $destFile))) {
                throw new BuildException("Unable to remove existing file " . $destFile);
            }
        }
        return $sourceFile->renameTo($destFile);
    }
}
