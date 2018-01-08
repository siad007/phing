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

/**
 * An abstract representation of file and directory pathnames.
 *
 * @package   phing.system.io
 */
class PhingFileInfo extends SplFileInfo
{
    /** separator string, static, obtained from FileSystem */
    public static $separator;

    /** path separator string, static, obtained from FileSystem (; or :)*/
    public static $pathSeparator;

    /**
     * The length of this abstract pathname's prefix, or zero if it has no prefix.
     * @var int
     */
    protected $prefixLength = 0;

    /**
     * constructor
     *
     * @param mixed $arg1
     * @param mixed $arg2
     *
     * @throws IOException
     * @throws NullPointerException
     */
    public function __construct($arg1 = null, $arg2 = null)
    {
        if (self::$separator === null || self::$pathSeparator === null) {
            $fs = FileSystem::getFileSystem();
            self::$separator = $fs->getSeparator();
            self::$pathSeparator = $fs->getPathSeparator();
        }

        /* simulate signature identified constructors */
        if ($arg1 instanceof PhingFile && is_string($arg2)) {
            $this->_constructFileParentStringChild($arg1, $arg2);
        } elseif (($arg2 === null) && is_string($arg1)) {
            $this->_constructPathname($arg1);
        } elseif (is_string($arg1) && is_string($arg2)) {
            $this->_constructStringParentStringChild($arg1, $arg2);
        } else {
            if ($arg1 === null) {
                throw new NullPointerException("Argument1 to function must not be null");
            }
            $this->path = (string) $arg1;
            $this->prefixLength = (int) $arg2;
        }

        parent::__construct($this->getPath());
    }

    /**
     * Returns the length of this abstract pathname's prefix.
     *
     * @return int
     */
    public function getPrefixLength()
    {
        return (int) $this->prefixLength;
    }

    /* -- constructors not called by signature match, so we need some helpers --*/

    /**
     * @param string $pathname
     *
     * @throws NullPointerException
     * @throws IOException
     */
    protected function _constructPathname($pathname)
    {
        // obtain ref to the filesystem layer
        $fs = FileSystem::getFileSystem();

        if ($pathname === null) {
            throw new NullPointerException("Argument to function must not be null");
        }

        $this->path = (string) $fs->normalize($pathname);
        $this->prefixLength = (int) $fs->prefixLength($this->getPath());
    }

    /**
     * @param string $parent
     * @param string $child
     *
     * @throws NullPointerException
     * @throws IOException
     */
    protected function _constructStringParentStringChild($parent, $child = null)
    {
        // obtain ref to the filesystem layer
        $fs = FileSystem::getFileSystem();

        if ($child === null) {
            throw new NullPointerException("Argument to function must not be null");
        }
        if ($parent !== null) {
            if ($parent === "") {
                $this->path = $fs->resolve($fs->getDefaultParent(), $fs->normalize($child));
            } else {
                $this->path = $fs->resolve($fs->normalize($parent), $fs->normalize($child));
            }
        } else {
            $this->path = (string) $fs->normalize($child);
        }
        $this->prefixLength = (int) $fs->prefixLength($this->getPath());
    }

    /**
     * @param PhingFile $parent
     * @param string $child
     *
     * @throws NullPointerException
     * @throws IOException
     */
    protected function _constructFileParentStringChild($parent, $child = null): void
    {
        // obtain ref to the filesystem layer
        $fs = FileSystem::getFileSystem();

        if ($child === null) {
            throw new NullPointerException("Argument to function must not be null");
        }

        if ($parent !== null) {
            if ($parent->getPath() === "") {
                $this->path = $fs->resolve($fs->getDefaultParent(), $fs->normalize($child));
            } else {
                $this->path = $fs->resolve($parent->getPath(), $fs->normalize($child));
            }
        } else {
            $this->path = $fs->normalize($child);
        }
        $this->prefixLength = $fs->prefixLength($this->getPath());
    }

    /**
     * Returns the pathname string of this abstract pathname's parent, or
     * null if this pathname does not name a parent directory.
     *
     * The parent of an abstract pathname consists of the pathname's prefix,
     * if any, and each name in the pathname's name sequence except for the last.
     * If the name sequence is empty then the pathname does not name a parent
     * directory.
     *
     * @return string|null $pathname string of the parent directory named by this
     *                          abstract pathname, or null if this pathname does not name a parent
     */
    public function getParent(): ?string
    {
        // that's a lastIndexOf
        $index = ((($res = strrpos($this->getPath(), self::$separator)) === false) ? -1 : $res);
        if ($index < $this->prefixLength) {
            if (($this->prefixLength > 0) && (strlen($this->getPath()) > $this->prefixLength)) {
                return substr($this->getPath(), 0, $this->prefixLength);
            }

            return null;
        }

        return substr($this->getPath(), 0, $index);
    }

    /**
     * Returns the abstract pathname of this abstract pathname's parent,
     * or null if this pathname does not name a parent directory.
     *
     * The parent of an abstract pathname consists of the pathname's prefix,
     * if any, and each name in the pathname's name sequence except for the
     * last.  If the name sequence is empty then the pathname does not name
     * a parent directory.
     *
     * @return PhingFile|null The abstract pathname of the parent directory named by this
     *             abstract pathname, or null if this pathname
     *             does not name a parent
     *
     * @throws \NullPointerException
     * @throws \IOException
     */
    public function getParentFile(): ?\PhingFile
    {
        $p = $this->getParent();
        if ($p === null) {
            return null;
        }

        return new PhingFile((string) $p, (int) $this->prefixLength);
    }

    /**
     * Returns path without leading basedir.
     *
     * @param string $basedir Base directory to strip
     *
     * @return string Path without basedir
     *
     * @uses getPath()
     */
    public function getPathWithoutBase($basedir): string
    {
        if (!StringHelper::endsWith(self::$separator, $basedir)) {
            $basedir .= self::$separator;
        }
        $path = $this->getPath();
        if (0 !== strpos($path, $basedir)) {
            //path does not begin with basedir, we don't modify it
            return $path;
        }

        return substr($path, strlen($basedir));
    }

    /**
     * Tests whether this abstract pathname is absolute.  The definition of
     * absolute pathname is system dependent.  On UNIX systems, a pathname is
     * absolute if its prefix is "/".  On Win32 systems, a pathname is absolute
     * if its prefix is a drive specifier followed by "\\", or if its prefix
     * is "\\".
     *
     * @return boolean true if this abstract pathname is absolute, false otherwise
     */
    public function isAbsolute(): bool
    {
        return ($this->prefixLength !== 0);
    }

    /**
     * Returns the absolute pathname string of this abstract pathname.
     *
     * If this abstract pathname is already absolute, then the pathname
     * string is simply returned as if by the getPath method.
     * If this abstract pathname is the empty abstract pathname then
     * the pathname string of the current user directory, which is named by the
     * system property user.dir, is returned.  Otherwise this
     * pathname is resolved in a system-dependent way.  On UNIX systems, a
     * relative pathname is made absolute by resolving it against the current
     * user directory.  On Win32 systems, a relative pathname is made absolute
     * by resolving it against the current directory of the drive named by the
     * pathname, if any; if not, it is resolved against the current user
     * directory.
     *
     * @return string The absolute pathname string denoting the same file or
     *                directory as this abstract pathname
     *
     * @throws IOException
     *
     * @see    isAbsolute()
     */
    public function getAbsolutePath()
    {
        $fs = FileSystem::getFileSystem();

        return $fs->resolveFile($this);
    }

    /**
     * Returns the absolute form of this abstract pathname.  Equivalent to
     * getAbsolutePath.
     *
     * @return PhingFile The absolute abstract pathname denoting the same file or
     *                directory as this abstract pathname
     *
     * @throws \NullPointerException
     * @throws \IOException
     */
    public function getAbsoluteFile(): \PhingFile
    {
        return new PhingFile((string) $this->getAbsolutePath());
    }

    /**
     * Returns the canonical pathname string of this abstract pathname.
     *
     * A canonical pathname is both absolute and unique. The precise
     * definition of canonical form is system-dependent. This method first
     * converts this pathname to absolute form if necessary, as if by invoking the
     * getAbsolutePath() method, and then maps it to its unique form in a
     * system-dependent way.  This typically involves removing redundant names
     * such as "." and .. from the pathname, resolving symbolic links
     * (on UNIX platforms), and converting drive letters to a standard case
     * (on Win32 platforms).
     *
     * Every pathname that denotes an existing file or directory has a
     * unique canonical form.  Every pathname that denotes a nonexistent file
     * or directory also has a unique canonical form.  The canonical form of
     * the pathname of a nonexistent file or directory may be different from
     * the canonical form of the same pathname after the file or directory is
     * created.  Similarly, the canonical form of the pathname of an existing
     * file or directory may be different from the canonical form of the same
     * pathname after the file or directory is deleted.
     *
     * @return string The canonical pathname string denoting the same file or
     *                directory as this abstract pathname
     *
     * @throws IOException
     */
    public function getCanonicalPath(): string
    {
        $fs = FileSystem::getFileSystem();

        return $fs->canonicalize($this->getPath());
    }


    /**
     * Returns the canonical form of this abstract pathname.  Equivalent to
     * getCanonicalPath(.
     *
     * @return PhingFile The canonical pathname string denoting the same file or
     *                   directory as this abstract pathname
     *
     * @throws \NullPointerException
     * @throws \IOException
     */
    public function getCanonicalFile(): \PhingFile
    {
        return new PhingFile($this->getCanonicalPath());
    }

    /**
     *
     * Enter description here ...
     * @param  PhingFile|string $path
     * @param  boolean          $isDirectory
     * @return string
     */
    public function slashify($path, $isDirectory): string
    {
        $p = (string) $path;

        if (self::$separator !== '/') {
            $p = str_replace(self::$separator, '/', $p);
        }

        if (!StringHelper::startsWith('/', $p)) {
            $p = '/' . $p;
        }

        if (!StringHelper::endsWith('/', $p) && $isDirectory) {
            $p .= '/';
        }

        return $p;
    }

    /* -- Attribute accessors -- */

    /**
     * Tests whether the file denoted by this abstract pathname exists.
     *
     * @return boolean true if and only if the file denoted by this
     *                 abstract pathname exists; false otherwise
     * @throws IOException
     */
    public function exists()
    {
        clearstatcache();

        if (is_link($this->getAbsolutePath())) {
            return true;
        }

        return $this->isDir() ? true : @file_exists($this->getPath());
    }

    /**
     * Tests whether the file named by this abstract pathname is a hidden
     * file.  The exact definition of hidden is system-dependent.  On
     * UNIX systems, a file is considered to be hidden if its name begins with
     * a period character ('.').  On Win32 systems, a file is considered to be
     * hidden if it has been marked as such in the filesystem. Currently there
     * seems to be no way to dermine isHidden on Win file systems via PHP
     *
     * @throws IOException
     * @return boolean true if and only if the file denoted by this
     *                 abstract pathname is hidden according to the conventions of the
     *                 underlying platform
     */
    public function isHidden(): bool
    {
        $fs = FileSystem::getFileSystem();
        if ($fs->checkAccess($this) !== true) {
            throw new IOException("No read access to " . $this->getPath());
        }

        return (($fs->getBooleanAttributes($this) & FileSystem::BA_HIDDEN) !== 0);
    }

    /**
     * Returns the time that the file denoted by this abstract pathname was
     * last modified.
     *
     * @throws IOException
     * @return int An integer value representing the time the file was
     *             last modified, measured in seconds since the epoch
     *             (00:00:00 GMT, January 1, 1970), or 0 if the
     *             file does not exist or if an I/O error occurs
     */
    public function lastModified()
    {
        $fs = FileSystem::getFileSystem();
        if ($fs->checkAccess($this) !== true) {
            throw new IOException("No read access to " . $this->getPath());
        }

        return $fs->getLastModifiedTime($this);
    }

    /* -- Filesystem interface -- */

    /**
     * List the available filesystem roots.
     *
     * A particular platform may support zero or more hierarchically-organized
     * file systems.  Each file system has a root  directory from which all
     * other files in that file system can be reached.
     * Windows platforms, for example, have a root directory for each active
     * drive; UNIX platforms have a single root directory, namely "/".
     * The set of available filesystem roots is affected by various system-level
     * operations such the insertion or ejection of removable media and the
     * disconnecting or unmounting of physical or virtual disk drives.
     *
     * This method returns an array of PhingFile objects that
     * denote the root directories of the available filesystem roots.  It is
     * guaranteed that the canonical pathname of any file physically present on
     * the local machine will begin with one of the roots returned by this
     * method.
     *
     * The canonical pathname of a file that resides on some other machine
     * and is accessed via a remote-filesystem protocol such as SMB or NFS may
     * or may not begin with one of the roots returned by this method.  If the
     * pathname of a remote file is syntactically indistinguishable from the
     * pathname of a local file then it will begin with one of the roots
     * returned by this method.  Thus, for example, PhingFile objects
     * denoting the root directories of the mapped network drives of a Windows
     * platform will be returned by this method, while PhingFile
     * objects containing UNC pathnames will not be returned by this method.
     *
     * @return array An array of PhingFile objects denoting the available
     *               filesystem roots, or null if the set of roots
     *               could not be determined.  The array will be empty if there are
     *               no filesystem roots.
     * @throws IOException
     */
    public function listRoots()
    {
        $fs = FileSystem::getFileSystem();

        return (array) $fs->listRoots();
    }
}
