<?php
/**
 * $Id$
 *
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

require_once 'phing/system/io/PhingFile.php';

/**
 * A wrapper for the implementations of PHPUnit2ResultFormatter.
 *
 * @author Michiel Rook <mrook@php.net>
 * @version $Id$
 * @package phing.tasks.ext.phpunit
 * @since 2.1.0
 */
class FormatterElement
{
    /** @var PHPUnitResultFormatter $fomatter */
    protected $formatter;

    protected $type = "";

    protected $useFile = true;

    protected $toDir = ".";

    protected $outfile = "";

    protected $parent;

    /**
     * Sets parent task
     * @param Task $parent Calling Task
     */
    public function setParent($parent): void
    {
        $this->parent = $parent;
    }

    /**
     * Loads a specific formatter type
     * @param string $type
     */
    public function setType($type): void
    {
        $this->type = $type;
    }

    /**
     * Loads a specific formatter class
     * @param $className
     */
    public function setClassName($className): void
    {
        $classNameNoDot = Phing::import($className);

        $this->formatter = new $classNameNoDot();
    }

    /**
     * Sets whether to store formatting results in a file
     * @param $useFile
     */
    public function setUseFile($useFile): void
    {
        $this->useFile = $useFile;
    }

    /**
     * Returns whether to store formatting results in a file
     */
    public function getUseFile(): bool
    {
        return $this->useFile;
    }

    /**
     * Sets output directory
     * @param string $toDir
     * @throws IOException
     */
    public function setToDir($toDir): void
    {
        if (!is_dir($toDir)) {
            $toDir = new PhingFile($toDir);
            $toDir->mkdirs();
        }

        $this->toDir = $toDir;
    }

    /**
     * Returns output directory
     * @return string
     */
    public function getToDir(): string
    {
        return $this->toDir;
    }

    /**
     * Sets output filename
     * @param string $outfile
     */
    public function setOutfile($outfile): void
    {
        $this->outfile = $outfile;
    }

    /**
     * Returns output filename
     * @return string
     */
    public function getOutfile(): ?string
    {
        if ($this->outfile) {
            return $this->outfile;
        } else {
            return $this->formatter->getPreferredOutfile() . $this->getExtension();
        }
    }

    /**
     * Returns extension
     * @return string
     */
    public function getExtension(): string
    {
        return $this->formatter->getExtension();
    }

    /**
     * Returns formatter object
     * @throws BuildException
     * @return mixed
     */
    public function getFormatter()
    {
        if ($this->formatter !== null) {
            return $this->formatter;
        }

        if (class_exists('PHPUnit_Runner_Version')) {
            if ($this->type === "summary") {
                $this->formatter = new SummaryPHPUnitResultFormatter5($this->parent);
            } elseif ($this->type === "clover") {
                $this->formatter = new CloverPHPUnitResultFormatter5($this->parent);
            } elseif ($this->type === "xml") {
                $this->formatter = new XMLPHPUnitResultFormatter5($this->parent);
            } elseif ($this->type === "plain") {
                $this->formatter = new PlainPHPUnitResultFormatter5($this->parent);
            } elseif ($this->type === "crap4j") {
                $this->formatter = new Crap4JPHPUnitResultFormatter5($this->parent);
            } else {
                throw new BuildException("Formatter '" . $this->type . "' not implemented");
            }
        } else {
            if ($this->type === "summary") {
                $this->formatter = new SummaryPHPUnitResultFormatter($this->parent);
            } elseif ($this->type === "clover") {
                $this->formatter = new CloverPHPUnitResultFormatter($this->parent);
            } elseif ($this->type === "xml") {
                $this->formatter = new XMLPHPUnitResultFormatter($this->parent);
            } elseif ($this->type === "plain") {
                $this->formatter = new PlainPHPUnitResultFormatter($this->parent);
            } elseif ($this->type === "crap4j") {
                $this->formatter = new Crap4JPHPUnitResultFormatter($this->parent);
            } else {
                throw new BuildException("Formatter '" . $this->type . "' not implemented");
            }
        }

        return $this->formatter;
    }
}
