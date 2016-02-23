<?php
/**
 * PHP client for IronMQ
 * IronMQ is a scalable, reliable, high performance message queue in the cloud.
 *
 * @link https://github.com/iron-io/iron_mq_php
 * @link http://www.iron.io/products/mq
 * @link http://dev.iron.io/
 * @package IronMQPHP
 * @copyright Feel free to copy, steal, take credit for, or whatever you feel like doing with this code. ;)
 */

namespace IronMQ;

/**
 * IronMQMessage
 * @package IronMQ
 */
class IronMQMessage
{
    private $body;
    private $timeout;
    private $delay;
    private $expires_in;

    const MAX_EXPIRES_IN = 2592000;

    /**
     * Create a new message.
     *
     * @param string $message
     *        A message body
     * @param array $properties
     *        An array of message properties
     * Fields in $properties array:
     * - timeout: Timeout, in seconds. After timeout, item will be placed back on queue. Defaults to 60.
     * - delay: The item will not be available on the queue until this many seconds have passed. Defaults to 0.
     * - expires_in: How long, in seconds, to keep the item on the queue before it is deleted.
     *               Defaults to 604800 (7 days). Maximum is 2592000 (30 days).
     */
    public function __construct($message, $properties = array())
    {
        $this->setBody($message);

        if (array_key_exists("timeout", $properties)) {
            $this->setTimeout($properties['timeout']);
        }
        if (array_key_exists("delay", $properties)) {
            $this->setDelay($properties['delay']);
        }
        if (array_key_exists("expires_in", $properties)) {
            $this->setExpiresIn($properties['expires_in']);
        }
    }

    public function getBody()
    {
        return $this->body;
    }

    public function setBody($body)
    {
        if (empty($body)) {
            throw new \InvalidArgumentException("Please specify a body");
        } else {
            $this->body = (string) $body;
        }
    }

    public function getTimeout()
    {
        # 0 is considered empty, but we want people to be able to set a timeout of 0
        if (!empty($this->timeout) || $this->timeout === 0) {
            return $this->timeout;
        } else {
            return null;
        }
    }

    public function setTimeout($timeout)
    {
        $this->timeout = $timeout;
    }

    public function getDelay()
    {
        # 0 is considered empty, but we want people to be able to set a delay of 0
        if (!empty($this->delay) || $this->delay == 0) {
            return $this->delay;
        } else {
            return null;
        }
    }

    public function setDelay($delay)
    {
        $this->delay = $delay;
    }

    public function getExpiresIn()
    {
        return $this->expires_in;
    }

    public function setExpiresIn($expires_in)
    {
        if ($expires_in > self::MAX_EXPIRES_IN) {
            throw new \InvalidArgumentException("Expires In can't be greater than " . self::MAX_EXPIRES_IN . ".");
        } else {
            $this->expires_in = $expires_in;
        }
    }

    public function asArray()
    {
        $array = array();
        $array['body'] = $this->getBody();
        if ($this->getTimeout() != null) {
            $array['timeout'] = $this->getTimeout();
        }
        if ($this->getDelay() != null) {
            $array['delay'] = $this->getDelay();
        }
        if ($this->getExpiresIn() != null) {
            $array['expires_in'] = $this->getExpiresIn();
        }
        return $array;
    }
}
