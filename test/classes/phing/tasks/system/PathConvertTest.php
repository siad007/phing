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
 * Tests the PathConvert Task
 *
 * @author  Siad Ardroumli <siad.ardroumli@gmail.com>
 * @package phing.tasks.system
 */
class PathConvertTest extends BuildFileTest
{

    public function setUp()
    {
        $this->configureProject(
            PHING_TEST_BASE . '/etc/tasks/system/PathConvertTest.xml'
        );
    }

    public function testPathReference()
    {
        $this->executeTarget(__FUNCTION__);
        $this->assertPropertyEquals('test.path.unix', '/this/is/a/test');
    }

    public function testComplexPathReference()
    {
        $this->executeTarget(__FUNCTION__);
        $this->assertPropertyEquals(
            'test.complex.path.result',
            '/testpath/lib/phing.phar:/testpath/classes:/testpath/mssqlserver4/classes:/winnt/System32'
        );
    }

    public function testDirsepSet()
    {
        $this->executeTarget(__FUNCTION__);
        $this->assertPropertyEquals('test.dirsep.prop', 'def|ghi');
    }

    public function testFileSet()
    {
        $this->executeTarget(__FUNCTION__);
        $this->assertContains('php', $this->getProject()->getProperty('phpfiles'));
    }

    public function testDirSet()
    {
        $this->executeTarget(__FUNCTION__);
        $this->assertContains('php', $this->getProject()->getProperty('dirset.files'));
    }

    public function testFileList()
    {
        $this->executeTarget(__FUNCTION__);
        $this->assertPropertyEquals('test.list', '/usr/local/CREDITS.md:/usr/local/README.md');
    }

    public function testEmbeddedMapper()
    {
        $this->executeTarget(__FUNCTION__);
        $this->assertPropertyEquals('test.embedded.mapper', 'build.xml');
    }

    public function testEmbeddedFileSet()
    {
        $this->executeTarget(__FUNCTION__);
        $this->assertContains('.php', $this->getProject()->getProperty('test.embedded.fileset'));
    }

    public function testEmbeddedDirSets()
    {
        $this->executeTarget(__FUNCTION__);
        $this->assertContains('.php', $this->getProject()->getProperty('test.embedded.dirsets'));
        $this->assertContains('.xml', $this->getProject()->getProperty('test.embedded.dirsets'));
    }

    public function testEmbeddedFileSetReferences()
    {
        $this->executeTarget(__FUNCTION__);
        $this->assertContains('.php', $this->getProject()->getProperty('test.embedded.fileset.ref'));
        $this->assertContains('.xml', $this->getProject()->getProperty('test.embedded.fileset.ref'));
    }

    public function testNotSetOnEmpty()
    {
        $this->executeTarget(__FUNCTION__);
        $this->assertPropertyUnset('test.not.set.on.empty');
    }
}
