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
 * Individual filter component of filterset.
 *
 * @author   Siad Ardroumli <siad.ardroumli@gmail.com>
 * @package  phing.types
 */
class Filter
{
    /** Token which will be replaced in the filter operation. */
    private $token;

    /** The value which will replace the token in the filtering operation. */
    private $value;

    /**
     * Constructor for the Filter object.
     *
     * @param string $token The token which will be replaced when filtering.
     * @param value  The value which will replace the token when filtering.
     */
    public function __construct($token, $value)
    {
        $this->setToken($token);
        $this->setValue($value);
    }

    /**
     * Sets the Token attribute of the Filter object.
     *
     * @param token  The new Token value.
     */
    public function setToken($token)
    {
        $this->token = $token;
    }

    /**
     * Sets the Value attribute of the Filter object.
     *
     * @param value The new Value value.
     */
    public function setValue($value)
    {
        $this->value = $value;
    }

    /**
     * Gets the Token attribute of the Filter object.
     *
     * @return string The Token value.
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * Gets the Value attribute of the Filter object.
     *
     * @return   The Value value.
     */
    public function getValue()
    {
        return $this->value;
    }
}
