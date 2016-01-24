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
 * A FilterSetCollection is a collection of filtersets each of which may have
 * a different start/end token settings.
 *
 * @author   Siad Ardroumli <siad.ardroumli@gmail.com>
 * @package  phing.types
 */
class FilterSetCollection
{
    /** @var FilterSet[] $filterSets */
    private $filterSets = array();

    /**
     * Constructor for a FilterSetCollection.
     * @param FilterSet $filterSet a filterset to start the collection with
     */
    public function __construct(FilterSet $filterSet = null)
    {
        $this->addFilterSet($filterSet);
    }


    /**
     * Add a filterset to the collection.
     *
     * @param FilterSet $filterSet a <code>FilterSet</code> value
     */
    public function addFilterSet(FilterSet $filterSet)
    {
        $this->filterSets[] = $filterSet;
    }

    /**
     * Does replacement on the given string with token matching.
     * This uses the defined begintoken and endtoken values which default to @ for both.
     *
     * @param  string $line  The line to process the tokens in.
     *
     * @return string with the tokens replaced.
     *
     * @throws BuildException
     */
    public function replaceTokens($line)
    {
        $replacedLine = $line;
        foreach ($this->filterSets as $filterSet) {
            $replacedLine = $filterSet->replaceTokens($replacedLine);
        }
        return $replacedLine;
    }

    /**
     * Test to see if this filter set it empty.
     *
     * @return boolean Return true if there are filter in this set otherwise false.
     *
     * @throws BuildException
     */
    public function hasFilters()
    {
        foreach ($this->filterSets as $filterSet) {
            if ($filterSet->hasFilters()) {
                return true;
            }
        }
        return false;
    }
}
