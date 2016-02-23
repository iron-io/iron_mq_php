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

use IronMQ\IronMQMessage;

class IronMQMessageTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Makes sure that all the properties for a message can be set via the constructor
     *
     * @since 2015-07-20
     */
    public function testConstructorSetsDataProperly()
    {
        $data = array(
            'body'       => 'This is the message body',
            'properties' => array(
                'timeout'    => 500,
                'delay'      => 450,
                'expires_in' => 400,
            ),
        );

        $message = new IronMQMessage($data['body'], $data['properties']);

        $this->assertEquals($data['body'], $message->getBody());
        $this->assertEquals($data['properties']['timeout'], $message->getTimeout());
        $this->assertEquals($data['properties']['delay'], $message->getDelay());
        $this->assertEquals($data['properties']['expires_in'], $message->getExpiresIn());
    }

    /**
     * Makes sure that we can set the body via the setter
     *
     * @since 2015-07-20
     */
    public function testCanSetBodyViaSetter()
    {
        $body = 'This is a message body';

        $message = new IronMQMessage($body);

        $this->assertEquals($body, $message->getBody());
    }

    /**
     * Empty message passed into the constructor should throw an exception
     *
     * @since 2015-07-20
     */
    public function testEmptyBodyThrowsExceptionViaConstuctor()
    {
        $this->setExpectedException('\InvalidArgumentException', 'Please specify a body');

        $message = new IronMQMessage('');
    }

    /**
     * Empty body message passed into the setter should throw an exception
     *
     * @since 2015-07-20
     */
    public function testEmptyBodyThrowsExceptionViaSetter()
    {
        $this->setExpectedException('\InvalidArgumentException', 'Please specify a body');

        $message = new IronMQMessage('Sample message');
        $message->setBody('');
    }

    /**
     * Makes sure that a timeout of 0 is properly returned
     *
     * @since 2015-07-20
     */
    public function testTimeoutOf0Returns0()
    {
        $message = new IronMQMessage('Sample Message', array('timeout' => 0));
        $this->assertEquals(0, $message->getTimeout());
    }

    /**
     * A timeout that is not set should return null
     *
     * @since 2015-07-20
     */
    public function testNoSpecifiedTimeoutReturnsNull()
    {
        $message = new IronMQMessage('Sample Message');
        $this->assertNull($message->getTimeout());
    }

    /**
     * Makes sure that a delay of 0 is properly returned
     *
     * @since 2015-07-20
     */
    public function testDelayOf0Returns0()
    {
        $message = new IronMQMessage('Sample Message', array('delay' => 0));
        $this->assertEquals(0, $message->getDelay());
    }

    /**
     * A delay that is not set should return null
     *
     * @since 2015-07-20
     */
    public function testNoSpecifiedDelayReturnsNull()
    {
        $message = new IronMQMessage('Sample Message');
        $this->assertNull($message->getDelay());
    }

    /**
     * Expirations cannot be larger than the max expires in value, makes sure that we throw an exception if it is
     *
     * @since 2015-07-20
     */
    public function testLargeExpiresInThrowsException()
    {
        $this->setExpectedException('\InvalidArgumentException', "Expires In can't be greater than " . IronMQMessage::MAX_EXPIRES_IN . ".");
        $message = new IronMQMessage('Sample Message', array('expires_in' => IronMQMessage::MAX_EXPIRES_IN + 1));
    }

    /**
     * Makes sure that the array copy of a messages matches the data supplied, and is in the proper format
     *
     * @since 2015-07-20
     */
    public function testCreatesArrayCopyProperly()
    {
        $data = array(
            'body'       => 'This is the message body',
            'properties' => array(
                'timeout'    => 500,
                'delay'      => 450,
                'expires_in' => 400,
            ),
        );

        $message = new IronMQMessage($data['body'], $data['properties']);
        $arrayCopy = $message->asArray();

        $this->assertEquals($data['body'], $arrayCopy['body']);
        $this->assertEquals($data['properties']['timeout'], $arrayCopy['timeout']);
        $this->assertEquals($data['properties']['delay'], $arrayCopy['delay']);
        $this->assertEquals($data['properties']['expires_in'], $arrayCopy['expires_in']);
    }
}
