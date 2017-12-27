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

include_once 'phing/SubBuildListener.php';
include_once 'phing/listener/statistics/StatisticsReport.php';
require_once 'phing/listener/statistics/DefaultClock.php';
require_once 'phing/listener/statistics/ProjectTimerMap.php';

/**
 * @author    Siad Ardroumli <siad.ardroumli@gmail.com>
 * @package   phing.listener
 */
class StatisticsListener implements SubBuildListener
{
    private static $BUILDEVENT_PROJECT_NAME_HAS_NULL_VALUE = true;

    /** @var ProjectTimerMap $projectTimerMap */
    protected $projectTimerMap;

    /** @var Clock $clock */
    private $clock;

    /** @var StatisticsReport $statisticsReport */
    private $statisticsReport;

    public function __construct(Clock $clock = null)
    {
        $this->projectTimerMap = new ProjectTimerMap();
        $this->statisticsReport = new StatisticsReport();
        if ($clock === null) {
            $this->clock = new DefaultClock();
        } else {
            $this->clock = $clock;
        }
    }

    public function buildStarted(BuildEvent $buildEvent): void
    {
        if (self::$BUILDEVENT_PROJECT_NAME_HAS_NULL_VALUE) {
            $this->findInitialProjectTimer()->start();
        }
    }

    public function buildFinished(BuildEvent $event): void
    {
        $projectTimer = $this->findProjectTimer($event);
        $this->updateDurationWithInitialProjectTimer($projectTimer);
        $this->buildFinishedTimer($projectTimer);
        $this->statisticsReport->write();
    }

    public function targetStarted(BuildEvent $event): void
    {
        $this->findTargetTimer($event)->start();
    }

    public function targetFinished(BuildEvent $buildEvent): void
    {
        $this->findTargetTimer($buildEvent)->finish();
    }

    public function taskStarted(BuildEvent $buildEvent): void
    {
        $this->findTaskTimer($buildEvent)->start();
    }

    public function taskFinished(BuildEvent $buildEvent): void
    {
        $this->findTaskTimer($buildEvent)->finish();
    }

    public function messageLogged(BuildEvent $event): void
    {
    }

    public function subBuildStarted(BuildEvent $buildEvent)
    {
        $this->findProjectTimer($buildEvent)->start();
    }

    public function subBuildFinished(BuildEvent $buildEvent)
    {
        $projectTimer = $this->findProjectTimer($buildEvent);
        $this->buildFinishedTimer($projectTimer);
    }

    private function findProjectTimer(BuildEvent $buildEvent)
    {
        $project = $buildEvent->getProject();
        return $this->projectTimerMap->find($project, $this->clock);
    }

    protected function findInitialProjectTimer()
    {
        return $this->projectTimerMap->find('', $this->clock);
    }

    /**
     * @param BuildEvent $buildEvent
     * @return SeriesTimer
     */
    private function findTargetTimer(BuildEvent $buildEvent): \SeriesTimer
    {
        $projectTimer = $this->findProjectTimer($buildEvent);
        $target = $buildEvent->getTarget();
        $name = $target->getName();
        return $projectTimer->getTargetTimer($name);
    }

    /**
     * @param BuildEvent $buildEvent
     * @return SeriesTimer
     */
    private function findTaskTimer(BuildEvent $buildEvent): \SeriesTimer
    {
        $projectTimer = $this->findProjectTimer($buildEvent);
        $task = $buildEvent->getTask();
        $name = $task->getTaskName();
        return $projectTimer->getTaskTimer($name);
    }

    private function buildFinishedTimer(ProjectTimer $projectTimer)
    {
        $projectTimer->finish();
        $this->statisticsReport->push($projectTimer);
    }

    private function updateDurationWithInitialProjectTimer(ProjectTimer $projectTimer)
    {
        $rootProjectTimer = $this->findInitialProjectTimer();
        $duration = $rootProjectTimer->getSeries()->current();
        $projectTimer->getSeries()->add($duration);
    }
}
