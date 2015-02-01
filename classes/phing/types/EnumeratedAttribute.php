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
 * Helper class for attributes that can only take one of a fixed list
 * of values.
 *
 * @author  Siad Ardroumli <siad.ardroumli@gmail.com>
 * @package phing.types
 */
abstract class EnumeratedAttribute
{
    /**
     * The selected value in this enumeration.
     */
    protected $value;

    /**
     * the index of the selected value in the array.
     */
    private $index = -1;

    /**
     * This is the only method a subclass needs to implement.
     *
     * @return string[] an array holding all possible values of the enumeration.
     * The order of elements must be fixed so that <tt>indexOfValue(String)</tt>
     * always return the same index for the same value.
     */
    public abstract function getValues();

    /** bean constructor */
    public function __construct()
    {
    }

    /**
     * Factory method for instantiating EAs via API in a more
     * developer friendly way.
     * @param string $clazz         Class, extending EA, which to instantiate
     * @param string $value         The value to set on that EA
     * @return EnumeratedAttribute  Configured EA
     * @throws BuildException       If the class could not be found or the value
     *                              is not valid for the given EA-class.
     */
    public static function getInstance($clazz, $value)
    {
        if (!is_a($clazz, 'EnumeratedAttribute', true)) {
            throw new BuildException("You have to provide a subclass from EnumeratedAttribut as clazz-parameter.");
        }

        /** @var EnumeratedAttribute $ea */
        $ea = new $clazz;

        $ea->setValue($value);
        return $ea;
    }

    /**
     * Invoked by {@link IntrospectionHelper IntrospectionHelper}.
     * @param string $value the <code>String</code> value of the attribute
     * @throws BuildException if the value is not valid for the attribute
     */
    public function setValue($value)
    {
        $idx = $this->indexOfValue($value);
        if ($idx == -1) {
            throw new BuildException($value . " is not a legal value for this attribute");
        }
        $this->index = $idx;
        $this->value = $value;
    }

    /**
     * Is this value included in the enumeration?
     * @param string $value the <code>String</code> value to look up
     * @return true if the value is valid
     */
    public function containsValue($value)
    {
        return ($this->indexOfValue($value) != -1);
    }

    /**
     * get the index of a value in this enumeration.
     * @param string $value the string value to look for.
     * @return int the index of the value in the array of strings
     * or -1 if it cannot be found.
     * @see #getValues()
     */
    public function indexOfValue($value)
    {
        $values = $this->getValues();
        if ($values == null || $value == null) {
            return -1;
        }
        for ($i = 0; $i < count($values); $i++) {
            if ($value === $values[$i]) {
                return $i;
            }
        }
        return -1;
    }

    /**
     * @return string the selected value.
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @return int the index of the selected value in the array.
     * @see getValues()
     */
    public function getIndex()
    {
        return $this->index;
    }

    /**
     * Convert the value to its string form.
     *
     * @return string the string form of the value.
     */
    public function toString()
    {
        return $this->getValue();
    }

    public function __toString()
    {
        return $this->toString();
    }
}
