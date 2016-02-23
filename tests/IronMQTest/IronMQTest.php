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

use IronMQ\IronMQ;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamWrapper;

class IronMQTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Makes sure that a file can be loaded for the config
     *
     * @since 2015-07-20
     */
    public function testConfigCanBeLoadedFromFile()
    {
        $configFile = vfsStream::setup('config');
        vfsStream::newFile('config.ini')
            ->withContent(file_get_contents(__DIR__ . '/_files/config.ini'))
            ->at(vfsStreamWrapper::getRoot())
        ;

        $ironMQ = new IronMQ(vfsStream::url('config/config.ini'));
    }

    /**
     * Makes sure that an array can be used for the config
     *
     * @since 2015-07-20
     */
    public function testConfigCanBeLoadedFromArray()
    {
        $config = array(
            'project_id' => 'Project',
            'token'      => 'Token',
        );

        $ironMQ = new IronMQ($config);
    }

    /**
     * We require a token, so make sure that the config supplies it
     *
     * @since 2015-07-20
     */
    public function testMissingTokenThrowsException()
    {
        $this->setExpectedException('\InvalidArgumentException', 'token or project_id not found in any of the available sources');
        $config = array(
            'project_id' => 'Project',
        );

        $ironMQ = new IronMQ($config);
    }

    /**
     * We require a project ID, so make sure that the config supplies it
     *
     * @since 2015-07-20
     */
    public function testMissingProjectIdThrowsException()
    {
        $this->setExpectedException('\InvalidArgumentException', 'token or project_id not found in any of the available sources');
        $config = array(
            'token' => 'Token',
        );

        $ironMQ = new IronMQ($config);
    }

    /**
     * Makes sure that we can set the project ID manually, if we need to for some reason
     * The Project ID is required during initialization, so setting it manually will not be used directly.
     * setProjectId() is normally called during the constructor.
     *
     * @since 2015-07-20
     */
    public function testCanSetProjectIdManually()
    {
        $ironMQ = new IronMQ(array('project_id' => 'Project', 'token' => 'Token'));
        $ironMQ->setProjectId('Test Project');
    }

    /**
     * Makes sure that a blank project ID cannot be set
     * The Project ID is required during initialization, so setting it manually will not be used directly.
     * setProjectId() is normally called during the constructor.
     *
     * @since 2015-07-20
     */
    public function testSettingBlankProjectIDThrowsException()
    {
        $this->setExpectedException('\InvalidArgumentException', 'token or project_id not found in any of the available sources');
        $ironMQ = new IronMQ(array('project_id' => '', 'token' => 'Token'));
        $ironMQ->setProjectId('');
    }
}
