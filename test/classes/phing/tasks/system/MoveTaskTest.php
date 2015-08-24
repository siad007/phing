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

require_once 'phing/BuildFileTest.php';

/**
 * Tests the Move Task
 *
 * @author  Siad Ardroumli
 * @package phing.tasks.system
 */
class MoveTaskTest extends BuildFileTest
{
    public function setUp()
    {
        $this->configureProject(
            PHING_TEST_BASE
            . "/etc/tasks/system/MoveTaskTest.xml"
        );
        $this->executeTarget("setup");
    }

    public function tearDown()
    {
        $this->executeTarget("clean");
    }

    public function testRegexMapper()
    {
        $this->executeTarget(__FUNCTION__);

        /** @var Project $project */
        $project = $this->getProject();
        $output = $project->getProperty('output');

        $this->assertFileExists($output . '/BBB/foo/bar.txt');
        $this->assertFileExists($output . '/foo/bar.txt');
        $this->assertFileExists($output . 'foo/BBB/bar.txt');
        $this->assertFileExists($output . '/foo/bar/BBB.txt');
        $this->assertFileExists($output . '/foo/bar/baz.txt');
    }

    public function testOverwriteIsTrueByDefault()
    {
        $this->executeTarget(__FUNCTION__);

        /** @var Project $project */
        $project = $this->getProject();
        $input = $project->getProperty('input');
        $output = $project->getProperty('output');

        $this->assertFileNotExists($input . '/x.txt');
        $this->assertStringEqualsFile($output . '/y.txt', 'X');
    }

    public function testOverwriteIsHonored()
    {
        $this->executeTarget(__FUNCTION__);

        /** @var Project $project */
        $project = $this->getProject();
        $input = $project->getProperty('input');
        $output = $project->getProperty('output');

        $this->assertFileExists($input . '/x.txt');
        $this->assertStringEqualsFile($output . '/y.txt', 'Y');
    }

    public function testMoveOverReadOnlyFile()
    {
        $this->executeTarget(__FUNCTION__);
        $this->assertInLogs('test succeeded.');
    }
}
