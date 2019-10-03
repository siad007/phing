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

require_once 'phing/system/io/Writer.php';
/**
 * This abstract class describes classes that format the results of a PHPUnit testrun.
 *
 * @author  Siad Ardroumli <siad.ardroumli@gmail.com>
 * @package phing.tasks.ext.phpunit.formatter
 */
abstract class PHPUnitResultFormatter7 implements PHPUnit\Framework\TestListener
{
    protected $out;
/** @var Project */
    protected $project;
/**
     * @var array
     */
    private $timers = [];
/**
     * @var array
     */
    private $runCounts = [];
/**
     * @var array
     */
    private $failureCounts = [];
/**
     * @var array
     */
    private $errorCounts = [];
/**
     * @var array
     */
    private $incompleteCounts = [];
/**
     * @var array
     */
    private $skipCounts = [];
/**
     * @var array
     */
    private $warningCounts = [];
/**
     * Constructor
     *
     * @param PHPUnitTask $parentTask Calling Task
     */
    public function __construct(PHPUnitTask $parentTask)
    {
        $this->project = $parentTask->getProject();
    }

    /**
     * Sets the writer the formatter is supposed to write its results to.
     *
     * @param Writer $out
     */
    public function setOutput(Writer $out)
    {
        $this->out = $out;
    }

    /**
     * Returns the extension used for this formatter
     *
     * @return string the extension
     */
    public function getExtension()
    {
        return '';
    }

    /**
     * @return string
     */
    public function getPreferredOutfile()
    {
        return '';
    }

    /**
     * @param PHPUnit\Framework\TestResult $result
     */
    public function processResult(PHPUnit\Framework\TestResult $result)
    {
    }

    public function startTestRun()
    {
        $this->timers = [$this->getMicrotime()];
        $this->runCounts = [0];
        $this->failureCounts = [0];
        $this->errorCounts = [0];
        $this->warningCounts = [0];
        $this->incompleteCounts = [0];
        $this->skipCounts = [0];
    }

    public function endTestRun()
    {
    }

    /**
     * @param PHPUnit\Framework\TestSuite $suite
     */
    public function startTestSuite(PHPUnit\Framework\TestSuite $suite): void
    {
        $this->timers[] = $this->getMicrotime();
        $this->runCounts[] = 0;
        $this->failureCounts[] = 0;
        $this->errorCounts[] = 0;
        $this->incompleteCounts[] = 0;
        $this->skipCounts[] = 0;
    }

    /**
     * @param PHPUnit\Framework\TestSuite $suite
     */
    public function endTestSuite(PHPUnit\Framework\TestSuite $suite): void
    {
        $lastRunCount = array_pop($this->runCounts);
        $this->runCounts[count($this->runCounts) - 1] += $lastRunCount;
        $lastFailureCount = array_pop($this->failureCounts);
        $this->failureCounts[count($this->failureCounts) - 1] += $lastFailureCount;
        $lastErrorCount = array_pop($this->errorCounts);
        $this->errorCounts[count($this->errorCounts) - 1] += $lastErrorCount;
        $lastIncompleteCount = array_pop($this->incompleteCounts);
        $this->incompleteCounts[count($this->incompleteCounts) - 1] += $lastIncompleteCount;
        $lastSkipCount = array_pop($this->skipCounts);
        $this->skipCounts[count($this->skipCounts) - 1] += $lastSkipCount;
        array_pop($this->timers);
    }

    /**
     * @param PHPUnit\Framework\Test $test
     */
    public function startTest(PHPUnit\Framework\Test $test): void
    {
        $this->runCounts[count($this->runCounts) - 1]++;
    }

    /**
     * @param PHPUnit\Framework\Test $test
     * @param float $time
     */
    public function endTest(PHPUnit\Framework\Test $test, float $time): void
    {
    }

    /**
     * @param PHPUnit\Framework\Test $test
     * @param Exception $e
     * @param float $time
     */
    public function addError(PHPUnit\Framework\Test $test, Throwable $e, float $time): void
    {
        $this->errorCounts[count($this->errorCounts) - 1]++;
    }

    /**
     * @param PHPUnit\Framework\Test $test
     * @param PHPUnit\Framework\AssertionFailedError $e
     * @param float $time
     */
    public function addFailure(PHPUnit\Framework\Test $test, PHPUnit\Framework\AssertionFailedError $e, float $time): void
    {
        $this->failureCounts[count($this->failureCounts) - 1]++;
    }

    /**
     * @param PHPUnit\Framework\Test $test
     * @param PHPUnit\Framework\Warning $e
     * @param float $time
     */
    public function addWarning(PHPUnit\Framework\Test $test, \PHPUnit\Framework\Warning $e, float $time): void
    {
        $this->warningCounts[count($this->warningCounts) - 1]++;
    }

    /**
     * @param PHPUnit\Framework\Test $test
     * @param Exception $e
     * @param float $time
     */
    public function addIncompleteTest(PHPUnit\Framework\Test $test, Throwable $e, float $time): void
    {
        $this->incompleteCounts[count($this->incompleteCounts) - 1]++;
    }

    /**
     * @param PHPUnit\Framework\Test $test
     * @param Exception $e
     * @param float $time
     */
    public function addSkippedTest(PHPUnit\Framework\Test $test, Throwable $e, float $time): void
    {
        $this->skipCounts[count($this->skipCounts) - 1]++;
    }

    /**
     * @param PHPUnit\Framework\Test $test
     * @param Exception $e
     * @param float $time
     */
    public function addRiskyTest(PHPUnit\Framework\Test $test, Throwable $e, float $time): void
    {
    }

    /**
     * @return mixed
     */
    public function getRunCount()
    {
        return end($this->runCounts);
    }

    /**
     * @return mixed
     */
    public function getFailureCount()
    {
        return end($this->failureCounts);
    }

    /**
     * @return mixed
     */
    public function getWarningCount()
    {
        return end($this->warningCounts);
    }

    /**
     * @return mixed
     */
    public function getErrorCount()
    {
        return end($this->errorCounts);
    }

    /**
     * @return mixed
     */
    public function getIncompleteCount()
    {
        return end($this->incompleteCounts);
    }

    /**
     * @return mixed
     */
    public function getSkippedCount()
    {
        return end($this->skipCounts);
    }

    /**
     * @return float|int
     */
    public function getElapsedTime()
    {
        if (end($this->timers)) {
            return $this->getMicrotime() - end($this->timers);
        }

        return 0;
    }

    /**
     * @return float
     */
    private function getMicrotime()
    {
        [$usec, $sec] = explode(' ', microtime());
        return (float) $usec + (float) $sec;
    }
}
