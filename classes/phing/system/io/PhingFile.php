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

include_once 'phing/system/io/FileSystem.php';
include_once 'phing/system/lang/NullPointerException.php';

/**
 * An abstract representation of file and directory pathnames.
 *
 * @package   phing.system.io
 */
class PhingFile extends PhingFileInfo
{
    /**
     * Convenience method for returning the contents of this file as a string.
     * This method uses file_get_contents() to read file in an optimized way.
     * @return string
     * @throws Exception - if file cannot be read
     */
    public function contents()
    {
        if (!$this->isReadable() || !$this->isFile()) {
            throw new IOException("Cannot read file contents!");
        }

        return file_get_contents($this->getAbsolutePath());
    }

    /**
     * Atomically creates a new, empty file named by this abstract pathname if
     * and only if a file with this name does not yet exist.  The check for the
     * existence of the file and the creation of the file if it does not exist
     * are a single operation that is atomic with respect to all other
     * filesystem activities that might affect the file.
     *
     * @param bool $parents
     * @param int $mode
     * @throws IOException
     * @return boolean     true if the named file does not exist and was
     *                     successfully created; <code>false</code> if the named file
     *                     already exists
     * @throws NullPointerException
     */
    public function createNewFile($parents = true, $mode = 0777)
    {
        /** @var PhingFile $parent */
        $parent = $this->getParentFile();
        if ($parents && !$parent->exists()) {
            $parent->mkdirs();
        }

        return FileSystem::getFileSystem()->createNewFile($this->path);
    }

    /**
     * Deletes the file or directory denoted by this abstract pathname.  If
     * this pathname denotes a directory, then the directory must be empty in
     * order to be deleted.
     *
     * @param bool $recursive
     * @throws IOException
     */
    public function delete($recursive = false)
    {
        $fs = FileSystem::getFileSystem();
        if ($fs->canDelete($this) !== true) {
            throw new IOException("Cannot delete " . $this->path . "\n");
        }

        $fs->delete($this, $recursive);
    }

    /**
     * Requests that the file or directory denoted by this abstract pathname
     * be deleted when php terminates.  Deletion will be attempted only for
     * normal termination of php and if and if only Phing::shutdown() is
     * called.
     *
     * Once deletion has been requested, it is not possible to cancel the
     * request.  This method should therefore be used with care.
     *
     * @throws IOException
     */
    public function deleteOnExit()
    {
        $fs = FileSystem::getFileSystem();
        $fs->deleteOnExit($this);
    }

    /**
     * Returns an array of strings naming the files and directories in the
     * directory denoted by this abstract pathname.
     *
     * If this abstract pathname does not denote a directory, then this
     * method returns null  Otherwise an array of strings is
     * returned, one for each file or directory in the directory.  Names
     * denoting the directory itself and the directory's parent directory are
     * not included in the result.  Each string is a file name rather than a
     * complete path.
     *
     * There is no guarantee that the name strings in the resulting array
     * will appear in any specific order; they are not, in particular,
     * guaranteed to appear in alphabetical order.
     *
     * @return array An array of strings naming the files and directories in the
     *               directory denoted by this abstract pathname.  The array will be
     *               empty if the directory is empty.  Returns null if
     *               this abstract pathname does not denote a directory, or if an
     *               I/O error occurs.
     * @throws IOException
     * @throws Exception
     */
    public function listDir()
    {
        $fs = FileSystem::getFileSystem();

        return $fs->listContents($this);
    }

    /**
     * Creates the directory named by this abstract pathname, including any
     * necessary but nonexistent parent directories.  Note that if this
     * operation fails it may have succeeded in creating some of the necessary
     * parent directories.
     *
     * @param int $mode
     * @throws IOException
     * @return boolean     true if and only if the directory was created,
     *                     along with all necessary parent directories; false
     *                     otherwise
     * @throws NullPointerException
     */
    public function mkdirs($mode = 0755): bool
    {
        if ($this->exists()) {
            return false;
        }
        try {
            if ($this->mkdir($mode)) {
                return true;
            }
        } catch (IOException $ioe) {
            // IOException from mkdir() means that directory propbably didn't exist.
        }
        $parentFile = $this->getParentFile();

        return (($parentFile !== null) && ($parentFile->mkdirs($mode) && $this->mkdir($mode)));
    }

    /**
     * Creates the directory named by this abstract pathname.
     *
     * @param int $mode
     * @throws IOException
     * @return boolean     true if and only if the directory was created; false otherwise
     */
    public function mkdir($mode = 0755)
    {
        $fs = FileSystem::getFileSystem();

        if ($fs->checkAccess(new PhingFile($this->path), true) !== true) {
            throw new IOException("No write access to " . $this->getPath());
        }

        return $fs->createDirectory($this, $mode);
    }

    /**
     * Renames the file denoted by this abstract pathname.
     *
     * @param  PhingFile $destFile The new abstract pathname for the named file
     * @throws IOException
     */
    public function renameTo(PhingFile $destFile)
    {
        $fs = FileSystem::getFileSystem();
        if ($fs->checkAccess($this) !== true) {
            throw new IOException("No write access to " . $this->getPath());
        }

        $fs->rename($this, $destFile);
    }

    /**
     * Simple-copies file denoted by this abstract pathname into another
     * PhingFile
     *
     * @param  PhingFile $destFile The new abstract pathname for the named file
     * @throws IOException
     */
    public function copyTo(PhingFile $destFile)
    {
        $fs = FileSystem::getFileSystem();

        if ($fs->checkAccess($this) !== true) {
            throw new IOException("No read access to " . $this->getPath() . "\n");
        }

        if ($fs->checkAccess($destFile, true) !== true) {
            throw new IOException("File::copyTo() No write access to " . $destFile->getPath());
        }

        $fs->copy($this, $destFile);
    }

    /**
     * Sets the last-modified time of the file or directory named by this
     * abstract pathname.
     *
     * All platforms support file-modification times to the nearest second,
     * but some provide more precision.  The argument will be truncated to fit
     * the supported precision.  If the operation succeeds and no intervening
     * operations on the file take place, then the next invocation of the
     * lastModified method will return the (possibly truncated) time argument
     * that was passed to this method.
     *
     * @param  int $time The new last-modified time, measured in milliseconds since
     *                       the epoch (00:00:00 GMT, January 1, 1970)
     * @throws Exception
     */
    public function setLastModified($time)
    {
        $time = (int) $time;
        if ($time < 0) {
            throw new Exception("IllegalArgumentException, Negative $time\n");
        }

        $fs = FileSystem::getFileSystem();

        $fs->setLastModifiedTime($this, $time);
    }

    /**
     * Marks the file or directory named by this abstract pathname so that
     * only read operations are allowed.  After invoking this method the file
     * or directory is guaranteed not to change until it is either deleted or
     * marked to allow write access.  Whether or not a read-only file or
     * directory may be deleted depends upon the underlying system.
     *
     * @throws IOException
     */
    public function setReadOnly()
    {
        $fs = FileSystem::getFileSystem();
        if ($fs->checkAccess($this, true) !== true) {
            // Error, no write access
            throw new IOException("No write access to " . $this->getPath());
        }

        $fs->setReadOnly($this);
    }

    /**
     * Sets the owner of the file.
     *
     * @param mixed $user User name or number.
     *
     * @throws IOException
     */
    public function setUser($user)
    {
        $fs = FileSystem::getFileSystem();

        $fs->chown($this->getPath(), $user);
    }

    /**
     * Retrieve the owner of this file.
     *
     * @return int User ID of the owner of this file.
     */
    public function getUser()
    {
        return @fileowner($this->getPath());
    }

    /**
     * Sets the group of the file.
     *
     * @param string $group
     *
     * @throws IOException
     */
    public function setGroup($group)
    {
        $fs = FileSystem::getFileSystem();

        $fs->chgrp($this->getPath(), $group);
    }

    /**
     * Sets the mode of the file
     *
     * @param int $mode Octal mode.
     * @throws IOException
     */
    public function setMode($mode)
    {
        $fs = FileSystem::getFileSystem();

        $fs->chmod($this->getPath(), $mode);
    }

    /**
     * Retrieve the mode of this file.
     *
     * @return int
     */
    public function getMode()
    {
        return @fileperms($this->getPath());
    }

    /* -- Tempfile management -- */

    /**
     * Returns the path to the temp directory.
     * @return string
     */
    public static function getTempDir()
    {
        return Phing::getProperty('php.tmpdir');
    }

    /**
     * Static method that creates a unique filename whose name begins with
     * $prefix and ends with $suffix in the directory $directory. $directory
     * is a reference to a PhingFile Object.
     * Then, the file is locked for exclusive reading/writing.
     *
     * @author manuel holtgrewe, grin@gmx.net
     *
     * @param $prefix
     * @param $suffix
     * @param PhingFile $directory
     *
     * @throws IOException
     * @return PhingFile
     */
    public static function createTempFile($prefix, $suffix, PhingFile $directory)
    {

        // quick but efficient hack to create a unique filename ;-)
        $result = null;
        do {
            $result = new PhingFile($directory, $prefix . substr(md5(time()), 0, 8) . $suffix);
        } while (file_exists($result->getPath()));

        $fs = FileSystem::getFileSystem();
        $fs->createNewFile($result->getPath());
        $fs->lock($result);

        return $result;
    }

    /**
     * If necessary, $File the lock on $File is removed and then the file is
     * deleted.
     * @throws IOException
     */
    public function removeTempFile()
    {
        $fs = FileSystem::getFileSystem();
        // catch IO Exception
        $fs->unlock($this);
        $this->delete();
    }

    /**
     * Compares two abstract pathnames lexicographically.  The ordering
     * defined by this method depends upon the underlying system.  On UNIX
     * systems, alphabetic case is significant in comparing pathnames; on Win32
     * systems it is not.
     *
     * @param PhingFile $file Th file whose pathname sould be compared to the pathname of this file.
     *
     * @return int Zero if the argument is equal to this abstract pathname, a
     *             value less than zero if this abstract pathname is
     *             lexicographically less than the argument, or a value greater
     *             than zero if this abstract pathname is lexicographically
     *             greater than the argument
     * @throws IOException
     */
    public function compareTo(PhingFile $file)
    {
        $fs = FileSystem::getFileSystem();

        return $fs->compare($this, $file);
    }

    /**
     * Tests this abstract pathname for equality with the given object.
     * Returns <code>true</code> if and only if the argument is not
     * <code>null</code> and is an abstract pathname that denotes the same file
     * or directory as this abstract pathname.  Whether or not two abstract
     * pathnames are equal depends upon the underlying system.  On UNIX
     * systems, alphabetic case is significant in comparing pathnames; on Win32
     * systems it is not.
     *
     * @param PhingFile $obj
     *
     * @return boolean
     * @throws IOException
     */
    public function equals($obj)
    {
        if (($obj !== null) && ($obj instanceof PhingFile)) {
            return ($this->compareTo($obj) === 0);
        }

        return false;
    }
}
