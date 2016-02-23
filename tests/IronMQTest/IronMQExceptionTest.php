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
namespace IronMQTest;

use IronMQ\IronMQException;

class IronMQExceptionTest extends \PHPUnit_Framework_TestCase
{
    public function testIsInstanceOfException()
    {
        $exception = new IronMQException();
        $this->assertTrue($exception instanceof \Exception);
    }
}
