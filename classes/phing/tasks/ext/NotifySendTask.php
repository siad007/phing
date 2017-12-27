<?php
/**
 * Utilise notify-send from within Phing.
 *
 * PHP Version 5
 *
 * @category Tasks
 * @package  phing.tasks.ext
 * @author   Ken Guest <ken@linux.ie>
 * @license  LGPL (see http://www.gnu.org/licenses/lgpl.html)
 * @link     https://github.com/kenguest/Phing-NotifySendTask
 */

require_once 'phing/Task.php';

/**
 * NotifySendTask
 *
 * @category Tasks
 * @package  phing.tasks.ext
 * @author   Ken Guest <ken@linux.ie>
 * @license  LGPL (see http://www.gnu.org/licenses/lgpl.html)
 * @link     NotifySendTask.php
 */
class NotifySendTask extends Task
{
    protected $msg = null;
    protected $title = null;
    protected $icon = 'info';
    protected $silent = false;

    /**
     * Set icon attribute
     *
     * @param string $icon name/location of icon
     *
     * @return void
     */
    public function setIcon($icon): void
    {
        switch ($icon) {
            case 'info':
            case 'error':
            case 'warning':
                $this->icon = $icon;
                break;
            default:
                if (file_exists($icon) && is_file($icon)) {
                    $this->icon = $icon;
                } else {
                    if (isset($this->log)) {
                        $this->log(
                            sprintf(
                                "%s is not a file. Using default icon instead.",
                                $icon
                            ),
                            Project::MSG_WARN
                        );
                    }
                }
        }
    }

    /**
     * Get icon to be used (filename or generic name)
     *
     * @return string
     */
    public function getIcon(): string
    {
        return $this->icon;
    }

    /**
     * Set to a true value to not execute notifysend command.
     *
     * @param string $silent Don't execute notifysend? Truthy value.
     *
     * @return void
     */
    public function setSilent($silent): void
    {
        $this->silent = StringHelper::booleanValue($silent);
    }

    /**
     * Set title attribute
     *
     * @param string $title Title to display
     *
     * @return void
     */
    public function setTitle($title): void
    {
        $this->title = $title;
    }

    /**
     * Get Title
     *
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * Set msg attribute
     *
     * @param string $msg Message
     *
     * @return void
     */
    public function setMsg($msg): void
    {
        $this->msg = $msg;
    }

    /**
     * Get message.
     *
     * @return string
     */
    public function getMsg(): string
    {
        return $this->msg;
    }

    /**
     * The main entry point method.
     *
     * @throws BuildException
     * @return void
     */
    public function main(): void
    {
        $msg = '';
        $title = 'Phing';
        $executable = 'notify-send';

        if ($this->title != '') {
            $title = "'" . $this->title . "'";
        }

        if ($this->msg != '') {
            $msg = "'" . $this->msg . "'";
        }

        $cmd = $executable . ' -i ' . $this->icon . ' ' . $title . ' ' . $msg;

        $this->log(sprintf("Title: '%s'", $title), Project::MSG_DEBUG);
        $this->log(sprintf("Message: '%s'", $msg), Project::MSG_DEBUG);
        $this->log($msg, Project::MSG_INFO);

        $this->log(sprintf("cmd: %s", $cmd), Project::MSG_DEBUG);
        if (!$this->silent) {
            $fs = FileSystem::getFileSystem();
            if ($fs->which($executable) !== false) {
                exec(escapeshellcmd($cmd), $output, $return);
                if ($return !== 0) {
                    throw new BuildException("Notify task failed.");
                }
            } else {
                $this->log("Executable ($executable) not found", Project::MSG_DEBUG);
            }
        } else {
            $this->log("Silent flag set; not executing", Project::MSG_DEBUG);
        }
    }
}

// vim:set et ts=4 sw=4:
