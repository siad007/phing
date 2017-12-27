<?php

/**
 * Holds a token.
 *
 * @package   phing.filters
 */
class Token
{

    /**
     * Token key.
     * @var string
     */
    private $_key;

    /**
     * Token value.
     * @var string
     */
    private $_value;

    /**
     * Sets the token key.
     *
     * @param string $key The key for this token. Must not be <code>null</code>.
     */
    public function setKey($key): void
    {
        $this->_key = (string)$key;
    }

    /**
     * Sets the token value.
     *
     * @param string $value The value for this token. Must not be <code>null</code>.
     */
    public function setValue($value): void
    {
        // special case for boolean values
        if (is_bool($value)) {
            if ($value) {
                $this->_value = "true";
            } else {
                $this->_value = "false";
            }
        } else {
            $this->_value = (string)$value;
        }
    }

    /**
     * Returns the key for this token.
     *
     * @return string The key for this token.
     */
    public function getKey(): string
    {
        return $this->_key;
    }

    /**
     * Returns the value for this token.
     *
     * @return string The value for this token.
     */
    public function getValue(): string
    {
        return $this->_value;
    }

    /**
     * Sets the token value from text.
     *
     * @param string $value The value for this token. Must not be <code>null</code>.
     */
    public function addText($value): void
    {
        $this->setValue($value);
    }
}
