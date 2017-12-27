<?php

/**
 * "Inner" class used for nested xml command line definitions.
 *
 * @package phing.types
 */
class CommandlineArgument
{
    private $parts = [];
    private $outer;
    public $escape = false;

    /**
     * @param Commandline $outer
     */
    public function __construct(Commandline $outer)
    {
        $this->outer = $outer;
    }

    /**
     * @param bool $escape
     */
    public function setEscape($escape): void
    {
        $this->escape = $escape;
    }

    /**
     * Sets a single commandline argument.
     *
     * @param string $value a single commandline argument.
     */
    public function setValue($value): void
    {
        $this->parts = [$value];
    }

    /**
     * Line to split into several commandline arguments.
     *
     * @param string $line line to split into several commandline arguments
     */
    public function setLine($line): void
    {
        if ($line === null) {
            return;
        }
        $this->parts = $this->outer->translateCommandline($line);
    }

    /**
     * Sets a single commandline argument and treats it like a
     * PATH - ensures the right separator for the local platform
     * is used.
     *
     * @param string $value a single commandline argument
     */
    public function setPath($value): void
    {
        $this->parts = [(string)$value];
    }

    /**
     * Sets a single commandline argument to the absolute filename
     * of the given file.
     *
     * @param PhingFile $value
     * @internal param a $value single commandline argument.
     */
    public function setFile(PhingFile $value): void
    {
        $this->parts = [$value->getAbsolutePath()];
    }

    /**
     * Returns the parts this Argument consists of.
     * @return array string[]
     */
    public function getParts(): array
    {
        return $this->parts;
    }
}
