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

include_once 'phing/types/DataType.php';

/**
 * A set of filters to be applied to something. A filter set may have begintoken
 * and endtokens defined.
 *
 * @author   Siad Ardroumli <siad.ardroumli@gmail.com>
 * @package  phing.types
 */
class FilterSet extends DataType
{
    /** The default token start string */
    const DEFAULT_TOKEN_START = "@";

    /** The default token end string */
    const DEFAULT_TOKEN_END = "@";

    /** @var string $startOfToken */
    private $startOfToken = self::DEFAULT_TOKEN_START;

    /** @var string $endOfToken */
    private $endOfToken = self::DEFAULT_TOKEN_END;

    /**
     * Contains a list of parsed tokens.
     *
     * @var string[] $passedTokens
     */
    private $passedTokens;

    /**
     * if a duplicate token is found, this is set to true.
     *
     * @var boolean $duplicateToken
     */
    private $duplicateToken = false;

    /** @var bool $recurse */
    private $recurse = true;

    private $filterHash = null;

    /** @var array $filterFiles */
    private $filtersFiles = array();

    private $onMissingFiltersFile = 'fail';

    /** @var bool $readingFiles */
    private $readingFiles = false;

    /** @var int $recurseDepth */
    private $recurseDepth = 0;

    /**
     * List of ordered filters and filter files.
     */
    private $filters = array();

    /**
     * Create a Filterset from another filterset.
     *
     * @param FilterSet $filterset the filterset upon which this filterset will be based.
     *
     * @throws BuildException
     */
    public function __construct(FilterSet $filterset = null)
    {
        $this->filters = $filterset->getFilters();
    }

    /**
     * Get the filters in the filter set.
     *
     * @return array a Vector of Filter instances.
     *
     * @throws BuildException
     */
    protected function getFilters()
    {
        if ($this->isReference()) {
            return $this->getRef()->getFilters();
        }
        $stk = array();
        array_push($stk, $this);
        $this->dieOnCircularReference($stk, $this->project);
        if (!$this->readingFiles) {
            $this->readingFiles = true;
            $size = count($this->filtersFiles);
            foreach ($this->filtersFiles as $file) {
                $this->readFiltersFromFile($file);
            }
            $this->filtersFiles = null;
            $this->readingFiles = false;
        }

        return $this->filters;
    }

    /**
     * Get the referenced filter set.
     *
     * @return FilterSet the filterset from the reference.
     *
     * @throws BuildException
     */
    protected function getRef()
    {
        return $this->getCheckedRef(get_class($this), 'filterset');
    }

    /**
     * Gets the filter hash of the FilterSet.
     *
     * @return array The hash of the tokens and values for quick lookup.
     *
     * @throws BuildException
     */
    public function getFilterHash()
    {
        if ($this->isReference()) {
            return $this->getRef()->getFilterHash();
        }
        $stk = array();
        array_push($stk, $this);
        $this->dieOnCircularReference($stk, $this->project);

        if ($this->filterHash === null) {
            $this->filterHash = $this->getFilters();
            /** @var Filter $filter */
            foreach ($this->getFilters() as $filter) {
                $this->filterHash[$filter->getToken()] = $filter->getValue();
            }
        }
        return $this->filterHash;
    }

    /**
     * Set the file containing the filters for this filterset.
     *
     * @param PhingFile $filtersFile sets the filter file from which to read filters
     *        for this filter set.
     *
     * @throws BuildException if there is an error.
     */
    public function setFiltersfile(PhingFile $filtersFile)
    {
        if ($this->isReference()) {
            throw $this->tooManyAttributes();
        }
        $this->filtersFiles[] = $filtersFile;
    }

    /**
     * Set the string used to id the beginning of a token.
     *
     * @param string $startOfToken The new Begintoken value.
     *
     * @throws BuildException
     */
    public function setBeginToken($startOfToken)
    {
        if ($this->isReference()) {
            throw $this->tooManyAttributes();
        }
        if (empty($this->startOfToken)) {
            throw new BuildException('beginToken must not be empty');
        }
        $this->startOfToken = $startOfToken;
    }

    /**
     * Get the begin token for this filterset.
     *
     * @return string the filter set's begin token for filtering.
     *
     * @throws BuildException
     */
    public function getBeginToken()
    {
        if ($this->isReference()) {
            return $this->getRef()->getBeginToken();
        }
        return $this->startOfToken;
    }

    /**
     * Set the string used to id the end of a token.
     *
     * @param string $endOfToken The new Endtoken value.
     *
     * @throws BuildException
     */
    public function setEndToken($endOfToken)
    {
        if ($this->isReference()) {
            throw $this->tooManyAttributes();
        }

        if (empty($endOfToken)) {
            throw new BuildException('endToken must not be empty');
        }
        $this->endOfToken = $endOfToken;
    }

    /**
     * Get the end token for this filterset.
     *
     * @return string the filter set's end token for replacement delimiting.
     *
     * @throws BuildException
     */
    public function getEndToken()
    {
        if ($this->isReference()) {
            return $this->getRef()->getEndToken();
        }
        return $this->endOfToken;
    }

    /**
     * Set whether recursive token expansion is enabled.
     *
     * @param recurse <code>boolean</code> whether to recurse.
     */
    public function setRecurse($recurse)
    {
        $this->recurse = $recurse;
    }

    /**
     * Get whether recursive token expansion is enabled.
     *
     * @return boolean whether enabled.
     */
    public function isRecurse()
    {
        return $this->recurse;
    }

    /**
     * Read the filters from the given file.
     *
     * @param PhingFile $filtersFile the file from which filters are read.
     *
     * @throws BuildException when the file cannot be read.
     */
    public function readFiltersFromFile(PhingFile $filtersFile)
    {
        if ($this->isReference()) {
            throw $this->tooManyAttributes();
        }
        if (!$filtersFile->exists()) {
            $this->handleMissingFile('Could not read filters from file '
                . $filtersFile . ' as it doesn\'t exist.');
        }
        if ($filtersFile->isFile()) {
            $this->log('Reading filters from ' . $filtersFile, Project::MSG_VERBOSE);
            $in = null;
            try {
                $props = new Properties();
                $props->load($filtersFile);

                $e = $props->propertyNames();
                $filts = &$this->getFilters();
                foreach ($e as $strPropName) {
                    $strValue = $props->getProperty($strPropName);
                    $filts[] = new Filter($strPropName, $strValue);
                }
            } catch (Exception $ex) {
                throw new BuildException('Could not read filters from file: '
                    . $filtersFile, $ex);
            }
        } else {
            $this->handleMissingFile(
                'Must specify a file rather than a directory in '
                . 'the filtersfile attribute:' . $filtersFile);
        }
        $this->filterHash = null;
    }

    /**
     * Does replacement on the given string with token matching.
     * This uses the defined begintoken and endtoken values which default
     * to @ for both.
     * This resets the passedTokens and calls iReplaceTokens to
     * do the actual replacements.
     *
     * @param string $line The line in which to process embedded tokens.
     *
     * @return string The input string after token replacement.
     *
     * @throws BuildException
     */
    public function replaceTokens($line)
    {
        return $this->iReplaceTokens($line);
    }

    /**
     * Add a new filter.
     *
     * @param Filter $filter the filter to be added.
     *
     * @throws BuildException
     */
    public function addFilter(Filter $filter)
    {
        if ($this->isReference()) {
            throw $this->noChildrenAllowed();
        }
        $this->filters[] = $filter;
        $this->filterHash = null;
    }

    /**
     * Create a new FiltersFile.
     *
     * @return FiltersFile The filtersfile that was created.
     *
     * @throws BuildException
     */
    public function createFiltersfile()
    {
        if ($this->isReference()) {
            throw $this->noChildrenAllowed();
        }

        return $this->addFilterToList($this->filtersFiles);
    }

    /**
     * add a name entry to the given list
     *
     * @param  array List onto which the nameentry should be added
     * @return PatternSetNameEntry Reference to the created PsetNameEntry instance
     */
    private function addFilterToList(&$list)
    {
        $num = array_push($list, new FiltersFile());

        return $list[$num - 1];
    }

    /**
     * Add a Filterset to this filter set.
     *
     * @param FilterSet $filterSet the filterset to be added to this filterset
     *
     * @throws BuildException
     */
    public function addConfiguredFilterSet(FilterSet $filterSet)
    {
        if ($this->isReference()) {
            throw $this->noChildrenAllowed();
        }
        foreach ($filterSet->getFilters() as $filter) {
            $this->addFilter($filter);
        }
    }

    /**
     * Test to see if this filter set has filters.
     *
     * @return boolean Return true if there are filters in this set.
     *
     * @throws BuildException
     */
    public function hasFilters()
    {
        return count($this->getFilters()) > 0;
    }

    /**
     * Set the behavior WRT missing filtersfiles.
     *
     * @param string $onMissingFiltersFile the OnMissing describing the
     * behavior.
     */
    public function setOnMissingFiltersFile($onMissingFiltersFile)
    {
        $this->onMissingFiltersFile = $onMissingFiltersFile;
    }

    /**
     * Get the onMissingFiltersFile setting.
     * @return string
     */
    public function getOnMissingFiltersFile()
    {
        return $this->onMissingFiltersFile;
    }

    /**
     * Does replacement on the given string with token matching.
     * This uses the defined begintoken and endtoken values which
     * default
     * to @ for both.
     *
     * @param string $line The line to process the tokens in.
     *
     * @return string The string with the tokens replaced.
     *
     * @throws BuildException
     */
    private function iReplaceTokens($line)
    {
        $beginToken = $this->getBeginToken();
        $endToken = $this->getEndToken();
        $index = strpos($line, $beginToken);

        if ($index !== false) {
            $tokens = $this->getFilterHash();

            $b = "";
            $i = 0;
            $token = null;
            $value = null;

            while ($index > -1) {
                //can't have zero-length token
                $endIndex = strpos($line, $endToken, $index + count($beginToken) + 1);
                if ($endIndex === false) {
                    break;
                }
                $token = StringHelper::substring($line, $index + strlen($beginToken), $endIndex);
                $b .= StringHelper::substring($line, $i, $index);
                if (isset($tokens[$token])) {
                    $value = $tokens[$token];
                    if ($this->recurse && !$value === $token) {
                        // we have another token, let's parse it.
                        $value = $this->_replaceTokens($value, $token);
                    }
                    $this->log('Replacing: ' . $beginToken . $token . $endToken
                        . ' -> ' . $value, Project::MSG_VERBOSE);
                    $b .= $value;
                    $i = $index + strlen($beginToken) + strlen($token) + strlen($endToken);
                } else {
                    $b .= $beginToken{0};
                    $i = $index + 1;
                }
                $index = strpos($line, $beginToken, $i);
            }

            $b .= StringHelper::substring($line, $i);
            return $b;

        } else {
            return $line;
        }
    }

    /**
     * This parses tokens which point to tokens.
     * It also maintains a list of currently used tokens, so
     * we cannot
     * get into an infinite loop.
     *
     * @param string $line the value / token to parse.
     * @param string $parent the parent token (= the token it was
     * parsed from).
     *
     * @return string
     *
     * @throws BuildException
     */
    private function _replaceTokens($line, $parent)
    {
        $beginToken = $this->getBeginToken();
        $endToken = $this->getEndToken();
        if ($this->recurseDepth === 0) {
            $this->passedTokens = [];
        }
        $this->recurseDepth++;
        if (!$this->duplicateToken && in_array($this->passedTokens, $parent)) {
            $this->duplicateToken = true;
            $this->log(
                'Infinite loop in tokens. Currently known tokens : '
                . implode('', $this->passedTokens) . "\nProblem token : " .
                $beginToken
                . $parent . $endToken . ' called from ' . $beginToken
                . end($this->passedTokens) . $endToken, Project::MSG_WARN);
            $this->recurseDepth--;
            return $parent;
        }
        $this->passedTokens[] = $parent;
        $value = $this->iReplaceTokens($line);
        if (!$this->duplicateToken && $this->recurseDepth === 1 && strpos($value, $beginToken) === false) {
            $passedTokens = null;
        } elseif ($this->duplicateToken) {
            // should always be the case...
            if (count($this->passedTokens) > 0) {
                $value = $this->passedTokens[count($this->passedTokens) - 1];
                unset($this->passedTokens[count($this->passedTokens) - 1]);
                if (count($this->passedTokens) === 0) {
                    $value = $beginToken . $value . $endToken;
                    $this->duplicateToken = false;
                }
            }
        } elseif (count($this->passedTokens) > 0) {
            // remove last seen token when crawling out of recursion
            unset($this->passedTokens[count($this->passedTokens) - 1]);
        }
        $this->recurseDepth--;
        return $value;
    }

    /**
     * Handle missing file.
     * 
     * @param string $message
     * 
     * @throws BuildException
     */
    private function handleMissingFile($message)
    {
        switch ($this->onMissingFiltersFile) {
            case 'ignore':
                return;
            case 'fail':
                throw new BuildException($message);
            case 'warn':
                $this->log($message, Project::MSG_WARN);
                return;
            default:
                throw new BuildException('Invalid value for onMissingFiltersFile');
        }
    }
}
