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
 * @author  Siad Ardroumli <siad.ardroumli@gmail.com>
 * @package phing.tasks.ext
 */
class HttpClientTaskTest extends BuildFileTest
{
    public function setUp()
    {
        $this->configureProject(PHING_TEST_BASE . "/etc/tasks/ext/httpclient/httpclient.xml");
    }

    public function testMethods()
    {
        $this->executeTarget(__FUNCTION__);
    }

    public function testPostRequest()
    {
        $responses = [
            new \GuzzleHttp\Psr7\Response(200, ['content-type' => 'application/xml'])
        ];

        $this->executeTarget($this->injectMockResponses($responses, __FUNCTION__));
    }

    public function testWithHeaders()
    {
        $this->executeTarget(__FUNCTION__);
    }

    public function testPut()
    {
        $this->executeTarget(__FUNCTION__);
    }

    private function injectMockResponses(array $responses, $callee)
    {
        $targets = $this->getProject()->getTargets();
        $target = $targets[$callee];
        $tasks = $target->getTasks();
        foreach ($tasks as $task) {
            if ($task instanceof UnknownElement) {
                $task->maybeConfigure();
                $task = $task->getRuntimeConfigurableWrapper()->getProxy(); // gets HttpTask instead of UE
            }
            if ($task instanceof HttpClientTask) {
                $task->setHandler($this->getMockHandler($responses));
            }
        }

        return $target->getName();
    }

    private function getMockHandler(array $responses)
    {
        return \GuzzleHttp\HandlerStack::create(
            new \GuzzleHttp\Handler\MockHandler()
        );
    }
}
