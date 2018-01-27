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

/**
 * Default Target executor implementation. Runs each target individually
 * (including all of its dependencies). If an error occurs, behavior is
 * determined by the Project's "keep-going" mode.
 *
 * @author Siad Ardroumli <siad.ardroumli@gmail.com>
 * @package phing.helper
 */
class DefaultExecutor implements Executor
{
    /**
     * @param Project $project
     * @param array $targetNames
     * @throws Exception
     * @throws Throwable
     */
    public function executeTargets(Project $project, array $targetNames): void
    {
        $thrownException = null;
        foreach ($targetNames as $name) {
            try {
                $project->executeTarget($name);
            } catch (BuildException $ex) {
                if ($project->isKeepGoingMode()) {
                    $thrownException = $ex;
                } else {
                    throw $ex;
                }
            }
        }
        if ($thrownException !== null) {
            throw $thrownException;
        }
    }
}
