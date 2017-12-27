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
 * @author    Siad Ardroumli <siad.ardroumli@gmail.com>
 * @package   phing.listener.statistics
 */
class Table
{
    private $header;

    private $output;

    private $maxLengths;

    public function __construct(array $header = [], $rows)
    {
        $this->header = $header;
        $columnSize = ($rows >= 0) ? $rows + 1 : 1;
        $this->output = array_fill(0, $columnSize, array_fill(0, count($this->header), null));
        $this->output[0] = $this->header;
        $this->maxLengths = $this->getHeaderLengths();
    }

    private function getHeaderLengths(): array
    {
        return array_map('\strlen', $this->header);
    }

    public function getMaxLengths(): array
    {
        return $this->maxLengths;
    }

    public function put($x, $y, $value): void
    {
        $this->maxLengths[$y] = $this->max($y, strlen((string) $value));
        $this->output[$x][$y] = $value;
    }

    private function max($column, $length)
    {
        $max = $length;
        for ($i = 0, $iMax = count($this->output); $i < $iMax; $i++) {
        $valueLength = ($this->output[$i][$column] !== null) ? strlen($this->output[$i][$column]) : 0;
            $max = max([$max, $valueLength]);
        }
        return $max;
    }

    public function get($x, $y)
    {
        return $this->output[$x][$y];
    }

    public function rows(): int
    {
        return count($this->output);
    }

    public function columns(): int
    {
        return count($this->header);
    }
}