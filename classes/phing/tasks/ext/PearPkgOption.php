<?php

/**
 * Generic option class is used for non-complex options.
 *
 * @package  phing.tasks.ext
 */
class PearPkgOption
{
    private $name;
    private $value;

    /**
     * @param $v
     */
    public function setName($v): void
    {
        $this->name = $v;
    }

    public function getName(): void
    {
        return $this->name;
    }

    /**
     * @param $v
     */
    public function setValue($v): void
    {
        $this->value = $v;
    }

    public function getValue(): void
    {
        return $this->value;
    }

    /**
     * @param $txt
     */
    public function addText($txt): void
    {
        $this->value = trim($txt);
    }
}
