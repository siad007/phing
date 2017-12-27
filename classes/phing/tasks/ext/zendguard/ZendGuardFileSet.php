<?php

/**
 * This is a FileSet with the to specify permissions.
 *
 * Permissions are currently not implemented by PEAR Archive_Tar,
 * but hopefully they will be in the future.
 *
 * @package phing.tasks.ext.zendguard
 */
class ZendGuardFileSet extends FileSet
{
    private $files = null;

    /**
     *  Get a list of files and directories specified in the fileset.
     * @param Project $p
     * @param bool $includeEmpty
     * @return array a list of file and directory names, relative to
     *               the baseDir for the project.
     * @throws Exception
     */
    public function getFiles(Project $p, $includeEmpty = true): array
    {
        if ($this->files === null) {
            $ds = $this->getDirectoryScanner($p);
            $this->files = $ds->getIncludedFiles();
        } // if ($this->files===null)

        return $this->files;
    }
}
