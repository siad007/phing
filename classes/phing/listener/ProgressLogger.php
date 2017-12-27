<?php

use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\ConsoleOutput;

class ProgressLogger extends AnsiColorLogger
{
    /**
     * @var ProgressBar
     */
    private $bar;
    
    private $numTargets = 0;
    private $remTargets = 0;
    private $numTasks = 0;
    private $remTasks = 0;

    public function __construct()
    {
        parent::__construct();
        
        $this->bar = new ProgressBar(new ConsoleOutput());
        $this->bar->setBarWidth(80);
        $this->bar->setFormat("<fg=cyan>Buildfile: %buildfile%</>\n" .
            "  <fg=green>%current%/%max% [%bar%] %percent:3s%% %elapsed:6s%</>\n" .
            "<fg=cyan>[%target% %task%] %message%</>");
        $this->bar->setProgressCharacter('|');
        $this->bar->setMessage('', 'target');
        $this->bar->setMessage('', 'task');
    }

    /**
     * Fired before any targets are started.
     *
     * @param BuildEvent $event The BuildEvent
     */
    public function buildStarted(BuildEvent $event): void
    {
        $this->startTime = Phing::currentTimeMillis();
        $this->bar->setMessage($event->getProject()->getProperty("phing.file"), 'buildfile');
    }

    /**
     * Fired after the last target has finished.
     *
     * @param BuildEvent $event The BuildEvent
     * @throws IOException
     * @see BuildEvent::getException()
     */
    public function buildFinished(BuildEvent $event): void
    {
        $this->bar->finish();
        echo "\n";
        
        parent::buildFinished($event);
    }

    /**
     * Fired when a target is started.
     *
     * @param BuildEvent $event The BuildEvent
     * @throws Exception
     * @see BuildEvent::getTarget()
     */
    public function targetStarted(BuildEvent $event): void
    {
        $this->bar->setMessage($event->getTarget()->getName(), 'target');
        $this->determineDepth($event);
    }

    /**
     * Fired when a target has finished.
     *
     * @param BuildEvent $event The BuildEvent
     * @see BuildEvent#getException()
     */
    public function targetFinished(BuildEvent $event): void
    {
        $this->remTargets--;
    }

    /**
     * Fired when a task is started.
     *
     * @param BuildEvent $event The BuildEvent
     * @see BuildEvent::getTask()
     */
    public function taskStarted(BuildEvent $event): void
    {
        // ignore tasks in root
        if ($event->getTarget()->getName() == "") {
            return;
        }

        $this->bar->setMessage($event->getTask()->getTaskName(), 'task');

        $this->determineDepth($event);
    }

    /**
     * Fired when a task has finished.
     *
     * @param BuildEvent $event The BuildEvent
     * @see BuildEvent::getException()
     */
    public function taskFinished(BuildEvent $event): void
    {
        // ignore tasks in root
        if ($event->getTarget()->getName() == "") {
            return;
        }

        $this->remTasks--;
        $this->bar->advance();
    }

    /**
     * Fired whenever a message is logged.
     *
     * @param BuildEvent $event The BuildEvent
     * @see BuildEvent::getMessage()
     */
    public function messageLogged(BuildEvent $event): void
    {
        $priority = $event->getPriority();
        if ($priority <= $this->msgOutputLevel) {
            $this->bar->setMessage(str_replace(["\n","\r"], ["",""], $event->getMessage()));
            $this->bar->display();
        }
    }

    /**
     * @param BuildEvent $event
     * @throws Exception
     */
    protected function determineDepth(BuildEvent $event)
    {
        if ($this->numTargets == 0) {
            $this->numTasks = 0;
            $this->numTargets = 0;

            $project = $event->getProject();

            $executedTargetNames = $project->getExecutedTargetNames();

            foreach ($executedTargetNames as $targetName) {
                $targets = $project->topoSort($targetName);

                foreach ($targets as $target) {
                    if ($target->getName() == "") {
                        continue;
                    }

                    $tasks = $target->getTasks();
                    $this->numTasks += count($tasks);
                    $this->numTargets++;
                }
            }
            
            $this->remTargets = $this->numTargets;
            $this->remTasks = $this->numTasks;
            $this->bar->start($this->numTasks);
        }
    }
}
