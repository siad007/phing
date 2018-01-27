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
 * Target executor implementation that ignores dependencies. Runs each
 * target by calling <code>target.performTasks()</code> directly. If an
 * error occurs, behavior is determined by the Project's "keep-going" mode.
 * To be used when you know what you're doing.
 *
 * @author Siad Ardroumli <siad.ardroumli@gmail.com>
 * @package phing.helper
 */
class IgnoreDependenciesExecutor implements Executor
{
    /** {@inheritDoc}. */
    public function executeTargets(Project $project, array $targetNames): void
    {
        $targets = $project->getTargets();
        $thrownException = null;
        foreach ($targetNames as $name) {
            try {
                if (!isset($targets[$name])) {
                    throw new BuildException('Unknown target ' . $name);
                }
                $targets[$name]->performTasks();
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
