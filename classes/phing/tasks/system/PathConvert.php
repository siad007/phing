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

require_once 'phing/tasks/ext/property/AbstractPropertySetterTask.php';

/**
 * Converts path and classpath information to a specific target OS
 * format. The resulting formatted path is placed into the specified property.
 *
 * @author    Siad Ardroumli <siad.ardroumli@gmail.com.com>
 * @package   phing.tasks.system
 */
class PathConvert extends AbstractPropertySetterTask
{
    /**
     * Set if we're running on windows
     */
    private $onWindows = null;

    /**
     * Path to be converted
     */
    private $path = null;

    /**
     * Reference to path/fileset to convert
     * @var Reference
     */
    private $refid = null;

    /**
     * The target OS type
     */
    private $targetOS = null;

    /**
     * Set when targetOS is set to windows
     */
    private $targetWindows = false;

    /**
     * Set if we should create a new property even if the result is empty
     */
    private $setonempty = true;

    /**
     * Path prefix map
     * @var MapEntry[]
     */
    private $prefixMap = array();

    /**
     * User override on path sep char
     */
    private $pathSep = null;

    /**
     * User override on directory sep char
     */
    private $dirSep = null;

    private $fromDirSep = '';

    /**
     * Filename mapper
     * @var Mapper
     */
    private $mapper = null;

    private $filenames = array();

    /**
     * Construct a new instance of the PathConvert task.
     */
    public function __construct()
    {
        if ($this->onWindows === null) {
            $this->onWindows = strtoupper(substr(php_uname(), 0, 3)) === 'WIN';
        }

        $this->fromDirSep = $this->onWindows ? "\\" : "/";
    }

    /**
     * @return FileSet
     * @throws BuildException
     */
    public function createFileSet()
    {
        if ($this->isReference()) {
            throw $this->noChildrenAllowed();
        }
        $result = new FileSet();

        if ($this->refid instanceof Reference) {
            $result->setRefid($this->refid);
        }

        $this->path[] = $result;
        return $result;
    }

    /**
     * @return DirSet
     * @throws BuildException
     */
    public function createDirSet()
    {
        if ($this->isReference()) {
            throw $this->noChildrenAllowed();
        }
        $result = new DirSet();

        if ($this->refid instanceof Reference) {
            $result->setRefid($this->refid);
        }

        $this->path[] = $result;
        return $result;
    }

    /**
     * @return FileList
     * @throws BuildException
     */
    public function createFileList()
    {
        if ($this->isReference()) {
            throw $this->noChildrenAllowed();
        }
        $result = new FileList();

        if ($this->refid instanceof Reference) {
            $result->setRefid($this->refid);
        }

        $this->path[] = $result;
        return $result;
    }

    /**
     * Create a nested path element.
     * @return Path
     * @throws BuildException
     */
    public function createPath()
    {
        if ($this->isReference()) {
            throw $this->noChildrenAllowed();
        }
        $result = new Path($this->getProject());

        if ($this->refid instanceof Reference) {
            $result->setRefid($this->refid);
        }

        $this->path[] = $result;
        return $result;
    }

    /**
     * Create a nested MAP element.
     * @param MapEntry $mapEntry
     * @return MapEntry a Map to configure.
     */
    public function addMap(MapEntry $mapEntry)
    {
        $mapEntry->setOnWindows($this->onWindows);
        $this->prefixMap[] = $mapEntry;
        return $mapEntry;
    }

    /**
     * Set targetos to a platform to one of
     * "windows", "unix", "netware", or "os/2";
     * current platform settings are used by default.
     *
     * Currently, we deal with only two path formats: Unix and Windows
     * And Unix is everything that is not Windows
     * for NetWare and OS/2, piggy-back on Windows, since in the
     * validateSetup code, the same assumptions can be made as
     * with windows - that ; is the path separator
     *
     * @param string $target the target os
     */
    public function setTargetos($target)
    {
        $to = new TargetOs();
        $to->setValue($target);

        $this->targetOS      = $to->getValue();
        $this->targetWindows = $this->targetOS !== "unix" && $this->targetOS !== "tandem";
    }

    /**
     * Set whether the specified property will be set if the result
     * is the empty string.
     * @param boolean $setonempty true or false.
     */
    public function setSetonempty($setonempty)
    {
        $this->setonempty = $setonempty;
    }

    /**
     * Add a reference to a Path, FileSet, DirSet, or FileList defined elsewhere.
     * @param Reference $r the reference to a path, fileset, dirset or filelist.
     * @throws BuildException
     */
    public function setRefid(Reference $r)
    {
        if ($this->path != null) {
            throw $this->noChildrenAllowed();
        }
        $this->refid = $r;
    }

    /**
     * @param Project $p
     * @return Reference
     * @throws BuildException
     */
    public function getRef(Project $p)
    {
        $o = $this->refid->getReferencedObject($p);

        if (!($o instanceof Path) && !($o instanceof FileSet) && !($o instanceof DirSet) && !($o instanceof FileList)) {
            throw new BuildException("refid '" . $this->refid->getRefId() . "' does not refer to a resource.");
        }

        return $o;
    }

    /**
     * Set the default path separator string; defaults to current JVM
     * {@link java.io.File#pathSeparator File.pathSeparator}.
     * @param string $sep path separator string.
     */
    public function setPathSep($sep)
    {
        $this->pathSep = $sep;
    }


    /**
     * Set the default directory separator string;
     * @param string $sep directory separator string.
     */
    public function setDirSep($sep)
    {
        $this->dirSep = $sep;
    }

    /**
     * Learn whether the refid attribute of this element been set.
     * @return true if refid is valid.
     */
    public function isReference()
    {
        return $this->refid != null;
    }

    /**
     * Do the execution.
     * @throws BuildException if something is invalid.
     */
    public function main()
    {
        $this->checkReference();
        $this->validateSetup();

        foreach ($this->path as $resource) {
            $this->assembleResources($resource);
        }
        $rslt = $this->processMapping($this->filenames);

        // Place the result into the specified property,
        // unless setonempty == false
        if ($this->setonempty || strlen($rslt) > 0) {
            $this->setPropertyValue($rslt);
        }
    }

    private function processMapping($elements)
    {
        $mapperImpl = $this->mapper === null ? new IdentityMapper() : $this->mapper->getImplementation();

        $rslt = "";
        foreach ($elements as $index => $mappedElement) {
            /* Apply mapper */
            $mapped = $mapperImpl->main($mappedElement);

            /* Apply the path prefix map */
            $elem = $this->mapElement($mapped[0]);

            if ($index !== 0) {
                $rslt .= $this->pathSep;
            }

            $rslt .= str_replace($this->fromDirSep, $this->dirSep, $elem);
        }

        return $rslt;
    }

    /**
     * If we are a reference, create a Path from the reference.
     * @throws BuildException
     */
    private function checkReference()
    {
        if ($this->isReference()) {
            $this->path[] = $this->getRef($this->getProject());
        }
    }

    /**
     * @param object $resource
     * @return Path[]
     * @throws BuildException
     */
    private function assembleResources($resource)
    {
        if ($resource instanceof Path) {
            $paths = $resource->listPaths();
            $this->filenames = array_merge($this->filenames, $paths);
        } elseif ($resource instanceof FileSet || $resource instanceof DirSet) {
            $ds = $resource->getDirectoryScanner($this->getProject());
            $paths = $ds->getIncludedFiles();
            $this->filenames = array_merge($this->filenames, $paths);
        } elseif ($resource instanceof FileList) {
            foreach ($resource->getFiles($this->getProject()) as $element) {
                $this->filenames[] = rtrim($resource->getDir($this->getProject()), "\\/") . $this->dirSep . $element;
            }
        }
    }

    /**
     * Apply the configured map to a path element. The map is used to convert
     * between Windows drive letters and Unix paths. If no map is configured,
     * then the input string is returned unchanged.
     *
     * @param Path $elem The path element to apply the map to.
     * @return string Updated element.
     */
    private function mapElement($elem)
    {
        if (!empty($this->prefixMap)) {
            // Iterate over the map entries and apply each one.
            // Stop when one of the entries actually changes the element.
            foreach ($this->prefixMap as $entry) {
                $newElem = $entry->apply($elem);

                // Note I'm using "!=" to see if we got a new object back from
                // the apply method.
                if ($newElem != $elem) {
                    $elem = $newElem;
                    break; // We applied one, so we're done
                }
            }
        }
        return $elem;
    }

    /**
     * Add a mapper to convert the file names.
     * @return Mapper
     * @throws BuildException
     */
    public function createMapper()
    {
        if ($this->mapper != null) {
            throw new BuildException("Cannot define more than one mapper", $this->getLocation());
        }
        $this->mapper = new Mapper($this->getProject());
        return $this->mapper;
    }

    /**
     * Validate that all our parameters have been properly initialized.
     *
     * @throws BuildException if something is not set up properly.
     */
    private function validateSetup()
    {

        if ($this->path == null) {
            throw new BuildException("You must specify at least a path to convert");
        }
        // Determine the separator strings.  The dirsep and pathsep attributes
        // override the targetOS settings.
        $dsep = PhingFile::$separator;
        $psep = PhingFile::$pathSeparator;

        if ($this->targetOS !== null) {
            $psep = $this->targetWindows ? ";" : ":";
            $dsep = $this->targetWindows ? "\\" : "/";
        }
        if ($this->pathSep !== null) {
            // override with pathsep=
            $psep = $this->pathSep;
        }
        if ($this->dirSep !== null) {
            // override with dirsep=
            $dsep = $this->dirSep;
        }
        $this->pathSep = $psep;
        $this->dirSep = $dsep;
    }

    /**
     * Creates an exception that indicates that this XML element must not have
     * child elements if the refid attribute is set.
     * @return BuildException.
     */
    private function noChildrenAllowed()
    {
        return new BuildException("You must not specify nested elements when using the refid attribute.");
    }

}

/**
 * Helper class, holds the nested &lt;map&gt; values. Elements will look like
 * this: &lt;map from=&quot;d:&quot; to=&quot;/foo&quot;/&gt;
 *
 * When running on windows, the prefix comparison will be case
 * insensitive.
 */
class MapEntry
{
    private $from = null;
    private $to = null;
    private $onWindows;

    /**
     * Set the &quot;from&quot; attribute of the map entry.
     * @param string $from the prefix string to search for; required.
     * Note that this value is case-insensitive when the build is
     * running on a Windows platform and case-sensitive when running on
     * a Unix platform.
     */
    public function setFrom($from)
    {
        $this->from = $from;
    }

    /**
     * Set the replacement text to use when from is matched; required.
     * @param string $to new prefix.
     */
    public function setTo($to)
    {
        $this->to = $to;
    }

    public function setOnWindows($onWindows)
    {
        $this->onWindows = $onWindows;
    }

    /**
     * Apply this map entry to a given path element.
     *
     * @param Path $elem element to process.
     * @return string Updated path element after mapping.
     * @throws BuildException
     */
    public function apply($elem)
    {
        if ($this->from === null || $this->to === null) {
            throw new BuildException("Both 'from' and 'to' must be set in a map entry");
        }

        $cmpElem = $this->onWindows ? str_replace('\\', '/', strtolower($elem)) : $elem;
        $cmpFrom = $this->onWindows ? str_replace('\\', '/', strtolower($this->from)) : $this->from;

        // If the element starts with the configured prefix, then
        // convert the prefix to the configured 'to' value.
        return StringHelper::startsWith($cmpFrom, $cmpElem)
            ? $this->to . StringHelper::substring($elem, strlen($this->from))
            : $elem;
    }
}

include_once 'phing/types/EnumeratedAttribute.php';

/**
 * An enumeration of supported targets:
 * "windows", "unix", "netware", and "os/2".
 */
class TargetOs extends EnumeratedAttribute
{
    /**
     * @return string[] the list of values for this enumerated attribute.
     */
    public function getValues()
    {
        return array("windows", "unix", "netware", "os/2", "tandem");
    }
}
