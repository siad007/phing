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

require_once 'phing/listener/statistics/Duration.php';

/**
 * @author    Siad Ardroumli <siad.ardroumli@gmail.com>
 * @package   phing.listener.statistics
 */
class Series
{
    private $stack;

    /** @var Duration[] $list */
    private $list = [];

    public function __construct()
    {
        $this->stack = new SplStack();
    }

    public function add(Duration $duration): void
    {
        $this->list[] = $duration;
        $this->stack->push($duration);
    }

    public function setFinishTime($time): void
    {
        $duration = $this->stack->pop();
        $duration->setFinishTime($time);
    }

    public function getTimes(): array
    {
        return array_map(
            function (Duration $elem) {
                return $elem->getTime();
            },
            $this->list
        );
    }

    public function getTotalTime()
    {
        return array_sum($this->getTimes());
    }

    public function getAverageTime()
    {
        if (count($this->list) === 0) {
            return 0;
        }
        return $this->getTotalTime() / count($this->list);
    }

    public function size(): int
    {
        return count($this->list);
    }

    public function current()
    {
        if ($this->stack->isEmpty()) {
            $this->stack->push(new Duration());
        }
        return $this->stack->top();
    }
}
