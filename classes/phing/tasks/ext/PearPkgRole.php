<?php

/**
 * Encapsulates file roles
 *
 * @package phing.tasks.ext
 */
class PearPkgRole
{
    /**
     * @var string
     */
    private $extension;

    /**
     * @var string
     */
    private $role;

    /**
     * Sets the file extension
     * @param string $extension
     */
    public function setExtension($extension): void
    {
        $this->extension = $extension;
    }

    /**
     * Retrieves the file extension
     * @return string
     */
    public function getExtension(): string
    {
        return $this->extension;
    }

    /**
     * Sets the role
     * @param string $role
     */
    public function setRole($role): void
    {
        $this->role = $role;
    }

    /**
     * Retrieves the role
     * @return string
     */
    public function getRole(): string
    {
        return $this->role;
    }
}
