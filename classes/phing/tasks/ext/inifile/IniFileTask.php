<?php

/**
 * INI file modification task for Phing, the PHP build tool.
 *
 * Based on http://ant-contrib.sourceforge.net/tasks/tasks/inifile.html
 *
 * PHP version 5
 *
 * @category Tasks
 * @package  phing.tasks.ext
 * @author   Ken Guest <kguest@php.net>
 * @license  LGPL v3 or later http://www.gnu.org/licenses/lgpl.html
 * @link     http://www.phing.info/
 */

require_once 'IniFileGet.php';
require_once 'IniFileSet.php';
require_once 'IniFileRemove.php';
require_once 'IniFileConfig.php';
/**
 * InifileTask
 *
 * @category Tasks
 * @package  phing.tasks.ext
 * @author   Ken Guest <ken@linux.ie>
 * @license  LGPL (see http://www.gnu.org/licenses/lgpl.html)
 * @link     InifileTask.php
 */
class IniFileTask extends Task
{
    /**
     * Source file
     *
     * @var string|null
     */
    protected $source = null;
/**
     * Dest file
     *
     * @var string|null
     */
    protected $dest = null;
/**
     * Whether to halt phing on error.
     *
     * @var bool
     */
    protected $haltonerror = false;
/**
     * Gets
     *
     * @var array
     */
    protected $gets = [];
/**
     * Sets
     *
     * @var array
     */
    protected $sets = [];
/**
     * Removals
     *
     * @var array
     */
    protected $removals = [];
/**
     * IniFileConfig instance
     *
     * @var IniFileConfig
     */
    protected $ini = null;
/**
     * Taskname for logger
     *
     * @var string
     */
    protected $taskName = 'IniFile';
/**
     * Verbose
     *
     * @var bool
     */
    protected $verbose = false;
/**
     * Check file to be read from
     *
     * @param string $readFile Filename
     *
     * @return bool
     */
    public function checkReadFile($readFile)
    {
        if (null === $readFile) {
            return false;
        }
        if (!file_exists($readFile)) {
            $msg = "$readFile does not exist.";
            if ($this->haltonerror) {
                throw new BuildException($msg);
            }
            $this->log($msg, Project::MSG_ERR);
            return false;
        }
        if (!is_readable($readFile)) {
            $msg = "$readFile is not readable.";
            if ($this->haltonerror) {
                throw new BuildException($msg);
            }
            $this->log($msg, Project::MSG_ERR);
            return false;
        }
        $this->ini->read($readFile);
        $this->log("Read from $readFile");
        return true;
    }

    /**
     * Check file to write to
     *
     * @param string $writeFile Filename
     *
     * @return bool
     */
    public function checkWriteFile($writeFile)
    {
        if (file_exists($writeFile) && !is_writable($writeFile)) {
            $msg = "$writeFile is not writable";
            if ($this->haltonerror) {
                throw new BuildException($msg);
            }
            $this->log($msg, Project::MSG_ERR);
            return false;
        }
        return true;
    }

    /**
     * The main entry point method.
     *
     * @return void
     */
    public function main()
    {
        $this->ini = new IniFileConfig();
        $readFile = null;
        $writeFile = null;
        if (null !== $this->source && null === $this->dest) {
            $readFile = $this->source;
        } elseif (null !== $this->dest && null === $this->source) {
            $readFile = $this->dest;
        } else {
            $readFile = $this->source;
        }

        if (null !== $this->dest) {
            $writeFile = $this->dest;
        } elseif (null !== $this->source) {
            $writeFile = $this->source;
        } else {
            $writeFile = $this->dest;
        }

        if ($readFile === null && $writeFile === null) {
            $msg = "Neither source nor dest is set";
            if ($this->haltonerror) {
                throw new BuildException($msg);
            }
            $this->log($msg, Project::MSG_ERR);
            return;
        }

        if (!$this->checkReadFile($readFile)) {
            return;
        }

        if (!$this->checkWriteFile($writeFile)) {
            return;
        }

        $this->enumerateGets();
        $this->enumerateSets();
        $this->enumerateRemoves();
        if (count($this->sets) || count($this->removals)) {
            try {
                $this->ini->write($writeFile);
                $this->log("Wrote to $writeFile");
            } catch (Exception $ex) {
                $msg = $ex->getMessage();
                if ($this->haltonerror) {
                    throw new BuildException($msg);
                }
                $this->log($msg, Project::MSG_ERR);
            }
        }
    }

    /**
     * Work through all Get commands.
     *
     * @return void
     */
    public function enumerateGets()
    {
        foreach ($this->gets as $get) {
            $outProperty = $get->getOutputProperty();
            $property = $get->getProperty();
            $section = $get->getSection();
            $value = '';
            if ($property === null) {
                throw new BuildException("property must be set");
            }
            if ($outProperty === null) {
                throw new BuildException("outputproperty must be set");
            }
            if ($section === null) {
                throw new BuildException("section must be set");
            }
            try {
                $value = $this->ini->get($section, $property);
            } catch (RuntimeException $ex) {
                $this->logDebugOrMore(sprintf('%s: section = %s; key = %s', $ex->getMessage(), $section, $property));
            } finally {
                if ($value === '') {
                    $value = $get->getDefault();
                }
            }

            $project = $this->getProject();
            $project->setProperty($outProperty, $value);
            $this->logDebugOrMore(sprintf('Set property %s to value \'%s\' read from key %s in section %s', $outProperty, $value, $property, $section));
        }
    }

    /**
     * Work through all Set commands.
     *
     * @return void
     */
    public function enumerateSets()
    {
        foreach ($this->sets as $set) {
            $value = $set->getValue();
            $key = $set->getProperty();
            $section = $set->getSection();
            $operation = $set->getOperation();
            if ($value !== null) {
                try {
                    $this->ini->set($section, $key, $value);
                    $this->logDebugOrMore("[$section] $key set to $value");
                } catch (Exception $ex) {
                    $this->log("Error setting value for section '" . $section .
                    "', key '" . $key . "'", Project::MSG_ERR);
                    $this->logDebugOrMore($ex->getMessage());
                }
            } elseif ($operation !== null) {
                $v = $this->ini->get($section, $key);
            // value might be wrapped in quotes with a semicolon at the end
                if (!is_numeric($v)) {
                    if (preg_match('/^"(\d*)";?$/', $v, $match)) {
                        $v = $match[1];
                    } elseif (preg_match("/^'(\d*)';?$/", $v, $match)) {
                        $v = $match[1];
                    } else {
                        $this->log("Value $v is not numeric. Skipping $operation operation.");
                        continue;
                    }
                }
                if ($operation == '+') {
                    ++$v;
                } elseif ($operation == '-') {
                    --$v;
                } else {
                    if (($operation != '-') && ($operation != '+')) {
                        $msg = "Unrecognised operation $operation";
                        if ($this->haltonerror) {
                            throw new BuildException($msg);
                        }
                        $this->log($msg, Project::MSG_ERR);
                    }
                }
                try {
                    $this->ini->set($section, $key, $v);
                    $this->logDebugOrMore("[$section] $key set to $v");
                } catch (Exception $ex) {
                    $this->log("Error setting value for section '" . $section .
                                "', key '" . $key . "'");
                    $this->logDebugOrMore($ex->getMessage());
                }
            } else {
                $this->log("Set: value and operation are both not set", Project::MSG_ERR);
            }
        }
    }

    /**
     * Work through all Remove commands.
     *
     * @return void
     */
    public function enumerateRemoves()
    {
        foreach ($this->removals as $remove) {
            $key = $remove->getProperty();
            $section = $remove->getSection();
            if ($section == '') {
                $this->log("Remove: section must be set", Project::MSG_ERR);
                continue;
            }
            $this->ini->remove($section, $key);
            if (($section != '') && ($key != '')) {
                $this->logDebugOrMore("$key in section [$section] has been removed.");
            } elseif (($section != '') && ($key == '')) {
                $this->logDebugOrMore("[$section] has been removed.");
            }
        }
    }

    /**
     * Set Source property
     *
     * @param string $source Name of originating ini file to parse
     *
     * @return void
     */
    public function setSource($source)
    {
        $this->source = $source;
    }

    /**
     * Set Dest property
     *
     * @param string $dest Destination filename to write ini contents to.
     *
     * @return void
     */
    public function setDest($dest)
    {
        $this->dest = $dest;
    }

    /**
     * Set haltonerror attribute.
     *
     * @param string $halt 'yes', or '1' to halt.
     *
     * @return void
     */
    public function setHaltonerror($halt)
    {
        $this->haltonerror = StringHelper::booleanValue($halt);
    }

    /**
     * Set verbose attribute.
     *
     * Screech like a Camaar fishwife...
     *
     * @param boolean $verbose Verbose?
     *
     * @return void
     */
    public function setVerbose($verbose)
    {
        $this->verbose = StringHelper::booleanValue($verbose);
    }

    /**
     * Create a Get method
     *
     * @return IniFileGet
     */
    public function createGet()
    {
        $get = new IniFileGet();
        $this->gets[] = $get;
        return $get;
    }

    /**
     * Create a Set method
     *
     * @return IniFileSet
     */
    public function createSet()
    {
        $set = new IniFileSet();
        $this->sets[] = $set;
        return $set;
    }

    /**
     * Create a Remove method
     *
     * @return IniFileRemove
     */
    public function createRemove()
    {
        $remove = new IniFileRemove();
        $this->removals[] = $remove;
        return $remove;
    }

    /**
     * Log message at Debug level. If verbose prop is set, also log it at normal
     *
     * @param string $message Message to log
     *
     * @return bool False if message is only logged at debug level.
     */
    public function logDebugOrMore($message)
    {
        $this->log($message, Project::MSG_DEBUG);
        if ($this->verbose) {
            $this->log($message);
            return true;
        }
        return false;
    }
}
