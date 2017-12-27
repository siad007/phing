<?php
/**
 * Class for collecting details for setting values in ini file
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

/**
 * InifileSet
 *
 * @category Tasks
 * @package  phing.tasks.ext
 * @author   Ken Guest <ken@linux.ie>
 * @license  LGPL (see http://www.gnu.org/licenses/lgpl.html)
 * @link     InifileSet.php
 */
class IniFileSet
{
    /**
     * Property
     *
     * @var string
     */
    protected $property = null;

    /**
     * Section
     *
     * @var string
     */
    protected $section = null;

    /**
     * Value
     *
     * @var mixed
     */
    protected $value = null;

    /**
     * Operation
     *
     * @var mixed
     */
    protected $operation = null;

    /**
     * Set Operation
     *
     * @param string $operation +/-
     *
     * @return void
     */
    public function setOperation($operation): void
    {
        $this->operation = $operation;
    }

    /**
     * Get Operation
     *
     * @return string
     */
    public function getOperation(): ?string
    {
        return $this->operation;
    }

    /**
     * Set Section name
     *
     * @param string $section Name of section in ini file
     *
     * @return void
     */
    public function setSection($section): void
    {
        $this->section = trim($section);
    }

    /**
     * Set Property
     *
     * @param string $property property/key name
     *
     * @return void
     */
    public function setProperty($property): void
    {
        $this->property = $property;
    }

    /**
     * Set Value
     *
     * @param string $value Value to set for key in ini file
     *
     * @return void
     */
    public function setValue($value): void
    {
        $this->value = $value;
    }

    /**
     * Get Property
     *
     * @return string
     */
    public function getProperty(): ?string
    {
        return $this->property;
    }

    /**
     * Get Value
     *
     * @return string
     */
    public function getValue(): ?string
    {
        return $this->value;
    }
    /**
     * Get Section
     *
     * @return string
     */
    public function getSection(): ?string
    {
        return $this->section;
    }
}
