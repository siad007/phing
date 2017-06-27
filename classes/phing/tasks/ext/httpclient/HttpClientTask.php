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

use GuzzleHttp\RequestOptions;

require_once 'phing/Task.php';

/**
 * @package phing.tasks.ext
 * @author  Siad Ardroumli <siad.ardroumli@gmail.com>
 */
class HttpClientTask extends Task
{
    private $config = [
        RequestOptions::HEADERS => [],
        RequestOptions::VERSION => 1.1,
        RequestOptions::BODY    => null
    ];

    /**
     * @var string
     */
    private $uri = '';

    /**
     * @var string
     */
    private $method = 'get';

    /**
     * @param string $uri
     */
    public function setUri($uri)
    {
        $this->uri = $uri;
    }

    /**
     * @param string $method
     */
    public function setMethod($method)
    {
        $this->method = $method;
    }

    /**
     * @param string $uri
     */
    public function setBaseUri($uri)
    {
        $this->config['base-uri'] = $uri;
    }

    /**
     * @param bool $allowRedirect
     */
    public function setAllowRedirect($allowRedirect)
    {
        $this->config[RequestOptions::ALLOW_REDIRECTS] = $allowRedirect;
    }

    /**
     * @param string $auth
     */
    public function setAuth($auth)
    {
        $this->config[RequestOptions::AUTH] = $auth;
    }

    /**
     * @param string $body
     */
    public function setBody($body)
    {
        $this->config[RequestOptions::BODY] = $body;
    }

    /**
     * @param string $cert
     */
    public function setCert($cert)
    {
        $this->config[RequestOptions::CERT] = $cert;
    }

    /**
     * @param int $connectTimeout
     */
    public function setConnectTimeout($connectTimeout)
    {
        $this->config[RequestOptions::CONNECT_TIMEOUT] = $connectTimeout;
    }

    /**
     * @param string $debug
     */
    public function setDebug($debug)
    {
        $this->config[RequestOptions::DEBUG] = $debug;
    }

    /**
     * @param bool $decodeContent
     */
    public function setDecodeContent($decodeContent)
    {
        $this->config[RequestOptions::DECODE_CONTENT] = $decodeContent;
    }

    /**
     * @param mixed $delay
     */
    public function setDelay($delay)
    {
        $this->config[RequestOptions::DELAY] = $delay;
    }

    /**
     * @param string $expect
     */
    public function setExpect($expect)
    {
        $this->config[RequestOptions::EXPECT] = $expect;
    }

    /**
     * @param mixed $forceIpResolve
     */
    public function setForceIpResolve($forceIpResolve)
    {
        $this->config[RequestOptions::FORCE_IP_RESOLVE] = $forceIpResolve;
    }

    /**
     * @param bool $httpErrors
     */
    public function setHttpErrors($httpErrors)
    {
        $this->config[RequestOptions::HTTP_ERRORS] = $httpErrors;
    }

    /**
     * @param string $proxy
     */
    public function setProxy($proxy)
    {
        $this->config[RequestOptions::PROXY] = $proxy;
    }

    /**
     * @param string $readTimeout
     */
    public function setReadTimeout($readTimeout)
    {
        $this->config[RequestOptions::READ_TIMEOUT] = $readTimeout;
    }

    /**
     * @param string $sink
     */
    public function setSink($sink)
    {
        $this->config[RequestOptions::SINK] = $sink;
    }

    /**
     * @param bool $stream
     */
    public function setStream($stream)
    {
        $this->config[RequestOptions::STREAM] = $stream;
    }

    /**
     * @param bool $synchronous
     */
    public function setSynchronous($synchronous)
    {
        $this->config[RequestOptions::SYNCHRONOUS] = $synchronous;
    }

    /**
     * @param bool $verify
     */
    public function setVerify($verify)
    {
        $this->config[RequestOptions::VERIFY] = $verify;
    }

    /**
     * @param int $timeout
     */
    public function setTimeout($timeout)
    {
        $this->config[RequestOptions::TIMEOUT] = $timeout;
    }

    /**
     * @param float $protocolVersion
     */
    public function setProtocolVersion($protocolVersion)
    {
        $this->config[RequestOptions::VERSION] = $protocolVersion;
    }

    /**
     * @param bool $failonerror
     */
    public function setFailOnError($failonerror)
    {
        $this->setHttpErrors($failonerror);
    }

    public function setHandler(\GuzzleHttp\HandlerStack $handler)
    {
        $this->config['handler'] = $handler;
    }

    /**
     * Creates post body parameters for this request
     *
     * @return Parameter The created post parameter
     */
    public function createHeader()
    {
        $num = array_push($this->config[RequestOptions::HEADERS], new Parameter());

        return $this->config[RequestOptions::HEADERS][$num - 1];
    }

    public function init()
    {
        if (!class_exists('GuzzleHttp\Client')) {
            throw new BuildException('Guzzle is not available.');
        }
    }

    public function main()
    {
        $headers = &$this->config[RequestOptions::HEADERS];

        if (count($headers) > 0) {
            foreach ($headers as $key => $value) {
                if ($value instanceof Parameter) {
                    $tmp = $key;
                    $headers[$value->getName()] = $value->getValue();
                    unset($headers[$tmp]);
                }
            }
        }

        $client = new \GuzzleHttp\Client($this->config);

        try {
            $response = $client->send($this->getRequest(), $this->config);
            $this->log($response->getBody()->getContents());
        } catch (\GuzzleHttp\Exception\RequestException $re) {
            throw new BuildException($re);
        }
    }

    private function getRequest()
    {
        return new \GuzzleHttp\Psr7\Request(
            $this->method,
            $this->uri,
            $this->config[RequestOptions::HEADERS],
            $this->config[RequestOptions::BODY],
            $this->config[RequestOptions::VERSION]
        );
    }
}
