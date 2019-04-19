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
 * VersionTask
 *
 * Increments a three-part version number from a given file
 * and writes it back to the file.
 * Incrementing is based on given releasetype, which can be one
 * of Major, Minor and Bugfix.
 * Resulting version number is also published under supplied property.
 *
 * @author  Mike Wittje <mw@mike.wittje.de>
 * @package phing.tasks.ext
 */
class VersionTask extends Task
{

    /**
     * The name of the property in which the build number is stored.
     */
    const DEFAULT_PROPERTY_NAME = "build.version";

    /**
     * The default filename to use if no file specified.
     */
    const DEFAULT_FILENAME = self::DEFAULT_PROPERTY_NAME;

    private $startingVersion = '0.0.0';

    /**
     * Property for Releasetype
     *
     * @var string $releasetype
     */
    private $releasetype;

    /**
     * Property for File
     *
     * @var PhingFile file
     */
    private $file;

    /**
     * Property to be set
     *
     * @var string $property
     */
    private $property;

    /* Allowed Releastypes */
    const RELEASETYPE_MAJOR = 'MAJOR';
    const RELEASETYPE_MINOR = 'MINOR';
    const RELEASETYPE_BUGFIX = 'BUGFIX';

    private $propFile = false;

    /**
     * @param string $startingVersion
     */
    public function setStartingVersion($startingVersion)
    {
        $this->startingVersion = $startingVersion;
    }

    /**
     * Set Property for Releasetype (Minor, Major, Bugfix)
     *
     * @param string $releasetype
     */
    public function setReleasetype($releasetype)
    {
        $this->releasetype = strtoupper($releasetype);
    }

    /**
     * Set Property for File containing versioninformation
     *
     * @param PhingFile $file
     */
    public function setFile(PhingFile $file)
    {
        $this->file = $file;
    }

    /**
     * Set name of property to be set
     *
     * @param  $property
     * @return void
     */
    public function setProperty($property)
    {
        $this->property = $property;
    }

    /**
     * @param bool $isPropFile
     */
    public function setPropFile($isPropFile)
    {
        $this->propFile = $isPropFile;
    }

    /**
     * Main-Method for the Task
     *
     * @return void
     * @throws BuildException
     */
    public function main()
    {
        // check supplied attributes
        $this->checkReleasetype();
        $this->checkFile();
        $this->checkProperty();

        // read file (or use fallback value if file is empty)
        try {
            if ($this->propFile) {
                $properties = $this->loadProperties();
                $content = $properties->getProperty($this->property);
            } else {
                $content = trim($this->file->contents());
            }
            if (empty($content)) {
                $content = $this->startingVersion;
            }
        } catch (Exception $e) {
            throw new BuildException($e);
        }

        // get new version
        $this->log("Old version: $content", Project::MSG_INFO);
        $newVersion = $this->getVersion($content);
        $this->log("New version: $newVersion", Project::MSG_INFO);

        if ($this->propFile) {
            $properties->put($this->property, $newVersion);
            try {
                $header = "Build Number for PHING. Do not edit!";
                $properties->store($this->file, $header);
            } catch (IOException $ioe) {
                $message = "Error while writing " . $this->file;
                throw new BuildException($message, $ioe);
            }
        } else {
            // write new Version to file
            file_put_contents($this->file, $newVersion . $this->getProject()->getProperty('line.separator'));
        }

        //Finally set the property
        $this->getProject()->setNewProperty($this->property, $newVersion);
    }

    /**
     * Utility method to load properties from file.
     *
     * @return Properties the loaded properties
     * @throws BuildException
     */
    private function loadProperties()
    {
        try {
            $properties = new Properties();
            $properties->load($this->file);
        } catch (IOException $ioe) {
            throw new BuildException($ioe);
        }
        return $properties;
    }

    /**
     * Returns new version number corresponding to Release type
     *
     * @param  string $oldVersion
     * @return string
     */
    public function getVersion($oldVersion = '')
    {
        preg_match('#^(?<PREFIX>v)?(?<MAJOR>\d+)?(?:\.(?<MINOR>\d+))?(?:\.(?<BUGFIX>\d+))?#', $oldVersion, $version);

        // Setting values if not captured
        $version['PREFIX'] = $version['PREFIX'] ?? '';
        $version[self::RELEASETYPE_MAJOR] = $version[self::RELEASETYPE_MAJOR] ?? '0';
        $version[self::RELEASETYPE_MINOR] = $version[self::RELEASETYPE_MINOR] ?? '0';
        $version[self::RELEASETYPE_BUGFIX] = $version[self::RELEASETYPE_BUGFIX] ?? '0';

        // Resetting Minor and/or Bugfix number according to release type
        switch ($this->releasetype) {
            case self::RELEASETYPE_MAJOR:
                $version[self::RELEASETYPE_MINOR] = '0';
            // no break
            case self::RELEASETYPE_MINOR:
                $version[self::RELEASETYPE_BUGFIX] = '0';
                break;
        }

        $version[$this->releasetype]++;

        return sprintf(
            '%s%u.%u.%u',
            $version['PREFIX'],
            $version[self::RELEASETYPE_MAJOR],
            $version[self::RELEASETYPE_MINOR],
            $version[self::RELEASETYPE_BUGFIX]
        );
    }

    /**
     * checks releasetype attribute
     *
     * @return void
     * @throws BuildException
     */
    private function checkReleasetype()
    {
        // check Releasetype
        if (null === $this->releasetype) {
            throw new BuildException('releasetype attribute is required', $this->getLocation());
        }
        // known releasetypes
        $releaseTypes = [
            self::RELEASETYPE_MAJOR,
            self::RELEASETYPE_MINOR,
            self::RELEASETYPE_BUGFIX
        ];

        if (!in_array($this->releasetype, $releaseTypes)) {
            throw new BuildException(
                sprintf(
                    'Unknown Releasetype %s..Must be one of Major, Minor or Bugfix',
                    $this->releasetype
                ),
                $this->getLocation()
            );
        }
    }

    /**
     * checks file attribute
     *
     * @return void
     * @throws BuildException
     */
    private function checkFile()
    {
        $fileUtils = new FileUtils();
        // check File
        try {
            if (null === $this->file) {
                $this->file = $fileUtils->resolveFile($this->getProject()->getBasedir(), self::DEFAULT_FILENAME);
            }
            if (!$this->file->exists()) {
                $this->file->createNewFile();
                $this->log(
                    'Creating file "' . $this->file->getName() . '" since it was not present',
                    Project::MSG_INFO
                );
            }
        } catch (IOException $ioe) {
            $message = $this->file . " doesn't exist and new file can't be created.";
            throw new BuildException($message, $ioe);
        }

        if (!$this->file->canRead()) {
            $message = "Unable to read from " . $this->file . ".";
            throw new BuildException($message);
        }
        if (!$this->file->canWrite()) {
            $message = "Unable to write to " . $this->file . ".";
            throw new BuildException($message);
        }
    }

    private function checkProperty()
    {
        if ($this->property === null) {
            $this->property = self::DEFAULT_PROPERTY_NAME;
        }
    }
}
