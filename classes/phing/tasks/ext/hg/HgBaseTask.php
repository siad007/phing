<?php

use Siad007\VersionControl\HG\Factory;

/**
 * Utilise Mercurial from within Phing.
 *
 * PHP Version 5.4
 *
 * @category Tasks
 * @package  phing.tasks.ext
 * @author   Ken Guest <kguest@php.net>
 * @license  LGPL (see http://www.gnu.org/licenses/lgpl.html)
 * @link     https://github.com/kenguest/Phing-HG
 */

/**
 * Base task for integrating phing and mercurial.
 *
 * @category Tasks
 * @package  phing.tasks.ext.hg
 * @author   Ken Guest <kguest@php.net>
 * @license  LGPL (see http://www.gnu.org/licenses/lgpl.html)
 * @link     HgBaseTask.php
 */
abstract class HgBaseTask extends Task
{
    /**
     * Insecure argument
     *
     * @var string
     */
    protected $insecure = '';

    /**
     * Repository directory
     *
     * @var string
     */
    protected $repository = '';

    /**
     * Whether to be quiet... --quiet argument.
     *
     * @var bool
     */
    protected $quiet = false;

    /**
     * Username.
     *
     * @var string
     */
    protected $user = '';

    public static $factory = null;
    /**
     * Set repository attribute
     *
     * @param string $repository Repository
     *
     * @return void
     */
    public function setRepository($repository): void
    {
        $this->repository = $repository;
    }


    /**
     * Set the quiet attribute --quiet
     *
     * @param string $quiet yes|no|true|false|1|0
     *
     * @return void
     */
    public function setQuiet($quiet): void
    {
        $this->quiet = StringHelper::booleanValue($quiet);
    }

    /**
     * Get the quiet attribute value.
     *
     * @return bool
     */
    public function getQuiet(): bool
    {
        return $this->quiet;
    }

    /**
     * Get Repository attribute/directory.
     *
     * @return string
     */
    public function getRepository(): string
    {
        return $this->repository;
    }

    /**
     * Set insecure attribute
     *
     * @param string $insecure 'yes', etc.
     *
     * @return void
     */
    public function setInsecure($insecure): void
    {
        $this->insecure = StringHelper::booleanValue($insecure);
    }

    /**
     * Get 'insecure' attribute value. (--insecure or null)
     *
     * @return string
     */
    public function getInsecure(): string
    {
        return $this->insecure;
    }

    /**
     * Set user attribute
     *
     * @param string $user username/email address.
     *
     * @return void
     */
    public function setUser($user): void
    {
        $this->user = $user;
    }

    /**
     * Get username attribute.
     *
     * @return string
     */
    public function getUser(): string
    {
        return $this->user;
    }

    /**
     * Check provided repository directory actually is an existing directory.
     *
     * @param string $dir Repository directory
     *
     * @return bool
     * @throws BuildException
     */
    public function checkRepositoryIsDirAndExists($dir): bool
    {
        if (file_exists($dir)) {
            if (!is_dir($dir)) {
                throw new BuildException("Repository '$dir' is not a directory.");
            }
        } else {
            throw new BuildException("Repository directory '$dir' does not exist.");
        }
        return true;
    }

    /**
     * Initialise the task.
     *
     * @return void
     */
    public function init(): void
    {
        @include_once 'vendor/autoload.php';
    }

    public function getFactoryInstance($command, array $options = [])
    {
        $vchq = Factory::class;
        self::$factory = $vchq::getInstance($command, $options);
        return self::$factory;
    }
}
