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

require_once 'phing/Task.php';
require_once 'phing/BuildException.php';
require_once 'phing/tasks/ext/git/GitBaseTask.php';

/**
 * Repository archive task
 *
 * @author Siad Ardroumli <siad.ardroumli@gmail.com>
 * @package phing.tasks.ext.git
 * @see VersionControl_Git
 */
class GitArchiveTask extends GitBaseTask
{
    /** @var string $format */
    private $format;

    /** @var PhingFile $output */
    private $output;

    /** @var string $prefix */
    private $prefix;

    /** @var string $treeish */
    private $treeish;

    /** @var string $remoteRepo */
    private $remoteRepo;

    /**
     * The main entry point for the task
     */
    public function main()
    {
        if (null === $this->getRepository() && false === $this->getRemoteRepo()) {
            throw new BuildException('"repository" is required parameter');
        }

        if (null === $this->getTreeish()) {
            throw new BuildException('"treeish" is required parameter');
        }

        $cmd = $this->getGitClient(false, $this->getRepository() ?? './')
            ->getCommand('archive')
            ->setOption('prefix', $this->prefix)
            ->setOption('output', $this->output !== null ? $this->output->getPath() : false)
            ->setOption('format', $this->format)
            ->setOption('remote', $this->remoteRepo)
            ->addArgument($this->treeish);

        $this->log('Git command : ' . $cmd->createCommandString(), Project::MSG_DEBUG);

        $cmd->execute();

        $msg = 'git-archive: archivating ' . '"' . $this->getRepository() . '" repository (' . $this->getTreeish() . ')';
        $this->log($msg, Project::MSG_INFO);
    }

    /**
     * @return string
     */
    public function getFormat(): ?string
    {
        return $this->format;
    }

    /**
     * @param string $format
     */
    public function setFormat($format)
    {
        $this->format = $format;
    }

    /**
     * @return PhingFile
     */
    public function getOutput(): ?\PhingFile
    {
        return $this->output;
    }

    /**
     * @param PhingFile $output
     */
    public function setOutput(PhingFile $output)
    {
        $this->output = $output;
    }

    /**
     * @return string
     */
    public function getPrefix(): ?string
    {
        return $this->prefix;
    }

    /**
     * @param string $prefix
     */
    public function setPrefix($prefix)
    {
        $this->prefix = $prefix;
    }

    /**
     * @return string
     */
    public function getTreeish(): ?string
    {
        return $this->treeish;
    }

    /**
     * @param string $treeish
     */
    public function setTreeish($treeish)
    {
        $this->treeish = $treeish;
    }

    /**
     * @return string
     */
    public function getRemoteRepo(): ?string
    {
        return $this->remoteRepo;
    }

    /**
     * @param string $repo
     */
    public function setRemoteRepo($repo)
    {
        $this->remoteRepo = $repo;
    }
}
