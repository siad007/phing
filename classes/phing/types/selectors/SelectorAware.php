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

trait SelectorAware
{
    /**
     * @var BaseSelectorContainer[]
     */
    protected $selectorsList = [];

    /**
     * Indicates whether there are any selectors here.
     */
    public function hasSelectors(): bool
    {
        return !empty($this->selectorsList);
    }

    /**
     * Gives the count of the number of selectors in this container
     */
    public function selectorCount(): int
    {
        return count($this->selectorsList);
    }

    /**
     * Returns a copy of the selectors as an array.
     * @param Project $p
     * @return array
     */
    public function getSelectors(Project $p): array
    {
        $result = [];
        for ($i = 0, $size = count($this->selectorsList); $i < $size; $i++) {
            $result[] = clone $this->selectorsList[$i];
        }

        return $result;
    }

    /**
     * Returns an array for accessing the set of selectors (not a copy).
     */
    public function selectorElements(): array
    {
        return $this->selectorsList;
    }

    /**
     * Add a new selector into this container.
     *
     * @param FileSelector $selector new selector to add
     */
    public function appendSelector(FileSelector $selector): void
    {
        $this->selectorsList[] = $selector;
    }

    /**
     * add a "Select" selector entry on the selector list
     * @param SelectSelector $selector
     */
    public function addSelector(SelectSelector $selector): void
    {
        $this->appendSelector($selector);
    }

    /**
     * add an "And" selector entry on the selector list
     * @param AndSelector $selector
     */
    public function addAnd(AndSelector $selector): void
    {
        $this->appendSelector($selector);
    }

    /**
     * add an "Or" selector entry on the selector list
     * @param OrSelector $selector
     */
    public function addOr(OrSelector $selector): void
    {
        $this->appendSelector($selector);
    }

    /**
     * add a "Not" selector entry on the selector list
     */
    public function addNot(NotSelector $selector): void
    {
        $this->appendSelector($selector);
    }

    /**
     * add a "None" selector entry on the selector list
     */
    public function addNone(NoneSelector $selector): void
    {
        $this->appendSelector($selector);
    }

    /**
     * add a majority selector entry on the selector list
     */
    public function addMajority(MajoritySelector $selector): void
    {
        $this->appendSelector($selector);
    }

    /**
     * add a selector date entry on the selector list
     */
    public function addDate(DateSelector $selector): void
    {
        $this->appendSelector($selector);
    }

    /**
     * add a selector size entry on the selector list
     */
    public function addSize(SizeSelector $selector): void
    {
        $this->appendSelector($selector);
    }

    /**
     * add a selector filename entry on the selector list
     */
    public function addFilename(FilenameSelector $selector): void
    {
        $this->appendSelector($selector);
    }

    /**
     * add an extended selector entry on the selector list
     */
    public function addCustom(ExtendSelector $selector): void
    {
        $this->appendSelector($selector);
    }

    /**
     * add a contains selector entry on the selector list
     */
    public function addContains(ContainsSelector $selector): void
    {
        $this->appendSelector($selector);
    }

    /**
     * add a contains selector entry on the selector list
     */
    public function addContainsRegexp(ContainsRegexpSelector $selector): void
    {
        $this->appendSelector($selector);
    }

    /**
     * add a present selector entry on the selector list
     */
    public function addPresent(PresentSelector $selector): void
    {
        $this->appendSelector($selector);
    }

    /**
     * add a depth selector entry on the selector list
     */
    public function addDepth(DepthSelector $selector): void
    {
        $this->appendSelector($selector);
    }

    /**
     * add a depends selector entry on the selector list
     */
    public function addDepend(DependSelector $selector): void
    {
        $this->appendSelector($selector);
    }

    /**
     * add a different selector entry on the selector list
     */
    public function addDifferent(DifferentSelector $selector): void
    {
        $this->appendSelector($selector);
    }

    /**
     * add a type selector entry on the selector list
     */
    public function addType(TypeSelector $selector): void
    {
        $this->appendSelector($selector);
    }

    /**
     * add a executable selector entry on the selector list
     */
    public function addExecutable(ExecutableSelector $selector): void
    {
        $this->appendSelector($selector);
    }

    /**
     * add a readable selector entry on the selector list
     */
    public function addReadable(ReadableSelector $selector): void
    {
        $this->appendSelector($selector);
    }

    /**
     * add a writable selector entry on the selector list
     */
    public function addWritable(WritableSelector $selector): void
    {
        $this->appendSelector($selector);
    }

    /**
     * add a symlink selector entry on the selector list
     */
    public function addSymlink(SymlinkSelector $selector): void
    {
        $this->appendSelector($selector);
    }
}
