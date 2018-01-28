<?php
/**
 *
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

/**
 * Simple Testrunner for PHPUnit that runs all tests of a testsuite.
 *
 * @author Michiel Rook <mrook@php.net>
 * @package phing.tasks.ext.phpunit
 */
class PHPUnitTestRunner6 implements \PHPUnit\Framework\TestListener
{
    private $hasErrors = false;
    private $hasFailures = false;
    private $hasWarnings = false;
    private $hasIncomplete = false;
    private $hasSkipped = false;
    private $lastErrorMessage = '';
    private $lastFailureMessage = '';
    private $lastIncompleteMessage = '';
    private $lastSkippedMessage = '';
    private $formatters = [];
    private $listeners = [];

    private $codecoverage = null;

    private $project = null;

    private $groups = [];
    private $excludeGroups = [];

    private $processIsolation = false;

    private $useCustomErrorHandler = true;

    /**
     * @param Project $project
     * @param array $groups
     * @param array $excludeGroups
     * @param bool $processIsolation
     */
    public function __construct(
        Project $project,
        $groups = [],
        $excludeGroups = [],
        $processIsolation = false
    ) {
        $this->project = $project;
        $this->groups = $groups;
        $this->excludeGroups = $excludeGroups;
        $this->processIsolation = $processIsolation;
    }

    /**
     * @param $codecoverage
     */
    public function setCodecoverage($codecoverage)
    {
        $this->codecoverage = $codecoverage;
    }

    /**
     * @param $useCustomErrorHandler
     */
    public function setUseCustomErrorHandler($useCustomErrorHandler)
    {
        $this->useCustomErrorHandler = $useCustomErrorHandler;
    }

    /**
     * @param $formatter
     */
    public function addFormatter($formatter)
    {
        $this->addListener($formatter);
        $this->formatters[] = $formatter;
    }

    /**
     * @param $level
     * @param $message
     * @param $file
     * @param $line
     */
    public function handleError($level, $message, $file, $line)
    {
        return PHPUnit\Util\ErrorHandler::handleError($level, $message, $file, $line);
    }

    public function addListener($listener)
    {
        $this->listeners[] = $listener;
    }

    /**
     * Run a test
     * @param PHPUnit\Framework\TestSuite $suite
     * @throws \BuildException
     */
    public function run(PHPUnit\Framework\TestSuite $suite)
    {
        $res = new PHPUnit\Framework\TestResult();

        if ($this->codecoverage) {
            $whitelist = CoverageMerger::getWhiteList($this->project);

            $this->codecoverage->filter()->addFilesToWhiteList($whitelist);

            $res->setCodeCoverage($this->codecoverage);
        }

        $res->addListener($this);

        foreach ($this->formatters as $formatter) {
            $res->addListener($formatter);
        }

        /* Set PHPUnit error handler */
        if ($this->useCustomErrorHandler) {
            $oldErrorHandler = set_error_handler([$this, 'handleError'], E_ALL | E_STRICT);
        }

        $this->injectFilters($suite);
        $suite->run($res);

        foreach ($this->formatters as $formatter) {
            $formatter->processResult($res);
        }

        /* Restore Phing error handler */
        if ($this->useCustomErrorHandler) {
            restore_error_handler();
        }

        if ($this->codecoverage) {
            try {
                CoverageMerger::merge($this->project, $this->codecoverage->getData());
            } catch (IOException $e) {
                throw new BuildException('Merging code coverage failed.', $e);
            }
        }

        $this->checkResult($res);
    }

    private function checkResult($res)
    {
        if ($res->skippedCount() > 0) {
            $this->hasSkipped = true;
        }

        if ($res->notImplementedCount() > 0) {
            $this->hasIncomplete = true;
        }

        if ($res->warningCount() > 0) {
            $this->hasWarnings = true;
        }

        if ($res->failureCount() > 0) {
            $this->hasFailures = true;
        }

        if ($res->errorCount() > 0) {
            $this->hasErrors = true;
        }
    }

    /**
     * @return boolean
     */
    public function hasErrors()
    {
        return $this->hasErrors;
    }

    /**
     * @return boolean
     */
    public function hasFailures()
    {
        return $this->hasFailures;
    }

    /**
     * @return boolean
     */
    public function hasWarnings()
    {
        return $this->hasWarnings;
    }

    /**
     * @return boolean
     */
    public function hasIncomplete()
    {
        return $this->hasIncomplete;
    }

    /**
     * @return boolean
     */
    public function hasSkipped()
    {
        return $this->hasSkipped;
    }

    /**
     * @return string
     */
    public function getLastErrorMessage()
    {
        return $this->lastErrorMessage;
    }

    /**
     * @return string
     */
    public function getLastFailureMessage()
    {
        return $this->lastFailureMessage;
    }

    /**
     * @return string
     */
    public function getLastIncompleteMessage()
    {
        return $this->lastIncompleteMessage;
    }

    /**
     * @return string
     */
    public function getLastSkippedMessage()
    {
        return $this->lastSkippedMessage;
    }

    /**
     * @param string $message
     * @param PHPUnit\Framework\Test $test
     * @param Exception $e
     * @return string
     */
    protected function composeMessage($message, PHPUnit\Framework\Test $test, Exception $e)
    {
        $message = "Test $message (" . $test->getName() . " in class " . get_class($test) . "): " . $e->getMessage();

        if ($e instanceof PHPUnit\Framework\ExpectationFailedException && $e->getComparisonFailure()) {
            $message .= "\n" . $e->getComparisonFailure()->getDiff();
        }

        return $message;
    }

    /**
     * An error occurred.
     *
     * @param PHPUnit\Framework\Test $test
     * @param Exception              $e
     * @param float                  $time
     */
    public function addError(PHPUnit\Framework\Test $test, Exception $e, $time)
    {
        $this->lastErrorMessage = $this->composeMessage("ERROR", $test, $e);
    }

    /**
     * A failure occurred.
     *
     * @param PHPUnit\Framework\Test                 $test
     * @param PHPUnit\Framework\AssertionFailedError $e
     * @param float                                  $time
     */
    public function addFailure(PHPUnit\Framework\Test $test, PHPUnit\Framework\AssertionFailedError $e, $time)
    {
        $this->lastFailureMessage = $this->composeMessage("FAILURE", $test, $e);
    }

    /**
     * A failure occurred.
     *
     * @param PHPUnit\Framework\Test                 $test
     * @param PHPUnit\Framework\AssertionFailedError $e
     * @param float                                  $time
     */
    public function addWarning(PHPUnit\Framework\Test $test, \PHPUnit\Framework\Warning $e, $time)
    {
        $this->lastWarningMessage = $this->composeMessage("WARNING", $test, $e);
    }

    /**
     * Incomplete test.
     *
     * @param PHPUnit\Framework\Test $test
     * @param Exception              $e
     * @param float                  $time
     */
    public function addIncompleteTest(PHPUnit\Framework\Test $test, Exception $e, $time)
    {
        $this->lastIncompleteMessage = $this->composeMessage("INCOMPLETE", $test, $e);
    }

    /**
     * Skipped test.
     *
     * @param PHPUnit\Framework\Test $test
     * @param Exception              $e
     * @param float                  $time
     * @since  Method available since Release 3.0.0
     */
    public function addSkippedTest(PHPUnit\Framework\Test $test, Exception $e, $time)
    {
        $this->lastSkippedMessage = $this->composeMessage("SKIPPED", $test, $e);
    }

    /**
     * Risky test
     *
     * @param PHPUnit\Framework\Test $test
     * @param Exception              $e
     * @param float                  $time
     */
    public function addRiskyTest(PHPUnit\Framework\Test $test, Exception $e, $time)
    {
    }

    /**
     * A test started.
     *
     * @param string $testName
     */
    public function testStarted($testName)
    {
    }

    /**
     * A test ended.
     *
     * @param string $testName
     */
    public function testEnded($testName)
    {
    }

    /**
     * A test failed.
     *
     * @param integer                                $status
     * @param PHPUnit\Framework\Test                 $test
     * @param PHPUnit\Framework\AssertionFailedError $e
     */
    public function testFailed($status, PHPUnit\Framework\Test $test, PHPUnit\Framework\AssertionFailedError $e)
    {
    }

    /**
     * Override to define how to handle a failed loading of
     * a test suite.
     *
     * @param string $message
     * @throws BuildException
     */
    protected function runFailed($message)
    {
        throw new BuildException($message);
    }

    /**
     * A test suite started.
     *
     * @param PHPUnit\Framework\TestSuite $suite
     * @since  Method available since Release 2.2.0
     */
    public function startTestSuite(PHPUnit\Framework\TestSuite $suite)
    {
    }

    /**
     * A test suite ended.
     *
     * @param PHPUnit\Framework\TestSuite $suite
     * @since  Method available since Release 2.2.0
     */
    public function endTestSuite(PHPUnit\Framework\TestSuite $suite)
    {
    }

    /**
     * A test started.
     *
     * @param PHPUnit\Framework\Test $test
     */
    public function startTest(PHPUnit\Framework\Test $test)
    {
    }

    /**
     * A test ended.
     *
     * @param PHPUnit\Framework\Test $test
     * @param float                  $time
     */
    public function endTest(PHPUnit\Framework\Test $test, $time)
    {
        if ($test instanceof PHPUnit\Framework\TestCase) {
            if (!$test->hasExpectationOnOutput()) {
                echo $test->getActualOutput();
            }
        }
    }

    /**
     * @param PHPUnit\Framework\TestSuite $suite
     */
    private function injectFilters(PHPUnit\Framework\TestSuite $suite)
    {
        $filterFactory = new PHPUnit\Runner\Filter\Factory();

        if (!empty($this->excludeGroups)) {
            $filterFactory->addFilter(
                new ReflectionClass(\PHPUnit\Runner\Filter\ExcludeGroupFilterIterator::class),
                $this->excludeGroups
            );
        }

        if (!empty($this->groups)) {
            $filterFactory->addFilter(
                new ReflectionClass(\PHPUnit\Runner\Filter\IncludeGroupFilterIterator::class),
                $this->groups
            );
        }

        $suite->injectFilter($filterFactory);
    }
}
